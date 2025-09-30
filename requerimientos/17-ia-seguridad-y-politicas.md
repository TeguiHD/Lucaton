## IA — Seguridad, Políticas y Agentes

Objetivo: asegurar un uso responsable de IA, con moderación previa, trazabilidad y control de acceso a los artefactos generados (texto/imagen), vinculando cada creación al usuario para evidencia y cumplimiento.

### 1) Normas de contenido (resumen)
- Prohibido: contenido sexual con menores, violencia explícita/extrema, incitación al odio, doxxing, fraude, actividades ilegales.
- Sensible: salud/finanzas/legales → tono informativo, evitar diagnósticos o promesas; sugerir consultar profesionales.
- Privacidad: evitar datos personales sensibles; anonimizar cuando sea apropiado.
- Dignidad: comunicación respetuosa, no manipuladora, no sensacionalista.

### 2) Agente global (System Prompt)
Aplicar a todos los servicios (texto/imagen/chat):
```
Eres el "Estratega de Impacto Social" de Lucatón (Chile). Tu misión es ayudar a crear campañas claras, dignas y transparentes.
Normas:
- Persuasión ética: evita manipulación, exageraciones o falsas promesas.
- Transparencia: exige una sección con uso de fondos, plazos estimados, evidencias y contacto del responsable.
- Privacidad: no incluyas datos sensibles; sugiere anonimizar si es necesario.
- Seguridad: si el contenido solicitado viola la política (sexual infantil, odio, violencia extrema, ilegal), rechaza con explicación breve y ofrece alternativas seguras.
Formato:
- Si detectas violación de política: responde "DENEGADO" + lista de políticas afectadas + sugerencias para reformular.
- Si es permitido: continúa con la tarea siguiendo el tono y estructura solicitada.
```

### 3) Flujo seguro por tipo
- Texto (generate_text.php):
  1) Pre‑chequeo (clasificación) con el Agente global. Si "DENEGADO": registrar en `ai_generations` (status=denied, policy_flags) y devolver mensaje de política.
  2) Si permitido: generar texto con sección de Transparencia y recomendaciones de evidencia.
  3) Post‑chequeo opcional (buscar términos prohibidos) y registro en `ai_generations` (status=generated, prompt_used, input_text).

- Imagen (generate_image.php):
  1) Pre‑chequeo textual (prompt) con el Agente global. Si "DENEGADO": registrar/denegar.
  2) Si permitido: generar imagen (Gemini). Guardar en `UPLOAD_DIR_PRIVATE/user_{id}/` con nombre aleatorio; registrar en `ai_generations` (type=image, output_path, status=generated).
  3) Alt‑text automático opcional y registro.

- Chatbot (N8N embebido):
  - Instrucciones base alineadas al Agente global; derivar a `/faq` y `/transparencia` cuando aplique; no dar consejos médicos/legales.

### 4) Publicación y control de acceso
- Por defecto, todas las generaciones IA son privadas y accesibles sólo por su autor y administradores.
- Al adjuntar una generación IA a una campaña:
  - Si la campaña está en "pendiente": permanece privada (sólo autor y admin).
  - Si es "aprobada": crear copia pública en `UPLOAD_DIR_PUBLIC/campaign_{id}/` (nombre aleatorio) y actualizar `ai_generations.status=published` + `used_in_campaign_id`.
- Mantener el original privado como evidencia.
- Servir privados desde un endpoint PHP (p. ej., `/file/ai/{id}`) que valida sesión y permisos (owner/admin).

### 5) Datos y trazabilidad (tablas)
- `ai_generations`: vincula cada creación a `user_id`, guarda `input_text`, `prompt_used`, `output_path`, `status`, `policy_flags`, `used_in_campaign_id`.
- `ai_policy_logs`: logs de políticas violadas.
- Campos opcionales en `campanas`: `alt_text`, `tags`, `ai_flags`, `embedding`.

### 6) Endpoints relacionados
- POST `/ai/moderate_text.php` → Pre‑chequeo de políticas (opcional; integrado en generate_text).
- POST `/usuario/campana/{id}/adjuntar_ai/{generation_id}` → Adjuntar y publicar (si aprueban) la generación.
- GET  `/file/ai/{id}` → Servir asset privado (owner/admin).
- Admin: listado de `ai_generations` con filtros por estado y políticas.

### 7) Retención y auditoría
- Conservar generaciones denegadas por 90 días como evidencia, visibles sólo para admin.
- Registrar cambios de estado (published/denied) en `ai_generations` y, si aplica, en `auditoria_estados`.

### 8) UX y mensajes de política
- En denegación, devolver razones claras y una propuesta de reformulación segura.
- Marcar "Contenido generado con IA" cuando corresponda.
