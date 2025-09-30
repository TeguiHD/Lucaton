## IA — Extensiones sugeridas (viables en 1.5 meses)

Objetivo: fortalecer el aporte de ingeniería y la justificación de tesis sin aumentar demasiado la complejidad. Todas se implementan server‑side (PHP cURL) y son opcionales por prioridad.

1) Moderación de contenido asistida por IA (ligera)
- Qué: verificación previa de texto (tono ofensivo, contenido sensible, spam) para ayudar la revisión humana (no bloquea automáticamente).
- Implementación: endpoint `POST /ai/moderate_text.php` que recibe `texto`, consulta un clasificador (p. ej., endpoint de moderación) y devuelve flags.
- UI: en el formulario de campaña, mostrar "Observaciones de moderación" si hay flags.
- Datos: guardar resultado en `campanas.ai_flags` (JSON) u otra tabla auxiliar.
- Valor de tesis: evidencia de mejora en calidad y seguridad percibida.

2) Alt‑text automático para accesibilidad (visión)
- Qué: generar descripción corta de la imagen para `alt` (mejora accesibilidad/SEO). Usuario puede editarla.
- Implementación: endpoint `POST /ai/alt_text.php` (modelo visión/captioning) usando la imagen subida.
- UI: campo "Descripción alternativa" prellenado con botón "Regenerar".
- Datos: columna `campanas.alt_text` (VARCHAR(255)).
- Valor de tesis: accesibilidad medible (criterios WCAG + evaluación de claridad por usuarios).

3) Sugerencia de etiquetas y categorías (descubribilidad)
- Qué: extraer palabras clave y sugerir categorías (salud, vivienda, educación, etc.).
- Implementación: endpoint `POST /ai/suggest_tags.php` que recibe título+descripción y devuelve `{tags:[...], categoria:"..."}`.
- UI: chips seleccionables; el usuario puede aceptar/editar.
- Datos: columna `campanas.tags` (JSON) o tabla `campana_tags` si prefieres normalizar.
- Valor de tesis: mejora de búsqueda/filtrado y navegación temática.

4) Variantes A/B de títulos y CTAs (evaluación)
- Qué: generar 2–3 variantes de título/CTA y permitir elegir una.
- Implementación: ampliar `generate_text.php` para devolver múltiples opciones.
- UI: selector de variante; registrar la selección final.
- Datos: en `campanas` (p. ej., `titulo_variant` y `cta_variant`), + métrica de intención de compartir.
- Valor de tesis: comparación cuantitativa (claridad/atractivo) y tiempos de redacción.

5) "Campañas similares" (recomendación simplificada)
- Qué: mostrar 3 campañas similares para fomentar descubrimiento y comunidad.
- Implementación: endpoint `POST /ai/embeddings.php` (modelo de embeddings); guardar vector (JSON) en `campanas.embedding` y calcular similitud coseno en PHP (dataset pequeño).
- UI: sección en detalle de campaña.
- Valor de tesis: navegación basada en contenido, justificable técnicamente.

6) Pack de difusión social (ampliado)
- Qué: generar textos para IG/FB y X, 5–8 hashtags y una versión corta para WhatsApp; opcional: imagen IA recortada 1:1.
- Implementación: extender `generate_text.php` + ya existente `generate_image.php`.
- UI: bloque "Compartir" con copiar/pegar.
- Valor de tesis: impacto en alcance (métrica Likert + clics de CTA en pruebas).

SQL sugerido (campos ligeros, opcionales)
```sql
ALTER TABLE campanas
  ADD COLUMN alt_text VARCHAR(255) NULL,
  ADD COLUMN tags JSON NULL,
  ADD COLUMN ai_flags JSON NULL;

-- Para embeddings (opcional, dataset pequeño)
ALTER TABLE campanas
  ADD COLUMN embedding JSON NULL;
```

Endpoints sugeridos
- POST `/ai/moderate_text.php` → flags
- POST `/ai/alt_text.php` → descripción alternativa
- POST `/ai/suggest_tags.php` → tags/categoría
- POST `/ai/embeddings.php` → vector (para guardar tras crear/editar campaña)

Prioridad recomendada (de mayor a menor)
1) Alt‑text automático
2) Moderación asistida
3) Sugerencia de etiquetas/categorías
4) Variantes A/B de títulos/CTAs
5) Pack de difusión social ampliado
6) Campañas similares (embeddings)

Notas de seguridad/ética
- Siempre revisión humana final (admin) antes de publicar.
- Mostrar aviso “Contenido generado con IA” cuando aplique.
- No exponer API keys en el cliente; todo por servidor.
 - Limpieza de generados no usados a los 30 días; conservar denegados 90 días.

7) Asistente tipo wizard para creación (mitigar brecha digital)
- Qué: flujo paso a paso con preguntas aclaratorias (objetivo, uso de fondos, plazos, evidencias, contacto), con sugerencias IA en cada paso.
- Implementación: reutilizar `generate_text.php` con prompts seccionales; almacenar estado en sesión hasta enviar.
- UI: 4–5 pasos con progreso y checklist de transparencia.
- Valor de tesis: evidencia de reducción de carga mental y aumento de completitud.

8) Chatbot N8N (FAQ y guía)
- Qué: chatbot embebido con respuestas a dudas frecuentes (creación, proceso de revisión, pagos simulados, transparencia, privacidad).
- Implementación: iframe del flujo de N8N; CSP `frame-src` ya contemplado; conocimiento base en páginas `/faq` y `/transparencia` para respuestas consistentes.
- Valor de tesis: soporte continuo que reduce fricción y dudas.
