<?php

class NewsletterAdminController
{
    private NewsletterSubscription $subscriptions;
    private NewsletterCampaign $campaigns;
    private NewsletterMailer $mailer;

    public function __construct()
    {
        $this->subscriptions = new NewsletterSubscription();
        $this->campaigns = new NewsletterCampaign();
        $this->mailer = new NewsletterMailer();
    }

    public function index()
    {
        if (!$this->isAdmin()) {
            Router::redirect('/');
        }

        $page_title = 'Boletines y Newsletter';
        $current_page = 'admin-newsletter';
        $templates = $this->templateCatalog();
        $activeSubscribers = $this->subscriptions->countActive();
        $totalSubscribers = $this->subscriptions->countTotal();
        $recentCampaigns = $this->campaigns->recentCampaigns(10);
        $old = $_SESSION['old_newsletter_form'] ?? [];
        unset($_SESSION['old_newsletter_form']);

        include VIEWS_PATH . '/admin/newsletter.php';
    }

    public function send()
    {
        if (!$this->isAdmin()) {
            Router::redirect('/');
        }

        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $templateKey = $_POST['template_key'] ?? 'general_update';
        $ctaLabel = trim($_POST['cta_label'] ?? '');
        $ctaUrl = trim($_POST['cta_url'] ?? '');

        $templates = $this->templateCatalog();
        if (!isset($templates[$templateKey])) {
            $templateKey = 'general_update';
        }

        $errors = [];
        if ($subject === '') {
            $errors[] = 'El asunto es obligatorio.';
        }
        if ($message === '') {
            $errors[] = 'Agrega un mensaje para tus suscriptores.';
        }
        if ($ctaUrl !== '' && !filter_var($ctaUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'El enlace del botón debe ser una URL válida.';
        }

        $formBackup = [
            'subject' => $subject,
            'message' => $message,
            'template_key' => $templateKey,
            'cta_label' => $ctaLabel,
            'cta_url' => $ctaUrl
        ];

        if (!empty($errors)) {
            $_SESSION['old_newsletter_form'] = $formBackup;
            SessionHelper::setFlash('error', implode(' ', $errors));
            Router::redirect('/admin/newsletter');
        }

        $totalSubscribers = $this->subscriptions->countActive();
        if ($totalSubscribers === 0) {
            $_SESSION['old_newsletter_form'] = $formBackup;
            SessionHelper::setFlash('error', 'Aún no tienes personas suscritas al newsletter.');
            Router::redirect('/admin/newsletter');
        }

        try {
            $campaignId = $this->campaigns->create([
                'subject' => $subject,
                'template_key' => $templateKey,
                'message' => $message,
                'cta_label' => $ctaLabel,
                'cta_url' => $ctaUrl,
                'created_by' => SessionHelper::getUserId(),
                'recipient_count' => 0
            ]);

            $batchSize = 200;
            $offset = 0;
            $processed = 0;
            $primaryPreview = null;

            while (true) {
                $batch = $this->subscriptions->getActiveSubscribers($batchSize, $offset);
                if (empty($batch)) {
                    break;
                }

                $recipientRows = [];
                foreach ($batch as $subscriber) {
                    $unsubscribeUrl = Router::url('newsletter/desuscribir/' . $subscriber['unsubscribe_token']);
                    $sendResult = $this->mailer->send([
                        'subject' => $subject,
                        'template_key' => $templateKey,
                        'message' => $message,
                        'cta_label' => $ctaLabel,
                        'cta_url' => $ctaUrl
                    ], $subscriber, [
                        'unsubscribe_url' => $unsubscribeUrl
                    ]);

                    if (!$primaryPreview && isset($sendResult['preview_path'])) {
                        $primaryPreview = $sendResult['preview_path'];
                    }

                    $recipientRows[] = [
                        'campaign_id' => $campaignId,
                        'subscription_id' => $subscriber['id'],
                        'email' => $subscriber['email'],
                        'status' => 'queued',
                        'preview_path' => $sendResult['preview_path'] ?? null
                    ];

                    $this->subscriptions->touchSent((int)$subscriber['id']);
                    $processed++;
                }

                $this->campaigns->attachRecipients($campaignId, $recipientRows);
                $offset += $batchSize;
            }

            if ($primaryPreview) {
                $this->campaigns->updatePreviewPath($campaignId, $primaryPreview);
            }

            SessionHelper::setFlash('success', sprintf('Campaña enviada a %d suscriptores.', $processed));
        } catch (Exception $exception) {
            $_SESSION['old_newsletter_form'] = $formBackup;
            SessionHelper::setFlash('error', 'No pudimos generar el envío: ' . $exception->getMessage());
        }

        Router::redirect('/admin/newsletter');
    }

    private function isAdmin(): bool
    {
        return SessionHelper::isAuthenticated() && SessionHelper::isAdmin();
    }

    private function templateCatalog(): array
    {
        return [
            'general_update' => [
                'label' => 'Actualización general',
                'description' => 'Ideal para compartir múltiples novedades y recordatorios breves.'
            ],
            'platform_update' => [
                'label' => 'Mejoras de plataforma',
                'description' => 'Destaca nuevas funcionalidades o cambios importantes en Lucatón.'
            ],
            'impact_story' => [
                'label' => 'Historia de impacto',
                'description' => 'Comparte logros de campañas y agradece a la comunidad.'
            ],
        ];
    }
}
