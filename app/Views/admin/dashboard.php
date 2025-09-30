<?php
$title = 'Panel de Administración';
$stats = $data['stats'];
$recentActivity = $data['recent_activity'];
$topCampaigns = $data['top_campaigns'];
$systemAlerts = $data['system_alerts'];
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Panel de Administración</h1>
            <p class="text-muted mb-0">Bienvenido al centro de control administrativo</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt me-2"></i>Actualizar
            </button>
            <a href="/admin/reports" class="btn btn-primary">
                <i class="fas fa-chart-bar me-2"></i>Ver Reportes
            </a>
        </div>
    </div>

    <!-- Alertas del Sistema -->
    <?php if (!empty($systemAlerts)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Alertas del Sistema
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($systemAlerts as $alert): ?>
                            <div class="alert alert-<?= $alert['type'] ?> d-flex justify-content-between align-items-center mb-2">
                                <span><?= htmlspecialchars($alert['message']) ?></span>
                                <?php if (isset($alert['action_url'])): ?>
                                    <a href="<?= htmlspecialchars($alert['action_url']) ?>" class="btn btn-sm btn-outline-<?= $alert['type'] ?>">
                                        Ver Detalles
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Estadísticas Principales -->
    <div class="row mb-4">
        <!-- Usuarios -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Usuarios Totales
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_users']) ?>
                            </div>
                            <div class="text-xs text-success mt-1">
                                <i class="fas fa-arrow-up me-1"></i>
                                <?= number_format($stats['new_users_this_month']) ?> este mes
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campañas -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Campañas Activas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['active_campaigns']) ?>
                            </div>
                            <div class="text-xs text-info mt-1">
                                <i class="fas fa-clock me-1"></i>
                                <?= number_format($stats['pending_campaigns']) ?> pendientes
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donaciones -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Recaudado
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?= number_format($stats['total_amount_raised'], 2) ?>
                            </div>
                            <div class="text-xs text-success mt-1">
                                <i class="fas fa-dollar-sign me-1"></i>
                                $<?= number_format($stats['amount_raised_today'], 2) ?> hoy
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donaciones Hoy -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Donaciones Hoy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['donations_today']) ?>
                            </div>
                            <div class="text-xs text-muted mt-1">
                                <i class="fas fa-heart me-1"></i>
                                <?= number_format($stats['total_donations']) ?> total
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-heart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Actividad Reciente -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Actividad Reciente</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Filtros
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="filterActivity('all')">Todas</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterActivity('users')">Usuarios</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterActivity('campaigns')">Campañas</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterActivity('donations')">Donaciones</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        <!-- Usuarios Recientes -->
                        <div class="activity-section" data-type="users">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-users me-2"></i>Nuevos Usuarios
                            </h6>
                            <?php if (!empty($recentActivity['recent_users'])): ?>
                                <?php foreach ($recentActivity['recent_users'] as $user): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-primary">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-header">
                                                <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                                se registró en la plataforma
                                            </div>
                                            <div class="activity-meta">
                                                <span class="text-muted"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></span>
                                                <a href="/admin/users/<?= $user['id'] ?>" class="ms-2 text-decoration-none">Ver perfil</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No hay usuarios recientes.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Campañas Recientes -->
                        <div class="activity-section" data-type="campaigns">
                            <h6 class="text-success mb-3">
                                <i class="fas fa-bullhorn me-2"></i>Nuevas Campañas
                            </h6>
                            <?php if (!empty($recentActivity['recent_campaigns'])): ?>
                                <?php foreach ($recentActivity['recent_campaigns'] as $campaign): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-success">
                                            <i class="fas fa-bullhorn"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-header">
                                                <strong><?= htmlspecialchars($campaign['title']) ?></strong>
                                                <span class="badge badge-<?= $campaign['status'] === 'pending' ? 'warning' : 'success' ?> ms-2">
                                                    <?= ucfirst($campaign['status']) ?>
                                                </span>
                                            </div>
                                            <div class="activity-meta">
                                                <span class="text-muted"><?= date('d/m/Y H:i', strtotime($campaign['created_at'])) ?></span>
                                                <a href="/campaigns/<?= $campaign['id'] ?>" class="ms-2 text-decoration-none">Ver campaña</a>
                                                <?php if ($campaign['status'] === 'pending'): ?>
                                                    <a href="/admin/campaigns?id=<?= $campaign['id'] ?>" class="ms-2 text-warning text-decoration-none">Moderar</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No hay campañas recientes.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Donaciones Recientes -->
                        <div class="activity-section" data-type="donations">
                            <h6 class="text-info mb-3">
                                <i class="fas fa-heart me-2"></i>Donaciones Recientes
                            </h6>
                            <?php if (!empty($recentActivity['recent_donations'])): ?>
                                <?php foreach ($recentActivity['recent_donations'] as $donation): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-info">
                                            <i class="fas fa-heart"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-header">
                                                Donación de <strong>$<?= number_format($donation['amount'], 2) ?></strong>
                                                <?php if (!$donation['is_anonymous']): ?>
                                                    por <?= htmlspecialchars($donation['donor_name']) ?>
                                                <?php else: ?>
                                                    (anónima)
                                                <?php endif; ?>
                                            </div>
                                            <div class="activity-meta">
                                                <span class="text-muted"><?= date('d/m/Y H:i', strtotime($donation['created_at'])) ?></span>
                                                <a href="/campaigns/<?= $donation['campaign_id'] ?>" class="ms-2 text-decoration-none">
                                                    <?= htmlspecialchars(substr($donation['campaign_title'], 0, 30)) ?>...
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No hay donaciones recientes.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Acciones Rápidas -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/admin/users" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>Gestionar Usuarios
                        </a>
                        <a href="/admin/campaigns" class="btn btn-outline-success">
                            <i class="fas fa-bullhorn me-2"></i>Moderar Campañas
                        </a>
                        <a href="/admin/donations" class="btn btn-outline-info">
                            <i class="fas fa-heart me-2"></i>Ver Donaciones
                        </a>
                        <a href="/admin/categories" class="btn btn-outline-warning">
                            <i class="fas fa-tags me-2"></i>Gestionar Categorías
                        </a>
                        <a href="/admin/settings" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a>
                    </div>
                </div>
            </div>

            <!-- Top Campañas -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Campañas Destacadas</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($topCampaigns)): ?>
                        <?php foreach (array_slice($topCampaigns, 0, 5) as $index => $campaign): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="ranking-badge">
                                        <?= $index + 1 ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-bold">
                                        <?php $campaignUrl = Router::url('campana/' . ($campaign['slug'] ?? $campaign['id'])); ?>
                                        <a href="<?= htmlspecialchars($campaignUrl) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars(substr($campaign['title'], 0, 25)) ?>...
                                        </a>
                                    </div>
                                    <?php
                                        $raised = (float)($campaign['raised_amount'] ?? $campaign['current_amount'] ?? 0);
                                        $goal = (float)($campaign['goal_amount'] ?? 0);
                                        $percentage = $goal > 0 ? min(100, ($raised / $goal) * 100) : 0;
                                    ?>
                                    <div class="text-success small">
                                        $<?= number_format($raised, 2) ?> recaudados
                                    </div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-success" style="width: <?= $percentage ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="/admin/campaigns" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No hay campañas disponibles.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Resumen del Mes</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <div class="h4 text-primary"><?= number_format($stats['new_users_this_month']) ?></div>
                            <div class="small text-muted">Nuevos Usuarios</div>
                        </div>
                        <div class="col-6">
                            <div class="h4 text-success"><?= number_format($stats['campaigns_this_month']) ?></div>
                            <div class="small text-muted">Nuevas Campañas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    border-left: 4px solid;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.border-left-primary { border-left-color: #4e73df !important; }
.border-left-success { border-left-color: #1cc88a !important; }
.border-left-info { border-left-color: #36b9cc !important; }
.border-left-warning { border-left-color: #f6c23e !important; }

.activity-timeline {
    max-height: 600px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e3e6f0;
}

.activity-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.activity-content {
    margin-left: 1rem;
    flex-grow: 1;
}

.activity-header {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.activity-meta {
    font-size: 0.8rem;
}

.ranking-badge {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.8rem;
}

.activity-section {
    margin-bottom: 2rem;
}

.activity-section:last-child {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .stats-card .h5 {
        font-size: 1.1rem;
    }
    
    .activity-timeline {
        max-height: 400px;
    }
}
</style>

<script>
function refreshDashboard() {
    // Mostrar indicador de carga
    const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
    const originalContent = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
    refreshBtn.disabled = true;

    // Simular actualización (en una implementación real, harías una llamada AJAX)
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function filterActivity(type) {
    const sections = document.querySelectorAll('.activity-section');
    
    sections.forEach(section => {
        if (type === 'all' || section.dataset.type === type) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
    });
}

// Auto-refresh cada 5 minutos
setInterval(() => {
    // En una implementación real, actualizarías solo las estadísticas via AJAX
    console.log('Auto-refresh dashboard stats');
}, 300000);

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>