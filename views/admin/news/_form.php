<?php
$errors = $errors ?? [];
$old = $old ?? [];
$is_edit = $is_edit ?? false;
$article = $article ?? null;
$categories = $categories ?? [];

$coverPreview = $old['cover_image'] ?? ($article['cover_image'] ?? null);
$contentValue = $old['content'] ?? ($article['content'] ?? '');
$statusValue = $old['status'] ?? ($article['status'] ?? 'draft');
$categoryIdValue = $old['category_id'] ?? ($article['category_id'] ?? '');
$categoryNameValue = $old['category_name'] ?? '';
$publishedAtValue = $old['published_at'] ?? ($article['published_at'] ?? '');
?>
<div class="space-y-8">
    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="space-y-6">
            <div>
                <label class="form-label" for="title">Título *</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($old['title'] ?? ($article['title'] ?? '')) ?>" class="form-input" required>
                <?php if (!empty($errors['title'])): ?><p class="text-sm text-danger-600 mt-1"><?= htmlspecialchars($errors['title']) ?></p><?php endif; ?>
            </div>

            <div>
                <label class="form-label" for="summary">Resumen breve</label>
                <textarea name="summary" id="summary" rows="3" class="form-textarea" placeholder="Resumen visible en listados"><?= htmlspecialchars($old['summary'] ?? ($article['summary'] ?? '')) ?></textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="category_id">Categoría</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">Selecciona categoría</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= (string)$categoryIdValue === (string)$category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="category_name">Nueva categoría</label>
                    <input type="text" name="category_name" id="category_name" value="<?= htmlspecialchars($categoryNameValue) ?>" class="form-input" placeholder="Crea una nueva categoría">
                    <p class="text-xs text-gray-500 mt-1">Si ingresas una nueva categoría se creará automáticamente.</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="status">Estado</label>
                    <select name="status" id="status" class="form-select">
                        <option value="draft" <?= $statusValue === 'draft' ? 'selected' : '' ?>>Borrador</option>
                        <option value="published" <?= $statusValue === 'published' ? 'selected' : '' ?>>Publicado</option>
                        <option value="archived" <?= $statusValue === 'archived' ? 'selected' : '' ?>>Archivado</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="published_at">Fecha publicación</label>
                    <input type="datetime-local" name="published_at" id="published_at" value="<?= $publishedAtValue ? date('Y-m-d\TH:i', strtotime($publishedAtValue)) : '' ?>" class="form-input">
                </div>
            </div>

            <div>
                <label class="form-label" for="cover_image">Imagen de portada <?= $is_edit ? '' : '*' ?></label>
                <input type="file" name="cover_image" id="cover_image" accept="image/*" class="form-input">
                <p class="text-xs text-gray-500 mt-1">Formatos permitidos: <?= htmlspecialchars(strtoupper(UPLOAD_ALLOWED_TYPES)) ?>. Máx <?= round(UPLOAD_MAX_SIZE / 1048576, 1) ?>MB.</p>
                <?php if (!empty($errors['cover_image'])): ?><p class="text-sm text-danger-600 mt-1"><?= htmlspecialchars($errors['cover_image']) ?></p><?php endif; ?>
                <?php if ($coverPreview): ?>
                    <div class="mt-3 border border-dashed border-gray-300 rounded-lg overflow-hidden">
                        <img src="<?= APP_URL . '/' . ltrim($coverPreview, '/') ?>" alt="Portada" class="w-full h-48 object-cover">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-6">
            <div>
                <label class="form-label">Contenido *</label>
                <div class="border border-gray-200 rounded-lg bg-white shadow-sm">
                    <div class="flex flex-wrap gap-2 border-b border-gray-200 p-3 text-sm text-gray-600">
                        <button type="button" data-command="bold" class="btn-secondary text-xs">Negrita</button>
                        <button type="button" data-command="italic" class="btn-secondary text-xs">Cursiva</button>
                        <button type="button" data-command="underline" class="btn-secondary text-xs">Subrayar</button>
                        <button type="button" data-command="insertUnorderedList" class="btn-secondary text-xs">Lista</button>
                        <button type="button" data-command="insertOrderedList" class="btn-secondary text-xs">Numeración</button>
                        <button type="button" data-command="formatBlock" data-value="h3" class="btn-secondary text-xs">Subtítulo</button>
                        <button type="button" data-command="formatBlock" data-value="blockquote" class="btn-secondary text-xs">Cita</button>
                    </div>
                    <div id="news-editor" contenteditable="true" class="min-h-[280px] p-4 prose prose-sm max-w-none focus:outline-none" data-placeholder="Escribe o pega tu contenido...">
                        <?= $contentValue ?: '<p></p>' ?>
                    </div>
                </div>
                <?php if (!empty($errors['content'])): ?><p class="text-sm text-danger-600 mt-1"><?= htmlspecialchars($errors['content']) ?></p><?php endif; ?>
                <textarea name="content_html" id="content_html" class="hidden"><?= htmlspecialchars($contentValue) ?></textarea>
            </div>

            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-800">Galería de imágenes</h3>
                <?php if ($is_edit && !empty($article['gallery'])): ?>
                    <div class="grid gap-4">
                        <?php foreach ($article['gallery'] as $image): ?>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
                                <div class="flex items-start gap-4">
                                    <div class="w-24 h-24 rounded-lg overflow-hidden border border-gray-100">
                                        <img src="<?= APP_URL . '/' . ltrim($image['image_path'], '/') ?>" alt="Imagen" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1 space-y-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500">Caption</label>
                                            <input type="text" name="existing_gallery[<?= $image['id'] ?>][caption]" value="<?= htmlspecialchars($image['caption'] ?? '') ?>" class="form-input">
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500">Orden</label>
                                                <input type="number" name="existing_gallery[<?= $image['id'] ?>][sort_order]" value="<?= htmlspecialchars((string)$image['sort_order']) ?>" class="form-input w-24">
                                            </div>
                                            <label class="inline-flex items-center gap-2 text-xs text-danger-600">
                                                <input type="checkbox" name="remove_gallery[<?= $image['id'] ?>]" value="1" class="form-checkbox">
                                                Eliminar
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="space-y-2">
                    <label class="form-label" for="gallery_images">Agregar imágenes</label>
                    <input type="file" name="gallery_images[]" id="gallery_images" accept="image/*" multiple class="form-input">
                    <p class="text-xs text-gray-500">Puedes seleccionar varias imágenes. Opcionalmente agrega notas y orden en el listado.</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Captions (opcional)</label>
                            <input type="text" name="gallery_captions[]" class="form-input" placeholder="Caption para la primera imagen">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Orden (opcional)</label>
                            <input type="number" name="gallery_sort_order[]" class="form-input" placeholder="0">
                        </div>
                    </div>
                </div>
                <?php if (!empty($errors['gallery'])): ?><p class="text-sm text-danger-600"><?= htmlspecialchars($errors['gallery']) ?></p><?php endif; ?>
            </div>

            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-800">SEO opcional</h3>
                <div>
                    <label class="form-label" for="meta_title">Meta título</label>
                    <input type="text" name="meta_title" id="meta_title" value="<?= htmlspecialchars($old['meta_title'] ?? ($article['meta_title'] ?? '')) ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="meta_description">Meta descripción</label>
                    <textarea name="meta_description" id="meta_description" rows="2" class="form-textarea"><?= htmlspecialchars($old['meta_description'] ?? ($article['meta_description'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
