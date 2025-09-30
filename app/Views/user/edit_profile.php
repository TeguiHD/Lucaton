<?php
$title = 'Editar Perfil';
$user = $data['user'];
$errors = $data['errors'] ?? [];
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h3 mb-1">Editar Perfil</h2>
                    <p class="text-muted mb-0">Actualiza tu información personal y configuración de perfil</p>
                </div>
                <a href="/user/profile" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Perfil
                </a>
            </div>

            <!-- Formulario Principal -->
            <form action="/user/update-profile" method="POST" enctype="multipart/form-data" id="profileForm">
                <?= csrf_field() ?>
                
                <!-- Avatar -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-circle me-2"></i>Foto de Perfil
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="avatar-preview">
                                    <img src="<?= htmlspecialchars($user['avatar_url'] ?? '/assets/images/default-avatar.png') ?>" 
                                         alt="Avatar actual" 
                                         class="rounded-circle" 
                                         id="avatarPreview">
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="avatar" class="form-label">Cambiar foto de perfil</label>
                                    <input type="file" 
                                           class="form-control <?= isset($errors['avatar']) ? 'is-invalid' : '' ?>" 
                                           id="avatar" 
                                           name="avatar" 
                                           accept="image/*">
                                    <?php if (isset($errors['avatar'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['avatar']) ?></div>
                                    <?php endif; ?>
                                    <div class="form-text">
                                        Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB.
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="removeAvatar">
                                    <i class="fas fa-trash me-1"></i>Eliminar Foto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Personal -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Información Personal
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Nombre *</label>
                                <input type="text" 
                                       class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?= htmlspecialchars($user['first_name']) ?>" 
                                       required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['first_name']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Apellido *</label>
                                <input type="text" 
                                       class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?= htmlspecialchars($user['last_name']) ?>" 
                                       required>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['last_name']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" 
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" 
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                            <?php endif; ?>
                            <div class="form-text">
                                Si cambias tu email, deberás verificar la nueva dirección.
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="tel" 
                                       class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['phone']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Ubicación</label>
                                <input type="text" 
                                       class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>" 
                                       id="location" 
                                       name="location" 
                                       value="<?= htmlspecialchars($user['location'] ?? '') ?>" 
                                       placeholder="Ciudad, País">
                                <?php if (isset($errors['location'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['location']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">Biografía</label>
                            <textarea class="form-control <?= isset($errors['bio']) ? 'is-invalid' : '' ?>" 
                                      id="bio" 
                                      name="bio" 
                                      rows="4" 
                                      maxlength="500" 
                                      placeholder="Cuéntanos un poco sobre ti..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <?php if (isset($errors['bio'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['bio']) ?></div>
                            <?php endif; ?>
                            <div class="form-text">
                                <span id="bioCounter">0</span>/500 caracteres
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="website" class="form-label">Sitio Web</label>
                            <input type="url" 
                                   class="form-control <?= isset($errors['website']) ? 'is-invalid' : '' ?>" 
                                   id="website" 
                                   name="website" 
                                   value="<?= htmlspecialchars($user['website'] ?? '') ?>" 
                                   placeholder="https://tu-sitio-web.com">
                            <?php if (isset($errors['website'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['website']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Redes Sociales -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-share-alt me-2"></i>Redes Sociales
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="social_facebook" class="form-label">
                                    <i class="fab fa-facebook text-primary me-2"></i>Facebook
                                </label>
                                <input type="url" 
                                       class="form-control <?= isset($errors['social_facebook']) ? 'is-invalid' : '' ?>" 
                                       id="social_facebook" 
                                       name="social_facebook" 
                                       value="<?= htmlspecialchars($user['social_facebook'] ?? '') ?>" 
                                       placeholder="https://facebook.com/tu-perfil">
                                <?php if (isset($errors['social_facebook'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['social_facebook']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="social_twitter" class="form-label">
                                    <i class="fab fa-twitter text-info me-2"></i>Twitter
                                </label>
                                <input type="url" 
                                       class="form-control <?= isset($errors['social_twitter']) ? 'is-invalid' : '' ?>" 
                                       id="social_twitter" 
                                       name="social_twitter" 
                                       value="<?= htmlspecialchars($user['social_twitter'] ?? '') ?>" 
                                       placeholder="https://twitter.com/tu-usuario">
                                <?php if (isset($errors['social_twitter'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['social_twitter']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="social_instagram" class="form-label">
                                    <i class="fab fa-instagram text-danger me-2"></i>Instagram
                                </label>
                                <input type="url" 
                                       class="form-control <?= isset($errors['social_instagram']) ? 'is-invalid' : '' ?>" 
                                       id="social_instagram" 
                                       name="social_instagram" 
                                       value="<?= htmlspecialchars($user['social_instagram'] ?? '') ?>" 
                                       placeholder="https://instagram.com/tu-usuario">
                                <?php if (isset($errors['social_instagram'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['social_instagram']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Privacidad -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Configuración de Privacidad
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="privacy_show_email" 
                                   name="privacy_show_email" 
                                   value="1" 
                                   <?= ($user['privacy_show_email'] ?? false) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="privacy_show_email">
                                Mostrar mi email en el perfil público
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="privacy_show_donations" 
                                   name="privacy_show_donations" 
                                   value="1" 
                                   <?= ($user['privacy_show_donations'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="privacy_show_donations">
                                Mostrar mis donaciones en el perfil público
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="notifications_email" 
                                   name="notifications_email" 
                                   value="1" 
                                   <?= ($user['notifications_email'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notifications_email">
                                Recibir notificaciones por email
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="submit" class="btn btn-primary me-2" id="saveBtn">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                                <a href="/user/profile" class="btn btn-outline-secondary">
                                    Cancelar
                                </a>
                            </div>
                            <div>
                                <a href="/user/settings" class="btn btn-outline-info">
                                    <i class="fas fa-cog me-2"></i>Configuración Avanzada
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.avatar-preview img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border: 3px solid #e9ecef;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    transform: translateY(-1px);
}

#bioCounter {
    font-weight: 500;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

@media (max-width: 768px) {
    .avatar-preview img {
        width: 80px;
        height: 80px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview de avatar
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const removeAvatarBtn = document.getElementById('removeAvatar');
    
    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    
    removeAvatarBtn.addEventListener('click', function() {
        avatarInput.value = '';
        avatarPreview.src = '/assets/images/default-avatar.png';
    });
    
    // Contador de caracteres para biografía
    const bioTextarea = document.getElementById('bio');
    const bioCounter = document.getElementById('bioCounter');
    
    function updateBioCounter() {
        const length = bioTextarea.value.length;
        bioCounter.textContent = length;
        bioCounter.style.color = length > 450 ? '#dc3545' : length > 400 ? '#ffc107' : '#6c757d';
    }
    
    bioTextarea.addEventListener('input', updateBioCounter);
    updateBioCounter(); // Inicializar contador
    
    // Validación del formulario
    const form = document.getElementById('profileForm');
    const saveBtn = document.getElementById('saveBtn');
    
    form.addEventListener('submit', function(e) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    });
    
    // Validación de URLs
    const urlInputs = document.querySelectorAll('input[type="url"]');
    urlInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !this.value.startsWith('http')) {
                this.value = 'https://' + this.value;
            }
        });
    });
    
    // Auto-resize para textarea
    bioTextarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});
</script>