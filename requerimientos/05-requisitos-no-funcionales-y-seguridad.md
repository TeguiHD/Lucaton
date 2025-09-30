## Requisitos No Funcionales

### Seguridad (prioridad alta)
- SQLi: PDO + consultas preparadas; usuario BD de mínimos privilegios.
- XSS: escape de salida (`htmlspecialchars`), escapar atributos/URLs.
- CSRF: token por formulario POST.
- Sesiones: cookies HttpOnly, Secure (HTTPS), SameSite=Lax/Strict; regenerar ID en login; invalidar en logout.
- Rate limiting: login e IA (AI_MAX_REQ_PER_HOUR por usuario). Implementación simple por sesión + ventana temporal.
- Cabeceras: X-Frame-Options=SAMEORIGIN; X-Content-Type-Options=nosniff; Referrer-Policy=strict-origin-when-cross-origin; HSTS en prod.
- Gestión de secretos: .env en .gitignore; no loggear API keys.

### Rendimiento
- Índices en FKs y campos de filtro; paginación.
- Cacheo básico de listados públicos (opcional).
 - Optimización de imágenes: redimensionar a tamaño máximo (ej. 1600x900) y comprimir (JPEG 80%) al subir o generar.
   - Implementación con GD (sin dependencias): `imagecreatefromjpeg/png/webp`, `imagescale`/`imagecopyresampled`, `imagejpeg`.
   - Proteger de imágenes malformadas: límites de memoria y tamaño; validar con finfo.

### Observabilidad
- Logging básico a archivo en LOG_DIR vía `error_log()`; redactar datos sensibles.
- Auditoría en `auditoria_estados`.

### Accesibilidad y UX
- Mobile‑First; contraste WCAG AA; focus visible; labels/aria en formularios.

### Mantenibilidad
- PHPDoc; organización por capas (controladores/servicios/repositorios) y partials.

### Política de retención (IA)
- Generaciones denegadas: retención por 90 días (evidencia para auditoría).
- Generaciones no usadas (status=generated y sin `used_in_campaign_id`): limpieza opcional a los 30 días.

---

## Snippets de Seguridad

### .htaccess en UPLOAD_DIR (ej. storage/uploads/.htaccess)
```
<IfModule mod_php7.c>
  php_flag engine off
</IfModule>
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps
Options -Indexes
```

### .htaccess raíz (cabeceras y CSP en producción, sin CDN)
```
<IfModule mod_headers.c>
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-Content-Type-Options "nosniff"
  Header set Referrer-Policy "strict-origin-when-cross-origin"
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" env=HTTPS
  Header set Content-Security-Policy "default-src 'self'; img-src 'self' data: blob: https:; media-src 'none'; object-src 'none'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; style-src 'self' 'unsafe-inline'; font-src 'self' data:; script-src 'self'; frame-src 'self' https://TU_N8N_HOST"
</IfModule>
```

### php.ini (producción)
```
session.cookie_httponly=1
session.cookie_samesite=Lax
session.use_strict_mode=1
session.cookie_secure=1
```

### Validación de subidas (PHP)
```
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $_FILES['imagen']['tmp_name']);
if (!in_array($mime, ['image/jpeg','image/png','image/webp'])) { /* error */ }
$newName = bin2hex(random_bytes(8)).'.'.pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
```
