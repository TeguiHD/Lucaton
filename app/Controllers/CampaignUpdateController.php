<?php

class CampaignUpdateController
{
    private Campaign $campaigns;
    private CampaignUpdate $updates;

    public function __construct()
    {
        $this->campaigns = new Campaign();
        $this->updates = new CampaignUpdate();
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
        $campaignStatus = strtolower((string)($campaign['status'] ?? ''));
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

        try {
            $this->updates->create((int)$campaign['id'], $currentUserId, [
                'title' => $input['title'],
                'body' => $input['body'],
                'media' => $this->parseMedia($input),
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
        return [
            'title' => trim((string)($_POST['title'] ?? '')),
            'body' => trim((string)($_POST['body'] ?? '')),
            'media_urls' => $_POST['media_urls'] ?? []
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

        if (!empty($input['media_urls']) && is_array($input['media_urls'])) {
            $validUrls = array_filter($input['media_urls'], function ($url) {
                $url = trim((string)$url);
                return $url === '' || filter_var($url, FILTER_VALIDATE_URL);
            });

            if (count($validUrls) !== count($input['media_urls'])) {
                $errors[] = 'Una o más URLs de medios no tienen un formato válido.';
            }
        }

        return $errors;
    }

    private function parseMedia(array $input): array
    {
        if (empty($input['media_urls']) || !is_array($input['media_urls'])) {
            return [];
        }

        $media = [];
        foreach ($input['media_urls'] as $url) {
            $url = trim((string)$url);
            if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $media[] = ['url' => $url];
        }

        return $media;
    }
}
