<?php
$page_title = $page_title ?? 'Editar noticia';
$additional_head = $additional_head ?? '';
$errors = $errors ?? [];
$old = $old ?? [];
$categories = $categories ?? [];
$is_edit = true;

ob_start();
?>
<form action="<?= Router::url('admin/news/' . $article['id'] . '/update') ?>" method="POST" enctype="multipart/form-data" class="space-y-6" id="news-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
    <?php include __DIR__ . '/_form.php'; ?>
    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
        <a href="<?= Router::url('admin/news') ?>" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">Guardar cambios</button>
    </div>
</form>
<?php
$content = ob_get_clean();

ob_start();
?>
<script>
(function() {
    const form = document.getElementById('news-form');
    const editor = document.getElementById('news-editor');
    const hiddenField = document.getElementById('content_html');
    const buttons = document.querySelectorAll('[data-command]');

    buttons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const command = button.dataset.command;
            const value = button.dataset.value || null;
            editor.focus();
            document.execCommand(command, false, value);
        });
    });

    form.addEventListener('submit', () => {
        hiddenField.value = editor.innerHTML;
    });
})();
</script>
<?php
$additional_scripts = ob_get_clean();

include VIEWS_PATH . '/layouts/admin.php';
?>
