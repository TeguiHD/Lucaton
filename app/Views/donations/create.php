<?php
$title = $title ?? 'Realizar Donación';
$campaign = $campaign ?? [];
$user = $user ?? null;
$paymentMethods = $paymentMethods ?? [];

$campaignImage = $campaign['image_url'] ?? $campaign['cover_image_url'] ?? APP_URL . '/public/assets/images/campaigns/placeholder.jpg';
$campaignSummary = $campaign['summary'] ?? ($campaign['description'] ?? '');
$campaignRaised = (float)($campaign['raised_amount'] ?? $campaign['current_amount'] ?? 0);
$campaignGoal = (float)($campaign['goal_amount'] ?? 0);
$campaignPercentage = $campaignGoal > 0 ? min(100, ($campaignRaised / $campaignGoal) * 100) : 0;
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Información de la campaña -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <img src="<?= htmlspecialchars($campaignImage) ?>" 
                                 class="img-fluid rounded" alt="<?= htmlspecialchars($campaign['title']) ?>">
                        </div>
                        <div class="col-md-9">
                            <h4 class="mb-2"><?= htmlspecialchars($campaign['title']) ?></h4>
                            <p class="text-muted mb-3"><?= htmlspecialchars(mb_strlen($campaignSummary) > 150 ? mb_substr($campaignSummary, 0, 150) . '…' : $campaignSummary) ?></p>
                            
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?= $campaignPercentage ?>%"></div>
                            </div>

                            <div class="row text-center">
                                <div class="col-4">
                                    <strong class="text-success">$<?= number_format($campaignRaised, 2) ?></strong>
                                    <br><small class="text-muted">Recaudado</small>
                                </div>
                                <div class="col-4">
                                    <strong>$<?= number_format($campaignGoal, 2) ?></strong>
                                    <br><small class="text-muted">Meta</small>
                                </div>
                                <div class="col-4">
                                    <strong><?= number_format($campaignPercentage, 1) ?>%</strong>
                                    <br><small class="text-muted">Completado</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de donación -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-heart me-2"></i>
                        Realizar Donación
                    </h5>
                </div>
                <div class="card-body">
                    <form id="donation-form" method="POST" action="/donations/store">
                        <?= SecurityHelper::generateCSRFToken() ?>
                        <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
                        
                        <!-- Monto de donación -->
                        <div class="mb-4">
                            <label for="amount" class="form-label fw-bold">Monto de Donación *</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       min="1" max="10000" step="0.01" required>
                            </div>
                            <div class="form-text">Monto mínimo: $1.00 - Monto máximo: $10,000.00</div>
                            
                            <!-- Montos sugeridos -->
                            <div class="mt-3">
                                <label class="form-label">Montos sugeridos:</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm amount-btn" data-amount="10">$10</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm amount-btn" data-amount="25">$25</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm amount-btn" data-amount="50">$50</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm amount-btn" data-amount="100">$100</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm amount-btn" data-amount="250">$250</button>
                                </div>
                            </div>
                        </div>

                        <!-- Método de pago -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Método de Pago *</label>
                            <div class="row">
                                <?php foreach ($paymentMethods as $method => $info): ?>
                                    <?php if ($info['enabled']): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card payment-method-card" data-method="<?= $method ?>">
                                                <div class="card-body text-center">
                                                    <input type="radio" class="form-check-input d-none" 
                                                           name="payment_method" value="<?= $method ?>" id="method_<?= $method ?>">
                                                    <label for="method_<?= $method ?>" class="w-100 cursor-pointer">
                                                        <i class="<?= $info['icon'] ?> fa-2x mb-2"></i>
                                                        <div><?= $info['name'] ?></div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Detalles de pago (se muestran dinámicamente) -->
                        <div id="payment-details" class="mb-4" style="display: none;">
                            <!-- Tarjeta de crédito -->
                            <div id="credit-card-details" class="payment-details" style="display: none;">
                                <h6 class="fw-bold mb-3">Detalles de la Tarjeta</h6>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="card_number" class="form-label">Número de Tarjeta</label>
                                        <input type="text" class="form-control" id="card_number" name="card_number" 
                                               placeholder="1234 5678 9012 3456" maxlength="19">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="card_expiry" class="form-label">Vencimiento</label>
                                        <input type="text" class="form-control" id="card_expiry" name="card_expiry" 
                                               placeholder="MM/AA" maxlength="5">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="card_cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="card_cvv" name="card_cvv" 
                                               placeholder="123" maxlength="4">
                                    </div>
                                </div>
                            </div>

                            <!-- PayPal -->
                            <div id="paypal-details" class="payment-details" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fab fa-paypal me-2"></i>
                                    Serás redirigido a PayPal para completar el pago de forma segura.
                                </div>
                            </div>

                            <!-- Transferencia bancaria -->
                            <div id="bank-transfer-details" class="payment-details" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-university me-2"></i>
                                    Recibirás las instrucciones de transferencia después de confirmar la donación.
                                </div>
                            </div>
                        </div>

                        <!-- Información del donante (solo para invitados) -->
                        <?php if (!$user): ?>
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Información del Donante</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="donor_name" class="form-label">Nombre Completo *</label>
                                        <input type="text" class="form-control" id="donor_name" name="donor_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="donor_email" class="form-label">Email (opcional)</label>
                                        <input type="email" class="form-control" id="donor_email" name="donor_email">
                                        <div class="form-text">Para recibir confirmación de la donación</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Mensaje opcional -->
                        <div class="mb-4">
                            <label for="message" class="form-label">Mensaje de Apoyo (opcional)</label>
                            <textarea class="form-control" id="message" name="message" rows="3" 
                                      placeholder="Comparte un mensaje de apoyo para la campaña..."></textarea>
                            <div class="form-text">Tu mensaje será visible públicamente junto con tu donación</div>
                        </div>

                        <!-- Opciones adicionales -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_anonymous" name="is_anonymous">
                                <label class="form-check-label" for="is_anonymous">
                                    Donación anónima
                                </label>
                                <div class="form-text">Tu nombre no aparecerá públicamente en la lista de donantes</div>
                            </div>
                        </div>

                        <!-- Resumen de donación -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Resumen de Donación</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Monto de donación:</span>
                                    <span id="summary-amount">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Comisión de procesamiento:</span>
                                    <span id="summary-fee">$0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total:</span>
                                    <span id="summary-total">$0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Términos y condiciones -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="accept_terms" required>
                                <label class="form-check-label" for="accept_terms">
                                    Acepto los <a href="/terms" target="_blank">términos y condiciones</a> 
                                    y la <a href="/privacy" target="_blank">política de privacidad</a> *
                                </label>
                            </div>
                        </div>

                        <!-- Botón de envío -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" id="submit-btn">
                                <i class="fas fa-heart me-2"></i>
                                Donar Ahora
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información de seguridad -->
            <div class="card mt-4 border-0 bg-light">
                <div class="card-body text-center">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        Donación Segura
                    </h6>
                    <div class="row">
                        <div class="col-md-4">
                            <i class="fas fa-lock text-muted mb-2"></i>
                            <div class="small">Encriptación SSL</div>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-credit-card text-muted mb-2"></i>
                            <div class="small">Pagos Seguros</div>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-user-shield text-muted mb-2"></i>
                            <div class="small">Datos Protegidos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('donation-form');
    const amountInput = document.getElementById('amount');
    const paymentMethodCards = document.querySelectorAll('.payment-method-card');
    const paymentDetails = document.getElementById('payment-details');
    
    // Manejar montos sugeridos
    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const amount = this.getAttribute('data-amount');
            amountInput.value = amount;
            updateSummary();
            
            // Highlight del botón seleccionado
            document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('btn-primary'));
            document.querySelectorAll('.amount-btn').forEach(b => b.classList.add('btn-outline-primary'));
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
        });
    });
    
    // Manejar selección de método de pago
    paymentMethodCards.forEach(card => {
        card.addEventListener('click', function() {
            const method = this.getAttribute('data-method');
            const radio = document.getElementById('method_' + method);
            
            // Limpiar selecciones previas
            paymentMethodCards.forEach(c => c.classList.remove('border-primary', 'bg-light'));
            document.querySelectorAll('.payment-details').forEach(d => d.style.display = 'none');
            
            // Seleccionar método actual
            this.classList.add('border-primary', 'bg-light');
            radio.checked = true;
            
            // Mostrar detalles del método
            paymentDetails.style.display = 'block';
            const detailsDiv = document.getElementById(method.replace('_', '-') + '-details');
            if (detailsDiv) {
                detailsDiv.style.display = 'block';
            }
        });
    });
    
    // Formatear número de tarjeta
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function() {
            let value = this.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            this.value = formattedValue;
        });
    }
    
    // Formatear fecha de vencimiento
    const cardExpiryInput = document.getElementById('card_expiry');
    if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            this.value = value;
        });
    }
    
    // Actualizar resumen cuando cambie el monto
    amountInput.addEventListener('input', updateSummary);
    
    function updateSummary() {
        const amount = parseFloat(amountInput.value) || 0;
        const fee = amount * 0.029; // 2.9% de comisión
        const total = amount + fee;
        
        document.getElementById('summary-amount').textContent = '$' + amount.toFixed(2);
        document.getElementById('summary-fee').textContent = '$' + fee.toFixed(2);
        document.getElementById('summary-total').textContent = '$' + total.toFixed(2);
    }
    
    // Validación del formulario
    form.addEventListener('submit', function(e) {
        const amount = parseFloat(amountInput.value);
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        if (!amount || amount < 1) {
            e.preventDefault();
            alert('Por favor, ingresa un monto válido de donación.');
            return;
        }
        
        if (!paymentMethod) {
            e.preventDefault();
            alert('Por favor, selecciona un método de pago.');
            return;
        }
        
        // Validaciones específicas por método de pago
        if (paymentMethod.value === 'credit_card') {
            const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
            const cardExpiry = document.getElementById('card_expiry').value;
            const cardCvv = document.getElementById('card_cvv').value;
            
            if (!cardNumber || cardNumber.length < 13) {
                e.preventDefault();
                alert('Por favor, ingresa un número de tarjeta válido.');
                return;
            }
            
            if (!cardExpiry || !/^\d{2}\/\d{2}$/.test(cardExpiry)) {
                e.preventDefault();
                alert('Por favor, ingresa una fecha de vencimiento válida (MM/AA).');
                return;
            }
            
            if (!cardCvv || cardCvv.length < 3) {
                e.preventDefault();
                alert('Por favor, ingresa un CVV válido.');
                return;
            }
        }
        
        // Confirmar donación
        if (!confirm(`¿Confirmas que deseas donar $${amount.toFixed(2)} a esta campaña?`)) {
            e.preventDefault();
            return;
        }
    });
});
</script>

<style>
.payment-method-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #dee2e6;
}

.payment-method-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.payment-method-card.border-primary {
    border-color: #007bff !important;
    background-color: #f8f9fa !important;
}

.amount-btn {
    transition: all 0.3s ease;
}

.cursor-pointer {
    cursor: pointer;
}

.card-img-top {
    height: 200px;
    object-fit: cover;
}
</style>