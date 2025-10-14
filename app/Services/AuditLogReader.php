<?php

class AuditLogReader {
    private string $logPath;

    public function __construct() {
        $basePath = defined('ROOT_PATH') ? ROOT_PATH : __DIR__ . '/../../';
        $this->logPath = rtrim($basePath . '/' . env('LOG_PATH', 'storage/logs'), '/');
    }

    public function getRecentEventsForUser(int $userId, int $limit = 10): array {
        if ($userId <= 0 || $limit <= 0) {
            return [];
        }

        return $this->getRecentEvents($limit, [
            'user_id' => $userId,
            'max_days' => 14,
        ]);
    }

    public function getRecentEvents(int $limit = 50, array $filters = []): array {
        $limit = max(1, $limit);
        $maxDays = isset($filters['max_days']) ? (int)$filters['max_days'] : 21;
        if ($maxDays <= 0) {
            $maxDays = 21;
        }
        unset($filters['max_days']);

        $events = [];
        $daysChecked = 0;

        while (count($events) < $limit && $daysChecked < $maxDays) {
            $date = date('Y-m-d', strtotime("-{$daysChecked} days"));
            $file = $this->logPath . "/info-{$date}.log";
            $daysChecked++;

            if (!is_readable($file)) {
                continue;
            }

            $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                continue;
            }

            for ($i = count($lines) - 1; $i >= 0 && count($events) < $limit; $i--) {
                $line = $lines[$i];
                if (!str_contains($line, '"type":"audit"')) {
                    continue;
                }

                $parsed = $this->parseAuditLine($line, $filters);
                if ($parsed === null) {
                    continue;
                }

                $events[] = $parsed;
            }
        }

        usort($events, static function (array $a, array $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return array_slice($events, 0, $limit);
    }

    private function parseAuditLine(string $line, array $filters = []): ?array {
        $pos = strpos($line, '{');
        if ($pos === false) {
            return null;
        }

        $contextJson = substr($line, $pos);
        $context = json_decode($contextJson, true);
        if (!is_array($context) || ($context['type'] ?? '') !== 'audit') {
            return null;
        }

        $targetId = (int)($context['user_id'] ?? 0);
        $actorId = isset($context['details']['actor_id']) ? (int)$context['details']['actor_id'] : null;
        $userIdFilter = isset($filters['user_id']) ? (int)$filters['user_id'] : null;
        $scopeFilter = isset($filters['scope']) ? strtolower((string)$filters['scope']) : null;
        $matchesSubject = $userIdFilter !== null ? ($targetId === $userIdFilter) : false;
        $matchesActor = $userIdFilter !== null && $actorId !== null ? ($actorId === $userIdFilter) : false;

        if ($userIdFilter !== null) {
            if ($scopeFilter === 'actor' && !$matchesActor) {
                return null;
            }
            if ($scopeFilter === 'subject' && !$matchesSubject) {
                return null;
            }
            if ($scopeFilter === null && !$matchesSubject && !$matchesActor) {
                return null;
            }
        }

        if (isset($filters['actor_id']) && (int)$filters['actor_id'] !== ($actorId ?? 0)) {
            return null;
        }

        if (isset($filters['target_id']) && (int)$filters['target_id'] !== $targetId) {
            return null;
        }

        if (isset($filters['action'])) {
            $actionFilter = strtolower(trim((string)$filters['action']));
            if ($actionFilter !== '') {
                $actionValue = strtolower((string)($context['action'] ?? ''));
                if ($actionValue === '' || !str_contains($actionValue, $actionFilter)) {
                    return null;
                }
            }
        }

        if (isset($filters['ip'])) {
            $ipFilter = strtolower(trim((string)$filters['ip']));
            if ($ipFilter !== '') {
                $eventIp = strtolower((string)($context['ip'] ?? ''));
                if ($eventIp === '' || !str_contains($eventIp, $ipFilter)) {
                    return null;
                }
            }
        }

        $timestamp = $this->extractTimestamp($line);
        $action = $context['action'] ?? 'evento';

        return [
            'timestamp' => $timestamp,
            'action' => $action,
            'details' => $context['details'] ?? [],
            'ip' => $context['ip'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'scope' => $userIdFilter !== null ? ($matchesActor ? 'actor' : 'subject') : 'event',
            'actor_id' => $actorId,
            'target_id' => $targetId,
        ];
    }

    private function extractTimestamp(string $line): int {
        if (preg_match('/^\[(.*?)\]/', $line, $matches)) {
            $time = strtotime($matches[1]);
            if ($time !== false) {
                return $time;
            }
        }
        return time();
    }
}
