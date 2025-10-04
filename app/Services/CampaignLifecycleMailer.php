<?php

class CampaignLifecycleMailer
{
    private Notification $notifications;
    private Campaign $campaigns;
    private User $users;
    private bool $mailEnabled;
    private bool $notificationsEnabled;
    private string $previewDirectory;

    private const OWNER_MILESTONE_PREFIX = 'owner_';
    private const FUNDING_THRESHOLDS = [25.0, 50.0, 75.0, 90.0];

    public function __construct()
    {
        $this->notifications = new Notification();
        $this->campaigns = new Campaign();
        $this->users = new User();

        $db = Database::getInstance();
        $this->notificationsEnabled = $db->tableExists('notifications') && $db->tableExists('notification_user');

        $this->mailEnabled = $this->resolveMailEnabled();
        $this->previewDirectory = ROOT_PATH . '/storage/logs/mail-previews';
    }

    public function campaignCreated(int $campaignId, array $context = []): void
    {
        $campaign = $this->resolveCampaign($campaignId, $context['campaign'] ?? null);
        if (!$campaign) {
            return;
        }

        $owner = $this->resolveOwner($campaign, $context['owner_id'] ?? null);
        if (!$owner || empty($owner['email'])) {
            return;
        }

        $milestoneKey = self::OWNER_MILESTONE_PREFIX . 'campaign_created';
        if ($this->hasNotificationRecord($campaignId, $milestoneKey)) {
            return;
        }

        $subject = sprintf('Recibimos tu campaña "%s"', $campaign['title'] ?? 'Campaña');
        $body = $this->buildBody([
            sprintf('Hola %s,', $this->formatUserName($owner)),
            sprintf('Tu campaña "%s" quedó registrada y está en revisión por el equipo.', $campaign['title'] ?? 'la campaña'),
            'Te avisaremos cuando pase a estado publicado. Mientras tanto puedes preparar materiales para difundirla apenas esté en línea.',
            $this->buildSummaryLine($campaign)
        ]);

        $aiPrompt = $this->buildAiPrompt('campaign_created', $campaign, [
            'owner_name' => $this->formatUserName($owner),
            'requires_peer_review' => !empty($context['is_admin_owner']),
        ]);

        $meta = [
            'milestone' => $milestoneKey,
            'campaign_id' => (int)$campaignId,
            'event' => 'campaign_created',
            'requires_peer_review' => !empty($context['is_admin_owner'])
        ];

        $this->recordOwnerNotification(
            $campaignId,
            $milestoneKey,
            (int)($owner['id'] ?? 0),
            'Campaña registrada',
            'Tu campaña quedó registrada y está en revisión.',
            'info',
            $meta
        );

        $payload = $this->buildMailPayload($owner, $campaign, $subject, $body, [
            'meta' => $meta,
            'ai_prompt' => $aiPrompt
        ]);

        $this->dispatchMail($payload, $milestoneKey);
    }

    public function campaignApproved(int $campaignId, array $context = []): void
    {
        $campaign = $this->resolveCampaign($campaignId, $context['campaign'] ?? null);
        if (!$campaign) {
            return;
        }

        $owner = $this->resolveOwner($campaign, $context['owner_id'] ?? null);
        if (!$owner || empty($owner['email'])) {
            return;
        }

        $milestoneKey = self::OWNER_MILESTONE_PREFIX . 'campaign_published';
        if ($this->hasNotificationRecord($campaignId, $milestoneKey)) {
            return;
        }

        $campaignUrl = $this->buildCampaignUrl($campaign);

        $subject = sprintf('Tu campaña "%s" ya está publicada', $campaign['title'] ?? 'Campaña');
        $bodyLines = [
            sprintf('Hola %s,', $this->formatUserName($owner)),
            '¡Listo! Tu campaña fue aprobada y quedó visible en la plataforma.',
            'Revisa que toda la información esté correcta y comparte el enlace con tu comunidad.',
        ];
        if ($campaignUrl) {
            $bodyLines[] = sprintf('Enlace directo: %s', $campaignUrl);
        }
        $bodyLines[] = $this->buildSummaryLine($campaign);
        $body = $this->buildBody($bodyLines);

        $aiPrompt = $this->buildAiPrompt('campaign_approved', $campaign, [
            'published_url' => $campaignUrl,
        ]);

        $meta = [
            'milestone' => $milestoneKey,
            'campaign_id' => (int)$campaignId,
            'event' => 'campaign_approved',
            'slug' => $campaign['slug'] ?? null
        ];

        $this->recordOwnerNotification(
            $campaignId,
            $milestoneKey,
            (int)($owner['id'] ?? 0),
            'Campaña publicada',
            'Publicamos tu campaña. Empieza a difundirla y sigue su desempeño desde el panel.',
            'success',
            $meta
        );

        $payload = $this->buildMailPayload($owner, $campaign, $subject, $body, [
            'meta' => $meta,
            'ai_prompt' => $aiPrompt,
            'published_url' => $campaignUrl
        ]);

        $this->dispatchMail($payload, $milestoneKey);
    }

    public function campaignRejected(int $campaignId, string $reason, array $context = []): void
    {
        $campaign = $this->resolveCampaign($campaignId, $context['campaign'] ?? null);
        if (!$campaign) {
            return;
        }

        $owner = $this->resolveOwner($campaign, $context['owner_id'] ?? null);
        if (!$owner || empty($owner['email'])) {
            return;
        }

        $milestoneKey = self::OWNER_MILESTONE_PREFIX . 'campaign_rejected';
        if ($this->hasNotificationRecord($campaignId, $milestoneKey)) {
            return;
        }

        $subject = sprintf('Tu campaña "%s" necesita ajustes', $campaign['title'] ?? 'Campaña');
        $body = $this->buildBody([
            sprintf('Hola %s,', $this->formatUserName($owner)),
            'Revisamos tu campaña y por ahora no puede publicarse. Te dejamos el detalle para que hagas los ajustes necesarios.',
            sprintf('Observaciones del equipo: %s', $reason ?: 'Sin comentarios adicionales.'),
            'Una vez que hayas aplicado los cambios, vuelve a enviarla para revisión desde el panel.'
        ]);

        $aiPrompt = $this->buildAiPrompt('campaign_rejected', $campaign, [
            'rejection_reason' => $reason
        ]);

        $meta = [
            'milestone' => $milestoneKey,
            'campaign_id' => (int)$campaignId,
            'event' => 'campaign_rejected'
        ];

        $this->recordOwnerNotification(
            $campaignId,
            $milestoneKey,
            (int)($owner['id'] ?? 0),
            'Campaña con observaciones',
            'No pudimos publicar tu campaña. Revisa las observaciones y vuelve a enviarla.',
            'warning',
            array_merge($meta, ['reason' => $reason])
        );

        $payload = $this->buildMailPayload($owner, $campaign, $subject, $body, [
            'meta' => array_merge($meta, ['reason' => $reason]),
            'ai_prompt' => $aiPrompt
        ]);

        $this->dispatchMail($payload, $milestoneKey);
    }

    public function campaignGoalReached(array $campaign, array $stats): void
    {
        $campaignId = (int)($campaign['id'] ?? 0);
        if ($campaignId <= 0) {
            return;
        }

        $owner = $this->resolveOwner($campaign);
        if (!$owner || empty($owner['email'])) {
            return;
        }

        $milestoneKey = self::OWNER_MILESTONE_PREFIX . 'goal_reached';
        if ($this->hasNotificationRecord($campaignId, $milestoneKey)) {
            return;
        }

        $progress = (float)($stats['progress'] ?? 100.0);
        $raised = (float)($stats['raised_amount'] ?? 0.0);
        $goal = (float)($stats['goal_amount'] ?? ($campaign['goal_amount'] ?? 0.0));
        $currency = $campaign['currency'] ?? 'CLP';

        $subject = sprintf('¡Meta alcanzada! "%s" superó el objetivo', $campaign['title'] ?? 'Campaña');
        $body = $this->buildBody([
            sprintf('Hola %s,', $this->formatUserName($owner)),
            sprintf('La campaña llegó al %.1f%% de la meta y acumula %s.', $progress, $this->formatCurrency($raised, $currency)),
            'Agradece a tus donantes y comparte los próximos pasos. Puedes pausar nuevas donaciones o lanzar objetivos extendidos desde el panel.'
        ]);

        $aiPrompt = $this->buildAiPrompt('campaign_goal_reached', $campaign, [
            'progress' => $progress,
            'stats' => $stats
        ]);

        $meta = [
            'milestone' => $milestoneKey,
            'campaign_id' => $campaignId,
            'event' => 'campaign_goal_reached',
            'progress' => $progress,
            'raised_amount' => $raised,
            'goal_amount' => $goal
        ];

        $this->recordOwnerNotification(
            $campaignId,
            $milestoneKey,
            (int)($owner['id'] ?? 0),
            'La campaña alcanzó la meta',
            'Felicitaciones, alcanzaste el objetivo de recaudación. Comunica los próximos pasos a tu audiencia.',
            'success',
            $meta
        );

        $payload = $this->buildMailPayload($owner, $campaign, $subject, $body, [
            'meta' => $meta,
            'ai_prompt' => $aiPrompt,
            'progress' => $progress
        ]);

        $this->dispatchMail($payload, $milestoneKey);
    }

    public function campaignNearGoal(array $campaign, array $stats): void
    {
        $campaignId = (int)($campaign['id'] ?? 0);
        if ($campaignId <= 0) {
            return;
        }

        $progress = (float)($stats['progress'] ?? 0.0);
        if ($progress >= 90.0) {
            return;
        }

        $owner = $this->resolveOwner($campaign);
        if (!$owner || empty($owner['email'])) {
            return;
        }

        $milestoneKey = self::OWNER_MILESTONE_PREFIX . 'near_goal';
        if ($this->hasNotificationRecord($campaignId, $milestoneKey)) {
            return;
        }

        $goal = (float)($stats['goal_amount'] ?? ($campaign['goal_amount'] ?? 0.0));
        $raised = (float)($stats['raised_amount'] ?? 0.0);
        $remaining = max(0.0, $goal - $raised);

        $subject = sprintf('Estás muy cerca de la meta en "%s"', $campaign['title'] ?? 'la campaña');
        $body = $this->buildBody([
            sprintf('Hola %s,', $this->formatUserName($owner)),
            sprintf('Vas en %.1f%% de la meta y solo faltan %s para lograrlo.', $progress, $this->formatCurrency($remaining, $campaign['currency'] ?? 'CLP')),
            'Activa a tus seguidores: comparte avances concretos y propón incentivos finales para cerrar la recaudación.'
        ]);

        $aiPrompt = $this->buildAiPrompt('campaign_near_goal', $campaign, [
            'progress' => $progress,
            'remaining' => $remaining,
            'stats' => $stats
        ]);

        $meta = [
            'milestone' => $milestoneKey,
            'campaign_id' => $campaignId,
            'event' => 'campaign_near_goal',
            'progress' => $progress,
            'remaining_amount' => $remaining
        ];

        $this->recordOwnerNotification(
            $campaignId,
            $milestoneKey,
            (int)($owner['id'] ?? 0),
            'Último empujón para la meta',
            'Estás muy cerca de cerrar la campaña. Coordina una acción en redes para alcanzar el objetivo.',
            'info',
            $meta
        );

        $payload = $this->buildMailPayload($owner, $campaign, $subject, $body, [
            'meta' => $meta,
            'ai_prompt' => $aiPrompt,
            'progress' => $progress
        ]);

        $this->dispatchMail($payload, $milestoneKey);
    }

    public function handleFundingProgress(array $campaign, array $stats, float $progress): void
    {
        $campaignId = (int)($campaign['id'] ?? 0);
        if ($campaignId <= 0) {
            return;
        }

        $owner = $this->resolveOwner($campaign);
        if (!$owner || empty($owner['email'])) {
            return;
        }

        foreach (self::FUNDING_THRESHOLDS as $threshold) {
            if ($progress < $threshold) {
                continue;
            }

            $milestoneKey = self::OWNER_MILESTONE_PREFIX . 'progress_' . (int)$threshold;
            if ($this->hasNotificationRecord($campaignId, $milestoneKey)) {
                continue;
            }

            $raised = (float)($stats['raised_amount'] ?? 0.0);
            $goal = (float)($stats['goal_amount'] ?? ($campaign['goal_amount'] ?? 0.0));
            $currency = $campaign['currency'] ?? 'CLP';

            $subject = sprintf('Tu campaña alcanzó el %d%% de la meta', (int)$threshold);
            $body = $this->buildBody([
                sprintf('Hola %s,', $this->formatUserName($owner)),
                sprintf('La campaña ya superó el %d%% de la meta y acumula %s.', (int)$threshold, $this->formatCurrency($raised, $currency)),
                'Comparte esta noticia con tu comunidad y aprovecha el momentum para sumar más apoyo.'
            ]);

            $aiPrompt = $this->buildAiPrompt('campaign_progress', $campaign, [
                'progress' => $progress,
                'threshold' => $threshold,
                'stats' => $stats
            ]);

            $meta = [
                'milestone' => $milestoneKey,
                'campaign_id' => $campaignId,
                'event' => 'campaign_progress',
                'progress' => $progress,
                'threshold' => $threshold
            ];

            $this->recordOwnerNotification(
                $campaignId,
                $milestoneKey,
                (int)($owner['id'] ?? 0),
                sprintf('La campaña llegó al %d%%', (int)$threshold),
                'La recaudación avanza bien. Refuerza la difusión para mantener el ritmo.',
                'info',
                $meta
            );

            $payload = $this->buildMailPayload($owner, $campaign, $subject, $body, [
                'meta' => $meta,
                'ai_prompt' => $aiPrompt,
                'progress' => $progress
            ]);

            $this->dispatchMail($payload, $milestoneKey);
        }
    }

    private function resolveMailEnabled(): bool
    {
        $host = env('MAIL_HOST', '');
        $username = env('MAIL_USERNAME', '');
        $password = env('MAIL_PASSWORD', '');
        $from = env('MAIL_FROM_ADDRESS', '');

        return $host !== '' && $username !== '' && $password !== '' && $from !== '';
    }

    private function resolveCampaign(int $campaignId, ?array $fallback): ?array
    {
        if (!empty($fallback) && (int)($fallback['id'] ?? 0) === $campaignId) {
            return $fallback;
        }

        $campaign = $this->campaigns->findById($campaignId);
        if ($campaign) {
            return $campaign;
        }

        return $fallback;
    }

    private function resolveOwner(array $campaign, ?int $overrideId = null): ?array
    {
        $ownerId = $overrideId ?: (int)($campaign['owner_id'] ?? $campaign['user_id'] ?? 0);
        if ($ownerId <= 0) {
            return null;
        }

        return $this->users->findById($ownerId);
    }

    private function hasNotificationRecord(int $campaignId, string $milestone): bool
    {
        if (!$this->notificationsEnabled) {
            return false;
        }

        try {
            return $this->notifications->hasMilestoneNotification($campaignId, $milestone);
        } catch (Exception $exception) {
            Logger::warning('No se pudo verificar notificaciones previas', [
                'campaign_id' => $campaignId,
                'milestone' => $milestone,
                'error' => $exception->getMessage()
            ]);
            return false;
        }
    }

    private function recordOwnerNotification(int $campaignId, string $milestone, int $userId, string $title, string $message, string $type, array $meta): void
    {
        if (!$this->notificationsEnabled || $userId <= 0) {
            return;
        }

        try {
            $this->notifications->createSystem([
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'audience' => 'users',
                'user_ids' => [$userId],
                'meta' => array_merge($meta, [
                    'campaign_id' => $campaignId,
                    'milestone' => $milestone
                ])
            ]);
        } catch (Exception $exception) {
            Logger::warning('No se pudo registrar la notificación del propietario', [
                'campaign_id' => $campaignId,
                'milestone' => $milestone,
                'error' => $exception->getMessage()
            ]);
        }
    }

    private function buildMailPayload(array $recipient, array $campaign, string $subject, string $body, array $context): array
    {
        $payload = [
            'from' => [
                'email' => defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'noreply@lucaton.local',
                'name' => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Lucatón'
            ],
            'to' => [$this->formatRecipient($recipient)],
            'subject' => $subject,
            'body_text' => $body,
            'generated_at' => date('c'),
            'context' => [
                'campaign' => [
                    'id' => (int)($campaign['id'] ?? 0),
                    'title' => $campaign['title'] ?? null,
                    'slug' => $campaign['slug'] ?? null,
                    'status' => $campaign['status'] ?? null,
                    'goal_amount' => (float)($campaign['goal_amount'] ?? 0.0),
                    'currency' => $campaign['currency'] ?? 'CLP',
                    'progress' => $context['progress'] ?? null
                ],
                'ai_prompt' => $context['ai_prompt'] ?? null,
                'meta' => $context['meta'] ?? []
            ]
        ];

        $cc = $this->buildCcList([
            'owner_email' => $recipient['email'] ?? null,
            'extra_cc' => $context['cc'] ?? []
        ]);
        if (!empty($cc)) {
            $payload['cc'] = $cc;
        }

        if (!empty($context['published_url'])) {
            $payload['context']['campaign']['public_url'] = $context['published_url'];
        }

        return $payload;
    }

    private function dispatchMail(array $payload, string $eventKey): void
    {
        $mode = $this->mailEnabled ? 'smtp_ready' : 'preview';
        $payload['delivery_mode'] = $mode;
        $payload['event_key'] = $eventKey;

        $this->writePreview($payload, $eventKey);

        Logger::info('Se preparó correo de ciclo de campaña', [
            'event' => $eventKey,
            'mode' => $mode,
            'subject' => $payload['subject'],
            'to' => array_map(fn ($item) => $item['email'] ?? '', $payload['to']),
            'campaign_id' => $payload['context']['campaign']['id'] ?? null
        ]);
    }

    private function writePreview(array $payload, string $eventKey): void
    {
        try {
            if (!is_dir($this->previewDirectory)) {
                mkdir($this->previewDirectory, 0775, true);
            }

            $filename = sprintf(
                '%s/%s-%s.json',
                $this->previewDirectory,
                date('Ymd_His'),
                $eventKey
            );

            file_put_contents(
                $filename,
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        } catch (Throwable $exception) {
            Logger::warning('No se pudo escribir el preview de correo', [
                'event' => $eventKey,
                'error' => $exception->getMessage()
            ]);
        }
    }

    private function buildAiPrompt(string $eventKey, array $campaign, array $context = []): string
    {
        $title = $campaign['title'] ?? 'Campaña';
        $campaignId = (int)($campaign['id'] ?? 0);
        $goal = $this->formatCurrency((float)($campaign['goal_amount'] ?? 0.0), $campaign['currency'] ?? 'CLP');
        $progress = isset($context['progress']) ? (float)$context['progress'] : null;

        switch ($eventKey) {
            case 'campaign_created':
                return sprintf(
                    'Genera un plan breve de lanzamiento en redes para la campaña "%s" (ID %d, meta %s). Incluye ideas para anunciar que está en revisión, contenidos para captar aliados iniciales y sugerencias de próximos pasos antes de la publicación.',
                    $title,
                    $campaignId,
                    $goal
                );
            case 'campaign_approved':
                return sprintf(
                    'Diseña 3 publicaciones (texto, carrusel e historia) para redes sociales que celebren la publicación de "%s" (ID %d). Sugiere también un incentivo creativo para los primeros donantes y métricas clave a monitorear durante la primera semana.',
                    $title,
                    $campaignId
                );
            case 'campaign_rejected':
                return sprintf(
                    'Resume en lenguaje constructivo las observaciones para la campaña "%s" (ID %d) y entrega un checklist de mejoras. Propón mensajes para informar al equipo interno y planificar la nueva presentación.',
                    $title,
                    $campaignId
                );
            case 'campaign_goal_reached':
                return sprintf(
                    'Genera un mensaje de agradecimiento y un hilo de 3 pasos para comunicar que "%s" (ID %d) alcanzó la meta. Incluye ideas para comunicar impactos logrados y cómo mantener a la comunidad vinculada.',
                    $title,
                    $campaignId
                );
            case 'campaign_near_goal':
                return sprintf(
                    'Propón 3 acciones rápidas de comunicación para cerrar la brecha final de la campaña "%s" (ID %d, progreso %.1f%%). Incluye un ejemplo de copy urgente y una propuesta de live o transmisión en directo.',
                    $title,
                    $campaignId,
                    $progress ?? 0.0
                );
            case 'campaign_progress':
                $threshold = (float)($context['threshold'] ?? $progress ?? 0.0);
                return sprintf(
                    'Sugiere un mensaje de actualización y dos ideas de incentivos para cuando la campaña "%s" (ID %d) alcanza el %.0f%% de su meta. Incluye hashtags recomendados y un llamado a compartir en redes.',
                    $title,
                    $campaignId,
                    $threshold
                );
            default:
                return sprintf(
                    'Genera recomendaciones de comunicación y engagement para la campaña "%s" (ID %d).',
                    $title,
                    $campaignId
                );
        }
    }

    private function buildSummaryLine(array $campaign): string
    {
        $goal = $this->formatCurrency((float)($campaign['goal_amount'] ?? 0.0), $campaign['currency'] ?? 'CLP');
        $endDate = $campaign['end_date'] ?? null;
        if ($endDate) {
            return sprintf('Meta: %s · Cierre: %s', $goal, date('d/m/Y', strtotime($endDate)));
        }

        return sprintf('Meta: %s', $goal);
    }

    private function formatCurrency(float $amount, string $currency): string
    {
        $formatted = number_format($amount, 0, ',', '.');
        return sprintf('%s %s', strtoupper($currency), $formatted);
    }

    private function formatUserName(array $user): string
    {
        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        if (!empty($user['username'])) {
            return (string)$user['username'];
        }

        if (!empty($user['email'])) {
            return (string)$user['email'];
        }

        return 'usuario';
    }

    private function formatRecipient(array $user): array
    {
        return [
            'email' => $user['email'],
            'name' => $this->formatUserName($user)
        ];
    }

    private function buildBody(array $lines): string
    {
        return implode("\n\n", array_filter($lines, fn ($line) => $line !== ''));
    }

    private function buildCcList(array $context): array
    {
        $cc = [];
        $ownerEmail = $context['owner_email'] ?? null;

        if (defined('PROJECT_OWNER_EMAIL') && PROJECT_OWNER_EMAIL !== '' && PROJECT_OWNER_EMAIL !== $ownerEmail) {
            $cc[] = [
                'email' => PROJECT_OWNER_EMAIL,
                'name' => defined('PROJECT_OWNER_NAME') ? PROJECT_OWNER_NAME : 'Equipo Lucatón'
            ];
        }

        if (!empty($context['extra_cc']) && is_array($context['extra_cc'])) {
            foreach ($context['extra_cc'] as $entry) {
                if (empty($entry['email'])) {
                    continue;
                }
                $cc[] = [
                    'email' => $entry['email'],
                    'name' => $entry['name'] ?? $entry['email']
                ];
            }
        }

        return $cc;
    }

    private function buildCampaignUrl(array $campaign): ?string
    {
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
        if ($base === '') {
            return null;
        }

        if (!empty($campaign['slug'])) {
            return $base . '/campana/' . $campaign['slug'];
        }

        if (!empty($campaign['id'])) {
            return $base . '/campana/' . $campaign['id'];
        }

        return null;
    }
}
