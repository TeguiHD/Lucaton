<?php

class CampaignPresenter {
    /**
     * Build a normalized presentation payload for campaign records.
     */
    public static function present(array $row): array {
        $defaultImage = APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';

        $goal = isset($row['goal_amount']) ? (float)$row['goal_amount'] : 0.0;
        $raisedSource = $row['raised_amount'] ?? $row['current_amount'] ?? $row['raised'] ?? 0;
        $raised = (float)$raisedSource;
        $progress = $goal > 0 ? min(100, round(($raised / $goal) * 100, 2)) : 0.0;

        $now = time();
        $status = $row['status'] ?? '';
        if (is_string($status)) {
            $status = trim($status);
        }

        $endDate = $row['end_date'] ?? null;
        $daysLeft = null;
        $endTimestamp = null;
        $timeRemaining = null;
        $timeRemainingLabel = null;
        if (!empty($endDate)) {
            $parsedTimestamp = self::parseEndTimestamp($endDate);
            if ($parsedTimestamp !== null) {
                $endTimestamp = $parsedTimestamp;
                $secondsRemaining = $parsedTimestamp - $now;
                if ($secondsRemaining > 0) {
                    $timeRemaining = self::describeInterval($secondsRemaining);
                    $timeRemainingLabel = $timeRemaining['label'];
                    $daysLeft = $timeRemaining['days'];
                } else {
                    $daysLeft = 0;
                    $timeRemaining = [
                        'seconds' => 0,
                        'days' => 0,
                        'hours' => 0,
                        'minutes' => 0,
                        'unit' => 'expired',
                        'label' => 'Campaña finalizada'
                    ];
                    $timeRemainingLabel = $timeRemaining['label'];
                }
                if (($status === '' || $status === null) && $parsedTimestamp <= $now) {
                    $status = 'ended';
                }
            }
        } elseif (isset($row['days_left'])) {
            $daysLeft = (int)$row['days_left'];
        }

        if ($status === '' || $status === null) {
            $status = 'draft';
        }

        $ownerName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        if ($ownerName === '') {
            $ownerName = $row['owner_name']
                ?? $row['creator_name']
                ?? $row['username']
                ?? $row['creator']
                ?? null;
        }

        if ($ownerName === null || $ownerName === '') {
            $ownerName = 'Campañista';
        }

        $categoryName = $row['category_name'] ?? $row['category'] ?? 'Causa social';
        $categorySlug = $row['category_slug'] ?? null;

        $ownerId = $row['user_id'] ?? $row['owner_id'] ?? null;
        $ownerAvatar = SessionHelper::normalizeAvatarUrl($row['avatar_url'] ?? ($row['creator_avatar'] ?? null));
        $imageCandidates = [
            $row['cover_image_url'] ?? null,
            $row['featured_image_url'] ?? null,
            $row['featured_image'] ?? null,
            $row['banner_image_url'] ?? null,
            $row['banner_url'] ?? null,
            $row['main_image_url'] ?? null,
            $row['image_url'] ?? null,
            $row['image_path'] ?? null,
            $row['image'] ?? null,
            $row['hero_image'] ?? null,
        ];

        $image = null;
        foreach ($imageCandidates as $candidate) {
            $resolvedImage = self::normalizeImageUrl($candidate, $defaultImage);
            if ($resolvedImage !== $defaultImage) {
                $image = $resolvedImage;
                break;
            }
        }

        if ($image === null) {
            $manifestImage = self::resolveCoverFromManifest($row['id'] ?? null);
            if ($manifestImage !== null) {
                $image = $manifestImage;
            }
        }

        if ($image === null) {
            $image = $defaultImage;
        }

        $donorCount = (int)($row['donor_count'] ?? $row['supporters_count'] ?? 0);
        $shareCount = (int)($row['share_count'] ?? 0);
        $viewCount = (int)($row['view_count'] ?? 0);
        $averageDonation = (float)($row['average_donation'] ?? 0);

        $summary = $row['summary'] ?? $row['short_description'] ?? ($row['description'] ?? '');
        $story = $row['story'] ?? $row['full_story'] ?? ($row['description'] ?? $summary);

        $goalReached = $goal > 0 && $raised >= $goal;
        $timeOver = $endTimestamp !== null && $endTimestamp <= $now;
        $completionOutcome = null;
        if ($goalReached) {
            $completionOutcome = 'goal_reached';
        } elseif ($timeOver) {
            $completionOutcome = 'time_over';
        }

        $presented = [
            'id' => isset($row['id']) ? (int)$row['id'] : null,
            'slug' => $row['slug'] ?? null,
            'title' => $row['title'] ?? '',
            'summary' => $summary,
            'story' => $story,
            'goal_amount' => $goal,
            'raised_amount' => $raised,
            'progress' => $progress,
            'currency' => $row['currency'] ?? 'CLP',
            'status' => $status,
            'start_date' => $row['start_date'] ?? null,
            'end_date' => $endDate,
            'days_left' => $daysLeft,
            'end_timestamp' => $endTimestamp,
            'time_remaining' => $timeRemaining,
            'time_remaining_label' => $timeRemainingLabel,
            'time_remaining_unit' => $timeRemaining['unit'] ?? null,
            'hours_left' => $timeRemaining['hours'] ?? null,
            'minutes_left' => $timeRemaining['minutes'] ?? null,
            'cover_image_url' => $image,
            'image_url' => $image,
            'featured_image_url' => $image,
            'video_url' => $row['video_url'] ?? null,
            'owner_id' => $ownerId !== null ? (int)$ownerId : null,
            'owner_name' => $ownerName,
            'owner_avatar' => $ownerAvatar,
            'category_id' => isset($row['category_id']) ? (int)$row['category_id'] : null,
            'category_name' => $categoryName,
            'category_slug' => $categorySlug,
            'beneficiary_type' => $row['beneficiary_type'] ?? null,
            'beneficiary_name' => $row['beneficiary_name'] ?? null,
            'beneficiary_contact' => $row['beneficiary_contact'] ?? null,
            'location_label' => $row['location_label'] ?? $row['location'] ?? null,
            'ai_assisted' => !empty($row['ai_assisted']),
            'featured' => !empty($row['featured']),
            'donor_count' => $donorCount,
            'share_count' => $shareCount,
            'view_count' => $viewCount,
            'average_donation' => $averageDonation,
            'last_donation_at' => $row['last_donation_at'] ?? null,
            'funded_at' => $row['funded_at'] ?? null,
            'funding_notified_at' => $row['funding_notified_at'] ?? null,
            'funding_celebrated_at' => $row['funding_celebrated_at'] ?? null,
            'visibility' => $row['visibility'] ?? null,
            'goal_reached' => $goalReached,
            'time_over' => $timeOver,
            'completion_outcome' => $completionOutcome,
        ];

        // Backwards-compatibility aliases for legacy templates still in transition.
        $presented['current_amount'] = $presented['raised_amount'];
        $presented['description'] = $presented['summary'] !== '' ? $presented['summary'] : $presented['story'];
        $presented['category'] = $presented['category_name'];
        $presented['creator_name'] = $presented['owner_name'];
        $presented['creator'] = $presented['owner_name'];
        $presented['supporters_count'] = $presented['donor_count'];

        return $presented;
    }

    private static function normalizeImageUrl(?string $value, string $fallback): string {
        $resolved = CampaignMediaUploadService::normalizePublicUrl($value);
        return $resolved ?? $fallback;
    }

    private static function resolveCoverFromManifest($campaignId): ?string
    {
        if ($campaignId === null) {
            return null;
        }

        static $mediaService = null;
        if ($mediaService === null) {
            $mediaService = new CampaignMediaUploadService();
        }

        try {
            $manifest = $mediaService->readManifest((int)$campaignId);
        } catch (Throwable $exception) {
            return null;
        }

        if (!is_array($manifest)) {
            return null;
        }

        $coverPath = $manifest['cover_image']
            ?? $manifest['cover']
            ?? null;

        if (!$coverPath) {
            return null;
        }

        return CampaignMediaUploadService::normalizePublicUrl($coverPath);
    }

    public static function statusMeta(string $status): array {
        $map = [
            'draft' => ['label' => 'Borrador', 'badge_class' => 'bg-gray-100 text-gray-700'],
            'under_review' => ['label' => 'En revisión', 'badge_class' => 'bg-amber-100 text-amber-700'],
            'published' => ['label' => 'Publicada', 'badge_class' => 'bg-emerald-100 text-emerald-700'],
            'active' => ['label' => 'Activa', 'badge_class' => 'bg-emerald-100 text-emerald-700'],
            'paused' => ['label' => 'Pausada', 'badge_class' => 'bg-orange-100 text-orange-700'],
            'completed' => ['label' => 'Finalizada', 'badge_class' => 'bg-blue-100 text-blue-700'],
            'cancelled' => ['label' => 'Cancelada', 'badge_class' => 'bg-red-100 text-red-700'],
            'archived' => ['label' => 'Archivada', 'badge_class' => 'bg-slate-100 text-slate-700'],
            'funded' => ['label' => 'Financiada', 'badge_class' => 'bg-blue-100 text-blue-700'],
            'ended' => ['label' => 'Finalizada', 'badge_class' => 'bg-gray-100 text-gray-700'],
            'default' => ['label' => 'Sin estado', 'badge_class' => 'bg-gray-100 text-gray-700'],
        ];

        return $map[$status] ?? $map['default'];
    }

    public static function statusLabel(string $status): string {
        return self::statusMeta($status)['label'];
    }

    private static function parseEndTimestamp($value): ?int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        $stringValue = trim((string)$value);
        if ($stringValue === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $stringValue) === 1) {
            $dateOnly = DateTime::createFromFormat('Y-m-d', $stringValue);
            if ($dateOnly instanceof DateTime) {
                $dateOnly->setTime(23, 59, 59);
                return $dateOnly->getTimestamp();
            }
        }

        $timestamp = strtotime($stringValue);
        if ($timestamp !== false) {
            return $timestamp;
        }

        $dateWithTime = DateTime::createFromFormat('Y-m-d H:i', $stringValue);
        if ($dateWithTime instanceof DateTime) {
            return $dateWithTime->getTimestamp();
        }

        $dateOnly = DateTime::createFromFormat('Y-m-d', $stringValue);
        if ($dateOnly instanceof DateTime) {
            $dateOnly->setTime(23, 59, 59);
            return $dateOnly->getTimestamp();
        }

        return null;
    }

    private static function describeInterval(int $seconds): array
    {
        $seconds = max(0, $seconds);
        $days = intdiv($seconds, 86400);
        $hoursTotal = (int)ceil($seconds / 3600);
        $minutesTotal = (int)ceil($seconds / 60);

        if ($days >= 2) {
            $label = sprintf('%d días restantes', $days);
            $unit = 'days';
        } elseif ($days === 1) {
            $label = '1 día restante';
            $unit = 'days';
        } elseif ($hoursTotal >= 2) {
            $label = sprintf('%d horas restantes', $hoursTotal);
            $unit = 'hours';
        } elseif ($hoursTotal === 1) {
            $label = '1 hora restante';
            $unit = 'hours';
        } elseif ($minutesTotal >= 2) {
            $label = sprintf('%d minutos restantes', $minutesTotal);
            $unit = 'minutes';
        } elseif ($minutesTotal === 1) {
            $label = '1 minuto restante';
            $unit = 'minutes';
        } else {
            $label = 'Menos de 1 minuto restante';
            $unit = 'minutes';
        }

        return [
            'seconds' => $seconds,
            'days' => $days,
            'hours' => $hoursTotal,
            'minutes' => $minutesTotal,
            'unit' => $unit,
            'label' => $label,
        ];
    }
}
