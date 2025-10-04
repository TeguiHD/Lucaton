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

        $events = [];
        $daysChecked = 0;
        $maxDays = 14;

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

                $parsed = $this->parseAuditLine($line, $userId);
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

    private function parseAuditLine(string $line, int $userId): ?array {
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
        $matchesSubject = $targetId === $userId;
        $matchesActor = $actorId !== null && $actorId === $userId;

        if (!$matchesSubject && !$matchesActor) {
            return null;
        }

        $timestamp = $this->extractTimestamp($line);
        $action = $context['action'] ?? 'evento';

        return [
            'timestamp' => $timestamp,
            'action' => $action,
            'details' => $context['details'] ?? [],
            'ip' => $context['ip'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'scope' => $matchesActor ? 'actor' : 'subject',
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
