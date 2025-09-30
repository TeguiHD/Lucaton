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

        $endDate = $row['end_date'] ?? null;
        $daysLeft = null;
        if (!empty($endDate)) {
            $timestamp = strtotime($endDate);
            if ($timestamp !== false) {
                $daysLeft = max(0, (int)ceil(($timestamp - time()) / 86400));
            }
        } elseif (isset($row['days_left'])) {
            $daysLeft = (int)$row['days_left'];
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

        $status = $row['status'] ?? 'draft';
        $categoryName = $row['category_name'] ?? $row['category'] ?? 'Causa social';
        $categorySlug = $row['category_slug'] ?? null;

        $ownerId = $row['user_id'] ?? $row['owner_id'] ?? null;
        $image = $row['cover_image_url'] ?? $row['image_url'] ?? $row['image'] ?? null;
        if (empty($image)) {
            $image = $defaultImage;
        }

        $donorCount = (int)($row['donor_count'] ?? $row['supporters_count'] ?? 0);
        $shareCount = (int)($row['share_count'] ?? 0);
        $viewCount = (int)($row['view_count'] ?? 0);
        $averageDonation = (float)($row['average_donation'] ?? 0);

        $summary = $row['summary'] ?? $row['short_description'] ?? ($row['description'] ?? '');
        $story = $row['story'] ?? $row['full_story'] ?? ($row['description'] ?? $summary);

        $presented = [
            'id' => isset($row['id']) ? (int)$row['id'] : null,
            'slug' => $row['slug'] ?? null,
            'title' => $row['title'] ?? '',
            'summary' => $summary,
            'story' => $story,
            'goal_amount' => $goal,
            'raised_amount' => $raised,
            'progress' => $progress,
            'status' => $status,
            'start_date' => $row['start_date'] ?? null,
            'end_date' => $endDate,
            'days_left' => $daysLeft,
            'cover_image_url' => $image,
            'image_url' => $image,
            'video_url' => $row['video_url'] ?? null,
            'owner_id' => $ownerId !== null ? (int)$ownerId : null,
            'owner_name' => $ownerName,
            'owner_avatar' => $row['avatar_url'] ?? $row['creator_avatar'] ?? null,
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

    public static function statusMeta(string $status): array {
        $map = [
            'draft' => ['label' => 'Borrador', 'badge_class' => 'bg-gray-100 text-gray-700'],
            'under_review' => ['label' => 'En revisión', 'badge_class' => 'bg-amber-100 text-amber-700'],
            'published' => ['label' => 'Publicada', 'badge_class' => 'bg-emerald-100 text-emerald-700'],
            'active' => ['label' => 'Activa', 'badge_class' => 'bg-emerald-100 text-emerald-700'],
            'paused' => ['label' => 'Pausada', 'badge_class' => 'bg-orange-100 text-orange-700'],
            'completed' => ['label' => 'Completada', 'badge_class' => 'bg-blue-100 text-blue-700'],
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
}
