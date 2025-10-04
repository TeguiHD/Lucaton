<?php

class CampaignMilestoneNotifier {
    private Notification $notifications;
    private Donation $donations;
    private Campaign $campaigns;
    private CampaignLifecycleMailer $mailer;

    private const NEAR_GOAL_THRESHOLD = 85.0;

    public function __construct() {
        $this->notifications = new Notification();
        $this->donations = new Donation();
        $this->campaigns = new Campaign();
        $this->mailer = new CampaignLifecycleMailer();
    }

    public function handleDonationEvent(array $donation): void {
        $campaignId = (int)($donation['campaign_id'] ?? 0);
        if ($campaignId <= 0) {
            return;
        }

        $campaign = $this->campaigns->findById($campaignId);
        if (!$campaign) {
            return;
        }

        $stats = $this->campaigns->getStats($campaignId);
        if (empty($stats)) {
            return;
        }

        $recipients = $this->donations->getSupporterUserIdsForCampaign($campaignId);
        if (empty($recipients)) {
            return;
        }

        $progress = (float)($stats['progress'] ?? 0);

        try {
            $this->mailer->handleFundingProgress($campaign, $stats, $progress);
        } catch (Throwable $exception) {
            Logger::warning('No se pudo preparar correo de progreso de campaña', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);
        }

        if ($progress >= 100.0 && !$this->notifications->hasMilestoneNotification($campaignId, 'goal_reached')) {
            $this->sendMilestoneNotification([
                'campaign_id' => $campaignId,
                'campaign_title' => $campaign['title'] ?? 'Campaña',
                'goal_amount' => (float)($stats['goal_amount'] ?? 0),
                'raised_amount' => (float)($stats['raised_amount'] ?? 0),
                'progress' => $progress,
                'recipients' => $recipients
            ], 'goal_reached', 'success',
                sprintf('¡%s alcanzó su meta!', $campaign['title'] ?? 'La campaña'),
                sprintf(
                    'Gracias a tu apoyo, la campaña %s superó su objetivo y lleva recaudados %s.',
                    $campaign['title'] ?? 'de la que formas parte',
                    $this->formatCurrency((float)($stats['raised_amount'] ?? 0), $campaign['currency'] ?? 'CLP')
                )
            );
            try {
                $this->mailer->campaignGoalReached($campaign, $stats);
            } catch (Throwable $exception) {
                Logger::warning('No se pudo preparar correo de meta alcanzada', [
                    'campaign_id' => $campaignId,
                    'error' => $exception->getMessage()
                ]);
            }
            return;
        }

        if ($progress >= self::NEAR_GOAL_THRESHOLD
            && !$this->notifications->hasMilestoneNotification($campaignId, 'near_goal')
            && !$this->notifications->hasMilestoneNotification($campaignId, 'goal_reached')) {
            $remaining = max(0.0, (float)($stats['goal_amount'] ?? 0) - (float)($stats['raised_amount'] ?? 0));
            $this->sendMilestoneNotification([
                'campaign_id' => $campaignId,
                'campaign_title' => $campaign['title'] ?? 'Campaña',
                'goal_amount' => (float)($stats['goal_amount'] ?? 0),
                'raised_amount' => (float)($stats['raised_amount'] ?? 0),
                'progress' => $progress,
                'recipients' => $recipients
            ], 'near_goal', 'warning',
                sprintf('La campaña %s está a punto de lograrlo', $campaign['title'] ?? 'que apoyaste'),
                sprintf(
                    'Solo faltan %s para que %s logre su objetivo. Comparte la campaña e invita a más personas a donar.',
                    $this->formatCurrency($remaining, $campaign['currency'] ?? 'CLP'),
                    $campaign['title'] ?? 'la campaña'
                )
            );
            try {
                $this->mailer->campaignNearGoal($campaign, $stats);
            } catch (Throwable $exception) {
                Logger::warning('No se pudo preparar correo de campaña cerca de la meta', [
                    'campaign_id' => $campaignId,
                    'error' => $exception->getMessage()
                ]);
            }
        }
    }

    public function handleCampaignClosure(array $campaign, string $status): void {
        $campaignId = (int)($campaign['id'] ?? 0);
        if ($campaignId <= 0) {
            return;
        }

        if ($this->notifications->hasMilestoneNotification($campaignId, 'goal_not_met')) {
            return;
        }

        $stats = $this->campaigns->getStats($campaignId);
        if (empty($stats) || (float)($stats['progress'] ?? 0) >= 100.0) {
            return;
        }

        $recipients = $this->donations->getSupporterUserIdsForCampaign($campaignId);
        if (empty($recipients)) {
            return;
        }

        $this->sendMilestoneNotification([
            'campaign_id' => $campaignId,
            'campaign_title' => $campaign['title'] ?? 'Campaña',
            'goal_amount' => (float)($stats['goal_amount'] ?? 0),
            'raised_amount' => (float)($stats['raised_amount'] ?? 0),
            'progress' => (float)($stats['progress'] ?? 0),
            'recipients' => $recipients
        ], 'goal_not_met', 'info',
            sprintf('La campaña %s finalizó sin alcanzar la meta', $campaign['title'] ?? ''),
            sprintf(
                'La campaña recaudó %s de los %s propuestos. Gracias por tu apoyo; te mantendremos informado sobre futuros pasos.',
                $this->formatCurrency((float)($stats['raised_amount'] ?? 0), $campaign['currency'] ?? 'CLP'),
                $this->formatCurrency((float)($stats['goal_amount'] ?? 0), $campaign['currency'] ?? 'CLP')
            ),
            ['status' => $status]
        );
    }

    private function sendMilestoneNotification(array $context, string $milestone, string $type, string $title, string $message, array $extraMeta = []): void {
        if (empty($context['recipients'])) {
            return;
        }

        try {
            $meta = array_merge([
                'milestone' => $milestone,
                'campaign_id' => $context['campaign_id'],
                'campaign_title' => $context['campaign_title'],
                'progress' => $context['progress'],
                'goal_amount' => $context['goal_amount'],
                'raised_amount' => $context['raised_amount']
            ], $extraMeta);

            $this->notifications->createSystem([
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'audience' => 'users',
                'user_ids' => $context['recipients'],
                'meta' => $meta
            ]);
        } catch (Exception $e) {
            Logger::error('Failed to send campaign milestone notification', [
                'campaign_id' => $context['campaign_id'],
                'milestone' => $milestone,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function formatCurrency(float $amount, string $currency): string {
        $formatted = number_format($amount, 0, ',', '.');
        return sprintf('%s %s', strtoupper($currency), $formatted);
    }
}
