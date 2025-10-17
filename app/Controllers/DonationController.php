<?php

class DonationController {
    private const GENERIC_SIMULATION_ERROR = 'No pudimos procesar tu donación simulada. Intenta nuevamente.';
    private Donation $donations;
    private Campaign $campaigns;
    private DonationReceiptStorage $receiptStorage;

    public function __construct() {
        $this->donations = new Donation();
        $this->campaigns = new Campaign();
        $this->receiptStorage = new DonationReceiptStorage();
    }

    public function simulate($id) {
        $campaignId = (int)$id;

        if ($campaignId <= 0) {
            return $this->respondError('Campaña inválida.', 404, 'campanas');
        }

        $campaign = $this->campaigns->findById($campaignId);
        if (!$campaign) {
            return $this->respondError('No encontramos la campaña seleccionada.', 404, 'campanas');
        }

        if (!isset($campaign['public_path'])) {
            $campaign = CampaignPresenter::present(array_merge($campaign, [
                'id' => $campaign['id'] ?? $campaignId,
            ]));
        }

        if (!SessionHelper::isAuthenticated()) {
            $campaignPublicUrl = isset($campaign['public_path'])
                ? Router::url($campaign['public_path'])
                : Router::url('campana/' . ($campaign['slug'] ?? $campaignId));
            $target = $campaignPublicUrl . '#donar';
            SessionHelper::setFlash('error', 'Inicia sesión para registrar tu aporte.');
            $loginUrl = Router::url('login') . '?redirect=' . urlencode($target);
            Router::redirect($loginUrl);
        }

        if (!SessionHelper::checkRateLimit('donate_campaign_' . $campaignId, 5, 900)) {
            $slug = $campaign['public_path'] ?? ('campana/' . ($campaign['slug'] ?? $campaignId));
            return $this->respondError('Hiciste demasiados intentos en poco tiempo. Intenta nuevamente en unos minutos.', 429, $slug . '#donar');
        }

        $payload = $this->collectPayload($campaignId);

        if (!empty($payload['errors'])) {
            return $this->handleValidationFailure(
                $campaignId,
                $payload['errors'],
                $payload['old'],
                422,
                $campaign['public_path'] ?? ($campaign['slug'] ?? null)
            );
        }

        try {
            $donationId = $this->donations->create($payload['data']);
            $result = $this->donations->processPayment($donationId, [
                'provider' => 'simulator',
                'method' => $payload['data']['payment_method'],
                'details' => $payload['data']['metadata']['payment'] ?? [],
            ]);

            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Falló la simulación de pago');
            }

            if (!empty($result['requires_review'])) {
                SessionHelper::setFlash('success', 'Recibimos tu comprobante. Revisaremos el aporte antes de publicarlo.');
            } else {
                SessionHelper::setFlash('success', 'Registramos tu aporte con éxito. ¡Gracias por apoyar esta causa!');
            }
            unset($_SESSION['donation_form_old'][$campaignId], $_SESSION['donation_form_errors'][$campaignId]);

            return $this->respondSuccess($campaign, $donationId);
        } catch (Exception $exception) {
            Logger::error('Simulated donation failed', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage(),
            ]);

            return $this->handleValidationFailure(
                $campaignId,
                ['general' => $this->normalizeDonationFailureMessage($exception)],
                $payload['old'],
                500,
                $campaign['public_path'] ?? ($campaign['slug'] ?? null)
            );
        }
    }

    public function list($username, $identifier) {
        $campaign = $this->resolveCampaign($username, $identifier);

        if (!$campaign) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $status = strtolower((string)($campaign['status'] ?? 'draft'));
        $visibility = strtolower((string)($campaign['visibility'] ?? 'public'));
        $ownerId = $campaign['owner_id'] ?? $campaign['user_id'] ?? null;
        $currentUser = SessionHelper::getUser();
        $currentUserId = SessionHelper::getUserId();
        if (is_array($currentUser) && isset($currentUser['id'])) {
            $currentUserId = (int)$currentUser['id'];
        }
        $isOwner = $currentUserId !== null && $ownerId !== null && (int)$currentUserId === (int)$ownerId;
        $isAdmin = is_array($currentUser) && (($currentUser['role'] ?? '') === 'admin');
        $isPublic = in_array($status, ['published', 'completed'], true) && $visibility !== 'private';

        if (!$isPublic && !$isOwner && !$isAdmin) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $supportsAnonymous = $this->donations->hasAnonymousColumn();
        $allowedFilters = $supportsAnonymous
            ? ['all', 'public', 'anonymous']
            : ['all'];
        $visibilityFilter = strtolower((string)($_GET['filtro'] ?? 'all'));
        if (!in_array($visibilityFilter, $allowedFilters, true)) {
            $visibilityFilter = 'all';
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $campaignId = (int)$campaign['id'];
        $filterCounts = [];
        foreach ($allowedFilters as $filterKey) {
            $filterCounts[$filterKey] = $this->donations->countByCampaignId(
                $campaignId,
                ['visibility' => $filterKey]
            );
        }

        $totalDonations = $filterCounts[$visibilityFilter] ?? 0;
        $totalPages = max(1, (int)ceil($totalDonations / $perPage));

        if ($totalDonations === 0) {
            $page = 1;
            $totalPages = 1;
            $donations = [];
        } else {
            if ($page > $totalPages) {
                $page = $totalPages;
                $offset = ($page - 1) * $perPage;
            }
            $donations = $this->donations->findByCampaignId(
                $campaignId,
                $perPage,
                $offset,
                ['visibility' => $visibilityFilter]
            );
        }

        $campaignPublicUrl = isset($campaign['public_path'])
            ? Router::url($campaign['public_path'])
            : Router::url('campana/' . ($campaign['slug'] ?? $campaignId));
        $campaignSlug = $campaign['slug'] ?? $campaignId;
        $breadcrumbs = [
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Campañas', 'href' => Router::url('campanas')],
            ['name' => $campaign['title'] ?? 'Campaña', 'href' => $campaignPublicUrl],
            ['name' => 'Aportes', 'href' => Router::url(rtrim($campaign['public_path'] ?? ('campana/' . $campaignSlug), '/') . '/donaciones')],
        ];

        $page_title = 'Aportes de ' . ($campaign['title'] ?? 'Campaña') . ' - Lucatón';
        $page_description = 'Historial de aportes registrados para la campaña ' . ($campaign['title'] ?? 'Lucatón') . '.';

        $activeFilter = $visibilityFilter;
        $donationFilterOptions = ['all' => 'Todos los aportes'];
        if ($supportsAnonymous) {
            $donationFilterOptions['public'] = 'Aportes públicos';
            $donationFilterOptions['anonymous'] = 'Aportes anónimos';
        }

        include VIEWS_PATH . '/public/campaign-donations.php';
    }

    private function collectPayload(int $campaignId): array {
        $selectedMethod = $_POST['payment_method'] ?? 'manual';
        $paymentInput = [
            'card_holder' => trim($_POST['card_holder'] ?? ''),
            'card_number' => preg_replace('/\s+/', '', trim($_POST['card_number'] ?? '')),
            'card_expiration' => strtoupper(trim($_POST['card_expiration'] ?? '')),
            'card_cvv' => trim($_POST['card_cvv'] ?? ''),
            'transfer_bank' => trim($_POST['transfer_bank'] ?? ''),
            'transfer_reference' => trim($_POST['transfer_reference'] ?? ''),
            'paypal_email' => trim($_POST['paypal_email'] ?? ''),
            'webpay_rut' => strtoupper(trim($_POST['webpay_rut'] ?? '')),
            'webpay_bank' => trim($_POST['webpay_bank'] ?? ''),
        ];

        $old = [
            'amount' => trim($_POST['amount'] ?? ''),
            'message' => trim($_POST['message'] ?? ''),
            'payment_method' => $selectedMethod,
            'is_anonymous' => isset($_POST['is_anonymous']) ? '1' : '0',
            'payment_fields' => array_merge(
                $paymentInput,
                [
                    'card_number' => '',
                    'card_cvv' => '',
                ]
            ),
        ];

        $errors = [];

        $amountNumeric = preg_replace('/[^0-9]/', '', $old['amount']);
        if ($amountNumeric === '') {
            $errors['amount'] = 'Ingresa un monto válido.';
        }

        $amount = (float)$amountNumeric;
        if ($amount < 1000) {
            $errors['amount'] = 'El aporte mínimo es de $1.000 CLP.';
        }

        $validMethods = ['credit_card','debit_card','bank_transfer','paypal','webpay','manual'];
        $paymentMethod = in_array($old['payment_method'], $validMethods, true)
            ? $old['payment_method']
            : 'manual';

        $paymentErrors = [];
        $paymentMetadata = [
            'method' => $paymentMethod,
            'validated' => false,
        ];
        $receiptMetadata = null;
        $userId = SessionHelper::getUserId();
        $currentUser = SessionHelper::getUser();

        if (in_array($paymentMethod, ['credit_card', 'debit_card'], true)) {
            if ($paymentInput['card_holder'] === '') {
                $paymentErrors['payment_card_holder'] = 'Ingresa el nombre del titular de la tarjeta.';
            }

            $cardDigits = preg_replace('/\D/', '', $paymentInput['card_number']);
            if ($cardDigits === '' || strlen($cardDigits) < 12 || strlen($cardDigits) > 19) {
                $paymentErrors['payment_card_number'] = 'Ingresa un número de tarjeta válido (12 a 19 dígitos).';
            }

            if ($paymentInput['card_expiration'] === '' || !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $paymentInput['card_expiration'])) {
                $paymentErrors['payment_card_expiration'] = 'Ingresa la fecha de expiración en formato MM/AA.';
            }

            $cardCvvDigits = preg_replace('/\D/', '', $paymentInput['card_cvv']);
            if ($cardCvvDigits === '' || strlen($cardCvvDigits) < 3 || strlen($cardCvvDigits) > 4) {
                $paymentErrors['payment_card_cvv'] = 'Ingresa el CVV de 3 o 4 dígitos.';
            }

            if (empty($paymentErrors)) {
                $paymentMetadata['validated'] = true;
                $cardBrand = $this->detectCardBrand($cardDigits);
                $paymentMetadata['card'] = [
                    'holder' => ucwords(strtolower($paymentInput['card_holder'])),
                    'last4' => substr($cardDigits, -4),
                    'brand' => $cardBrand,
                    'expiration' => $paymentInput['card_expiration'],
                ];
                $paymentMetadata['summary'] = sprintf(
                    'Tarjeta %s terminada en %s',
                    strtoupper($cardBrand),
                    substr($cardDigits, -4)
                );
            }
        } elseif (in_array($paymentMethod, ['bank_transfer', 'manual'], true)) {
            if ($paymentInput['transfer_bank'] === '') {
                $paymentErrors['payment_transfer_bank'] = 'Indica el banco desde el que simulas la transferencia.';
            }

            if ($paymentInput['transfer_reference'] === '' || strlen($paymentInput['transfer_reference']) < 4) {
                $paymentErrors['payment_transfer_reference'] = 'Ingresa un número de comprobante o referencia (mínimo 4 caracteres).';
            }

            if (empty($paymentErrors)) {
                $paymentMetadata['validated'] = true;
                $bankName = ucwords(strtolower($paymentInput['transfer_bank']));
                $referenceCode = strtoupper($paymentInput['transfer_reference']);
                $paymentMetadata['transfer'] = [
                    'bank' => $bankName,
                    'reference' => $referenceCode,
                ];
                $paymentMetadata['summary'] = sprintf('Transferencia %s · Ref %s', $bankName, $referenceCode);

                if (isset($_FILES['transfer_receipt']) && (int)$_FILES['transfer_receipt']['error'] !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $receiptMetadata = $this->receiptStorage->store(
                            $_FILES['transfer_receipt'],
                            $campaignId,
                            $userId ?? null
                        );
                        $paymentMetadata['receipt'] = [
                            'path' => $receiptMetadata['path'],
                            'original_name' => $receiptMetadata['original_name'],
                            'size' => $receiptMetadata['size'],
                            'checksum' => $receiptMetadata['checksum'],
                            'mime' => $receiptMetadata['mime'],
                            'uploaded_at' => date('c'),
                        ];
                        $paymentMetadata['requires_review'] = true;
                    } catch (RuntimeException $exception) {
                        $paymentErrors['payment_transfer_receipt'] = $exception->getMessage();
                        $paymentMetadata['validated'] = false;
                        if ($receiptMetadata !== null && isset($receiptMetadata['path'])) {
                            $this->removeStoredReceipt($receiptMetadata['path']);
                        }
                        unset($paymentMetadata['receipt'], $paymentMetadata['requires_review']);
                        $receiptMetadata = null;
                    }
                }
            }
        } elseif ($paymentMethod === 'paypal') {
            if ($paymentInput['paypal_email'] === '' || !filter_var($paymentInput['paypal_email'], FILTER_VALIDATE_EMAIL)) {
                $paymentErrors['payment_paypal_email'] = 'Ingresa un correo válido para PayPal.';
            } else {
                $paymentMetadata['validated'] = true;
                $paymentMetadata['wallet'] = [
                    'email' => strtolower($paymentInput['paypal_email']),
                    'provider' => 'paypal',
                ];
                $paymentMetadata['summary'] = sprintf('Pago PayPal (%s)', strtolower($paymentInput['paypal_email']));
            }
        } elseif ($paymentMethod === 'webpay') {
            if ($paymentInput['webpay_rut'] === '') {
                $paymentErrors['payment_webpay_rut'] = 'Ingresa el RUT que deseas simular.';
            } elseif (!preg_match('/^[0-9]{1,2}\.?[0-9]{3}\.?[0-9]{3}-[0-9kK]{1}$/', $paymentInput['webpay_rut'])) {
                $paymentErrors['payment_webpay_rut'] = 'Usa un formato válido, por ejemplo 11.111.111-1.';
            }

            if ($paymentInput['webpay_bank'] === '') {
                $paymentErrors['payment_webpay_bank'] = 'Selecciona un banco para la simulación de Webpay.';
            }

            if (empty($paymentErrors)) {
                $paymentMetadata['validated'] = true;
                $rut = strtoupper($paymentInput['webpay_rut']);
                $bank = $paymentInput['webpay_bank'];
                $paymentMetadata['webpay'] = [
                    'rut' => $rut,
                    'bank' => $bank,
                ];
                $paymentMetadata['summary'] = sprintf('Webpay %s · %s', $bank, $rut);
            }
        }

        $donorName = '';
        $donorEmail = '';

        if ($currentUser) {
            $nameParts = array_filter([
                $currentUser['first_name'] ?? null,
                $currentUser['last_name'] ?? null,
            ]);
            $donorName = trim(implode(' ', $nameParts));
            if ($donorName === '' && !empty($currentUser['name'])) {
                $donorName = trim((string)$currentUser['name']);
            }

            if ($donorName === '' && !empty($currentUser['username'])) {
                $donorName = trim((string)$currentUser['username']);
            }

            $donorEmail = trim((string)($currentUser['email'] ?? ''));

            if ($donorName === '' && $donorEmail !== '') {
                $donorName = trim((string)strtok($donorEmail, '@'));
            }
        }

        if (!$userId || !$currentUser) {
            $errors['general'] = 'Debes iniciar sesión para registrar tu aporte.';
        }

        if ($donorEmail === '') {
            $errors['general'] = 'Tu cuenta necesita un correo válido para completar el aporte.';
        }

        if (!empty($paymentErrors)) {
            $errors = array_merge($errors, $paymentErrors);
            if (!isset($errors['payment'])) {
                $errors['payment'] = 'Revisa los datos del método de aporte seleccionado.';
            }
        }

        if (isset($_POST['message']) && strlen($old['message']) > 280) {
            $errors['message'] = 'El mensaje puede tener hasta 280 caracteres.';
        }

        if (!empty($errors) && isset($paymentMetadata['receipt']['path'])) {
            $this->removeStoredReceipt($paymentMetadata['receipt']['path']);
            unset($paymentMetadata['receipt'], $paymentMetadata['requires_review']);
            $receiptMetadata = null;
        }

        $data = [
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'donor_name' => $donorName !== '' ? $donorName : null,
            'donor_email' => $donorEmail !== '' ? $donorEmail : null,
            'amount' => $amount,
            'currency' => 'CLP',
            'payment_method' => $paymentMethod,
            'is_anonymous' => isset($_POST['is_anonymous']),
            'message' => $old['message'] !== '' ? $old['message'] : null,
            'metadata' => [
                'source' => 'campaign_detail',
                'simulated' => true,
                'payment' => $paymentMetadata,
            ],
        ];

        return compact('data', 'errors', 'old');
    }

    private function normalizeDonationFailureMessage(Throwable $exception): string {
        $message = trim($exception->getMessage());
        if ($message === '') {
            return self::GENERIC_SIMULATION_ERROR;
        }

        $known = [
            'La campaña no está disponible para recibir donaciones' => 'Esta campaña aún está en revisión. Podrás registrar aportes cuando sea aprobada.',
            'La campaña ya finalizó' => 'Esta campaña finalizó y no acepta nuevos aportes.',
            'Donación no disponible para procesamiento' => 'Tuvimos un problema al continuar con la simulación. Refresca la página e inténtalo nuevamente.',
            'Error al registrar la donación' => self::GENERIC_SIMULATION_ERROR,
            'Información de pago incompleta para la simulación.' => 'Revisa los datos del método seleccionado y completa todos los campos ficticios.',
        ];

        if (isset($known[$message])) {
            return $known[$message];
        }

        $unsafePatterns = ['SQLSTATE', 'Stack trace', 'PDOException', 'Fatal error'];
        foreach ($unsafePatterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return self::GENERIC_SIMULATION_ERROR;
            }
        }

        if (mb_strlen($message) > 180) {
            return self::GENERIC_SIMULATION_ERROR;
        }

        return $message;
    }

    private function handleValidationFailure(int $campaignId, array $errors, array $old, int $status = 422, ?string $campaignPath = null) {
        if ($this->isJsonRequest()) {
            return $this->respondJson(['success' => false, 'errors' => $errors], $status);
        }

        $_SESSION['donation_form_errors'][$campaignId] = $errors;
        $_SESSION['donation_form_old'][$campaignId] = $old;

        SessionHelper::setFlash('error', $errors['general'] ?? 'Corrige los campos marcados para completar tu aporte.');

        $targetPath = $campaignPath ?? ('campana/' . $campaignId);
        Router::redirect($targetPath . '#donar');
    }

    private function respondSuccess(array $campaign, int $donationId) {
        if ($this->isJsonRequest()) {
            return $this->respondJson([
                'success' => true,
                'donation_id' => $donationId,
                'campaign_id' => $campaign['id'] ?? null,
            ]);
        }

        $publicPath = $campaign['public_path'] ?? ('campana/' . ($campaign['slug'] ?? $campaign['id']));
        Router::redirect($publicPath . '#donar');
    }

    private function respondError(string $message, int $status, ?string $redirectPath = null) {
        if ($this->isJsonRequest()) {
            return $this->respondJson(['success' => false, 'error' => $message], $status);
        }

        SessionHelper::setFlash('error', $message);
        $target = $redirectPath ?? '/';
        Router::redirect($target);
    }

    private function respondJson(array $payload, int $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function isJsonRequest(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'xmlhttprequest';
    }

    private function resolveCampaign($username, $identifier): ?array {
        $record = null;

        if (is_numeric($identifier)) {
            $record = $this->campaigns->findById((int)$identifier);
        } else {
            $record = $this->campaigns->findBySlug((string)$identifier, (string)$username);
        }

        if (!$record) {
            return null;
        }

        if (!isset($record['public_path'])) {
            if (!isset($record['owner_username']) && $username !== null) {
                $record['owner_username'] = $username;
            }
            $record = CampaignPresenter::present($record);
        }

        return $record;
    }

    private function removeStoredReceipt(string $relativePath): void
    {
        $normalized = ltrim($relativePath, '/');
        if ($normalized === '') {
            return;
        }

        $fullPath = STORAGE_PATH . '/' . $normalized;
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function detectCardBrand(string $digits): string {
        if ($digits === '') {
            return 'desconocida';
        }

        $patterns = [
            'visa' => '/^4[0-9]{6,}$/',
            'mastercard' => '/^(5[1-5][0-9]{5,}|2[2-7][0-9]{6,})$/',
            'amex' => '/^3[47][0-9]{5,}$/',
            'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{4,}$/',
            'discover' => '/^6(?:011|5[0-9]{2})[0-9]{3,}$/',
            'jcb' => '/^(?:2131|1800|35[0-9]{3})[0-9]{3,}$/',
        ];

        foreach ($patterns as $brand => $regex) {
            if (preg_match($regex, $digits)) {
                return $brand;
            }
        }

        return 'desconocida';
    }
}

?>
