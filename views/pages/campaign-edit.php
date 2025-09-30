<?php
ob_start();
?>
<div class="container py-8">
  <div class="card">
    <div class="card-header">Editar campaña</div>
    <div class="card-body">
      <p>Edición de campaña aún no implementada.</p>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
?>

