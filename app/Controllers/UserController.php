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
        $campaignsHaveOwner = $hasCampaignsTable && $database->columnExists('campaigns', 'owner_id');
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

        $campaigns = ($hasCampaignsTable && $campaignsHaveOwner)
            ? $campaignModel->findByUserId($userId, 6, 0)
            : [];

        $campaignStats = $this->resolveCampaignStats(
            $database,
            $hasCampaignsTable,
            $campaignsHaveOwner,
            $campaignsHaveStatus,
            $hasCampaignMetricsTable,
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
                 WHERE c.owner_id = ? AND d.status = 'completed'
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
                ];
            }, $notificationModel->getForUser($userId, 5));
        }

        $user = [
            'id' => (int)$userRecord['id'],
            'name' => trim(($userRecord['first_name'] ?? '') . ' ' . ($userRecord['last_name'] ?? '')) ?: ($userRecord['username'] ?? 'Usuario'),
            'email' => $userRecord['email'] ?? '',
            'avatar' => $userRecord['avatar_url'] ?? APP_URL . '/public/assets/images/avatars/default.jpg',
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
            'avatar_url' => $userRecord['avatar_url'] ?? APP_URL . '/public/assets/images/avatars/default.jpg',
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
        int $userId
    ): array {
        $defaults = [
            'total_campaigns' => 0,
            'total_raised' => 0.0,
            'total_supporters' => 0,
            'successful_campaigns' => 0,
        ];

        if (!$hasCampaignsTable || !$campaignsHaveOwner) {
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
                         WHERE c.owner_id = ?",
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
                         WHERE c.owner_id = ?",
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
                         WHERE owner_id = ?",
                        [$userId]
                    );
                } else {
                    $stats = $database->fetch(
                        "SELECT COUNT(*) AS total_campaigns,
                                0 AS total_raised,
                                0 AS total_supporters,
                                0 AS successful_campaigns
                         FROM campaigns
                         WHERE owner_id = ?",
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
