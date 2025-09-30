<?php
$title = $data['title'] ?? 'Perfil';
$user = $data['user'];
$currentUser = $data['current_user'];
$isOwnProfile = $data['is_own_profile'];
$stats = $data['stats'];
$userCampaigns = $data['user_campaigns'] ?? [];
$additionalData = $data['additional_data'] ?? [];

$normalizeCampaign = function ($campaign) {
    $data = is_object($campaign) ? get_object_vars($campaign) : (array)$campaign;
    $data['raised_amount'] = (float)($data['raised_amount'] ?? $data['current_amount'] ?? 0);
    $data['goal_amount'] = (float)($data['goal_amount'] ?? 0);
    $data['status'] = $data['status'] ?? ($data['state'] ?? 'draft');
    $data['slug'] = $data['slug'] ?? ($data['id'] ?? '');
    $data['image_url'] = $data['image_url'] ?? $data['cover_image_url'] ?? APP_URL . '/public/assets/images/campaigns/placeholder.jpg';
    $data['category_name'] = $data['category_name'] ?? $data['category'] ?? 'Causa social';
    $data['summary'] = $data['summary'] ?? ($data['description'] ?? '');
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
    return $campaign['slug'] !== '' ? Router::url('campana/' . $campaign['slug']) : '#';
};
?>

<div class="container py-4">
    <!-- Header del Perfil -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card profile-header">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="profile-avatar">
                                <img src="<?= htmlspecialchars($user['avatar_url'] ?? '/assets/images/default-avatar.png') ?>" 
                                     alt="<?= htmlspecialchars($user['first_name']) ?>" 
                                     class="rounded-circle">
                            </div>
                        </div>
                        <div class="col">
                            <h1 class="h3 mb-1">
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge badge-primary ms-2">Admin</span>
                                <?php endif; ?>
                            </h1>
                            <?php if (!empty($user['bio'])): ?>
                                <p class="text-muted mb-2"><?= htmlspecialchars($user['bio']) ?></p>
                            <?php endif; ?>
                            <div class="profile-meta">
                                <span class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Miembro desde <?= $stats['member_since'] ?>
                                </span>
                                <?php if (!empty($user['location'])): ?>
                                    <span class="text-muted ms-3">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($user['location']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($user['website'])): ?>
                                    <span class="ms-3">
                                        <a href="<?= htmlspecialchars($user['website']) ?>" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-globe me-1"></i>
                                            Sitio Web
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($isOwnProfile): ?>
                            <div class="col-auto">
                                <div class="d-flex gap-2">
                                    <a href="/user/edit-profile" class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-2"></i>Editar Perfil
                                    </a>
                                    <a href="/user/settings" class="btn btn-outline-secondary">
                                        <i class="fas fa-cog me-2"></i>Configuración
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Redes Sociales -->
    <?php if (!empty($user['social_facebook']) || !empty($user['social_twitter']) || !empty($user['social_instagram'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="mb-3">Sígueme en redes sociales</h6>
                        <div class="social-links">
                            <?php if (!empty($user['social_facebook'])): ?>
                                <a href="<?= htmlspecialchars($user['social_facebook']) ?>" target="_blank" class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fab fa-facebook-f me-1"></i>Facebook
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($user['social_twitter'])): ?>
                                <a href="<?= htmlspecialchars($user['social_twitter']) ?>" target="_blank" class="btn btn-outline-info btn-sm me-2">
                                    <i class="fab fa-twitter me-1"></i>Twitter
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($user['social_instagram'])): ?>
                                <a href="<?= htmlspecialchars($user['social_instagram']) ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                                    <i class="fab fa-instagram me-1"></i>Instagram
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card text-center">
                <div class="card-body">
                    <div class="stats-icon bg-primary-light mb-2">
                        <i class="fas fa-bullhorn text-primary"></i>
                    </div>
                    <h4 class="stats-number text-primary"><?= number_format($stats['campaigns_created']) ?></h4>
                    <p class="stats-label mb-0">Campañas Creadas</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card text-center">
                <div class="card-body">
                    <div class="stats-icon bg-success-light mb-2">
                        <i class="fas fa-dollar-sign text-success"></i>
                    </div>
                    <h4 class="stats-number text-success">$<?= number_format($stats['total_raised'], 2) ?></h4>
                    <p class="stats-label mb-0">Total Recaudado</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card text-center">
                <div class="card-body">
                    <div class="stats-icon bg-info-light mb-2">
                        <i class="fas fa-heart text-info"></i>
                    </div>
                    <h4 class="stats-number text-info"><?= number_format($stats['campaigns_funded']) ?></h4>
                    <p class="stats-label mb-0">Campañas Apoyadas</p>
                </div>
            </div>
        </div>
        
        <?php if ($isOwnProfile && isset($additionalData['total_donated'])): ?>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stats-icon bg-warning-light mb-2">
                            <i class="fas fa-hand-holding-heart text-warning"></i>
                        </div>
                        <h4 class="stats-number text-warning">$<?= number_format($additionalData['total_donated'], 2) ?></h4>
                        <p class="stats-label mb-0">Total Donado</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stats-icon bg-secondary-light mb-2">
                            <i class="fas fa-calendar text-secondary"></i>
                        </div>
                        <h4 class="stats-number text-secondary"><?= $stats['member_since'] ?></h4>
                        <p class="stats-label mb-0">Miembro Desde</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Campañas del Usuario -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-bullhorn me-2"></i>
                        <?= $isOwnProfile ? 'Mis Campañas' : 'Campañas de ' . htmlspecialchars($user['first_name']) ?>
                    </h5>
                    <?php if ($isOwnProfile): ?>
                        <a href="/user/campaigns" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($userCampaigns)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bullhorn fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">
                                <?= $isOwnProfile ? 'No has creado campañas aún' : 'Este usuario no ha creado campañas públicas' ?>
                            </h5>
                            <?php if ($isOwnProfile): ?>
                                <p class="text-muted mb-3">¡Crea tu primera campaña y comienza a recaudar fondos!</p>
                                <a href="/campaigns/create" class="btn btn-primary">Crear Campaña</a>
                            <?php endif; ?>
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
                                <div class="col-md-6 mb-4">
                                    <div class="card campaign-card h-100">
                                        <div class="campaign-image">
                                            <img src="<?= htmlspecialchars($campaignData['image_url']) ?>"
                                                 alt="<?= htmlspecialchars($campaignData['title'] ?? 'Campaña') ?>"
                                                 class="card-img-top">
                                            <div class="campaign-status">
                                                <span class="badge badge-<?= $statusBadgeClass ?>">
                                                    <?= htmlspecialchars($statusLabel) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title">
                                                <a href="<?= htmlspecialchars($url) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($campaignData['title'] ?? 'Campaña sin título') ?>
                                                </a>
                                            </h6>
                                            <p class="card-text text-muted small flex-grow-1">
                                                <?= htmlspecialchars(mb_strlen($campaignData['summary']) > 100 ? mb_substr($campaignData['summary'], 0, 100) . '…' : $campaignData['summary']) ?>
                                            </p>

                                            <div class="campaign-progress mb-3">
                                                <div class="progress mb-2" style="height: 8px;">
                                                    <div class="progress-bar bg-success" style="width: <?= $percentage ?>%"></div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-success fw-bold">$<?= $raisedLabel ?></span>
                                                    <span class="text-muted">de $<?= $goalLabel ?></span>
                                                </div>
                                                <div class="text-center mt-1">
                                                    <small class="text-muted"><?= $percentageLabel ?>% completado</small>
                                                </div>
                                            </div>
                                            
                                            <!-- Información adicional -->
                                            <div class="campaign-meta">
                                                <div class="d-flex justify-content-between text-muted small">
                                                    <span>
                                                        <i class="fas fa-users me-1"></i>
                                                        <?= number_format($campaign['donors_count'] ?? 0) ?> donantes
                                                    </span>
                                                    <span>
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php
                                                        $daysLeft = max(0, ceil((strtotime($campaign['end_date']) - time()) / (60 * 60 * 24)));
                                                        echo $daysLeft > 0 ? $daysLeft . ' días restantes' : 'Finalizada';
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($userCampaigns) >= 6): ?>
                            <div class="text-center mt-3">
                                <a href="<?= $isOwnProfile ? '/user/campaigns' : '/campaigns?user=' . $user['id'] ?>" 
                                   class="btn btn-outline-primary">
                                    Ver Más Campañas
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <?php if ($isOwnProfile && !empty($additionalData['recent_donations'])): ?>
                <!-- Donaciones Recientes (solo perfil propio) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-heart me-2"></i>Donaciones Recientes
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($additionalData['recent_donations'], 0, 5) as $donation): ?>
                                <div class="list-group-item px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 small">
                                                <a href="/campaigns/<?= $donation['campaign_id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars(substr($donation['campaign_title'], 0, 30)) ?>...
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                $<?= number_format($donation['amount'], 2) ?> • 
                                                <?= date('d/m/Y', strtotime($donation['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="/user/donations" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Información de Contacto -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <strong>Miembro desde:</strong><br>
                        <span class="text-muted"><?= date('F Y', strtotime($user['created_at'])) ?></span>
                    </div>
                    
                    <?php if (!empty($user['location'])): ?>
                        <div class="info-item mb-3">
                            <strong>Ubicación:</strong><br>
                            <span class="text-muted"><?= htmlspecialchars($user['location']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($user['privacy_show_email'] || $isOwnProfile): ?>
                        <div class="info-item mb-3">
                            <strong>Email:</strong><br>
                            <span class="text-muted"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['phone']) && $isOwnProfile): ?>
                        <div class="info-item mb-3">
                            <strong>Teléfono:</strong><br>
                            <span class="text-muted"><?= htmlspecialchars($user['phone']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <strong>Estado:</strong><br>
                        <span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($user['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-header {
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.profile-avatar img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.stats-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.stats-number {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stats-label {
    color: #6c757d;
    font-weight: 500;
    font-size: 0.9rem;
}

.bg-primary-light { background-color: rgba(13, 110, 253, 0.1); }
.bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
.bg-info-light { background-color: rgba(13, 202, 240, 0.1); }
.bg-warning-light { background-color: rgba(255, 193, 7, 0.1); }
.bg-secondary-light { background-color: rgba(108, 117, 125, 0.1); }

.campaign-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.campaign-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.campaign-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.campaign-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.campaign-status {
    position: absolute;
    top: 10px;
    right: 10px;
}

.social-links a {
    margin: 0 0.25rem;
}

.info-item {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.75rem;
}

.info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.badge-active { background-color: #28a745; }
.badge-inactive { background-color: #6c757d; }
.badge-suspended { background-color: #dc3545; }

@media (max-width: 768px) {
    .profile-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .profile-avatar img {
        width: 80px;
        height: 80px;
    }
    
    .stats-number {
        font-size: 1.25rem;
    }
}
</style>