<?php
$page_title = $page_title ?? 'Panel de Administración';
$meta_description = 'Panel de control administrativo';
ob_start();
?>
<div class="p-6">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="card">
      <div class="card-header">Campañas en revisión</div>
      <div class="card-body">Próximamente…</div>
    </div>
    <div class="card">
      <div class="card-header">Usuarios</div>
      <div class="card-body">Próximamente…</div>
    </div>
    <div class="card">
      <div class="card-header">Métricas</div>
      <div class="card-body">Próximamente…</div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>

