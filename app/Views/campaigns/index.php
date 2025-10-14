<?php
$title = 'Campañas - Lucatón';
$description = 'Descubre y apoya proyectos innovadores en nuestra plataforma de crowdfunding';

ob_start();
?>

<?php
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$availableCategories = $categories ?? [
    'education' => 'Educación',
    'health' => 'Salud y bienestar',
    'environment' => 'Medio ambiente',
    'community' => 'Comunidad y barrio',
    'entrepreneurship' => 'Emprendimiento social',
    'emergency' => 'Emergencias',
    'arts' => 'Arte y cultura',
    'technology' => 'Tecnología solidaria',
    'sports' => 'Deporte y recreación',
    'animals' => 'Protección animal',
    'other' => 'Otras causas'
];
$availableStatuses = [
    '' => 'Todos los estados',
    'published' => 'Activas',
    'completed' => 'Completadas',
    'under_review' => 'En revisión',
    'paused' => 'Pausadas'
];
?>

<div class="container py-4">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="bg-primary text-white rounded-3 p-5 text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-rocket me-3"></i>
                    Descubre Proyectos Increíbles
                </h1>
                <p class="lead mb-4">
                    Apoya ideas innovadoras y ayuda a hacer realidad los sueños de emprendedores de todo el mundo
                </p>
                <a href="<?= Router::url('/campaigns/create') ?>" class="btn btn-light btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Crear Tu Campaña
                </a>
            </div>
        </div>
    </div>
    
    <!-- Filtros y búsqueda -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="<?= Router::url('/campaigns') ?>" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Buscar campañas..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Categoría</label>
                            <select class="form-select" id="category" name="category">
                                <option value=""<?= $categoryFilter === '' ? ' selected' : '' ?>>Todas las categorías</option>
                                <?php foreach ($availableCategories as $slug => $label): ?>
                                    <option value="<?= htmlspecialchars($slug) ?>" <?= $categoryFilter === $slug ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach ($availableStatuses as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>
                                Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lista de campañas -->
    <div class="row">
        <?php if (empty($campaigns)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No se encontraron campañas</h3>
                    <p class="text-muted">Intenta ajustar los filtros de búsqueda o crear una nueva campaña.</p>
                    <a href="<?= Router::url('/campaigns/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Crear Campaña
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($campaigns as $campaign): ?>
                <?php
                    $campaignData = is_object($campaign) ? get_object_vars($campaign) : (array)$campaign;
                    $title = $campaignData['title'] ?? 'Campaña sin título';
                    $summary = $campaignData['summary'] ?? ($campaignData['description'] ?? '');
                    $imageUrl = $campaignData['image_url'] ?? $campaignData['cover_image_url'] ?? APP_URL . '/public/assets/images/campaigns/placeholder.jpg';
                    $ownerName = $campaignData['owner_name'] ?? $campaignData['creator_name'] ?? $campaignData['username'] ?? 'Campañista';
                    $categoryName = $campaignData['category_name'] ?? $campaignData['category'] ?? 'Causa social';
                    $raised = (float)($campaignData['raised_amount'] ?? $campaignData['current_amount'] ?? $campaignData['raised'] ?? 0);
                    $goal = (float)($campaignData['goal_amount'] ?? 0);
                    $percentage = $goal > 0 ? min(100, ($raised / $goal) * 100) : 0;
                    $percentageLabel = number_format($percentage, 1);
                    $raisedLabel = number_format($raised, 0);
                    $goalLabel = number_format($goal, 0);
                    $endDate = $campaignData['end_date'] ?? null;
                    $daysLeft = null;
                    if (!empty($endDate)) {
                        $timestamp = strtotime($endDate);
                        if ($timestamp !== false) {
                            $daysLeft = max(0, ceil(($timestamp - time()) / 86400));
                        }
                    }
                    $campaignId = $campaignData['id'] ?? null;
                    $slug = $campaignData['slug'] ?? ($campaignId !== null ? (string)$campaignId : '');
                    $campaignPublicPath = $campaignData['public_path'] ?? CampaignPresenter::buildPublicPath($campaignData);
                    $campaignUrl = $campaignPublicPath ? Router::url($campaignPublicPath) : ($slug !== '' ? Router::url('campana/' . $slug) : '#');
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="position-relative">
                            <?php if (!empty($imageUrl)): ?>
                                <img src="<?= htmlspecialchars($imageUrl) ?>"
                                     class="card-img-top" alt="<?= htmlspecialchars($title) ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center"
                                     style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <span class="badge bg-primary position-absolute top-0 end-0 m-2">
                                <?= htmlspecialchars($categoryName) ?>
                            </span>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <a href="<?= htmlspecialchars($campaignUrl) ?>"
                                   class="text-decoration-none">
                                    <?= htmlspecialchars($title) ?>
                                </a>
                            </h5>

                            <p class="card-text text-muted flex-grow-1">
                                <?= htmlspecialchars(mb_strlen($summary) > 120 ? mb_substr($summary, 0, 120) . '…' : $summary) ?>
                            </p>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Recaudado</small>
                                    <small class="fw-bold"><?= $percentageLabel ?>%</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: <?= $percentage ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-success fw-bold">
                                        $<?= $raisedLabel ?>
                                    </small>
                                    <small class="text-muted">
                                        de $<?= $goalLabel ?>
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= $daysLeft !== null ? ($daysLeft > 0 ? $daysLeft . ' días' : 'Finalizada') : 'Sin fecha límite' ?>
                                </small>
                                <a href="<?= htmlspecialchars($campaignUrl) ?>"
                                   class="btn btn-outline-primary btn-sm">
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Paginación -->
    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="row">
            <div class="col-12">
                <nav aria-label="Paginación de campañas">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= Router::url('/campaigns', array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>">
                                    Anterior
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= Router::url('/campaigns', array_merge($_GET, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= Router::url('/campaigns', array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>">
                                    Siguiente
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
?>
