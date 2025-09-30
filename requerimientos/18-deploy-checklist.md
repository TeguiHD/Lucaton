## Checklist de Deploy (Apache + PHP)

Previos
- PHP 8.2+, Apache con `mod_headers` y `mod_rewrite` habilitados.
- Certificado TLS (HTTPS) configurado (si aplica HSTS).

Pasos
1) Subir código del proyecto y `public/assets/app.css` generado (pnpm build prod).
2) Crear `.env` en servidor (nunca subir el local si contiene secretos de dev).
3) Crear estructura `storage/uploads`, `storage/logs`, `storage/private`, `public/storage` con permisos de escritura del usuario del servidor web.
4) Importar BD y usuario mínimo (ver `03-modelo-datos-y-sql.md`).
5) Configurar `.htaccess` raíz con cabeceras/CSP y `.htaccess` en `storage/uploads` (bloquear ejecución).
6) Ajustar `APP_TIMEZONE`, `BASE_URL`, rutas `UPLOAD_DIR_PRIVATE`/`UPLOAD_DIR_PUBLIC`.
7) Verificar cabeceras de seguridad en `/` (CSP, HSTS, X-Frame-Options, nosniff, Referrer-Policy).
8) Pruebas de humo: login/registro, crear campaña, generar texto/imagen (si hay API keys), subida de imagen local, ver listados.
9) Si AI no está configurado (sin API keys): ocultar/deshabilitar botones de IA y mostrar mensaje “IA no disponible temporalmente”.
10) Revisar logs en `storage/logs` tras 10–15 minutos de uso.

Notas
- No compilar Tailwind en el servidor (evitar instalar Node allí). Subir el CSS ya generado.
- Respaldos: exportar BD y `storage/private`/`public/storage` periódicamente.
