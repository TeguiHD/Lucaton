<?php
$page_title = $page_title ?? 'Moderación de Campañas';
$meta_description = 'Moderación de campañas';
ob_start();
?>
<div class="p-6">
  <div class="card">
    <div class="card-header">Listado de campañas pendientes</div>
    <div class="card-body">Aún no implementado.</div>
  </div>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>

