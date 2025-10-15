<?php

class SupportTicketStore {
    private const STORAGE_DIR = 'storage/private';
    private const STORAGE_FILENAME = 'support-tickets.jsonl';
    private const MAX_TICKETS = 500;

    public function store(array $ticket): void {
        $filePath = $this->getFilePath();
        $directory = dirname($filePath);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el directorio de soporte');
        }

        $payload = json_encode($ticket, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new RuntimeException('No se pudo serializar el ticket de soporte');
        }

        $result = file_put_contents($filePath, $payload . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($result === false) {
            throw new RuntimeException('No se pudo guardar el ticket de soporte');
        }

        $this->prune();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(int $limit = 200): array {
        $filePath = $this->getFilePath();
        if (!file_exists($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $tickets = [];
        foreach ($lines as $line) {
            try {
                $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                Logger::warning('Registro de ticket de soporte inválido omitido', ['error' => $exception->getMessage()]);
                continue;
            }

            if (is_array($decoded)) {
                $tickets[] = $decoded;
            }
        }

        usort($tickets, static function (array $a, array $b): int {
            $createdAtA = $a['created_at'] ?? '';
            $createdAtB = $b['created_at'] ?? '';
            return strcmp($createdAtB, $createdAtA);
        });

        if ($limit > 0) {
            $tickets = array_slice($tickets, 0, $limit);
        }

        return $tickets;
    }

    private function getFilePath(): string {
        $root = defined('ROOT_PATH') ? ROOT_PATH : __DIR__ . '/../../';
        return rtrim($root, '/') . '/' . self::STORAGE_DIR . '/' . self::STORAGE_FILENAME;
    }

    private function prune(): void {
        $filePath = $this->getFilePath();
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        $excess = count($lines) - self::MAX_TICKETS;
        if ($excess <= 0) {
            return;
        }

        $lines = array_slice($lines, $excess);
        file_put_contents($filePath, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
    }
}
