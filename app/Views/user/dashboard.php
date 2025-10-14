<?php
$title = $data['title'] ?? 'Dashboard';
$user = $data['user'];
$stats = $data['stats'];
$userCampaigns = $data['user_campaigns'] ?? [];
$recentDonations = $data['recent_donations'] ?? [];
$recommendedCampaigns = $data['recommended_campaigns'] ?? [];
$recentActivity = $data['recent_activity'] ?? [];

$normalizeCampaign = function ($campaign) {
    $data = is_object($campaign) ? get_object_vars($campaign) : (array)$campaign;
    $data['raised_amount'] = (float)($data['raised_amount'] ?? $data['current_amount'] ?? 0);
    $data['goal_amount'] = (float)($data['goal_amount'] ?? 0);
    $data['status'] = $data['status'] ?? ($data['state'] ?? 'draft');
    $data['slug'] = $data['slug'] ?? ($data['id'] ?? '');
    $data['image_url'] = $data['image_url'] ?? $data['cover_image_url'] ?? APP_URL . '/public/assets/images/campaigns/placeholder.jpg';
    $data['category_name'] = $data['category_name'] ?? $data['category'] ?? 'Causa social';
    $data['summary'] = $data['summary'] ?? ($data['description'] ?? '');
    if (!isset($data['public_path'])) {
        $data['public_path'] = CampaignPresenter::buildPublicPath($data);
    }
    return $data;
};

$campaignProgress = function (array $campaign) {
    $goal = $campaign['goal_amount'] ?: 0;
    if ($goal <= 0) {
        return 0;
    }
    return min(100, ($campaign['raised_amount'] / $goal) * 100);
};

$campaignUrl = function (array $campaign) {
    if (!empty($campaign['public_path'])) {
        return Router::url($campaign['public_path']);
    }

    return $campaign['slug'] !== '' ? Router::url('campana/' . $campaign['slug']) : '#';
};
?>

<div class="container-fluid py-4">
    <!-- Header del Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">¡Hola, <?= htmlspecialchars($user['first_name']) ?>! 👋</h1>
                    <p class="text-muted mb-0">Aquí tienes un resumen de tu actividad en Lucatón</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="/campaigns/create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nueva Campaña
                    </a>
                    <a href="/user/profile" class="btn btn-outline-secondary">
                        <i class="fas fa-user me-2"></i>Mi Perfil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Principales -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center">
                    <div class="stats-icon bg-primary-light mb-3">
                        <i class="fas fa-bullhorn text-primary"></i>
                    </div>
                    <h3 class="stats-number text-primary"><?= number_format($stats['campaigns_created']) ?></h3>
                    <p class="stats-label mb-0">Campañas Creadas</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center">
                    <div class="stats-icon bg-success-light mb-3">
                        <i class="fas fa-heart text-success"></i>
                    </div>
                    <h3 class="stats-number text-success">$<?= number_format($stats['total_donated'], 2) ?></h3>
                    <p class="stats-label mb-0">Total Donado</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center">
                    <div class="stats-icon bg-info-light mb-3">
                        <i class="fas fa-hand-holding-heart text-info"></i>
                    </div>
                    <h3 class="stats-number text-info"><?= number_format($stats['donations_count']) ?></h3>
                    <p class="stats-label mb-0">Donaciones Realizadas</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center">
                    <div class="stats-icon bg-warning-light mb-3">
                        <i class="fas fa-users text-warning"></i>
                    </div>
                    <h3 class="stats-number text-warning"><?= number_format($stats['campaigns_funded']) ?></h3>
                    <p class="stats-label mb-0">Campañas Apoyadas</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna Principal -->
        <div class="col-lg-8">
            <!-- Mis Campañas Recientes -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-bullhorn me-2"></i>Mis Campañas Recientes
                    </h5>
                    <a href="/user/campaigns" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                </div>
                <div class="card-body">
                    <?php if (empty($userCampaigns)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No has creado campañas aún</h6>
                            <p class="text-muted mb-3">¡Crea tu primera campaña y comienza a recaudar fondos!</p>
                            <a href="/campaigns/create" class="btn btn-primary">Crear Campaña</a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($userCampaigns as $campaign): ?>
                                <?php
                                    $campaignData = $normalizeCampaign($campaign);
                                    $percentage = $campaignProgress($campaignData);
                                    $percentageLabel = number_format($percentage, 1);
                                    $raisedLabel = number_format($campaignData['raised_amount'], 2);
                                    $goalLabel = number_format($campaignData['goal_amount'], 2);
                                    $statusLabel = class_exists('CampaignPresenter') ? CampaignPresenter::statusLabel($campaignData['status']) : ucfirst($campaignData['status']);
                                    $statusBadgeClass = in_array($campaignData['status'], ['published', 'active']) ? 'success' : 'secondary';
                                    $url = $campaignUrl($campaignData);
                                ?>
                                <div class="col-md-6 mb-3">
                                    <div class="campaign-card-mini">
                                        <div class="d-flex">
                                            <div class="campaign-image-mini me-3">
                                                <img src="<?= htmlspecialchars($campaignData['image_url']) ?>"
                                                     alt="<?= htmlspecialchars($campaignData['title'] ?? 'Campaña') ?>"
                                                     class="img-fluid rounded">
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <a href="<?= htmlspecialchars($url) ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($campaignData['title'] ?? 'Campaña sin título') ?>
                                                    </a>
                                                </h6>
                                                <div class="progress mb-2" style="height: 6px;">
                                                    <div class="progress-bar" style="width: <?= $percentage ?>%"></div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        $<?= $raisedLabel ?> / $<?= $goalLabel ?>
                                                    </small>
                                                    <span class="badge badge-<?= $statusBadgeClass ?>">
                                                        <?= htmlspecialchars($statusLabel) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Donaciones Recientes -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-heart me-2"></i>Donaciones Recientes
                    </h5>
                    <a href="/user/donations" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentDonations)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No has realizado donaciones aún</h6>
                            <p class="text-muted mb-3">¡Explora las campañas y apoya las causas que te importan!</p>
                            <a href="/campaigns" class="btn btn-primary">Explorar Campañas</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentDonations as $donation): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <a href="/campaigns/<?= $donation['campaign_id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($donation['campaign_title']) ?>
                                                </a>
                                            </h6>
                                            <p class="mb-1 text-muted small">
                                                Donación de $<?= number_format($donation['amount'], 2) ?>
                                                <?php if ($donation['is_anonymous']): ?>
                                                    <span class="badge badge-secondary ms-1">Anónima</span>
                                                <?php endif; ?>
                                            </p>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($donation['created_at'])) ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-<?= $donation['status'] === 'completed' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($donation['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Actividad Reciente -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Actividad Reciente
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivity)): ?>
                        <p class="text-muted text-center py-3">No hay actividad reciente</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-<?= $activity['type'] === 'donation' ? 'success' : 'primary' ?>">
                                        <i class="fas fa-<?= $activity['type'] === 'donation' ? 'heart' : 'bullhorn' ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <p class="mb-1">
                                            <a href="<?= htmlspecialchars($activity['url']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($activity['description']) ?>
                                            </a>
                                        </p>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($activity['date'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Campañas Recomendadas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>Campañas Recomendadas
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recommendedCampaigns)): ?>
                        <p class="text-muted text-center py-3">No hay recomendaciones disponibles</p>
                    <?php else: ?>
                        <?php foreach (array_slice($recommendedCampaigns, 0, 3) as $campaign): ?>
                            <?php
                                $campaignData = $normalizeCampaign($campaign);
                                $percentage = $campaignProgress($campaignData);
                                $title = $campaignData['title'] ?? 'Campaña sin título';
                                $titleShort = mb_strlen($title) > 50 ? mb_substr($title, 0, 50) . '…' : $title;
                                $url = $campaignUrl($campaignData);
                            ?>
                            <div class="recommended-campaign mb-3">
                                <div class="d-flex">
                                    <div class="recommended-image me-3">
                                        <img src="<?= htmlspecialchars($campaignData['image_url']) ?>"
                                             alt="<?= htmlspecialchars($title) ?>"
                                             class="img-fluid rounded">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="<?= htmlspecialchars($url) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($titleShort) ?>
                                            </a>
                                        </h6>
                                        <div class="progress mb-1" style="height: 4px;">
                                            <div class="progress-bar" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            $<?= number_format($campaignData['raised_amount'], 2) ?> recaudados
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="/campaigns" class="btn btn-sm btn-outline-primary">Ver Más Campañas</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.stats-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stats-label {
    color: #6c757d;
    font-weight: 500;
}

.bg-primary-light { background-color: rgba(13, 110, 253, 0.1); }
.bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
.bg-info-light { background-color: rgba(13, 202, 240, 0.1); }
.bg-warning-light { background-color: rgba(255, 193, 7, 0.1); }

.campaign-card-mini {
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    transition: box-shadow 0.2s;
}

.campaign-card-mini:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.campaign-image-mini {
    width: 60px;
    height: 60px;
    overflow: hidden;
}

.campaign-image-mini img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.timeline {
    position: relative;
}

.timeline-item {
    display: flex;
    margin-bottom: 1.5rem;
    position: relative;
}

.timeline-marker {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.timeline-content {
    flex-grow: 1;
    padding-top: 0.25rem;
}

.recommended-campaign {
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.recommended-campaign:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.recommended-image {
    width: 50px;
    height: 50px;
    overflow: hidden;
}

.recommended-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.badge-active { background-color: #28a745; }
.badge-completed { background-color: #6c757d; }
.badge-paused { background-color: #ffc107; }
.badge-cancelled { background-color: #dc3545; }
</style>
