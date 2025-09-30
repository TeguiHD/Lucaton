## Checklist de Implementación (ejecutable por TRAE/Claude)

1) Preparación del entorno
- Crear `.env` (ver `requerimientos/14-env-plantilla.md`).
- Crear usuario BD `lucaton_app` y ejecutar SQL (ver `requerimientos/03-modelo-datos-y-sql.md`).
- Estructura de carpetas mínima: `public/`, `includes/`, `config/`, `src/Services/`, `storage/uploads/`, `storage/logs/`.
- `.htaccess` en `storage/uploads/` para bloquear ejecución (ver `requerimientos/05-requisitos-no-funcionales-y-seguridad.md`).
- Seed admin inicial: crear un usuario admin (SQL o script) para el primer acceso al panel.

2) Tailwind (build local con pnpm)
- Instalar y configurar (ver `requerimientos/13-tailwind-pnpm.md`).
- Generar `public/assets/app.css` (modo dev y prod).

3) Base de app (PHP)
- Bootstrap que cargue `.env` (sin Composer) y configure sesiones seguras.
- Layout base (`includes/layout.php`) + `includes/navbar.php` + `includes/footer.php`.
- Páginas: `public/index.php`, `public/login.php`, `public/registro.php`, `public/logout.php`.
 - Configurar `date_default_timezone_set($_ENV['APP_TIMEZONE'])`.

4) Autenticación y roles
- Registro/login con `password_hash/password_verify` y rol en sesión.
- Middleware simple para rutas protegidas (usuario/admin).

5) Campañas (usuario)
- Formulario crear campaña (título, descripción, meta, fecha_inicio/fin, imagen/evidencias).
- Validación de uploads (MIME/extensión/tamaño, renombrado aleatorio) y guardado.
- Botones IA: `Generar texto` (POST `/generate_text.php`) y `Generar imagen` (POST `/generate_image.php`).
- Panel "Mis campañas" y acción de apelar.
- Guardar generados IA en `UPLOAD_DIR_PRIVATE/user_{id}/`; registrar en `ai_generations` (status=generated).
- Adjuntar IA a campaña con `POST /usuario/campana/{id}/adjuntar_ai/{generation_id}` (si aprobada → copiar a `UPLOAD_DIR_PUBLIC/campaign_{id}/`).
 - Si IA no disponible (sin API keys): ocultar botones IA y permitir flujo manual.

6) Panel admin
- Listar/filtrar campañas; ver detalle y evidencias.
- Acciones: aprobar/rechazar (con motivo)/pausar/finalizar; editar fecha_fin.
- Auditoría de cambios de estado.

7) Donaciones simuladas
- POST `/donar` (requiere login), actualizar barra de progreso y donadores recientes.

8) Seguridad esencial
- CSRF tokens en formularios.
- Rate limit: login (ventana 15 min) e IA (AI_MAX_REQ_PER_HOUR por usuario en 60 min) con `$_SESSION`.
- Cabeceras y CSP en `.htaccess` raíz (producción), ver módulo de seguridad.
 - Servir privados por endpoint (`/file/ai/{id}`) con control de acceso (owner/admin). No exponer rutas directas.

9) IA evaluable (tesis)
- Registrar tiempo de redacción, número de iteraciones IA usadas, selección de variantes (si aplica).
 - Registrar `policy_flags` en denegaciones, mantener evidencia 90 días.
- Añadir encuestas SUS/UEQ corto y preguntas Likert para claridad/intención de compartir.

10) QA rápido
- Verificar contraste de CTA y links (WCAG AA manual).
- Probar rechazo de upload inválido y caducidad de CSRF.
- Probar CSP activa (cabeceras en respuesta).

11) Endpoints IA — archivos privados y adjuntar a campaña (especificación exacta)

- GET `/file/ai/{id}` (servir asset privado con control de acceso)
  - Auth: requerida (sesión). Permisos: dueño del recurso (`ai_generations.user_id`) o admin.
  - Path params: `id` (INT, id en `ai_generations`).
  - Query opcional: `action=meta|download` (default: `download`).
  - Validaciones:
    - Existe `ai_generations.id = {id}` y `type IN ('image','text')`.
    - `output_path` apunta a `UPLOAD_DIR_PRIVATE/user_{owner}/...` (no path traversal).
    - Si `status='denied'`: solo admin puede acceder.
  - Respuestas:
    - 200 (meta): `application/json`
      `{ ok: true, id, owner_id, type, status, used_in_campaign_id, filename, mime, size, created_at }`
    - 200 (download): binario con `Content-Type: {output_mime}` y `Content-Disposition: inline; filename="{filename}"`
    - 403/404/500: `application/json` → `{ ok: false, error: "...", code: "FORBIDDEN|NOT_FOUND|SERVER_ERROR" }`

- POST `/usuario/campana/{campaign_id}/adjuntar_ai/{generation_id}` (publicar asset IA en campaña)
  - Auth: requerida. Permisos: dueño de la campaña (`campanas.id_usuario`) y dueño de la generación (`ai_generations.user_id`), o admin.
  - Path params: `campaign_id` (INT), `generation_id` (INT).
  - Body JSON opcional: `{ type: "image|text" }` (si falta, se usa `ai_generations.type`).
  - Validaciones:
    - Campaña existe y pertenece al usuario; estado `pendiente` o `aprobada`.
    - Generación existe, `status='generated'`, `used_in_campaign_id IS NULL`.
    - Si `type='image'`: copiar `output_path` desde `UPLOAD_DIR_PRIVATE/user_{id}/...` a `UPLOAD_DIR_PUBLIC/campaign_{campaign_id}/` con nombre aleatorio; actualizar `ai_generations.status='published'`, `used_in_campaign_id=campaign_id`.
    - Si `type='text'`: marcar `used_in_campaign_id`; la UI decide inserción en descripción.
  - Respuestas:
    - 200 (image): `{ ok: true, campaign_id, generation_id, type: "image", public_url: "/storage/campaign_{id}/{file}" }`
    - 200 (text): `{ ok: true, campaign_id, generation_id, type: "text" }`
    - 400 (estado/tipo inválido) / 403 (permisos) / 404 (no encontrado) / 409 (ya usado) / 500 (server): `{ ok: false, error: "...", code: "BAD_REQUEST|FORBIDDEN|NOT_FOUND|CONFLICT|SERVER_ERROR" }`

Orden recomendado de implementación
  1) Modelo/tablas (`ai_generations`, `ai_policy_logs`) y `.env` (`UPLOAD_DIR_PRIVATE`, `UPLOAD_DIR_PUBLIC`).
  2) Utilidades de ruta: función que resuelva paths seguros (privado/público) por `user_id` y `campaign_id`.
  3) GET `/file/ai/{id}` (primero `action=meta`, luego `download`).
  4) POST `/usuario/campana/{campaign_id}/adjuntar_ai/{generation_id}` (primero imágenes; luego texto).
  5) Integrar botón “Adjuntar a campaña” en UI de campañas y en listado de generaciones IA del usuario.
