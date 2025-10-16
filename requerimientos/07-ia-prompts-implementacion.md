## IA — Prompts y Implementación

### Prompt maestro (Texto de campaña)
Actúa como el "Estratega de Impacto Social" (Chile). Tono empático, profesional y digno (sin manipulación). Entrada: título, historia, objetivo, meta, protagonista. Salida en Markdown:
1) 3 títulos claros; 2) historia reescrita (≤300 palabras) en 3 párrafos; 3) 3 CTAs; 4) 2 textos para redes; 5) Sección de Transparencia (bullets): uso de fondos, plazos estimados, evidencias sugeridas, datos de contacto del responsable.

### Prompt maestro (Imagen de portada)
Actúa como "Estratega de Impacto Social" visual. Entrada: contexto y emoción clave. Salida: un prompt en inglés (photorealistic, cinematic lighting, warm tones, medium shot, hopeful expression, --ar 16:9).

---

## Implementación técnica (PHP cURL)

### Entorno (.env)
Ver plantilla en `requerimientos/14-env-plantilla.md`.

### Bootstrap sin Composer (carga .env)
```php
$envPath = __DIR__ . '/../.env';
if (is_file($envPath)) {
  $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#')) continue;
    [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
    $k = trim($k); $v = trim($v, " \"'\t");
    if ($k !== '') $_ENV[$k] = $v;
  }
}
```

### Servicio: Gemini (imagen)
```php
class GeminiImageService {
  private string $apiKey;
  public function __construct(string $apiKey){ $this->apiKey=$apiKey; }
  public function generateImage(string $prompt,string $outputDir,?string $model=null): string {
    $model = $model ?: 'models/gemini-2.5-flash-image-preview';
    $url = 'https://generativelanguage.googleapis.com/v1beta/'.rawurlencode($model).':generateContent?key='.urlencode($this->apiKey);
    $payload = json_encode([ 'contents' => [[ 'role'=>'user', 'parts'=>[['text'=>$prompt]] ]], 'generationConfig'=>[] ]);
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_POST=>true, CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_POSTFIELDS=>$payload, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>60]);
    $resp = curl_exec($ch); if ($resp===false) throw new RuntimeException('cURL error: '.curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code<200 || $code>=300) throw new RuntimeException('HTTP '.$code.': '.$resp);
    $data = json_decode($resp,true);
    $parts = $data['candidates'][0]['content']['parts'] ?? [];
    $imageB64=null; $mime='image/png';
    foreach ($parts as $p){
      if (isset($p['inlineData']['data'])){ $imageB64=$p['inlineData']['data']; $mime=$p['inlineData']['mimeType']??$mime; break; }
      if (isset($p['inline_data']['data'])){ $imageB64=$p['inline_data']['data']; $mime=$p['inline_data']['mime_type']??$mime; break; }
    }
    if (!$imageB64) throw new RuntimeException('La respuesta no contiene imagen.');
    $ext = $mime==='image/jpeg'?'.jpg':($mime==='image/webp'?'.webp':'.png');
    if (!is_dir($outputDir)) mkdir($outputDir,0775,true);
    $filePath = rtrim($outputDir,'/\\').DIRECTORY_SEPARATOR.'campaign_'.date('Ymd_His').$ext;
    file_put_contents($filePath, base64_decode($imageB64));
    return $filePath;
  }
}
```

### Servicio: OpenRouter + Google AI (texto)
```php
class AITextService {
  public function generateCampaignDraft(array $input): array {
    $prompt = $this->buildPrompt($input); // Normaliza título, historia, meta, protagonista

    try {
      return $this->callOpenRouter($prompt); // usa modelo DeepSeek Chimera vía OpenRouter
    } catch (Throwable $err) {
      // fallback a Google AI Studio (listas de llaves rotadas)
      foreach ($this->googleApiKeys as $key) {
        try {
          return $this->callGoogleAi($prompt, $key);
        } catch (Throwable $fallbackErr) {
          Logger::warning('Google AI fallback failed', ['error' => $fallbackErr->getMessage()]);
        }
      }
      throw new RuntimeException('Ningún proveedor pudo generar el borrador.');
    }
  }
}
```

### Endpoints IA (PHP)
- `POST /generate_text.php` → AITextService (valida input; rate‑limit por sesión; JSON de salida)
- `POST /generate_image.php` → GeminiImageService (valida prompt; guarda archivo en UPLOAD_DIR; JSON con path/url)

Ver también `requerimientos/12-endpoints.md`.
