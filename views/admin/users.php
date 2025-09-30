<?php
$page_title = $page_title ?? 'Gestión de Usuarios';
$meta_description = 'Gestión de usuarios';
ob_start();
?>
<div class="p-6">
  <div class="card">
    <div class="card-header">Usuarios</div>
    <div class="card-body">Aún no implementado.</div>
  </div>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>

