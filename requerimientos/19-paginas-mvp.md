## Páginas mínimas del MVP

Público
- `/` (Inicio): grid de campañas aprobadas (paginado).
- `/campana/{id}` (Detalle): imagen, historia, barra de progreso, donadores recientes (simulados), botón Donar.
- `/faq`, `/transparencia`, `/terminos`, `/privacidad`.

Autenticación
- `/login`, `/registro`, `/logout`.

Usuario (protegidas)
- `/usuario/panel`: listado de campañas propias por estado + acciones.
- `/usuario/campana/nueva`: formulario (con IA y evidencias).
- `/usuario/campana/{id}/editar` (opcional).
- `/usuario/campana/{id}/apelar` (POST).

Admin (protegidas)
- `/admin`: dashboard simple.
- `/admin/campanas?estado=...`, `/admin/campana/{id}`.
- Acciones POST en campañas y usuarios.

IA/archivos
- `POST /generate_text.php`, `POST /generate_image.php`.
- `GET /file/ai/{id}` (privado owner/admin).
- `POST /usuario/campana/{id}/adjuntar_ai/{generation_id}`.
