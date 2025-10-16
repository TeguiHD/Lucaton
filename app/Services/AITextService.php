<?php

/**
 * AITextService
 * Gestiona la generación de texto utilizando OpenRouter (DeepSeek Chimera)
 * con fallback a Google AI Studio (Gemini) y rotación básica de llaves.
 */
class AITextService {
    private string $openRouterApiKey;
    private string $openRouterBaseUrl;
    private string $openRouterModel;
    private int $openRouterMaxTokens;
    private ?string $openRouterReferer;
    private ?string $openRouterTitle;

    private array $googleApiKeys;
    private string $googleModel;
    private string $googleApiBaseUrl;
    private string $googleApiVersion;
    private ?string $googleApiFallbackBaseUrl = null;
    private float $temperature;
    private bool $googleUseThinking;
    private int $googleThinkingBudget;
    private string $cacheDirectory;
    private string $googleRotationStatePath;
    private int $googleRotationIndex = 0;
    private ?string $lastGoogleModelUsed = null;
    private ?array $lastGoogleCall = null;

    private ?string $lastPrompt = null;
    /** @var array<int, array<string, mixed>> */
    private array $attemptLog = [];

    public function __construct() {
        $this->openRouterApiKey = trim((string)env('OPENROUTER_API_KEY', ''));
        $this->openRouterBaseUrl = rtrim((string)env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'), '/');
        $this->openRouterModel = trim((string)env('OPENROUTER_MODEL', 'tngtech/deepseek-r1t2-chimera:free'));
        $this->openRouterMaxTokens = (int)env('OPENROUTER_MAX_TOKENS', 640);
        $this->openRouterReferer = $this->normaliseOptionalHeader(env('OPENROUTER_REFERER', ''));
        $this->openRouterTitle = $this->normaliseOptionalHeader(env('OPENROUTER_TITLE', APP_NAME ?? 'Lucatón'));

        $keys = (string)env('GOOGLE_AI_API_KEYS', '');
        $this->googleApiKeys = array_values(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, explode(',', $keys)), static function ($value) {
            return $value !== '';
        }));
        $this->googleModel = trim((string)env('GOOGLE_AI_TEXT_MODEL', 'gemini-1.5-flash'));
        $configuredBaseUrl = rtrim((string)env('GOOGLE_AI_API_BASE_URL', 'https://generativelanguage.googleapis.com/v1'), '/');
        if (!preg_match('#/v1(beta)?$#i', $configuredBaseUrl)) {
            $configuredBaseUrl .= '/v1';
        }
        $this->googleApiBaseUrl = $configuredBaseUrl;
        $this->googleApiVersion = stripos($configuredBaseUrl, '/v1beta') !== false ? 'v1beta' : 'v1';
        if ($this->googleApiVersion === 'v1beta') {
            $fallback = preg_replace('#/v1beta$#i', '/v1', $configuredBaseUrl);
            $this->googleApiFallbackBaseUrl = is_string($fallback) && $fallback !== '' ? $fallback : 'https://generativelanguage.googleapis.com/v1';
        }
        $this->googleUseThinking = filter_var(env('GOOGLE_AI_USE_THINKING', false), FILTER_VALIDATE_BOOLEAN);
        $budget = (int)env('GOOGLE_AI_THINKING_BUDGET', 0);
        $this->googleThinkingBudget = $budget >= 0 ? $budget : 0;

        $this->temperature = (float)env('AI_TEXT_TEMPERATURE', 0.65);
        $this->cacheDirectory = ROOT_PATH . '/storage/cache';
        $this->googleRotationStatePath = $this->cacheDirectory . '/google_ai_rotation.json';
        $this->googleRotationIndex = $this->loadGoogleRotationIndex();
    }

    /**
     * @param array<string, string> $input
     * @return array<string, mixed>
     */
    public function generateCampaignDraft(array $input): array {
        $this->attemptLog = [];
        $prompt = $this->buildPrompt($input);
        $this->lastPrompt = $prompt;

        $googleAllowed = !empty($this->googleApiKeys) && $this->googleModel !== '';
        $openRouterAllowed = $this->openRouterApiKey !== '' && $this->openRouterModel !== '';

        if (!$googleAllowed && !$openRouterAllowed) {
            throw new RuntimeException('No hay proveedores de IA configurados. Revisa las variables de entorno.');
        }

        if ($googleAllowed) {
            $sequence = $this->resolveGoogleKeySequence();
            $lastAttemptIndex = null;

            foreach ($sequence as $entry) {
                $apiKey = $entry['key'];
                $keyIndex = $entry['index'];
                $lastAttemptIndex = $keyIndex;

                try {
                    $response = $this->callGoogleAi($prompt, $apiKey);
                    $this->setGoogleRotationIndex($keyIndex + 1);

                    return [
                        'provider' => $response['provider'],
                        'model' => $response['model'],
                        'content' => $response['text'],
                        'raw_response' => $response['raw_response'] ?? null,
                        'tokens_input' => $response['tokens_input'] ?? null,
                        'tokens_output' => $response['tokens_output'] ?? null,
                        'latency_ms' => $response['latency_ms'] ?? null,
                    ];
                } catch (Throwable $exception) {
                    if (!($exception instanceof RuntimeException)) {
                        $this->attemptLog[] = [
                            'provider' => 'google_ai',
                            'model' => $this->getLastGoogleModel() ?? $this->googleModel,
                            'status' => 'failed',
                            'error' => $exception->getMessage(),
                            'api_key_suffix' => $this->maskApiKey($apiKey),
                        ];
                    }
                    Logger::warning('Google AI text generation attempt failed', [
                        'error' => $exception->getMessage(),
                        'key_suffix' => $this->maskApiKey($apiKey),
                    ]);
                    if (!$this->shouldRetryGoogleProvider($exception)) {
                        break;
                    }
                }
            }

            if ($lastAttemptIndex !== null) {
                $this->setGoogleRotationIndex($lastAttemptIndex + 1);
            }
        }

        if ($openRouterAllowed) {
            try {
                $response = $this->callOpenRouter($prompt);
                return [
                    'provider' => $response['provider'],
                    'model' => $response['model'],
                    'content' => $response['text'],
                    'raw_response' => $response['raw_response'] ?? null,
                    'tokens_input' => $response['tokens_input'] ?? null,
                    'tokens_output' => $response['tokens_output'] ?? null,
                    'latency_ms' => $response['latency_ms'] ?? null,
                ];
            } catch (Throwable $exception) {
                $this->attemptLog[] = [
                    'provider' => 'openrouter',
                    'model' => $this->openRouterModel,
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                ];
                Logger::error('OpenRouter text generation failed', ['error' => $exception->getMessage()]);
            }
        }

        throw new RuntimeException('No se pudo generar el borrador con los proveedores configurados.');
    }

    public function getLastPrompt(): ?string {
        return $this->lastPrompt;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAttemptLog(): array {
        return $this->attemptLog;
    }

    public function getLastGoogleModel(): ?string {
        return $this->lastGoogleModelUsed;
    }

    public function getLastGoogleCallMeta(): ?array {
        return $this->lastGoogleCall;
    }

    /**
     * @param array<string, string> $input
     */
    private function buildPrompt(array $input): string {
        $title = trim((string)($input['title'] ?? ''));
        $story = trim((string)($input['story'] ?? ''));
        $goal = trim((string)($input['goal'] ?? ''));
        $fundingTarget = trim((string)($input['funding_target'] ?? ''));
        $protagonist = trim((string)($input['protagonist'] ?? ''));

        return sprintf(
            "[TÍTULO]: %s\n[HISTORIA]: %s\n[OBJETIVO]: %s\n[META]: %s\n[PROTAGONISTA]: %s",
            $title,
            $story,
            $goal,
            $fundingTarget,
            $protagonist
        );
    }

    private function callOpenRouter(string $prompt, ?string $systemPrompt = null, ?array $options = null): array {
        $this->lastGoogleCall = null;
        $systemPrompt = $systemPrompt ?: "Actúa como el 'Estratega de Impacto Social' de Chile. Sé empático, transparente y digno. Evita manipulaciones. Incluye una sección de Transparencia con uso de fondos, plazos, evidencias y contacto.";
        $payload = json_encode([
            'model' => $this->openRouterModel,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $options['temperature'] ?? $this->temperature,
            'max_tokens' => $options['max_tokens'] ?? $this->openRouterMaxTokens,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new RuntimeException('No se pudo codificar la solicitud a OpenRouter.');
        }

        $start = microtime(true);
        $ch = curl_init($this->openRouterBaseUrl . '/chat/completions');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openRouterApiKey,
        ];
        if ($this->openRouterReferer) {
            $headers[] = 'HTTP-Referer: ' . $this->openRouterReferer;
        }
        if ($this->openRouterTitle) {
            $headers[] = 'X-Title: ' . $this->openRouterTitle;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Error de conexión con OpenRouter: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Respuesta no exitosa de OpenRouter (HTTP ' . $httpCode . '): ' . $response);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Respuesta inválida de OpenRouter.');
        }

        $content = trim((string)($decoded['choices'][0]['message']['content'] ?? ''));
        if ($content === '') {
            throw new RuntimeException('OpenRouter no devolvió contenido.');
        }

        $latencyMs = (int)round((microtime(true) - $start) * 1000);
        $usage = $decoded['usage'] ?? [];

        $result = [
            'provider' => 'openrouter',
            'model' => $this->openRouterModel,
            'text' => $content,
            'raw_response' => $decoded,
            'tokens_input' => isset($usage['prompt_tokens']) ? (int)$usage['prompt_tokens'] : null,
            'tokens_output' => isset($usage['completion_tokens']) ? (int)$usage['completion_tokens'] : null,
            'latency_ms' => $latencyMs,
        ];

        $this->attemptLog[] = [
            'provider' => 'openrouter',
            'model' => $this->openRouterModel,
            'status' => 'completed',
            'latency_ms' => $latencyMs,
        ];

        return $result;
    }

    private function callGoogleAi(string $prompt, string $apiKey, ?string $systemPrompt = null, ?array $options = null): array {
        $this->lastGoogleCall = null;
        $systemPrompt = $systemPrompt ?: "Actúa como el 'Estratega de Impacto Social' de Chile. Tono empático, responsable y transparente. Estructura la respuesta en Markdown con: 1) Tres títulos sugeridos; 2) Historia en tres párrafos (≤300 palabras); 3) Tres llamados a la acción; 4) Dos textos breves para redes; 5) Sección de Transparencia con uso de fondos, plazos, evidencias y contacto responsable.";
        $options = $options ?? [];

        $initialAttempt = [
            'base_url' => $this->googleApiBaseUrl,
            'model' => $this->googleModel !== '' ? $this->googleModel : 'gemini-1.5-flash',
            'payload_style' => $this->googleApiVersion === 'v1beta' ? 'legacy' : 'modern',
            'allow_thinking' => $this->googleApiVersion !== 'v1beta' && $this->googleUseThinking,
        ];

        $queue = [$initialAttempt];
        $attempted = [];
        $lastException = null;
        $this->lastGoogleModelUsed = null;

        while (!empty($queue)) {
            $attempt = array_shift($queue);
            $signature = $attempt['base_url'] . '|' . $attempt['model'] . '|' . $attempt['payload_style'];
            if (isset($attempted[$signature])) {
                continue;
            }
            $attempted[$signature] = true;

            try {
                $result = $this->dispatchGoogleRequest($prompt, $systemPrompt, $apiKey, $options, $attempt);
                $this->lastGoogleCall = $result;
                $this->attemptLog[] = [
                    'provider' => 'google_ai',
                    'model' => $result['model'],
                    'status' => 'completed',
                    'latency_ms' => $result['latency_ms'] ?? null,
                    'api_key_suffix' => $this->maskApiKey($apiKey),
                ];

                return $result;
            } catch (RuntimeException $exception) {
                $lastException = $exception;
                $this->attemptLog[] = [
                    'provider' => 'google_ai',
                    'model' => $this->lastGoogleModelUsed ?? $attempt['model'],
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                    'api_key_suffix' => $this->maskApiKey($apiKey),
                ];

                foreach ($this->determineGoogleFallbacks($attempt, $exception) as $fallback) {
                    $fallbackSignature = $fallback['base_url'] . '|' . $fallback['model'] . '|' . $fallback['payload_style'];
                    if (!isset($attempted[$fallbackSignature])) {
                        $queue[] = $fallback;
                    }
                }
            }
        }

        if ($lastException instanceof RuntimeException) {
            throw $lastException;
        }

        throw new RuntimeException('No se pudo completar la solicitud con Google AI.');
    }

    /**
     * @param array<string, mixed>|null $override
     * @return array<string, mixed>|null
     */
    private function resolveThinkingConfig(?array $override): ?array {
        if ($override !== null) {
            $budget = isset($override['thinkingBudget']) ? (int)$override['thinkingBudget'] : $this->googleThinkingBudget;
            $override['thinkingBudget'] = $budget >= 0 ? $budget : 0;
            return $override;
        }

        if (!$this->googleUseThinking) {
            return null;
        }

        return [
            'thinkingBudget' => $this->googleThinkingBudget,
        ];
    }

    private function shouldRetryGoogleProvider(Throwable $exception): bool {
        if (!($exception instanceof RuntimeException)) {
            return true;
        }

        $code = $exception->getCode();
        if ($code === 0) {
            return true;
        }

        if ($code === 429) {
            return true;
        }

        if ($code >= 500) {
            return true;
        }

        return false;
    }

    /**
     * @return array<int, array{index:int,key:string}>
     */
    private function resolveGoogleKeySequence(): array {
        $keys = $this->googleApiKeys;
        $count = count($keys);
        if ($count === 0) {
            return [];
        }

        if ($count === 1) {
            return [['index' => 0, 'key' => $keys[0]]];
        }

        $primaryIndex = $this->claimNextGoogleKeyIndex();
        $sequence = [];
        for ($offset = 0; $offset < $count; $offset++) {
            $index = ($primaryIndex + $offset) % $count;
            $sequence[] = [
                'index' => $index,
                'key' => $keys[$index],
            ];
        }

        return $sequence;
    }

    private function setGoogleRotationIndex(int $nextIndex): void {
        $count = count($this->googleApiKeys);
        if ($count <= 1) {
            return;
        }

        $normalized = $nextIndex % $count;
        if ($normalized < 0) {
            $normalized += $count;
        }

        $this->ensureCacheDirectory();

        $handle = @fopen($this->googleRotationStatePath, 'c+');
        if ($handle === false) {
            return;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return;
        }

        $payload = json_encode([
            'next_index' => $normalized,
            'updated_at' => time(),
        ], JSON_UNESCAPED_SLASHES);

        if ($payload !== false) {
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $payload);
            fflush($handle);
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        $this->googleRotationIndex = $normalized;
    }

    private function loadGoogleRotationIndex(): int {
        $count = count($this->googleApiKeys);
        if ($count <= 1) {
            return 0;
        }

        $handle = @fopen($this->googleRotationStatePath, 'r');
        if ($handle === false) {
            return 0;
        }

        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            return 0;
        }

        $contents = stream_get_contents($handle);

        flock($handle, LOCK_UN);
        fclose($handle);

        $decoded = json_decode($contents ?: '', true);
        if (!is_array($decoded)) {
            return 0;
        }

        $index = (int)($decoded['next_index'] ?? 0);
        if ($index < 0) {
            $index = 0;
        }

        return $count > 0 ? $index % $count : 0;
    }

    private function claimNextGoogleKeyIndex(): int {
        $count = count($this->googleApiKeys);
        if ($count <= 1) {
            $this->googleRotationIndex = 0;
            return 0;
        }

        $this->ensureCacheDirectory();
        $handle = @fopen($this->googleRotationStatePath, 'c+');
        if ($handle === false) {
            return $this->googleRotationIndex % $count;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return $this->googleRotationIndex % $count;
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        $state = json_decode($contents ?: '', true);
        $current = isset($state['next_index']) ? (int)$state['next_index'] : $this->googleRotationIndex;
        $current = $current % $count;
        if ($current < 0) {
            $current += $count;
        }

        $next = ($current + 1) % $count;
        $payload = json_encode([
            'next_index' => $next,
            'updated_at' => time(),
        ], JSON_UNESCAPED_SLASHES);

        if ($payload !== false) {
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $payload);
            fflush($handle);
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        $this->googleRotationIndex = $next;

        return $current;
    }

    private function ensureCacheDirectory(): void {
        if (!is_dir($this->cacheDirectory)) {
            @mkdir($this->cacheDirectory, 0775, true);
        }
    }

    private function appendModelLatestSuffix(string $model): string {
        $trimmed = trim($model);
        if ($trimmed === '') {
            return $trimmed;
        }

        if (preg_match('/-latest$/i', $trimmed)) {
            return $trimmed;
        }

        return $trimmed . '-latest';
    }

    /**
     * @param array<string, mixed> $attempt
     * @return array<int, array<string, mixed>>
     */
    private function determineGoogleFallbacks(array $attempt, RuntimeException $exception): array {
        $fallbacks = [];
        $code = $exception->getCode();
        $message = $exception->getMessage();

        if ($attempt['payload_style'] === 'legacy' && $this->googleApiFallbackBaseUrl !== null) {
            $fallbacks[] = [
                'base_url' => $this->googleApiFallbackBaseUrl,
                'model' => $attempt['model'],
                'payload_style' => 'modern',
                'allow_thinking' => $this->googleUseThinking,
            ];
        }

        if ($code === 404 || str_contains($message, 'not found')) {
            $altModel = $this->appendModelLatestSuffix($attempt['model']);
            if ($altModel !== '' && $altModel !== $attempt['model']) {
                $fallbacks[] = [
                    'base_url' => $attempt['base_url'],
                    'model' => $altModel,
                    'payload_style' => $attempt['payload_style'],
                    'allow_thinking' => $attempt['allow_thinking'],
                ];

                if ($attempt['payload_style'] === 'legacy' && $this->googleApiFallbackBaseUrl !== null) {
                    $fallbacks[] = [
                        'base_url' => $this->googleApiFallbackBaseUrl,
                        'model' => $altModel,
                        'payload_style' => 'modern',
                        'allow_thinking' => $this->googleUseThinking,
                    ];
                }
            }
        }

        if ($code === 400 && $attempt['payload_style'] === 'modern') {
            if (str_contains($message, 'Unknown name "responseMimeType"') || str_contains($message, 'Unknown name "systemInstruction"')) {
                $legacyBase = $this->convertBaseToVersion($attempt['base_url'], 'v1beta');
                if ($legacyBase !== null) {
                    $fallbacks[] = [
                        'base_url' => $legacyBase,
                        'model' => $attempt['model'],
                        'payload_style' => 'legacy',
                        'allow_thinking' => false,
                    ];
                }
            }
        }

        return $fallbacks;
    }

    private function convertBaseToVersion(string $baseUrl, string $version): ?string {
        $trimmed = rtrim($baseUrl, '/');

        if ($version === 'v1') {
            if (preg_match('#/v1beta$#i', $trimmed)) {
                $converted = preg_replace('#/v1beta$#i', '/v1', $trimmed);
                return is_string($converted) ? $converted : null;
            }

            if (preg_match('#/v1$#i', $trimmed)) {
                return $trimmed;
            }

            return $trimmed . '/v1';
        }

        if ($version === 'v1beta') {
            if (preg_match('#/v1beta$#i', $trimmed)) {
                return $trimmed;
            }

            if (preg_match('#/v1$#i', $trimmed)) {
                $converted = preg_replace('#/v1$#i', '/v1beta', $trimmed);
                return is_string($converted) ? $converted : null;
            }

            return $trimmed . '/v1beta';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $attempt
     */
    private function dispatchGoogleRequest(string $prompt, string $systemPrompt, string $apiKey, array $options, array $attempt): array {
        $this->lastGoogleModelUsed = $attempt['model'];
        $generationConfig = $this->buildGenerationConfig($options['generationConfig'] ?? null, $attempt['payload_style'] === 'legacy');
        $thinkingConfig = $attempt['allow_thinking'] ? $this->resolveThinkingConfig($options['thinkingConfig'] ?? null) : null;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        if ($attempt['payload_style'] === 'modern') {
            $payload['generationConfig'] = $generationConfig;
            if ($systemPrompt !== '') {
                $payload['systemInstruction'] = [
                    'role' => 'system',
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ];
            }

            if ($thinkingConfig !== null) {
                $payload['thinkingConfig'] = $thinkingConfig;
            }
        } else {
            $payload['generation_config'] = $generationConfig;
            if ($systemPrompt !== '') {
                $payload['system_instruction'] = [
                    'role' => 'system',
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ];
            }
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('No se pudo codificar la solicitud a Google AI.', 0);
        }

        $endpointBase = rtrim($attempt['base_url'], '/');
        $url = sprintf(
            '%s/models/%s:generateContent?key=%s',
            $endpointBase,
            rawurlencode($attempt['model']),
            urlencode($apiKey)
        );

        $start = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Error de conexión con Google AI: ' . $error, 0);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            $hint = '';
            if ($httpCode === 404 && preg_match('#/v1beta$#i', $endpointBase)) {
                $hint = ' Sugerencia: actualiza GOOGLE_AI_API_BASE_URL a https://generativelanguage.googleapis.com/v1.';
            }

            throw new RuntimeException(
                'Respuesta no exitosa de Google AI (HTTP ' . $httpCode . '): ' . $response . $hint,
                $httpCode
            );
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Respuesta inválida de Google AI.', 0);
        }

        $content = $this->extractGoogleContent($decoded);
        if ($content === '') {
            throw new RuntimeException('Google AI no devolvió contenido.', 0);
        }

        $latencyMs = (int)round((microtime(true) - $start) * 1000);
        $usage = $decoded['usageMetadata'] ?? [];

        return [
            'provider' => 'google_ai',
            'model' => $attempt['model'],
            'text' => $content,
            'raw_response' => $decoded,
            'tokens_input' => isset($usage['promptTokenCount']) ? (int)$usage['promptTokenCount'] : null,
            'tokens_output' => isset($usage['candidatesTokenCount']) ? (int)$usage['candidatesTokenCount'] : null,
            'latency_ms' => $latencyMs,
        ];
    }

    /**
     * @param array<string, mixed>|null $overrides
     * @return array<string, mixed>
     */
    private function buildGenerationConfig(?array $overrides, bool $legacyFormat): array {
        $config = [
            'temperature' => $this->temperature,
            'topP' => 0.9,
            'topK' => 40,
        ];

        if (is_array($overrides)) {
            $config = array_merge($config, $overrides);
        }

        if (!$legacyFormat) {
            return $config;
        }

        $mapped = [];
        foreach ($config as $key => $value) {
            $legacyKey = match ($key) {
                'topP' => 'top_p',
                'topK' => 'top_k',
                'responseMimeType' => 'response_mime_type',
                'maxOutputTokens' => 'max_output_tokens',
                'candidateCount' => 'candidate_count',
                default => $key,
            };
            $mapped[$legacyKey] = $value;
        }

        return $mapped;
    }

    private function extractGoogleContent(array $decoded): string {
        if (empty($decoded['candidates']) || !is_array($decoded['candidates'])) {
            return '';
        }

        foreach ($decoded['candidates'] as $candidate) {
            if (!isset($candidate['content']['parts']) || !is_array($candidate['content']['parts'])) {
                continue;
            }

            foreach ($candidate['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $text = trim((string)$part['text']);
                    if ($text !== '') {
                        return $text;
                    }
                }

                if (isset($part['inlineData']['data'])) {
                    $data = base64_decode((string)$part['inlineData']['data'], true);
                    if ($data !== false) {
                        $text = trim($data);
                        if ($text !== '') {
                            return $text;
                        }
                    }
                }
            }
        }

        return '';
    }

    /**
     * @param array<string, string> $textos
     * @return array<string, string>
     */
    public function mejorarTextosCampana(array $textos): array {
        $titulo = trim((string)($textos['titulo'] ?? ''));
        $descripcion = trim((string)($textos['descripcion'] ?? ''));
        $notas = trim((string)($textos['notas'] ?? ''));

        if ($titulo === '' && $descripcion === '' && $notas === '') {
            throw new InvalidArgumentException('Debes proporcionar al menos un campo para mejorar.');
        }

        if (empty($this->googleApiKeys)) {
            throw new RuntimeException('No hay claves de Google configuradas para mejorar textos.');
        }

        $systemPrompt = "Eres una copywriter senior especializada en campañas sociales latinoamericanas. Mantén la voz original, refuerza empatía responsable y evita exageraciones. Debes devolver únicamente un JSON con tres claves: titulo_mejorado, descripcion_mejorada y notas_mejoradas.";

        $prompt = <<<PROMPT
Mejora los textos de una campaña social chilena siguiendo estas reglas:
- Mantén datos, nombres, montos y hechos sin inventar información nueva.
- Optimiza claridad, cercanía y llamado a la acción ético.
- Usa español neutro, frases cortas y evita lenguaje técnico excesivo.
- Devuelve exclusivamente un JSON sin comentarios, sin texto adicional y con las claves exactas solicitadas.

Datos originales:
- Título actual: {$titulo}
- Descripción extensa: {$descripcion}
- Notas breves: {$notas}
PROMPT;

        $this->lastPrompt = $prompt;
        $sequence = $this->resolveGoogleKeySequence();
        if (empty($sequence)) {
            throw new RuntimeException('No hay claves de Google disponibles para la rotación.');
        }

        $selected = $sequence[0];
        $keySuffix = $this->maskApiKey($selected['key']);

        try {
            $response = $this->callGoogleAi($prompt, $selected['key'], $systemPrompt, [
                'generationConfig' => [
                    'temperature' => $this->temperature,
                    'responseMimeType' => 'application/json',
                ],
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'No fue posible mejorar los textos con Google AI: ' . $exception->getMessage(),
                (int)$exception->getCode()
            );
        }

        $rawContent = trim((string)($response['text'] ?? ''));
        $decoded = $this->decodeJsonContent($rawContent);
        if ($decoded === null) {
            $this->markLastGoogleAttemptAsFailed('La respuesta de Google AI no incluía JSON válido.', $keySuffix);
            Logger::warning('Google AI devolvió contenido sin JSON válido para mejora de campaña', [
                'key_suffix' => $keySuffix,
            ]);
            throw new RuntimeException('La respuesta de Google AI no incluyó JSON válido.');
        }

        foreach (['titulo_mejorado', 'descripcion_mejorada', 'notas_mejoradas'] as $clave) {
            if (!array_key_exists($clave, $decoded)) {
                $this->markLastGoogleAttemptAsFailed('Faltan claves esperadas en la respuesta de Google AI.', $keySuffix);
                throw new RuntimeException('La respuesta de Google AI omitió una clave obligatoria: ' . $clave . '.');
            }
        }

        return [
            'titulo_mejorado' => $this->fallbackIfEmpty((string)$decoded['titulo_mejorado'], $titulo),
            'descripcion_mejorada' => $this->fallbackIfEmpty((string)$decoded['descripcion_mejorada'], $descripcion),
            'notas_mejoradas' => $this->fallbackIfEmpty((string)$decoded['notas_mejoradas'], $notas),
        ];
    }

    /**
     * @param array<string, string> $input
     * @return array<string, mixed>
     */
    public function improveCampaignContent(array $input): array {
        $title = trim((string)($input['title'] ?? ''));
        $short = trim((string)($input['short_description'] ?? ''));
        $story = trim((string)($input['description'] ?? ''));

        if ($title === '' && $short === '' && $story === '') {
            throw new \InvalidArgumentException('Ingresa texto en al menos uno de los campos para mejorarlo.');
        }

        $payloadData = [
            'title' => $title,
            'short_description' => $short,
            'description' => $story,
        ];

        $jsonInput = json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonInput === false) {
            throw new RuntimeException('No se pudo preparar el contenido a mejorar.');
        }

        $systemPrompt = "Eres un estratega de campañas solidarias en Chile. Mantén la voz del autor, refuerza claridad, transparencia y un tono digno. No inventes datos nuevos.";
        $userPrompt = <<<PROMPT
Reformula el siguiente contenido para una campaña de crowdfunding social en Chile. Mejora claridad, emotividad responsable y coherencia. Mantén hechos, montos y nombres.

Devuelve únicamente un JSON con las claves exactas:
{
  "title": "...",
  "short_description": "...",
  "description": "..."
}

No agregues comentarios adicionales. Información a mejorar:
{$jsonInput}
PROMPT;

        $openRouterAllowed = $this->openRouterApiKey !== '' && $this->openRouterModel !== '';
        $googleAllowed = !empty($this->googleApiKeys);

        if (!$googleAllowed && !$openRouterAllowed) {
            throw new RuntimeException('No hay proveedores de IA configurados para mejorar el contenido.');
        }

        if ($googleAllowed) {
            try {
                $improved = $this->mejorarTextosCampana([
                    'titulo' => $title,
                    'descripcion' => $story,
                    'notas' => $short,
                ]);

                $googleMeta = $this->getLastGoogleCallMeta() ?? [];
                $content = [
                    'title' => $this->fallbackIfEmpty($improved['titulo_mejorado'] ?? null, $title),
                    'short_description' => $this->fallbackIfEmpty($improved['notas_mejoradas'] ?? null, $short),
                    'description' => $this->fallbackIfEmpty($improved['descripcion_mejorada'] ?? null, $story),
                ];

                return [
                    'provider' => $googleMeta['provider'] ?? 'google_ai',
                    'model' => $googleMeta['model'] ?? $this->getLastGoogleModel() ?? $this->googleModel,
                    'content' => $content,
                    'raw_response' => $googleMeta['raw_response'] ?? null,
                    'tokens_input' => $googleMeta['tokens_input'] ?? null,
                    'tokens_output' => $googleMeta['tokens_output'] ?? null,
                    'latency_ms' => $googleMeta['latency_ms'] ?? null,
                ];
            } catch (Throwable $exception) {
                Logger::warning('Google AI unified improvement attempt failed', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($openRouterAllowed) {
            $openRouterPrompt = <<<PROMPT
{$userPrompt}

Recuerda responder únicamente con JSON válido.
PROMPT;

            try {
                $response = $this->callOpenRouter($openRouterPrompt, $systemPrompt, [
                    'temperature' => $this->temperature,
                    'max_tokens' => $this->openRouterMaxTokens,
                ]);

                $parsed = $this->decodeJsonContent($response['text']);
                if ($parsed === null) {
                    throw new RuntimeException('No se pudo interpretar la respuesta del proveedor.');
                }

                $improved = [
                    'title' => $this->fallbackIfEmpty($parsed['title'] ?? null, $title),
                    'short_description' => $this->fallbackIfEmpty($parsed['short_description'] ?? null, $short),
                    'description' => $this->fallbackIfEmpty($parsed['description'] ?? null, $story),
                ];

                return [
                    'provider' => $response['provider'],
                    'model' => $response['model'],
                    'content' => $improved,
                    'raw_response' => $response['raw_response'] ?? null,
                    'tokens_input' => $response['tokens_input'] ?? null,
                    'tokens_output' => $response['tokens_output'] ?? null,
                    'latency_ms' => $response['latency_ms'] ?? null,
                ];
            } catch (Throwable $exception) {
                $this->attemptLog[] = [
                    'provider' => 'openrouter',
                    'model' => $this->openRouterModel,
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                ];
                Logger::warning('OpenRouter improvement attempt failed', ['error' => $exception->getMessage()]);
            }
        }

        throw new RuntimeException('No fue posible mejorar el contenido con los proveedores disponibles.');
    }

    private function decodeJsonContent(string $content): ?array {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return null;
        }

        // Si viene dentro de bloque de código
        if (preg_match('/```(?:json)?\s*(\{.*\})```/is', $trimmed, $matches)) {
            $trimmed = $matches[1];
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $length = strlen($trimmed);
        if ($length >= 2) {
            $firstChar = $trimmed[0];
            $lastChar = $trimmed[$length - 1];
            if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                $unwrapped = json_decode($trimmed, true);
                if (is_string($unwrapped)) {
                    $candidate = trim($unwrapped);
                    if ($candidate !== '') {
                        $decodedWrapped = json_decode($candidate, true);
                        if (is_array($decodedWrapped)) {
                            return $decodedWrapped;
                        }

                        $fallbackWrapped = $this->extractFirstJsonObject($candidate);
                        if ($fallbackWrapped !== null) {
                            $decodedWrapped = json_decode($fallbackWrapped, true);
                            if (is_array($decodedWrapped)) {
                                return $decodedWrapped;
                            }
                        }
                    }
                }
            }
        }

        $fallback = $this->extractFirstJsonObject($trimmed);
        if ($fallback !== null) {
            $decoded = json_decode($fallback, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function extractFirstJsonObject(string $content): ?string {
        $start = strpos($content, '{');
        if ($start === false) {
            return null;
        }

        $length = strlen($content);
        $depth = 0;

        for ($i = $start; $i < $length; $i++) {
            $char = $content[$i];

            if ($char === '{') {
                $depth++;
                continue;
            }

            if ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $start, $i - $start + 1);
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                $i++;
                while ($i < $length) {
                    if ($content[$i] === '\\') {
                        $i += 2;
                        continue;
                    }

                    if ($content[$i] === $quote) {
                        break;
                    }

                    $i++;
                }
            }
        }

        return null;
    }

    private function markLastGoogleAttemptAsFailed(string $message, ?string $keySuffix = null): void {
        $lastIndex = array_key_last($this->attemptLog);
        if ($lastIndex !== null
            && isset($this->attemptLog[$lastIndex]['provider'])
            && $this->attemptLog[$lastIndex]['provider'] === 'google_ai'
            && isset($this->attemptLog[$lastIndex]['status'])
            && $this->attemptLog[$lastIndex]['status'] === 'completed'
        ) {
            $this->attemptLog[$lastIndex]['status'] = 'failed';
            $this->attemptLog[$lastIndex]['error'] = $message;
            if ($keySuffix !== null) {
                $this->attemptLog[$lastIndex]['api_key_suffix'] = $keySuffix;
            }
            return;
        }

        $this->attemptLog[] = [
            'provider' => 'google_ai',
            'model' => $this->getLastGoogleModel() ?? $this->googleModel,
            'status' => 'failed',
            'error' => $message,
            'api_key_suffix' => $keySuffix,
        ];
    }

    private function fallbackIfEmpty(?string $value, string $fallback): string {
        $value = trim((string)$value);
        if ($value === '') {
            return $fallback;
        }
        return $value;
    }

    private function maskApiKey(string $apiKey): string {
        if ($apiKey === '') {
            return '';
        }

        $length = strlen($apiKey);
        if ($length <= 8) {
            return str_repeat('*', max(0, $length - 2)) . substr($apiKey, -2);
        }

        return str_repeat('*', $length - 6) . substr($apiKey, -6);
    }

    private function normaliseOptionalHeader($value): ?string {
        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }
}
