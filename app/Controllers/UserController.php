<?php
class UserController {
    public function dashboard() {
        $current_page = 'dashboard';

        $userId = SessionHelper::getUserId();
        if (!$userId) {
            Router::redirect('login');
            return;
        }

        $userModel = new User();
        $campaignModel = new Campaign();
        $donationModel = new Donation();
        $notificationModel = new Notification();
        $database = Database::getInstance();

        $hasCampaignsTable = $database->tableExists('campaigns');
        $campaignOwnerColumn = null;
        if ($hasCampaignsTable) {
            if ($database->columnExists('campaigns', 'owner_id')) {
                $campaignOwnerColumn = 'owner_id';
            } elseif ($database->columnExists('campaigns', 'user_id')) {
                $campaignOwnerColumn = 'user_id';
            }
        }

        $campaignsHaveOwner = $campaignOwnerColumn !== null;
        $campaignsHaveStatus = $hasCampaignsTable && $database->columnExists('campaigns', 'status');
        $campaignsHaveCreatedAt = $hasCampaignsTable && $database->columnExists('campaigns', 'created_at');
        $hasCampaignMetricsTable = $database->tableExists('campaign_metrics');
        $hasDonationsTable = $database->tableExists('donations');
        $donationsHaveSupporter = $hasDonationsTable && $database->columnExists('donations', 'supporter_id');
        $donationsHaveStatus = $hasDonationsTable && $database->columnExists('donations', 'status');
        $donationsHaveCreatedAt = $hasDonationsTable && $database->columnExists('donations', 'created_at');
        $hasNotificationsTable = $database->tableExists('notifications')
            && $database->tableExists('notification_user');

        $userRecord = $userModel->findById($userId);
        if (!$userRecord) {
            SessionHelper::logout();
            SessionHelper::setFlash('error', 'Tu cuenta ya no está disponible. Inicia sesión nuevamente.');
            Router::redirect('login');
            return;
        }

        if ($hasCampaignsTable && $campaignsHaveOwner) {
            $rawCampaigns = $campaignModel->findByUserId($userId, 6, 0);
            $campaigns = array_map(static function (array $row) {
                $presented = CampaignPresenter::present($row);
                return array_merge($row, $presented);
            }, $rawCampaigns);
        } else {
            $campaigns = [];
        }

        $campaignStats = $this->resolveCampaignStats(
            $database,
            $hasCampaignsTable,
            $campaignsHaveOwner,
            $campaignsHaveStatus,
            $hasCampaignMetricsTable,
            $campaignOwnerColumn,
            $userId
        );

        $donationStats = [
            'donations_count' => 0,
            'total_donated' => 0,
        ];

        if ($hasDonationsTable && $donationsHaveSupporter) {
            if ($donationsHaveStatus) {
                $donationStats = $database->fetch(
                    "SELECT COUNT(*) AS donations_count,
                            COALESCE(SUM(amount), 0) AS total_donated
                     FROM donations
                     WHERE supporter_id = ? AND status = 'completed'",
                    [$userId]
                ) ?: $donationStats;
            } else {
                $donationStats = $database->fetch(
                    "SELECT COUNT(*) AS donations_count,
                            COALESCE(SUM(amount), 0) AS total_donated
                     FROM donations
                     WHERE supporter_id = ?",
                    [$userId]
                ) ?: $donationStats;
            }
        }

        $recentCampaignDonations = [];
        if (
            $hasDonationsTable &&
            $donationsHaveSupporter &&
            $hasCampaignsTable &&
            $campaignsHaveOwner &&
            $donationsHaveStatus &&
            $donationsHaveCreatedAt
        ) {
            $recentCampaignDonations = $database->fetchAll(
                "SELECT d.amount, d.created_at, d.supporter_name, d.is_anonymous, d.supporter_id,
                        c.title AS campaign_title
                 FROM donations d
                 INNER JOIN campaigns c ON c.id = d.campaign_id
                 WHERE c.{$campaignOwnerColumn} = ? AND d.status = 'completed'
                 ORDER BY d.created_at DESC
                 LIMIT 5",
                [$userId]
            );
        }

        $recentUserDonations = (
            $hasDonationsTable &&
            $donationsHaveSupporter &&
            $hasCampaignsTable &&
            $donationsHaveCreatedAt
        )
            ? $donationModel->findByUserId($userId, 5, 0)
            : [];

        $recentActivity = [];

        foreach ($recentCampaignDonations as $donation) {
            $recentActivity[] = [
                'type' => 'donation_received',
                'message' => $this->formatDonationReceivedMessage($donation),
                'time' => $this->diffForHumans($donation['created_at']),
                'icon' => 'heart',
                'timestamp' => strtotime($donation['created_at']) ?: 0,
            ];
        }

        foreach ($recentUserDonations as $donation) {
            $recentActivity[] = [
                'type' => 'donation_made',
                'message' => $this->formatDonationMadeMessage($donation),
                'time' => $this->diffForHumans($donation['created_at'] ?? ''),
                'icon' => 'gift',
                'timestamp' => isset($donation['created_at']) ? (strtotime($donation['created_at']) ?: 0) : 0,
            ];
        }

        usort($recentActivity, static function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        $recentActivity = array_slice(array_map(static function ($item) {
            unset($item['timestamp']);
            return $item;
        }, $recentActivity), 0, 6);

        $notifications = [];
        if ($hasNotificationsTable) {
            $notifications = array_map(function ($notification) {
                return [
                    'id' => $notification['id'],
                    'type' => $notification['type'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'time' => $this->diffForHumans($notification['created_at']),
                    'read' => (bool)$notification['is_read'],
                    'meta' => $notification['meta'] ?? null,
                ];
            }, $notificationModel->getForUser($userId, 3));
        }

        $normalizedUserAvatar = SessionHelper::normalizeAvatarUrl($userRecord['avatar_url'] ?? null);

        $user = [
            'id' => (int)$userRecord['id'],
            'name' => trim(($userRecord['first_name'] ?? '') . ' ' . ($userRecord['last_name'] ?? '')) ?: ($userRecord['username'] ?? 'Usuario'),
            'email' => $userRecord['email'] ?? '',
            'avatar' => $normalizedUserAvatar ?? APP_URL . '/public/assets/images/avatars/default.jpg',
            'created_at' => $userRecord['created_at'] ?? '',
            'verified' => !empty($userRecord['email_verified_at']),
            'total_campaigns' => (int)$campaignStats['total_campaigns'],
            'total_raised' => (float)$campaignStats['total_raised'],
            'total_supporters' => (int)$campaignStats['total_supporters'],
            'success_rate' => $this->calculateSuccessRate(
                (int)$campaignStats['successful_campaigns'],
                (int)$campaignStats['total_campaigns']
            ),
        ];

        $campaignInsights = [
            'donations_count' => (int)$donationStats['donations_count'],
            'total_donated' => (float)$donationStats['total_donated'],
        ];

        $dashboardCelebration = $this->resolveDashboardCelebration($campaigns, $notifications);
        $campaignMetricsEndpoint = Router::url('api/mis-campanas/resumen');

        include VIEWS_PATH . '/user/dashboard.php';
    }

    public function profile() {
        $current_page = 'profile';

        $userId = SessionHelper::getUserId();
        if (!$userId) {
            Router::redirect('login');
            return;
        }

        $userModel = new User();
        $userRecord = $userModel->findById($userId);

        if (!$userRecord) {
            SessionHelper::logout();
            SessionHelper::setFlash('error', 'Tu cuenta ya no está disponible. Inicia sesión nuevamente.');
            Router::redirect('login');
            return;
        }

        SessionHelper::updateUserProfile($userRecord);
        $sessionUser = SessionHelper::getUser() ?? [];
        $normalizedAvatar = $sessionUser['avatar'] ?? SessionHelper::normalizeAvatarUrl($userRecord['avatar_url'] ?? null);

        $userProfile = [
            'id' => (int)$userRecord['id'],
            'first_name' => $userRecord['first_name'] ?? '',
            'last_name' => $userRecord['last_name'] ?? '',
            'name' => trim(($userRecord['first_name'] ?? '') . ' ' . ($userRecord['last_name'] ?? '')) ?: ($userRecord['username'] ?? 'Usuario'),
            'username' => $userRecord['username'] ?? '',
            'email' => $userRecord['email'] ?? '',
            'phone' => $userRecord['phone'] ?? '',
            'bio' => $userRecord['bio'] ?? '',
            'location' => $userRecord['location'] ?? '',
            'avatar_url' => $normalizedAvatar ?? APP_URL . '/public/assets/images/avatars/default.jpg',
            'created_at' => $userRecord['created_at'] ?? '',
            'email_verified_at' => $userRecord['email_verified_at'] ?? null,
            'role' => $userRecord['role'] ?? 'user',
            'status' => $userRecord['status'] ?? 'active',
            'last_login_at' => $userRecord['last_login_at'] ?? null,
            'last_login_ip' => $userRecord['last_login_ip'] ?? null,
            'social_links' => !empty($userRecord['social_links']) && is_string($userRecord['social_links'])
                ? (json_decode($userRecord['social_links'], true) ?: [])
                : (is_array($userRecord['social_links'] ?? null) ? $userRecord['social_links'] : []),
            'requested_first_name' => $userRecord['requested_first_name'] ?? null,
            'requested_last_name' => $userRecord['requested_last_name'] ?? null,
            'name_review_status' => $userRecord['name_review_status'] ?? null,
            'name_review_notes' => $userRecord['name_review_notes'] ?? null,
            'name_reviewed_at' => $userRecord['name_reviewed_at'] ?? null,
        ];

        $communicationPreferences = [
            'product_updates' => isset($userRecord['pref_product_updates']) ? (bool)$userRecord['pref_product_updates'] : true,
            'campaign_tips' => isset($userRecord['pref_campaign_tips']) ? (bool)$userRecord['pref_campaign_tips'] : true,
            'donation_alerts' => isset($userRecord['pref_donation_alerts']) ? (bool)$userRecord['pref_donation_alerts'] : true,
        ];

        $securityOverview = [
            'two_factor_enabled' => isset($userRecord['two_factor_enabled']) ? (bool)$userRecord['two_factor_enabled'] : false,
            'password_updated_at' => $userRecord['password_updated_at'] ?? null,
            'last_password_reset' => $userRecord['password_reset_at'] ?? null,
            'two_factor_supported' => isset($userRecord['two_factor_enabled']),
        ];

        include VIEWS_PATH . '/user/perfil.php';
    }

    public function updateProfile() {
        $userId = SessionHelper::getUserId();
        if (!$userId) {
            Router::redirect('login');
            return;
        }

        $userModel = new User();
        $userRecord = $userModel->findById($userId);
        if (!$userRecord) {
            SessionHelper::logout();
            SessionHelper::setFlash('error', 'Tu cuenta ya no está disponible. Inicia sesión nuevamente.');
            Router::redirect('login');
            return;
        }

        $errors = [];
        $profileUpdates = [];
        $shouldDeleteOldAvatar = false;
        $avatarService = null;

        $currentPhone = $userRecord['phone'] ?? '';
        if (array_key_exists('phone', $_POST)) {
            $phone = trim((string)$_POST['phone']);
            if ($phone !== $currentPhone) {
                if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{6,20}$/', $phone)) {
                    $errors[] = 'El teléfono tiene un formato inválido.';
                } else {
                    $profileUpdates['phone'] = $phone !== '' ? $phone : null;
                }
            }
        }

        $currentLocation = $userRecord['location'] ?? '';
        if (array_key_exists('location', $_POST)) {
            $location = trim((string)$_POST['location']);
            if ($location !== $currentLocation) {
                if (strlen($location) > 150) {
                    $errors[] = 'La ubicación no puede superar 150 caracteres.';
                } else {
                    $profileUpdates['location'] = $location !== '' ? $location : null;
                }
            }
        }

        $currentBio = $userRecord['bio'] ?? '';
        if (array_key_exists('bio', $_POST)) {
            $bio = trim((string)$_POST['bio']);
            if ($bio !== $currentBio) {
                if (strlen($bio) > 1500) {
                    $errors[] = 'La biografía puede tener máximo 1500 caracteres.';
                } else {
                    $profileUpdates['bio'] = $bio !== '' ? $bio : null;
                }
            }
        }

        $currentAvatar = $userRecord['avatar_url'] ?? '';
        if (array_key_exists('avatar_url', $_POST)) {
            $avatarUrl = trim((string)$_POST['avatar_url']);
            if ($avatarUrl !== $currentAvatar) {
                if ($avatarUrl !== '' && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
                    $errors[] = 'La URL de la imagen de perfil es inválida.';
                } else {
                    $profileUpdates['avatar_url'] = $avatarUrl !== '' ? $avatarUrl : null;
                    $shouldDeleteOldAvatar = true;
                }
            }
        }

        if (!empty($_FILES['avatar_file']) && is_array($_FILES['avatar_file'])) {
            $uploadError = $_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                if (!$avatarService) {
                    $avatarService = new AvatarUploadService();
                }

                try {
                    $newAvatarUrl = $avatarService->storeUploadedAvatar($_FILES['avatar_file'], $userId);
                    $profileUpdates['avatar_url'] = $newAvatarUrl;
                    $shouldDeleteOldAvatar = true;
                } catch (RuntimeException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        if (array_key_exists('social', $_POST)) {
            $submittedSocial = $_POST['social'];
            $cleanSocial = [];

            if (is_array($submittedSocial)) {
                foreach ($submittedSocial as $platform => $url) {
                    $url = trim((string)$url);
                    if ($url === '') {
                        continue;
                    }
                    if (!filter_var($url, FILTER_VALIDATE_URL)) {
                        $errors[] = 'Enlace inválido para ' . htmlspecialchars($platform, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '.';
                        continue;
                    }
                    $cleanSocial[$platform] = $url;
                }
            }

            $currentSocial = [];
            if (!empty($userRecord['social_links'])) {
                $decoded = json_decode($userRecord['social_links'], true);
                if (is_array($decoded)) {
                    $currentSocial = $decoded;
                }
            }

            if ($cleanSocial !== $currentSocial) {
                $profileUpdates['social_links'] = $cleanSocial;
            }
        }

        $currentFirst = $userRecord['first_name'] ?? '';
        $currentLast = $userRecord['last_name'] ?? '';
        $newFirst = $currentFirst;
        $newLast = $currentLast;
        $nameChangeRequested = false;

        $firstNameProvided = array_key_exists('first_name', $_POST);
        $lastNameProvided = array_key_exists('last_name', $_POST);

        if ($firstNameProvided) {
            $candidateFirst = trim((string)$_POST['first_name']);
            if ($candidateFirst !== '') {
                $newFirst = $candidateFirst;
            }
        }
        if ($lastNameProvided) {
            $candidateLast = trim((string)$_POST['last_name']);
            if ($candidateLast !== '') {
                $newLast = $candidateLast;
            }
        }

        if (($firstNameProvided || $lastNameProvided) && ($newFirst !== $currentFirst || $newLast !== $currentLast)) {
            if (!preg_match('/^[\p{L}\p{M}\s\-\']{2,100}$/u', $newFirst)) {
                $errors[] = 'El nombre debe contener solo letras y un mínimo de 2 caracteres.';
            }
            if (!preg_match('/^[\p{L}\p{M}\s\-\']{2,100}$/u', $newLast)) {
                $errors[] = 'El apellido debe contener solo letras y un mínimo de 2 caracteres.';
            }
            $nameChangeRequested = empty($errors);
        }

        if (!empty($errors)) {
            SessionHelper::setFlash('error', implode(' ', $errors));
            Router::redirect('perfil');
            return;
        }

        $previousAvatar = $userRecord['avatar_url'] ?? null;

        try {
            if (!empty($profileUpdates)) {
                $userModel->updateProfile($userId, $profileUpdates);

                if ($shouldDeleteOldAvatar) {
                    $avatarService = $avatarService ?? new AvatarUploadService();
                    $avatarService->deleteManagedAvatar($previousAvatar);
                }

                SessionHelper::setFlash('success', 'Guardamos los cambios de tu perfil.');
            }

            if ($nameChangeRequested) {
                $userModel->requestNameChange($userId, $newFirst, $newLast);
                SessionHelper::setFlash('info', 'Tu cambio de nombre quedará visible una vez que nuestro equipo lo apruebe.');
            }

            if (empty($profileUpdates) && !$nameChangeRequested) {
                SessionHelper::setFlash('info', 'No detectamos cambios para actualizar.');
            }

            if (!empty($profileUpdates) || $nameChangeRequested) {
                $updatedRecord = $userModel->findById($userId);
                if ($updatedRecord) {
                    SessionHelper::updateUserProfile($updatedRecord);
                }
            }
        } catch (Exception $e) {
            Logger::error('Error updating profile', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            SessionHelper::setFlash('error', 'No pudimos actualizar tu perfil. Intenta nuevamente.');
        }

        Router::redirect('perfil');
    }

    public function updatePreferences() {
        $userId = SessionHelper::getUserId();
        if (!$userId) {
            Router::redirect('login');
            return;
        }

        $preferences = [
            'product_updates' => isset($_POST['product_updates']) ? 1 : 0,
            'campaign_tips' => isset($_POST['campaign_tips']) ? 1 : 0,
            'donation_alerts' => isset($_POST['donation_alerts']) ? 1 : 0,
        ];

        $userModel = new User();

        try {
            $userModel->updatePreferences($userId, $preferences);
            SessionHelper::setFlash('success', 'Actualizamos tus preferencias de comunicación.');
        } catch (Exception $e) {
            Logger::error('Error updating preferences', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            SessionHelper::setFlash('error', 'No pudimos guardar las preferencias. Inténtalo nuevamente.');
        }

        Router::redirect('perfil');
    }

    public function updateSecurity() {
        $userId = SessionHelper::getUserId();
        if (!$userId) {
            Router::redirect('login');
            return;
        }

        $action = $_POST['two_factor_action'] ?? 'none';
        $userModel = new User();

        try {
            $currentUser = $userModel->findById($userId);
            if (!$currentUser) {
                throw new Exception('Cuenta no disponible.');
            }

            $enabled = isset($currentUser['two_factor_enabled']) ? (bool)$currentUser['two_factor_enabled'] : false;

            if ($action === 'enable') {
                $secret = bin2hex(random_bytes(16));
                $userModel->updateTwoFactorStatus($userId, true, $secret);
                SessionHelper::setFlash('success', 'Activamos la verificación en dos pasos. Pronto publicaremos la guía para completar el enrolamiento definitivo.');
            } elseif ($action === 'disable' && $enabled) {
                $userModel->updateTwoFactorStatus($userId, false, null);
                SessionHelper::setFlash('info', 'Desactivamos temporalmente la autenticación en dos pasos para tu cuenta.');
            } else {
                SessionHelper::setFlash('info', 'No realizamos cambios en la autenticación.');
            }
        } catch (Exception $e) {
            Logger::error('Error updating 2FA', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            SessionHelper::setFlash('error', 'No pudimos actualizar la configuración de seguridad.');
        }

        Router::redirect('perfil');
    }

    public function updatePassword() {
        $userId = SessionHelper::getUserId();
        if (!$userId) {
            Router::redirect('login');
            return;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $verificationCode = trim($_POST['verification_code'] ?? '');

        $userModel = new User();
        $userRecord = $userModel->findById($userId);
        if (!$userRecord) {
            SessionHelper::logout();
            SessionHelper::setFlash('error', 'Tu cuenta ya no está disponible. Inicia sesión nuevamente.');
            Router::redirect('login');
            return;
        }

        $errors = [];

        if (!password_verify($currentPassword, $userRecord['password_hash'] ?? '')) {
            $errors[] = 'La contraseña actual es incorrecta.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'La confirmación no coincide con la nueva contraseña.';
        }

        if (password_verify($newPassword, $userRecord['password_hash'] ?? '')) {
            $errors[] = 'La nueva contraseña debe ser distinta a la actual.';
        }

        if (!empty($errors)) {
            SessionHelper::setFlash('error', implode(' ', $errors));
            Router::redirect('perfil');
            return;
        }

        $sessionCode = $_SESSION['password_change_code'] ?? null;
        $sessionExpiry = $_SESSION['password_change_expires'] ?? 0;

        if (
            !$sessionCode ||
            $verificationCode === '' ||
            time() > (int)$sessionExpiry ||
            !hash_equals((string)$sessionCode, $verificationCode)
        ) {
            $generatedCode = (string)random_int(100000, 999999);
            $_SESSION['password_change_code'] = $generatedCode;
            $_SESSION['password_change_expires'] = time() + 600;

            Logger::audit('user_password_code_generated', $userId);

            SessionHelper::setFlash('info', 'Enviamos un código de verificación a tu correo. Ingrésalo para confirmar el cambio. (Entorno de pruebas: código ' . $generatedCode . ')');
            Router::redirect('perfil');
            return;
        }

        try {
            $userModel->changePassword($userId, $newPassword);
            unset($_SESSION['password_change_code'], $_SESSION['password_change_expires']);
            SessionHelper::setFlash('success', 'Tu contraseña fue actualizada correctamente.');
        } catch (Exception $e) {
            Logger::error('Error changing password', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            SessionHelper::setFlash('error', 'No pudimos cambiar tu contraseña. Intenta nuevamente.');
        }

        Router::redirect('perfil');
    }

    public function campaignMetrics(): void
    {
        if (!SessionHelper::isAuthenticated()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $userId = (int)SessionHelper::getUserId();
        $campaignModel = new Campaign();

        try {
            $rows = $campaignModel->findByUserId($userId, 100, 0);
        } catch (Throwable $exception) {
            Logger::error('No se pudieron cargar las métricas de campañas', [
                'user_id' => $userId,
                'error' => $exception->getMessage()
            ]);

            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'No se pudieron obtener las métricas'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = array_map(static function (array $row) {
            $campaignId = (int)($row['id'] ?? 0);
            $goal = (float)($row['goal_amount'] ?? 0.0);
            $raised = (float)($row['raised_amount'] ?? 0.0);
            $currency = $row['currency'] ?? 'CLP';
            $progress = $goal > 0 ? min(100, round(($raised / $goal) * 100, 2)) : 0.0;

            return [
                'id' => $campaignId,
                'slug' => $row['slug'] ?? null,
                'title' => $row['title'] ?? 'Campaña',
                'goal_amount' => $goal,
                'raised_amount' => $raised,
                'progress' => $progress,
                'currency' => $currency,
                'status' => $row['status'] ?? null,
                'funded_at' => $row['funded_at'] ?? null,
                'funding_celebrated_at' => $row['funding_celebrated_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ];
        }, $rows);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['data' => $payload], JSON_UNESCAPED_UNICODE);
    }

    private function diffForHumans(?string $timestamp): string {
        if (empty($timestamp)) {
            return '';
        }

        $time = strtotime($timestamp);
        if (!$time) {
            return '';
        }

        $diff = max(0, time() - $time);

        if ($diff < 60) {
            return 'unos segundos';
        }

        if ($diff < 3600) {
            $minutes = (int)floor($diff / 60);
            return $minutes . ' minuto' . ($minutes === 1 ? '' : 's');
        }

        if ($diff < 86400) {
            $hours = (int)floor($diff / 3600);
            return $hours . ' hora' . ($hours === 1 ? '' : 's');
        }

        if ($diff < 604800) {
            $days = (int)floor($diff / 86400);
            return $days . ' día' . ($days === 1 ? '' : 's');
        }

        return date('d/m/Y', $time);
    }

    private function resolveDashboardCelebration(array $campaigns, array $notifications): ?array
    {
        $sessionKey = 'campaign_celebrations_shown';
        $shown = $_SESSION[$sessionKey] ?? [];

        $campaignIndex = [];
        foreach ($campaigns as $campaign) {
            $id = (int)($campaign['id'] ?? 0);
            if ($id > 0) {
                $campaignIndex[$id] = $campaign;
            }
        }

        $formatCurrency = static function (float $amount, string $currency): string {
            $currency = strtoupper($currency ?: 'CLP');
            $formatted = number_format(max(0, $amount), 0, ',', '.');
            return $currency === 'CLP'
                ? '$' . $formatted
                : $currency . ' ' . $formatted;
        };

        $campaignModel = null;

        $buildPayload = function (array $campaignData, array $meta = []) use (&$shown, $sessionKey, $formatCurrency, &$campaignModel) {
            $campaignId = (int)($campaignData['id'] ?? ($meta['campaign_id'] ?? 0));
            if ($campaignId <= 0) {
                return null;
            }

            if (!empty($shown[$campaignId])) {
                return null;
            }

            if (!empty($campaignData['funding_celebrated_at'])) {
                return null;
            }

            $progress = (float)($campaignData['progress'] ?? ($meta['progress'] ?? 0));
            if ($progress < 100.0) {
                return null;
            }

            $goal = (float)($campaignData['goal_amount'] ?? ($meta['goal_amount'] ?? 0));
            $raised = (float)($campaignData['raised_amount'] ?? ($meta['raised_amount'] ?? 0));
            $currency = $campaignData['currency'] ?? ($meta['currency'] ?? 'CLP');

            $shown[$campaignId] = time();
            $_SESSION[$sessionKey] = $shown;

            if ($campaignModel === null) {
                $campaignModel = new Campaign();
            }

            try {
                $campaignModel->markFundingMilestone($campaignId, ['mark_celebrated' => true]);
            } catch (Throwable $exception) {
                Logger::warning('No se pudo marcar la celebración en panel', [
                    'campaign_id' => $campaignId,
                    'error' => $exception->getMessage()
                ]);
            }

            $campaignPublicPath = $campaignData['public_path'] ?? CampaignPresenter::buildPublicPath(array_merge($campaignData, ['id' => $campaignId]));
            $publicUrl = $campaignPublicPath ? Router::url($campaignPublicPath) : Router::url('campana/' . ($campaignData['slug'] ?? $campaignId));
            $manageUrl = Router::url('campana/' . $campaignId . '/editar');

            return [
                'campaign_id' => $campaignId,
                'campaign_title' => $campaignData['title'] ?? ($meta['campaign_title'] ?? 'Tu campaña'),
                'raised_amount' => $formatCurrency($raised, $currency),
                'goal_amount' => $formatCurrency($goal, $currency),
                'progress' => min(100, round($progress, 1)),
                'public_url' => $publicUrl,
                'manage_url' => $manageUrl,
            ];
        };

        foreach ($notifications as $notification) {
            $meta = is_array($notification['meta'] ?? null) ? $notification['meta'] : [];
            $event = $meta['event'] ?? $meta['milestone'] ?? null;
            if ($event !== 'campaign_goal_reached' && $event !== 'goal_reached') {
                continue;
            }

            $campaignId = (int)($meta['campaign_id'] ?? 0);
            if ($campaignId <= 0) {
                continue;
            }

            $campaignData = $campaignIndex[$campaignId] ?? null;
            if (!$campaignData) {
                if ($campaignModel === null) {
                    $campaignModel = new Campaign();
                }

                $record = $campaignModel->findById($campaignId);
                if (!$record) {
                    continue;
                }
                $campaignData = array_merge($record, CampaignPresenter::present($record));
            }

            $payload = $buildPayload($campaignData, $meta);
            if ($payload !== null) {
                return $payload;
            }
        }

        foreach ($campaignIndex as $campaignData) {
            $payload = $buildPayload($campaignData);
            if ($payload !== null) {
                return $payload;
            }
        }

        return null;
    }

    private function formatDonationReceivedMessage(array $donation): string {
        $supporterName = ($donation['is_anonymous'] ?? false)
            ? 'Una persona anónima'
            : ($donation['supporter_name'] ?? 'Un donante');

        $amount = isset($donation['amount'])
            ? '$' . number_format((float)$donation['amount'], 0, ',', '.')
            : 'una donación';

        $campaignTitle = $donation['campaign_title'] ?? 'tu campaña';

        return sprintf('%s aportó %s a "%s"', $supporterName, $amount, $campaignTitle);
    }

    private function formatDonationMadeMessage(array $donation): string {
        $amount = isset($donation['amount'])
            ? '$' . number_format((float)$donation['amount'], 0, ',', '.')
            : 'una donación';

        $campaignTitle = $donation['campaign_title'] ?? 'una campaña';

        return sprintf('Aportaste %s a "%s"', $amount, $campaignTitle);
    }

    private function resolveCampaignStats(
        Database $database,
        bool $hasCampaignsTable,
        bool $campaignsHaveOwner,
        bool $campaignsHaveStatus,
        bool $hasCampaignMetricsTable,
        ?string $campaignOwnerColumn,
        int $userId
    ): array {
        $defaults = [
            'total_campaigns' => 0,
            'total_raised' => 0.0,
            'total_supporters' => 0,
            'successful_campaigns' => 0,
        ];

        if (!$hasCampaignsTable || !$campaignsHaveOwner || empty($campaignOwnerColumn)) {
            return $defaults;
        }

        try {
            if ($hasCampaignMetricsTable) {
                if ($campaignsHaveStatus) {
                    $stats = $database->fetch(
                        "SELECT COUNT(c.id) AS total_campaigns,
                                COALESCE(SUM(cm.raised_amount), 0) AS total_raised,
                                COALESCE(SUM(cm.donor_count), 0) AS total_supporters,
                                COALESCE(SUM(CASE WHEN c.status IN ('completed', 'published') THEN 1 ELSE 0 END), 0) AS successful_campaigns
                         FROM campaigns c
                         LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
                         WHERE c.{$campaignOwnerColumn} = ?",
                        [$userId]
                    );
                } else {
                    $stats = $database->fetch(
                        "SELECT COUNT(c.id) AS total_campaigns,
                                COALESCE(SUM(cm.raised_amount), 0) AS total_raised,
                                COALESCE(SUM(cm.donor_count), 0) AS total_supporters,
                                0 AS successful_campaigns
                         FROM campaigns c
                         LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
                         WHERE c.{$campaignOwnerColumn} = ?",
                        [$userId]
                    );
                }
            } else {
                if ($campaignsHaveStatus) {
                    $stats = $database->fetch(
                        "SELECT COUNT(*) AS total_campaigns,
                                0 AS total_raised,
                                0 AS total_supporters,
                                COALESCE(SUM(CASE WHEN status IN ('completed', 'published') THEN 1 ELSE 0 END), 0) AS successful_campaigns
                         FROM campaigns
                         WHERE {$campaignOwnerColumn} = ?",
                        [$userId]
                    );
                } else {
                    $stats = $database->fetch(
                        "SELECT COUNT(*) AS total_campaigns,
                                0 AS total_raised,
                                0 AS total_supporters,
                                0 AS successful_campaigns
                         FROM campaigns
                         WHERE {$campaignOwnerColumn} = ?",
                        [$userId]
                    );
                }
            }

            if (!$stats) {
                return $defaults;
            }

            return [
                'total_campaigns' => (int)($stats['total_campaigns'] ?? 0),
                'total_raised' => (float)($stats['total_raised'] ?? 0),
                'total_supporters' => (int)($stats['total_supporters'] ?? 0),
                'successful_campaigns' => (int)($stats['successful_campaigns'] ?? 0),
            ];
        } catch (Exception $e) {
            Logger::warning('Campaign stats fallback', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $defaults;
        }
    }

    private function calculateSuccessRate(int $successful, int $total): float {
        if ($total === 0) {
            return 0.0;
        }

        return round(($successful / $total) * 100, 1);
    }
}
?>
