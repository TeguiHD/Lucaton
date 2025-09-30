<?php
$title = 'Iniciar Sesión - Lucatón';
$description = 'Inicia sesión en tu cuenta de Lucatón para gestionar tus campañas y donaciones';
$bodyClass = 'bg-light';

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <!-- Logo y título -->
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">
                            <i class="fas fa-heart me-2"></i>
                            Lucatón
                        </h2>
                        <p class="text-muted">Inicia sesión en tu cuenta</p>
                    </div>
                    
                    <!-- Formulario de login -->
                    <form method="POST" action="<?= Router::url('/login') ?>" id="loginForm">
                        <!-- CSRF Token -->
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $_SESSION[CSRF_TOKEN_NAME] ?? '' ?>">
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>
                                Correo Electrónico
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>" 
                                   required autocomplete="email" autofocus>
                            <?php if (isset($errors['email'])): ?>
                                <div class="text-danger small mt-1">
                                    <?= htmlspecialchars($errors['email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Contraseña -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                Contraseña
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" 
                                       required autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="text-danger small mt-1">
                                    <?= htmlspecialchars($errors['password']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Recordar sesión -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" 
                                   <?= isset($oldInput['remember']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="remember">
                                Recordar mi sesión
                            </label>
                        </div>
                        
                        <!-- Botón de envío -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar Sesión
                            </button>
                        </div>
                        
                        <!-- Enlaces adicionales -->
                        <div class="text-center">
                            <a href="<?= Router::url('/forgot-password') ?>" class="text-decoration-none">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                    </form>
                    
                    <!-- Separador -->
                    <hr class="my-4">
                    
                    <!-- Enlace de registro -->
                    <div class="text-center">
                        <p class="mb-0">
                            ¿No tienes una cuenta?
                            <a href="<?= Router::url('/register') ?>" class="text-decoration-none fw-bold">
                                Regístrate aquí
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Información adicional -->
            <div class="text-center mt-4">
                <p class="text-muted small">
                    Al iniciar sesión, aceptas nuestros 
                    <a href="#" class="text-decoration-none">Términos de Servicio</a> y 
                    <a href="#" class="text-decoration-none">Política de Privacidad</a>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Scripts adicionales -->
<?php
$additionalScripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Toggle password visibility
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");
    
    togglePassword.addEventListener("click", function() {
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);
        
        const icon = this.querySelector("i");
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    });
    
    // Form validation
    const form = document.getElementById("loginForm");
    form.addEventListener("submit", function(e) {
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;
        
        if (!email || !password) {
            e.preventDefault();
            alert("Por favor, completa todos los campos requeridos.");
            return false;
        }
        
        // Disable submit button to prevent double submission
        const submitBtn = form.querySelector("button[type=submit]");
        submitBtn.disabled = true;
        submitBtn.innerHTML = \'<i class="fas fa-spinner fa-spin me-2"></i>Iniciando sesión...\';
    });
});
</script>
';

$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
?>