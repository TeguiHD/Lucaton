<?php
class AuthController {
    private User $users;

    public function __construct() {
        $this->users = new User();
    }

    public function showLogin() {
        $current_page = 'login';
        include VIEWS_PATH . '/auth/login.php';
    }

    public function login() {
        if (!SessionHelper::checkRateLimit('login', RATE_LIMIT_LOGIN, RATE_LIMIT_WINDOW)) {
            return $this->respondError('Demasiados intentos de acceso. Intenta nuevamente en unos minutos.', 429, ['rate_limit' => 'exceeded']);
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        }

        if (strlen($password) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
        }

        if (!empty($errors)) {
            return $this->respondValidationErrors($errors);
        }

        try {
            $user = $this->users->authenticate($email, $password);
            SessionHelper::setUser($user);

            $welcomeName = SessionHelper::getUser()['name'] ?? ($user['first_name'] ?? '');
            $welcomeName = trim($welcomeName) !== '' ? trim($welcomeName) : 'de vuelta';

            $newsletterMessage = $this->completeNewsletterIntent($user);

            $flashMessage = 'Bienvenido de vuelta, ' . $welcomeName . '!';
            if ($newsletterMessage !== null) {
                $flashMessage .= ' ' . $newsletterMessage;
            }

            SessionHelper::setFlash('success', $flashMessage);

            $redirect = $_SESSION['intended_url'] ?? Router::url('panel');
            unset($_SESSION['intended_url']);

            $this->respondSuccess(['redirect' => $redirect]);
        } catch (Exception $e) {
            Logger::warning('Login failed', [
                'email' => $email,
                'reason' => $e->getMessage()
            ]);
            $message = $e->getMessage();
            $normalized = $message;
            $errors = [];

            if ($message === 'Credenciales inválidas') {
                $normalized = 'Correo o contraseña incorrectos.';
                $errors = [
                    'general' => $normalized,
                    'email' => 'Revisa tu correo o contraseña.',
                    'password' => 'Revisa tu correo o contraseña.'
                ];
                return $this->respondValidationErrors($errors, 401);
            }

            if ($message === 'Cuenta bloqueada temporalmente') {
                $errors = [
                    'general' => 'Tu cuenta fue bloqueada temporalmente por intentos fallidos. Intenta nuevamente en unos minutos.'
                ];
                return $this->respondValidationErrors($errors, 423);
            }

            if ($message === 'Cuenta no activa. Verifica tu email.') {
                $errors = [
                    'general' => 'Tu cuenta aún no ha sido activada. Revisa tu correo para completar la verificación.'
                ];
                return $this->respondValidationErrors($errors, 401);
            }

            $this->respondError($normalized, 401);
        }
    }

    public function showRegister() {
        $current_page = 'register';
        include VIEWS_PATH . '/auth/register.php';
    }

    public function showForgotPassword() {
        $current_page = 'forgot-password';
        include VIEWS_PATH . '/auth/forgot-password.php';
    }

    public function sendResetLink() {
        $email = trim($_POST['email'] ?? '');
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        }

        if (!empty($errors)) {
            if ($this->isJsonRequest()) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'errors' => $errors
                ]);
                exit;
            }

            SessionHelper::setFlash('error', 'Ingresa un correo válido para continuar.');
            Router::redirect('/recuperar');
        }

        $resetUrl = null;
        $tokenGenerated = false;

        $message = 'Si el correo existe en nuestra plataforma, recibirás un enlace para restablecer tu contraseña.';

        if (APP_ENV === 'development' || APP_ENV === 'local') {
            $message .= ' (Demo: revisa la consola del servidor para ver el enlace simulado).';
        }

        try {
            $token = $this->users->generatePasswordResetToken($email);
            $resetUrl = Router::url('recuperar/restablecer/' . $token);
            $tokenGenerated = true;

            if (APP_ENV === 'development' || APP_ENV === 'local') {
                Logger::info('Password reset demo link', [
                    'email' => $email,
                    'reset_url' => $resetUrl
                ]);
            } else {
                $this->logPasswordResetLink($email, $resetUrl);
            }
        } catch (Exception $e) {
            Logger::notice('Password reset requested for non-existent email', ['email' => $email]);
        }

        if ($this->isJsonRequest()) {
            $payload = ['message' => $message];
            if ($tokenGenerated && (APP_ENV === 'development' || APP_ENV === 'local')) {
                $payload['reset_url'] = $resetUrl;
            }
            $this->respondSuccess($payload);
            return;
        }

        $flashMessage = $message;
        if ($tokenGenerated && (APP_ENV === 'development' || APP_ENV === 'local')) {
            $flashMessage .= ' (Demo: enlace simulado en los logs).';
        }

        SessionHelper::setFlash('success', $flashMessage);
        Router::redirect('/recuperar');
    }

    public function showResetPassword($token) {
        $token = trim($token ?? '');

        if ($token === '') {
            SessionHelper::setFlash('error', 'El enlace de recuperación no es válido.');
            Router::redirect('/recuperar');
        }

        try {
            $user = $this->users->findByValidResetToken($token);
        } catch (Exception $e) {
            $user = null;
        }

        if (!$user) {
            SessionHelper::setFlash('error', 'El enlace de recuperación expiró o no es válido. Solicita uno nuevo.');
            Router::redirect('/recuperar');
        }

        $current_page = 'reset-password';
        $reset_token = $token;
        include VIEWS_PATH . '/auth/reset-password.php';
    }

    public function resetPassword($token) {
        $token = trim($token ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        $errors = [];

        if (!preg_match('/(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}/', $password)) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres e incluir una letra mayúscula, un número y un carácter especial.';
        }

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden.';
        }

        if (!empty($errors)) {
            return $this->respondValidationErrors($errors);
        }

        try {
            $this->users->resetPassword($token, $password);

            $message = 'Tu contraseña fue actualizada correctamente. Ahora puedes iniciar sesión con tus nuevas credenciales.';

            if ($this->isJsonRequest()) {
                $this->respondSuccess([
                    'message' => $message,
                    'redirect' => Router::url('login')
                ]);
                return;
            }

            SessionHelper::setFlash('success', $message);
            Router::redirect('/login');
        } catch (Exception $e) {
            Logger::warning('Password reset failed', ['error' => $e->getMessage()]);

            if ($this->isJsonRequest()) {
                $this->respondError('El enlace de recuperación expiró o es inválido.', 400);
                return;
            }

            SessionHelper::setFlash('error', 'El enlace de recuperación expiró o es inválido. Solicita uno nuevo.');
            Router::redirect('/recuperar');
        }
    }

    public function register() {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';
        $acceptTerms = ($_POST['terms'] ?? '0') === '1';

        $errors = [];

        if ($firstName === '' || mb_strlen($firstName) < 2) {
            $errors['first_name'] = 'Ingresa un nombre válido.';
        }

        if ($lastName === '' || mb_strlen($lastName) < 2) {
            $errors['last_name'] = 'Ingresa un apellido válido.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        }

        if (!$acceptTerms) {
            $errors['terms'] = 'Debes aceptar los términos y condiciones.';
        }

        if (!preg_match('/(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}/', $password)) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres e incluir una letra mayúscula, un número y un carácter especial.';
        }

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden.';
        }

        if (isset($errors['terms']) && !isset($errors['general'])) {
            $errors['general'] = 'Debes aceptar los términos, condiciones y la política de privacidad para crear tu cuenta.';
        }

        if (!empty($errors)) {
            return $this->respondValidationErrors($errors);
        }

        try {
            $username = $this->generateUsername($firstName, $lastName, $email);
            $userId = $this->users->create([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => 'user',
                'status' => 'active'
            ]);

            $user = $this->users->findById($userId);

            SessionHelper::setUser($user);
            SessionHelper::setFlash('success', '¡Cuenta creada con éxito!');

            Logger::audit('user_registered', $userId, ['email' => $email]);

            $this->respondSuccess([
                'redirect' => Router::url('panel')
            ], 201);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $errors = [];

            if (stripos($message, 'email') !== false) {
                $errors['email'] = 'Este correo ya está registrado. Inicia sesión o utiliza otro correo.';
            } elseif (stripos($message, 'usuario') !== false) {
                $errors['general'] = 'No pudimos generar un nombre de usuario único. Vuelve a intentar con datos distintos.';
            } else {
                $errors['general'] = $message;
            }

            if (!isset($errors['general']) && !empty($errors)) {
                $errors['general'] = reset($errors);
            }

            $this->respondValidationErrors($errors, 422);
        }
    }

    public function logout() {
        SessionHelper::logout();
        SessionHelper::setFlash('success', 'Sesión cerrada correctamente.');
        Router::redirect('/login');
    }

    private function generateUsername(string $firstName, string $lastName, string $email): string {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $firstName . '.' . $lastName));
        $base = trim($base, '.');

        if (strlen($base) > 40) {
            $base = substr($base, 0, 40);
        }

        if ($base === '') {
            $base = strstr($email, '@', true) ?: 'usuario';
        }

        $username = $base;
        $counter = 1;

        while ($this->users->usernameExists($username)) {
            $counter++;
            $username = $base . $counter;
            if ($counter > 999) {
                $username = $base . bin2hex(random_bytes(2));
                break;
            }
        }

        return $username;
    }

    private function isJsonRequest(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }

    private function respondSuccess(array $payload = [], int $status = 200): void {
        unset($_SESSION['validation_errors'], $_SESSION['old_input']);

        if ($this->isJsonRequest()) {

            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode(array_merge(['success' => true], $payload));
            exit;
        }

        $target = $payload['redirect'] ?? Router::url('/');
        header('Location: ' . $target, true, 302);
        exit;
    }

    private function respondError(string $message, int $status = 400, array $extra = []): void {
        if ($this->isJsonRequest()) {
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode(array_merge(['success' => false, 'message' => $message], $extra));
            exit;
        }

        $formKey = $this->detectFormKey();
        $errors = [];
        if (!empty($extra['errors']) && is_array($extra['errors'])) {
            $errors = $extra['errors'];
        }
        if (!isset($errors['general'])) {
            $errors['general'] = $message;
        }

        $this->storeFormState($formKey, $errors, $_POST);

        if ($formKey === 'default') {
            SessionHelper::setFlash('error', $message);
        }

        Router::redirect($formKey === 'register' ? '/registro' : '/login');
    }

    private function respondValidationErrors(array $errors, int $status = 422): void {
        if ($this->isJsonRequest()) {
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }

        $formKey = $this->detectFormKey();
        $this->storeFormState($formKey, $errors, $_POST);

        if ($formKey === 'default') {
            SessionHelper::setFlash('error', $errors['general'] ?? 'Revisa los datos ingresados.');
        }

        Router::redirect($formKey === 'register' ? '/registro' : ($formKey === 'login' ? '/login' : '/'));
    }

    private function detectFormKey(): string {
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        if (str_contains($requestUri, 'registro')) {
            return 'register';
        }
        if (str_contains($requestUri, 'login')) {
            return 'login';
        }
        return 'default';
    }

    private function storeFormState(string $formKey, array $errors, array $input): void {
        if (!isset($errors['general']) && !empty($errors)) {
            $firstKey = array_key_first($errors);
            if ($firstKey) {
                $errors['general'] = $errors[$firstKey];
            }
        }

        $oldInput = $this->sanitizeOldInput($input);

        if (!isset($_SESSION['validation_errors'])) {
            $_SESSION['validation_errors'] = [];
        }
        if (!isset($_SESSION['old_input'])) {
            $_SESSION['old_input'] = [];
        }

        $_SESSION['validation_errors'][$formKey] = $errors;
        $_SESSION['old_input'][$formKey] = $oldInput;
    }

    private function sanitizeOldInput(array $input): array {
        unset($input['password'], $input['password_confirmation']);
        if (isset($input[CSRF_TOKEN_NAME])) {
            unset($input[CSRF_TOKEN_NAME]);
        }

        if (isset($input['terms'])) {
            $input['terms'] = $input['terms'] === '1' || $input['terms'] === 'on' ? '1' : '0';
        }

        if (isset($input['marketing'])) {
            $input['marketing'] = $input['marketing'] === '1' || $input['marketing'] === 'on' ? '1' : '0';
        }

        if (isset($input['remember'])) {
            $input['remember'] = $input['remember'] === '1' || $input['remember'] === 'on' ? '1' : '0';
        }

        return $input;
    }

    private function completeNewsletterIntent(array $user): ?string
    {
        if (empty($_SESSION['newsletter_subscribe_intent'])) {
            return null;
        }

        $intent = $_SESSION['newsletter_subscribe_intent'];
        unset($_SESSION['newsletter_subscribe_intent']);

        $requestedAt = isset($intent['requested_at']) ? (int)$intent['requested_at'] : null;
        if ($requestedAt !== null && $requestedAt < (time() - 3600)) {
            return null;
        }

        $email = trim((string)($user['email'] ?? ''));
        if ($email === '') {
            return null;
        }

        $nameParts = array_filter([
            $user['first_name'] ?? null,
            $user['last_name'] ?? null,
        ]);
        $name = trim(implode(' ', $nameParts));
        if ($name === '' && !empty($user['name'])) {
            $name = trim((string)$user['name']);
        }

        try {
            $subscription = (new NewsletterSubscription())->subscribe($email, $name !== '' ? $name : null);
            $destination = $subscription['email'] ?? $email;
            $message = 'Te has suscrito a las novedades de Lucatón. Te enviaremos actualizaciones a ' . $destination . '. Podrás gestionar tu preferencia desde el enlace de cada correo.';
            SessionHelper::pushSiteToast('success', $message);
            return $message;
        } catch (Exception $exception) {
            Logger::warning('Intento de suscripción pendiente falló tras login', [
                'email' => $email,
                'error' => $exception->getMessage()
            ]);
            return null;
        }
    }


    private function logPasswordResetLink(string $email, string $resetUrl): void {
        try {
            $logDir = ROOT_PATH . '/storage/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $entry = sprintf(
                "%s | email=%s | url=%s%s",
                date('Y-m-d H:i:s'),
                $email,
                $resetUrl,
                PHP_EOL
            );

            file_put_contents($logDir . '/password-resets.log', $entry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            Logger::warning('Failed to log password reset link', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>
