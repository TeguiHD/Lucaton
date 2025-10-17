<?php
class AIController {
    /**
     * Cache de valores permitidos en el ENUM provider de ai_generations.
     *
     * @var array<int, string>|null
     */
    private static ?array $aiProviderCache = null;

    public function generateText() {
        $this->ensurePostJson();

        if (!SessionHelper::isAuthenticated()) {
            http_response_code(401);
            $this->respondJson(['error' => 'Debes iniciar sesión para usar la asistencia.']);
            return;
        }

        if (!SessionHelper::checkRateLimit('ai_text', RATE_LIMIT_AI_REQUESTS, RATE_LIMIT_WINDOW)) {
            http_response_code(429);
            $this->respondJson(['error' => 'Has alcanzado el límite temporal de asistencia. Inténtalo más tarde.']);
            return;
        }

        $payload = $this->readJsonPayload();
        $validation = $this->validateTextPayload($payload);

        if (!empty($validation)) {
            http_response_code(422);
            $this->respondJson(['error' => 'Revisa los campos marcados.', 'fields' => $validation]);
            return;
        }

        $userId = (int)SessionHelper::getUserId();
        $service = new AITextService();

        try {
            $result = $service->generateCampaignDraft([
                'title' => $payload['title'],
                'story' => $payload['story'],
                'goal' => $payload['goal'],
                'funding_target' => $payload['funding_target'],
                'protagonist' => $payload['protagonist'],
            ]);

            $generationId = $this->storeGenerationRecord(
                $userId,
                $service->getLastPrompt(),
                $payload,
                $result,
                'completed',
                null,
                $service->getAttemptLog()
            );

            http_response_code(200);
            $this->respondJson([
                'success' => true,
                'content' => $result['content'],
                'provider' => $result['provider'],
                'model' => $result['model'],
                'latency_ms' => $result['latency_ms'] ?? null,
                'tokens' => [
                    'input' => $result['tokens_input'] ?? null,
                    'output' => $result['tokens_output'] ?? null,
                ],
                'generation_id' => $generationId,
            ]);
        } catch (Throwable $exception) {
            Logger::error('AI text generation failed', ['error' => $exception->getMessage()]);
            $this->storeGenerationRecord(
                $userId,
                $service->getLastPrompt(),
                $payload,
                [
                    'provider' => $this->resolveFailedProvider($service),
                    'model' => '',
                    'content' => '',
                    'latency_ms' => null,
                ],
                'failed',
                $exception->getMessage(),
                $service->getAttemptLog()
            );

            http_response_code(500);
            $this->respondJson([
                'error' => 'No pudimos generar el borrador en este momento. Intenta nuevamente más tarde.',
            ]);
        }
    }

    public function improveText(): void {
        $this->ensurePostJson();

        if (!SessionHelper::isAuthenticated()) {
            http_response_code(401);
            $this->respondJson(['error' => 'Debes iniciar sesión para usar la asistencia.']);
            return;
        }

        if (!SessionHelper::checkRateLimit('ai_text', RATE_LIMIT_AI_REQUESTS, RATE_LIMIT_WINDOW)) {
            http_response_code(429);
            $this->respondJson(['error' => 'Has alcanzado el límite temporal de asistencia. Inténtalo más tarde.']);
            return;
        }

        $payload = $this->readJsonPayload();
        $validationMessage = $this->validateImprovePayload($payload);
        if ($validationMessage !== '') {
            http_response_code(422);
            $this->respondJson(['error' => $validationMessage]);
            return;
        }

        $userId = (int)SessionHelper::getUserId();
        $service = new AITextService();

        try {
            $result = $service->improveCampaignContent([
                'title' => $payload['title'],
                'short_description' => $payload['short_description'],
                'description' => $payload['description'],
            ]);

            $generationId = $this->storeGenerationRecord(
                $userId,
                $service->getLastPrompt(),
                $payload,
                $result,
                'completed',
                null,
                $service->getAttemptLog()
            );

            http_response_code(200);
            $this->respondJson([
                'success' => true,
                'fields' => $result['content'],
                'provider' => $result['provider'],
                'model' => $result['model'],
                'latency_ms' => $result['latency_ms'] ?? null,
                'tokens' => [
                    'input' => $result['tokens_input'] ?? null,
                    'output' => $result['tokens_output'] ?? null,
                ],
                'generation_id' => $generationId,
            ]);
        } catch (Throwable $exception) {
            Logger::error('AI improve content failed', ['error' => $exception->getMessage()]);

            $this->storeGenerationRecord(
                $userId,
                $service->getLastPrompt(),
                $payload,
                [
                    'provider' => $this->resolveFailedProvider($service),
                    'model' => '',
                    'content' => [
                        'title' => $payload['title'],
                        'short_description' => $payload['short_description'],
                        'description' => $payload['description'],
                    ],
                    'latency_ms' => null,
                ],
                'failed',
                $exception->getMessage(),
                $service->getAttemptLog()
            );

            http_response_code(500);
            $this->respondJson([
                'error' => 'No pudimos mejorar el contenido en este momento. Intenta nuevamente más tarde.',
            ]);
        }
    }

    public function generateImage() {
        if (!SessionHelper::checkRateLimit('ai_image', RATE_LIMIT_AI_REQUESTS, RATE_LIMIT_WINDOW)) {
            http_response_code(429);
            echo json_encode(['error' => 'Límite de IA alcanzado']);
            return;
        }
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Generación de imagen no configurada']);
    }

    public function moderate() {
        if (!SessionHelper::checkRateLimit('ai_moderate', RATE_LIMIT_AI_REQUESTS, RATE_LIMIT_WINDOW)) {
            http_response_code(429);
            echo json_encode(['error' => 'Límite de IA alcanzado']);
            return;
        }
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Moderación no configurada']);
    }

    public function serveFile($id) {
        http_response_code(404);
        echo 'Archivo no encontrado';
    }

    private function ensurePostJson(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            $this->respondJson(['error' => 'Método no permitido.']);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonPayload(): array {
        $body = (string)file_get_contents('php://input');
        if ($body === '') {
            return [
                'title' => trim((string)($_POST['title'] ?? '')),
                'story' => trim((string)($_POST['story'] ?? $_POST['description'] ?? '')),
                'goal' => trim((string)($_POST['goal'] ?? '')),
                'funding_target' => trim((string)($_POST['funding_target'] ?? '')),
                'protagonist' => trim((string)($_POST['protagonist'] ?? '')),
                'short_description' => trim((string)($_POST['short_description'] ?? '')),
                'description' => trim((string)($_POST['description'] ?? '')),
            ];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return [
                'title' => '',
                'story' => '',
                'goal' => '',
                'funding_target' => '',
                'protagonist' => '',
                'short_description' => '',
                'description' => '',
            ];
        }

        return [
            'title' => trim((string)($decoded['title'] ?? '')),
            'story' => trim((string)($decoded['story'] ?? $decoded['description'] ?? '')),
            'goal' => trim((string)($decoded['goal'] ?? '')),
            'funding_target' => trim((string)($decoded['funding_target'] ?? '')),
            'protagonist' => trim((string)($decoded['protagonist'] ?? '')),
            'short_description' => trim((string)($decoded['short_description'] ?? '')),
            'description' => trim((string)($decoded['description'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function validateTextPayload(array $payload): array {
        $errors = [];

        if ($payload['title'] === '' || mb_strlen($payload['title']) < 6) {
            $errors['title'] = 'Ingresa un título descriptivo (mínimo 6 caracteres).';
        }

        if ($payload['story'] === '' || mb_strlen($payload['story']) < 60) {
            $errors['story'] = 'Comparte la historia con al menos 60 caracteres.';
        }

        if ($payload['goal'] === '' || mb_strlen($payload['goal']) < 20) {
            $errors['goal'] = 'Describe el objetivo principal en al menos 20 caracteres.';
        }

        if ($payload['funding_target'] === '') {
            $errors['funding_target'] = 'Indica la meta de recaudación estimada.';
        }

        if ($payload['protagonist'] === '' || mb_strlen($payload['protagonist']) < 4) {
            $errors['protagonist'] = 'Indica quién lidera la campaña.';
        }

        return $errors;
    }

    private function validateImprovePayload(array $payload): string {
        $title = trim((string)($payload['title'] ?? ''));
        $short = trim((string)($payload['short_description'] ?? ''));
        $description = trim((string)($payload['description'] ?? ''));

        if ($title === '' && $short === '' && $description === '') {
            return 'Ingresa contenido en el título, descripción breve o historia para poder mejorarlo.';
        }

        if ($description !== '' && mb_strlen($description) < 40) {
            return 'La historia debe tener al menos 40 caracteres para sugerir mejoras.';
        }

        return '';
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $result
     * @param array<int, array<string, mixed>>|null $attemptLog
     */
    private function storeGenerationRecord(
        int $userId,
        ?string $prompt,
        array $payload,
        array $result,
        string $status,
        ?string $errorMessage = null,
        ?array $attemptLog = null
    ): ?int {
        try {
            $database = Database::getInstance();
        } catch (Throwable $databaseException) {
            Logger::warning('AI generation skipped because database is unavailable', [
                'error' => $databaseException->getMessage(),
            ]);
            return null;
        }

        if (!$database->tableExists('ai_generations')) {
            return null;
        }

        $inputParams = [
            'fields' => $payload,
        ];

        if (!empty($attemptLog)) {
            $inputParams['attempts'] = $attemptLog;
        }

        if (isset($result['raw_response'])) {
            $inputParams['raw_response'] = $result['raw_response'];
        }

        $inputJson = json_encode($inputParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($inputJson === false) {
            $inputJson = json_encode(['fields' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if ($inputJson === false) {
            $inputJson = '{}';
        }

        $sanitisedError = $errorMessage !== null ? mb_substr($errorMessage, 0, 1000) : null;

        $outputContent = $result['content'] ?? '';
        if (is_array($outputContent)) {
            $encoded = json_encode($outputContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $outputContent = $encoded === false ? '' : $encoded;
        }

        $sanitisedProvider = $this->normaliseAiProviderForStorage($database, (string)($result['provider'] ?? 'openrouter'));

        $data = [
            'user_id' => $userId,
            'context_entity_type' => 'standalone',
            'context_entity_id' => null,
            'mode' => 'text',
            'prompt' => $prompt ?? '',
            'input_parameters' => $inputJson,
            'model_used' => (string)($result['model'] ?? ''),
            'provider' => $sanitisedProvider,
            'output_text' => $outputContent,
            'tokens_input' => $result['tokens_input'] ?? null,
            'tokens_output' => $result['tokens_output'] ?? null,
            'latency_ms' => $result['latency_ms'] ?? null,
            'status' => $status,
            'error_message' => $sanitisedError,
        ];

        $filtered = array_filter($data, static function ($value) {
            return $value !== null;
        });

        try {
            $id = $database->insert('ai_generations', $filtered);
            return (int)$id;
        } catch (Throwable $exception) {
            Logger::error('Failed to store AI generation record', ['error' => $exception->getMessage()]);
            return null;
        }
    }

    private function respondJson(array $payload): void {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<int, array<string, mixed>> $attempts
     */
    private function resolveFailedProvider(AITextService $service, ?array $attempts = null): string {
        $log = $attempts ?? $service->getAttemptLog();
        if (empty($log)) {
            return 'openrouter';
        }

        $last = end($log);
        if (is_array($last) && isset($last['provider'])) {
            return (string)$last['provider'];
        }

        return 'openrouter';
    }

    /**
     * Normaliza el proveedor a un valor permitido por el ENUM de la tabla ai_generations.
     */
    private function normaliseAiProviderForStorage(Database $database, string $provider): string {
        $provider = strtolower(trim($provider));
        if ($provider === '') {
            $provider = 'openrouter';
        }

        $allowed = $this->getAllowedAiProviders($database);
        if (empty($allowed)) {
            return $provider;
        }

        if (in_array($provider, $allowed, true)) {
            return $provider;
        }

        $aliases = [
            'google_ai' => ['gemini'],
            'gemini' => ['google_ai'],
            'google' => ['google_ai', 'gemini'],
            'openrouter' => ['openai'],
            'deepseek' => ['openrouter', 'openai'],
            'openai' => ['openrouter'],
            'stability' => ['openrouter', 'openai'],
            'anthropic' => ['openrouter', 'openai'],
        ];

        if (isset($aliases[$provider])) {
            foreach ($aliases[$provider] as $candidate) {
                if (in_array($candidate, $allowed, true)) {
                    return $candidate;
                }
            }
        }

        $fallback = $allowed[0] ?? 'openrouter';
        return $fallback;
    }

    /**
     * Obtiene los valores disponibles en el ENUM provider de ai_generations.
     *
     * @return array<int, string>
     */
    private function getAllowedAiProviders(Database $database): array {
        if (self::$aiProviderCache !== null) {
            return self::$aiProviderCache;
        }

        try {
            $column = $database->fetch("SHOW COLUMNS FROM ai_generations LIKE 'provider'");
            if (!$column || empty($column['Type'])) {
                self::$aiProviderCache = [];
                return self::$aiProviderCache;
            }

            $type = (string)$column['Type'];
            if (!preg_match('/^enum\((.+)\)$/i', $type, $matches)) {
                self::$aiProviderCache = [];
                return self::$aiProviderCache;
            }

            $values = array_map(static function ($value) {
                return strtolower(trim(stripslashes(trim($value, "'\""))));
            }, explode(',', $matches[1]));

            self::$aiProviderCache = array_values(array_filter($values, static function ($value) {
                return $value !== '';
            }));
        } catch (Throwable $exception) {
            Logger::warning('No se pudo resolver el ENUM provider de ai_generations', [
                'error' => $exception->getMessage(),
            ]);
            self::$aiProviderCache = [];
        }

        return self::$aiProviderCache;
    }
}
?>
