## Stack y Configuración

- Servidor web: Apache (XAMPP en dev; equivalente en prod)
- Backend: PHP 8.2+
- BD: MariaDB 10.6+ o MySQL 8 (BD: lucaton)
- Frontend: HTML5, JS (Vanilla)
- Estilos: Tailwind CSS (Mobile‑First) con build local en producción (pnpm). HTML: `<link rel="/assets/app.css">`
- IA: OpenRouter (DeepSeek Chimera) para texto y Google Gemini para imágenes, invocados desde PHP (cURL) sin exponer API keys en cliente.

### Dependencias PHP
- Recomendado por tiempo: sin Composer (cURL nativo + cargador simple de .env)
- Opcional: `vlucas/phpdotenv`, `guzzlehttp/guzzle`, `monolog/monolog`, `ramsey/uuid`

### Variables de Entorno (.env)
- APP_ENV, BASE_URL, DB_HOST/NAME/USER/PASS
- UPLOAD_DIR (público opcional), LOG_DIR
- UPLOAD_DIR_PRIVATE (recomendado, fuera de docroot) y UPLOAD_DIR_PUBLIC (sirve assets aprobados)
- APP_TIMEZONE (ej. America/Santiago)
- OPENROUTER_API_KEY, OPENROUTER_BASE_URL, OPENROUTER_MODEL
- GOOGLE_AI_API_KEYS, GOOGLE_AI_TEXT_MODEL
- GEMINI_API_KEY, GEMINI_IMAGE_MODEL
- AI_MAX_REQ_PER_HOUR, LOGIN_MAX_ATTEMPTS, COOKIE_SECURE, CSP_ENABLE

Ejemplo completo: ver `requerimientos/14-env-plantilla.md`.

### CSP de producción (build local sin CDN)
Sugerida en `.htaccess` raíz:
```
default-src 'self'; img-src 'self' data: blob: https:; media-src 'none';
object-src 'none'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';
form-action 'self'; style-src 'self' 'unsafe-inline'; font-src 'self' data:;
script-src 'self'; frame-src 'self' https://TU_N8N_HOST
```
Nota: se mantiene `'unsafe-inline'` en style-src si usas estilos inline mínimos (por ejemplo ancho de barra de progreso).

### Decisión sobre Tailwind
- Producción: build local con pnpm para máximo control de diseño, mejor rendimiento y CSP sólida.
- Desarrollo: opcionalmente usar CDN para prototipos rápidos (requiere CSP ampliada temporalmente).

### Almacenamiento de archivos (privado vs público)
- Generaciones IA y evidencias: guardar por defecto en `UPLOAD_DIR_PRIVATE` (fuera de docroot) con subcarpetas por `user_id`.
- Publicación: al adjuntar a una campaña aprobada, generar una copia en `UPLOAD_DIR_PUBLIC` con nombre aleatorio; conservar original privado como evidencia.
- Acceso: servir archivos privados mediante un endpoint PHP que verifique permisos (owner/admin), no directo por URL.

### Configuración regional
- Zona horaria por defecto: `APP_TIMEZONE=America/Santiago` (configurar `date_default_timezone_set` en bootstrap).
- Formato de moneda/fecha: CLP con separador de miles y dd/mm/yyyy para vistas.
