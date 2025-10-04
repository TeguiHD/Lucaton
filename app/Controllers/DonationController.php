<?php

class DonationController {
    private Donation $donations;
    private Campaign $campaigns;

    public function __construct() {
        $this->donations = new Donation();
        $this->campaigns = new Campaign();
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

        if (!SessionHelper::checkRateLimit('donate_campaign_' . $campaignId, 5, 900)) {
            $slug = $campaign['slug'] ?? $campaignId;
            return $this->respondError('Hiciste demasiados intentos en poco tiempo. Intenta nuevamente en unos minutos.', 429, 'campana/' . $slug . '#donar');
        }

        $payload = $this->collectPayload($campaignId);

        if (!empty($payload['errors'])) {
            return $this->handleValidationFailure($campaignId, $payload['errors'], $payload['old'], 422, $campaign['slug'] ?? null);
        }

        try {
            $donationId = $this->donations->create($payload['data']);
            $result = $this->donations->processPayment($donationId, [
                'provider' => 'simulator'
            ]);

            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Falló la simulación de pago');
            }

            SessionHelper::setFlash('success', 'Registramos tu aporte con éxito. ¡Gracias por apoyar esta causa!');
            unset($_SESSION['donation_form_old'][$campaignId], $_SESSION['donation_form_errors'][$campaignId]);

            return $this->respondSuccess($campaign, $donationId);
        } catch (Exception $exception) {
            Logger::error('Simulated donation failed', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage(),
            ]);

            return $this->handleValidationFailure(
                $campaignId,
                ['general' => 'No pudimos procesar tu donación simulada. Intenta nuevamente.'],
                $payload['old'],
                500,
                $campaign['slug'] ?? null
            );
        }
    }

    private function collectPayload(int $campaignId): array {
        $old = [
            'amount' => trim($_POST['amount'] ?? ''),
            'donor_name' => trim($_POST['donor_name'] ?? ''),
            'donor_email' => trim($_POST['donor_email'] ?? ''),
            'message' => trim($_POST['message'] ?? ''),
            'payment_method' => $_POST['payment_method'] ?? 'manual',
            'is_anonymous' => isset($_POST['is_anonymous']) ? '1' : '0',
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

        $userId = SessionHelper::getUserId();
        $donorName = $old['donor_name'];
        $donorEmail = $old['donor_email'];

        if (!$userId) {
            if (strlen($donorName) < 3) {
                $errors['donor_name'] = 'Ingresa tu nombre para identificar el aporte.';
            }
            if ($donorEmail === '' || !filter_var($donorEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['donor_email'] = 'Necesitamos un correo válido para confirmar el aporte.';
            }
        } else {
            $currentUser = SessionHelper::getUser();
            $donorName = $currentUser['name'] ?? $donorName;
            $donorEmail = $currentUser['email'] ?? $donorEmail;
        }

        if (isset($_POST['message']) && strlen($old['message']) > 280) {
            $errors['message'] = 'El mensaje puede tener hasta 280 caracteres.';
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
            ],
        ];

        return compact('data', 'errors', 'old');
    }

    private function handleValidationFailure(int $campaignId, array $errors, array $old, int $status = 422, ?string $campaignSlug = null) {
        if ($this->isJsonRequest()) {
            return $this->respondJson(['success' => false, 'errors' => $errors], $status);
        }

        $_SESSION['donation_form_errors'][$campaignId] = $errors;
        $_SESSION['donation_form_old'][$campaignId] = $old;

        SessionHelper::setFlash('error', $errors['general'] ?? 'Corrige los campos marcados para completar tu aporte.');

        $target = $campaignSlug ?? $campaignId;
        Router::redirect('/campana/' . $target . '#donar');
    }

    private function respondSuccess(array $campaign, int $donationId) {
        if ($this->isJsonRequest()) {
            return $this->respondJson([
                'success' => true,
                'donation_id' => $donationId,
                'campaign_id' => $campaign['id'] ?? null,
            ]);
        }

        $slug = $campaign['slug'] ?? $campaign['id'];
        Router::redirect('/campana/' . $slug . '#donar');
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
}

?>
