<?php

class SupportController {
    private SupportTicketStore $store;
    private const ALLOWED_TYPES = ['tecnico', 'pagos', 'contenido', 'seguridad', 'otro'];
    private const ALLOWED_SEVERITIES = ['alta', 'media', 'baja'];

    public function __construct() {
        $this->store = new SupportTicketStore();
    }

    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, ['error' => 'Método no permitido.']);
            return;
        }

        $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
        if (!SessionHelper::verifyCSRFToken($csrfToken)) {
            $this->respond(419, ['error' => 'Tu sesión caducó. Vuelve a cargar la página e inténtalo nuevamente.']);
            return;
        }

        $payload = $this->extractPayload();
        $errors = $this->validate($payload);

        if (!empty($errors)) {
            $this->respond(422, ['success' => false, 'errors' => $errors]);
            return;
        }

        try {
            $ticket = $this->buildTicket($payload);
            $this->store->store($ticket);
            Logger::audit('support_ticket_created', SessionHelper::getUserId(), [
                'ticket_id' => $ticket['id'],
                'type' => $ticket['type'],
                'severity' => $ticket['severity'],
            ]);
        } catch (Throwable $exception) {
            Logger::error('Error al guardar ticket de soporte', ['error' => $exception->getMessage()]);
            $this->respond(500, ['error' => 'No pudimos registrar tu solicitud. Inténtalo más tarde.']);
            return;
        }

        $successMessage = 'Gracias por tu reporte. Nuestro equipo lo revisará y te contactará si necesita más antecedentes.';

        if ($this->wantsJson()) {
            $this->respond(200, ['success' => true, 'message' => $successMessage]);
            return;
        }

        SessionHelper::setFlash('success', $successMessage);
        Router::redirect('reportar');
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPayload(): array {
        $payload = array();
        $payload['name'] = trim((string)($_POST['name'] ?? ''));
        $payload['email'] = trim((string)($_POST['email'] ?? ''));
        $payload['type'] = trim((string)($_POST['type'] ?? ''));
        $payload['severity'] = trim((string)($_POST['severity'] ?? ''));
        $payload['url'] = trim((string)($_POST['url'] ?? ''));
        $payload['description'] = trim((string)($_POST['description'] ?? ''));
        $payload['consent'] = isset($_POST['consent']) ? (string)$_POST['consent'] : '';

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function validate(array $payload): array {
        $errors = [];

        if ($payload['name'] === '' || mb_strlen($payload['name']) < 3) {
            $errors['name'] = 'Ingresa tu nombre para que podamos contactarte.';
        } elseif (mb_strlen($payload['name']) > 120) {
            $errors['name'] = 'Tu nombre no puede superar los 120 caracteres.';
        }

        if ($payload['email'] === '' || !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Necesitamos un correo válido para responderte.';
        }

        if ($payload['type'] === '' || !in_array($payload['type'], self::ALLOWED_TYPES, true)) {
            $errors['type'] = 'Selecciona el tipo de problema que estás reportando.';
        }

        if ($payload['severity'] === '' || !in_array($payload['severity'], self::ALLOWED_SEVERITIES, true)) {
            $errors['severity'] = 'Indica el impacto que tiene el problema.';
        }

        if ($payload['description'] === '' || mb_strlen($payload['description']) < 30) {
            $errors['description'] = 'Describe con más detalle lo ocurrido (mínimo 30 caracteres).';
        } elseif (mb_strlen($payload['description']) > 1200) {
            $errors['description'] = 'Tu descripción es muy extensa. Intenta resumirla en menos de 1200 caracteres.';
        }

        if ($payload['url'] !== '' && !filter_var($payload['url'], FILTER_VALIDATE_URL)) {
            $errors['url'] = 'El enlace proporcionado no tiene un formato válido.';
        }

        if ($payload['consent'] === '') {
            $errors['consent'] = 'Debes aceptar el uso de la información para investigar el caso.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     * @throws Exception
     */
    private function buildTicket(array $payload): array {
        $ticketId = strtoupper(bin2hex(random_bytes(6)));
        $createdAt = (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM);

        return [
            'id' => $ticketId,
            'name' => $payload['name'],
            'email' => strtolower($payload['email']),
            'type' => $payload['type'],
            'severity' => $payload['severity'],
            'url' => $payload['url'] !== '' ? $payload['url'] : null,
            'description' => $payload['description'],
            'consent' => true,
            'status' => 'open',
            'created_at' => $createdAt,
            'user_id' => SessionHelper::getUserId(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ];
    }

    private function wantsJson(): bool {
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        return $acceptsJson || $isAjax;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function respond(int $status, array $payload): void {
        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($status >= 400) {
            SessionHelper::setFlash('error', $payload['error'] ?? 'Ocurrió un error al procesar tu solicitud.');
        }

        Router::redirect('reportar');
    }
}
