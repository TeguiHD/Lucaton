<?php

class FeedbackController {
    private const STORAGE_DIRECTORY = 'storage/feedback';
    private const STORAGE_FILENAME = 'creator-feedback.jsonl';

    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!SessionHelper::isAuthenticated()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para enviar feedback'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $userId = (int)SessionHelper::getUserId();
        $userRole = SessionHelper::getUserRole() ?? 'user';

        if (!$this->userCanSendCreatorFeedback($userId, $userRole)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Solo los creadores pueden enviar feedback'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = trim((string)($_POST['comment'] ?? ''));

        $errors = $this->validatePayload($rating, $comment);
        if (!empty($errors)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = [
            'user_id' => $userId,
            'rating' => $rating,
            'comment' => $comment,
            'user_role' => $userRole,
            'user_name' => SessionHelper::getUser()['name'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
        ];

        try {
            $this->persistFeedback($payload);
        } catch (Exception $exception) {
            Logger::error('Failed to persist creator feedback', ['error' => $exception->getMessage()]);
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'No pudimos guardar tu feedback. Inténtalo más tarde.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        Logger::audit('creator_feedback_submitted', $userId, ['rating' => $rating]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    private function userCanSendCreatorFeedback(int $userId, string $role): bool {
        if (in_array($role, ['admin', 'superadmin'], true)) {
            return true;
        }

        try {
            $campaignModel = new Campaign();
            $campaigns = $campaignModel->findByUserId($userId, 1, 0);
            return !empty($campaigns);
        } catch (Exception $exception) {
            Logger::warning('Unable to verify user campaigns for feedback', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    private function validatePayload(int $rating, string $comment): array {
        $errors = [];

        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = 'Selecciona una calificación válida (1 a 5).';
        }

        $length = mb_strlen($comment);
        if ($length < 280 || $length > 600) {
            $errors['comment'] = 'El comentario debe tener entre 280 y 600 caracteres.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $payload
     * @throws Exception
     */
    private function persistFeedback(array $payload): void {
        $directory = ROOT_PATH . '/' . self::STORAGE_DIRECTORY;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create feedback storage directory');
        }

        $filename = $directory . '/' . self::STORAGE_FILENAME;
        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        if ($line === false) {
            throw new RuntimeException('Failed to encode feedback payload');
        }

        $result = file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
        if ($result === false) {
            throw new RuntimeException('Failed to write feedback payload');
        }
    }
}
