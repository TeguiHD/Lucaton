<?php
$title = $title ?? 'Donación Exitosa';
$donation = $donation ?? [];
$campaign = $campaign ?? [];

$campaignImage = $campaign['image_url'] ?? $campaign['cover_image_url'] ?? APP_URL . '/public/assets/images/campaigns/placeholder.jpg';
$campaignSummary = $campaign['summary'] ?? ($campaign['description'] ?? '');
$campaignRaised = (float)($campaign['raised_amount'] ?? $campaign['current_amount'] ?? 0);
$campaignGoal = (float)($campaign['goal_amount'] ?? 0);
$newRaised = $campaignRaised + (float)($donation['amount'] ?? 0);
$campaignPercentage = $campaignGoal > 0 ? min(100, ($campaignRaised / $campaignGoal) * 100) : 0;
$newPercentage = $campaignGoal > 0 ? min(100, ($newRaised / $campaignGoal) * 100) : 0;
$campaignSlug = $campaign['slug'] ?? ($campaign['id'] ?? '');
$campaignUrl = $campaignSlug !== '' ? Router::url('campana/' . $campaignSlug) : '#';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Mensaje de éxito -->
            <div class="text-center mb-5">
                <div class="success-icon mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h1 class="display-4 text-success mb-3">¡Donación Exitosa!</h1>
                <p class="lead text-muted">
                    Gracias por tu generosa contribución. Tu apoyo hace la diferencia.
                </p>
            </div>

            <!-- Detalles de la donación -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Detalles de tu Donación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">ID de Donación:</td>
                                    <td>#<?= str_pad($donation['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Monto:</td>
                                    <td class="text-success fw-bold">$<?= number_format($donation['amount'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Método de Pago:</td>
                                    <td>
                                        <?php
                                        $methods = [
                                            'credit_card' => 'Tarjeta de Crédito',
                                            'paypal' => 'PayPal',
                                            'bank_transfer' => 'Transferencia Bancaria'
                                        ];
                                        echo $methods[$donation['payment_method']] ?? $donation['payment_method'];
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Referencia:</td>
                                    <td><code><?= htmlspecialchars($donation['payment_reference']) ?></code></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Fecha:</td>
                                    <td><?= date('d/m/Y H:i', strtotime($donation['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Estado:</td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>
                                            Completada
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Tipo:</td>
                                    <td>
                                        <?= $donation['is_anonymous'] ? 
                                            '<i class="fas fa-user-secret me-1"></i>Anónima' : 
                                            '<i class="fas fa-user me-1"></i>Pública' ?>
                                    </td>
                                </tr>
                                <?php if (!empty($donation['message'])): ?>
                                <tr>
                                    <td class="fw-bold">Mensaje:</td>
                                    <td class="fst-italic">"<?= htmlspecialchars($donation['message']) ?>"</td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de la campaña -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bullhorn me-2"></i>
                        Campaña Apoyada
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <img src="<?= htmlspecialchars($campaignImage) ?>" 
                                 class="img-fluid rounded" alt="<?= htmlspecialchars($campaign['title']) ?>">
                        </div>
                        <div class="col-md-9">
                            <h5 class="mb-2">
                                <a href="<?= htmlspecialchars($campaignUrl) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($campaign['title']) ?>
                                </a>
                            </h5>
                            <p class="text-muted mb-3">
                                <?= htmlspecialchars(mb_strlen($campaignSummary) > 200 ? mb_substr($campaignSummary, 0, 200) . '…' : $campaignSummary) ?>
                            </p>
                            
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?= $newPercentage ?>%"></div>
                            </div>

                            <div class="row text-center">
                                <div class="col-4">
                                    <strong class="text-success">$<?= number_format($newRaised, 2) ?></strong>
                                    <br><small class="text-muted">Nuevo Total</small>
                                </div>
                                <div class="col-4">
                                    <strong>$<?= number_format($campaignGoal, 2) ?></strong>
                                    <br><small class="text-muted">Meta</small>
                                </div>
                                <div class="col-4">
                                    <strong><?= number_format($newPercentage, 1) ?>%</strong>
                                    <br><small class="text-muted">Completado</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Impacto de la donación -->
            <div class="card shadow-sm mb-4 border-success">
                <div class="card-body text-center">
                    <h6 class="fw-bold text-success mb-3">
                        <i class="fas fa-heart me-2"></i>
                        El Impacto de tu Donación
                    </h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="impact-item">
                                <i class="fas fa-hand-holding-heart text-success fa-2x mb-2"></i>
                                <h6>Apoyo Directo</h6>
                                <p class="small text-muted">Tu donación va directamente a apoyar esta causa</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="impact-item">
                                <i class="fas fa-users text-success fa-2x mb-2"></i>
                                <h6>Comunidad</h6>
                                <p class="small text-muted">Te unes a una comunidad de personas que apoyan esta causa</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="impact-item">
                                <i class="fas fa-chart-line text-success fa-2x mb-2"></i>
                                <h6>Progreso</h6>
                                <p class="small text-muted">Ayudas a que la campaña se acerque más a su meta</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones disponibles -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-tasks me-2"></i>
                        ¿Qué puedes hacer ahora?
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="/campaigns/<?= $campaign['id'] ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-2"></i>
                                    Ver Campaña Completa
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <button class="btn btn-outline-success" onclick="shareOnSocialMedia()">
                                    <i class="fas fa-share-alt me-2"></i>
                                    Compartir Campaña
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="/campaigns" class="btn btn-outline-info">
                                    <i class="fas fa-search me-2"></i>
                                    Explorar Más Campañas
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <button class="btn btn-outline-secondary" onclick="window.print()">
                                    <i class="fas fa-print me-2"></i>
                                    Imprimir Recibo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-envelope text-info fa-2x mb-3"></i>
                            <h6 class="fw-bold">Confirmación por Email</h6>
                            <p class="small text-muted mb-0">
                                Recibirás un email de confirmación con los detalles de tu donación.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-bell text-warning fa-2x mb-3"></i>
                            <h6 class="fw-bold">Actualizaciones</h6>
                            <p class="small text-muted mb-0">
                                Te notificaremos sobre el progreso de la campaña que apoyaste.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón principal -->
            <div class="text-center mt-5">
                <a href="/campaigns" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a Campañas
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal para compartir -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-share-alt me-2"></i>
                    Compartir Campaña
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Ayuda a difundir esta campaña compartiéndola en tus redes sociales:</p>
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-primary" id="share-facebook">
                        <i class="fab fa-facebook-f me-2"></i>
                        Compartir en Facebook
                    </a>
                    <a href="#" class="btn btn-info" id="share-twitter">
                        <i class="fab fa-twitter me-2"></i>
                        Compartir en Twitter
                    </a>
                    <a href="#" class="btn btn-success" id="share-whatsapp">
                        <i class="fab fa-whatsapp me-2"></i>
                        Compartir en WhatsApp
                    </a>
                    <button class="btn btn-secondary" onclick="copyToClipboard()">
                        <i class="fas fa-copy me-2"></i>
                        Copiar Enlace
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function shareOnSocialMedia() {
    const modal = new bootstrap.Modal(document.getElementById('shareModal'));
    modal.show();
    
    const campaignUrl = window.location.origin + '/campaigns/<?= $campaign['id'] ?>';
    const campaignTitle = '<?= addslashes($campaign['title']) ?>';
    const shareText = `¡Apoya esta increíble campaña: ${campaignTitle}!`;
    
    // Configurar enlaces de compartir
    document.getElementById('share-facebook').href = 
        `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(campaignUrl)}`;
    
    document.getElementById('share-twitter').href = 
        `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(campaignUrl)}`;
    
    document.getElementById('share-whatsapp').href = 
        `https://wa.me/?text=${encodeURIComponent(shareText + ' ' + campaignUrl)}`;
}

function copyToClipboard() {
    const campaignUrl = window.location.origin + '/campaigns/<?= $campaign['id'] ?>';
    navigator.clipboard.writeText(campaignUrl).then(() => {
        LucatonApp.showAlert('Enlace copiado al portapapeles', 'success');
    }).catch(() => {
        // Fallback para navegadores que no soportan clipboard API
        const textArea = document.createElement('textarea');
        textArea.value = campaignUrl;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        LucatonApp.showAlert('Enlace copiado al portapapeles', 'success');
    });
}

// Animación de entrada
document.addEventListener('DOMContentLoaded', function() {
    const successIcon = document.querySelector('.success-icon i');
    successIcon.style.transform = 'scale(0)';
    successIcon.style.transition = 'transform 0.5s ease-out';
    
    setTimeout(() => {
        successIcon.style.transform = 'scale(1)';
    }, 200);
});
</script>

<style>
.success-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.impact-item {
    padding: 1rem;
    border-radius: 0.5rem;
    transition: transform 0.3s ease;
}

.impact-item:hover {
    transform: translateY(-5px);
}

@media print {
    .btn, .modal, .card-header {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    
    .text-success {
        color: #000 !important;
    }
}
</style>