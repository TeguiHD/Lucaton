<?php

class CampaignUpdateController
{
    private Campaign $campaigns;
    private CampaignUpdate $updates;
    private CampaignMediaUploadService $mediaUploads;

    public function __construct()
    {
        $this->campaigns = new Campaign();
        $this->updates = new CampaignUpdate();
        $this->mediaUploads = new CampaignMediaUploadService();
    }

    public function store($username, $identifier): void
    {
        if (!SessionHelper::isAuthenticated()) {
            SessionHelper::setFlash('warning', 'Debes iniciar sesión para publicar una actualización.');
            Router::redirect('/login');
            return;
        }

        $token = $_POST[CSRF_TOKEN_NAME] ?? '';
        if (!SessionHelper::verifyCSRFToken($token)) {
            SessionHelper::setFlash('error', 'Tu sesión caducó. Intenta nuevamente.');
            Router::redirect('/');
            return;
        }

        $campaign = $this->resolveCampaign($username, $identifier);
        if (!$campaign) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $campaignUpdatesTarget = ($campaign['public_path'] ?? ('campana/' . ($campaign['slug'] ?? $campaign['id']))) . '#actualizaciones';

        $currentUserId = (int)SessionHelper::getUserId();
        $ownerId = (int)($campaign['owner_id'] ?? $campaign['user_id'] ?? 0);
        $canManageCampaign = $ownerId === $currentUserId || SessionHelper::userHasRole('admin');

        if (!$canManageCampaign) {
            http_response_code(403);
            SessionHelper::setFlash('error', 'No tienes permisos para actualizar esta campaña.');
            Router::redirect($campaign['public_path'] ?? ('campana/' . ($campaign['slug'] ?? $campaign['id'])));
            return;
        }

        $goalAmount = (float)($campaign['goal_amount'] ?? 0);
        $raisedAmount = (float)($campaign['raised_amount'] ?? 0);
        $goalReached = $goalAmount > 0 && $raisedAmount >= $goalAmount;

        $endTimestamp = null;
        $rawEnd = trim((string)($campaign['end_date'] ?? ''));
        if ($rawEnd !== '') {
            $parsed = strtotime($rawEnd);
            if ($parsed === false) {
                $dateOnly = DateTime::createFromFormat('Y-m-d', $rawEnd);
                if ($dateOnly instanceof DateTime) {
                    $parsed = $dateOnly->getTimestamp();
                }
            }
            if ($parsed !== false) {
                $endTimestamp = $parsed;
            }
        }
        $timeOver = $endTimestamp !== null && $endTimestamp < time();
        $campaignStatus = strtolower(trim((string)($campaign['status'] ?? '')));
        $approvedStatuses = ['published', 'paused', 'completed'];
        if (!in_array($campaignStatus, $approvedStatuses, true)) {
            SessionHelper::setFlash('warning', 'Tu campaña aún está en revisión. Podrás compartir actualizaciones cuando el equipo la apruebe.');
            Router::redirect($campaignUpdatesTarget);
            return;
        }
        $campaignFinalized = in_array($campaignStatus, ['completed', 'cancelled', 'archived'], true) || $goalReached || $timeOver;
        $finalUpdateAlreadyPosted = !empty($campaign['funding_celebrated_at']);

        if ($campaignFinalized && $finalUpdateAlreadyPosted) {
            SessionHelper::setFlash('warning', 'Esta campaña ya fue cerrada definitivamente. Si necesitas compartir novedades, crea una nueva campaña o contáctanos.');
            Router::redirect($campaignUpdatesTarget);
            return;
        }

        $input = $this->collectInput();
        $errors = $this->validateInput($input);

        if (!empty($errors)) {
            $_SESSION['campaign_update_errors'] = $errors;
            $_SESSION['campaign_update_old'] = $input;
            Router::redirect($campaignUpdatesTarget);
            return;
        }

        $storedMediaUrls = [];
        try {
            $mediaPayloadData = $this->prepareMediaPayload($input, (int)$campaign['id'], $currentUserId);
            $mediaPayload = $mediaPayloadData['items'];
            $storedMediaUrls = $mediaPayloadData['cleanup'];
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
            $_SESSION['campaign_update_errors'] = $errors;
            $_SESSION['campaign_update_old'] = $input;
            Router::redirect($campaignUpdatesTarget);
            return;
        }

        try {
            $this->updates->create((int)$campaign['id'], $currentUserId, [
                'title' => $input['title'],
                'body' => $input['body'],
                'media' => $mediaPayload,
                'status' => 'published',
                'visibility' => 'public'
            ]);

            if ($campaignFinalized && !$finalUpdateAlreadyPosted) {
                $this->campaigns->markFundingMilestone((int)$campaign['id'], [
                    'mark_celebrated' => true,
                    'funding_celebrated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (Throwable $exception) {
            foreach ($storedMediaUrls as $url) {
                try {
                    $this->mediaUploads->deletePublicUrl($url);
                } catch (Throwable $cleanupException) {
                    Logger::warning('No se pudo eliminar un archivo de actualización tras error', [
                        'campaign_id' => $campaign['id'] ?? null,
                        'url' => $url,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }
            }
            Logger::error('No se pudo crear la actualización de campaña', [
                'campaign_id' => $campaign['id'] ?? null,
                'user_id' => $currentUserId,
                'error' => $exception->getMessage()
            ]);

            SessionHelper::setFlash('error', 'Ocurrió un error al guardar la actualización. Intenta de nuevo en unos minutos.');
            Router::redirect($campaignUpdatesTarget);
            return;
        }

        if ($campaignFinalized) {
            SessionHelper::setFlash('success', 'Tu mensaje de cierre se publicó correctamente. ¡Gracias por acompañar a tu comunidad con transparencia!');
        } else {
            SessionHelper::setFlash('success', 'Tu actualización se publicó correctamente.');
        }
        Router::redirect($campaignUpdatesTarget);
    }

    private function resolveCampaign($username, $identifier): ?array
    {
        $record = null;

        if (is_numeric($identifier)) {
            $record = $this->campaigns->findById((int)$identifier);
        } elseif (is_string($identifier) && $identifier !== '') {
            $record = $this->campaigns->findBySlug($identifier, (string)$username);
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

    private function collectInput(): array
    {
        $socialLinks = $_POST['social_links'] ?? [];
        if (!is_array($socialLinks)) {
            $socialLinks = [];
        }
        $socialLinks = array_map(static function ($value) {
            return trim((string)$value);
        }, array_slice($socialLinks, 0, 3));

        return [
            'title' => trim((string)($_POST['title'] ?? '')),
            'body' => trim((string)($_POST['body'] ?? '')),
            'social_links' => $socialLinks,
            'media_urls' => [] // legado
        ];
    }

    private function validateInput(array $input): array
    {
        $errors = [];

        if ($input['title'] !== '' && mb_strlen($input['title']) > 150) {
            $errors[] = 'El título puede tener máximo 150 caracteres.';
        }

        if ($input['body'] === '') {
            $errors[] = 'Comparte al menos un mensaje para tu comunidad.';
        } elseif (mb_strlen($input['body']) < 20) {
            $errors[] = 'La actualización debe tener al menos 20 caracteres.';
        }

        if (!empty($input['social_links']) && is_array($input['social_links'])) {
            foreach ($input['social_links'] as $link) {
                $link = trim((string)$link);
                if ($link === '') {
                    continue;
                }
                if (!filter_var($link, FILTER_VALIDATE_URL)) {
                    $errors[] = 'Una o más redes sociales no tienen un formato válido.';
                    break;
                }
            }
        }

        return $errors;
    }

    private function prepareMediaPayload(array $input, int $campaignId, int $userId): array
    {
        $storedImages = [];
        if (isset($_FILES['update_images'])) {
            $storedImages = $this->mediaUploads->storeUpdateImages($_FILES['update_images'], $campaignId, $userId);
        }

        $media = [];
        foreach ($storedImages as $image) {
            $media[] = [
                'type' => 'image',
                'url' => $image['url'],
                'mime' => $image['mime'] ?? null,
            ];
        }

        if (!empty($input['social_links']) && is_array($input['social_links'])) {
            foreach ($input['social_links'] as $link) {
                $link = trim((string)$link);
                if ($link === '' || !filter_var($link, FILTER_VALIDATE_URL)) {
                    continue;
                }
                $platformMeta = $this->detectSocialPlatform($link);
                $media[] = [
                    'type' => 'link',
                    'url' => $link,
                    'platform' => $platformMeta['platform'],
                    'label' => $platformMeta['label'],
                    'initial' => $platformMeta['initial'],
                ];
            }
        }

        return [
            'items' => $media,
            'cleanup' => array_column($storedImages, 'url'),
        ];
    }

    private function detectSocialPlatform(string $url): array
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $platformDomains = [
            'instagram' => ['instagram.com'],
            'facebook' => ['facebook.com', 'fb.com'],
            'x' => ['twitter.com', 'x.com'],
            'tiktok' => ['tiktok.com'],
            'youtube' => ['youtube.com', 'youtu.be'],
            'linkedin' => ['linkedin.com'],
            'whatsapp' => ['wa.me', 'whatsapp.com'],
        ];

        $platformLabels = [
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'x' => 'X (Twitter)',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'linkedin' => 'LinkedIn',
            'whatsapp' => 'WhatsApp',
            'website' => 'Sitio web',
            'linktree' => 'Linktree',
        ];

        foreach ($platformDomains as $platform => $domains) {
            foreach ($domains as $domain) {
                if ($host === $domain || substr($host, -strlen($domain)) === $domain) {
                    $label = $platformLabels[$platform] ?? ucfirst($platform);
                    $initial = strtoupper(mb_substr($label, 0, 2));
                    return [
                        'platform' => $platform,
                        'label' => $label,
                        'initial' => $initial !== '' ? $initial : 'EN',
                    ];
                }
            }
        }

        if ($host === 'linktr.ee' || substr($host, -strlen('linktr.ee')) === 'linktr.ee') {
            return [
                'platform' => 'linktree',
                'label' => $platformLabels['linktree'],
                'initial' => 'LT',
            ];
        }

        $label = $platformLabels['website'];
        return [
            'platform' => 'website',
            'label' => $label,
            'initial' => 'WEB',
        ];
    }
}
