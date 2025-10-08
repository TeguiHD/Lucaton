<?php

class NewsletterController
{
    private NewsletterSubscription $subscriptions;

    public function __construct()
    {
        $this->subscriptions = new NewsletterSubscription();
    }

    public function subscribe()
    {
        $emailInput = trim($_POST['email'] ?? '');

        if (!SessionHelper::isAuthenticated()) {
            $target = $this->queueSubscriptionIntent($emailInput);
            $message = 'Inicia sesión con tu cuenta para activar la suscripción y proteger tu correo.';

            SessionHelper::pushSiteToast('info', $message);

            if ($this->wantsJson()) {
                $this->respondJson([
                    'success' => false,
                    'requires_auth' => true,
                    'message' => $message,
                    'redirect' => Router::url('login')
                ], 401);
                return;
            }

            SessionHelper::setFlash('info', $message);
            Router::redirect('/login');
        }

        $currentUser = SessionHelper::getUser();
        $email = strtolower(trim($currentUser['email'] ?? ''));
        if ($email === '') {
            $error = 'No pudimos validar el correo de tu cuenta.';
            if ($this->wantsJson()) {
                $this->respondJson(['success' => false, 'message' => $error], 422);
                return;
            }

            SessionHelper::setFlash('error', $error);
            SessionHelper::pushSiteToast('error', $error);
            $this->redirectBackToNewsletter();
        }

        $name = $this->composeName($currentUser);

        try {
            $subscription = $this->subscriptions->subscribe($email, $name);
            $message = '¡Listo! Te enviaremos novedades de Lucatón a ' . $subscription['email'] . '. Podrás administrar tu suscripción desde el enlace incluido en cada correo.';
            if ($this->wantsJson()) {
                $this->respondJson(['success' => true, 'message' => $message], 200);
                return;
            }

            SessionHelper::setFlash('success', $message);
            SessionHelper::pushSiteToast('success', $message);
            $this->redirectBackToNewsletter();
        } catch (Exception $exception) {
            $error = $exception->getMessage() ?: 'No pudimos registrar tu suscripción.';
            if ($this->wantsJson()) {
                $this->respondJson(['success' => false, 'message' => $error], 422);
                return;
            }

            SessionHelper::setFlash('error', $error);
            SessionHelper::pushSiteToast('error', $error);
            $this->redirectBackToNewsletter();
        }
    }

    public function unsubscribe($token)
    {
        $token = trim((string)$token);
        $result = null;
        $status = 'invalid';

        if ($token !== '') {
            $result = $this->subscriptions->unsubscribeByToken($token);
            if ($result) {
                $status = $result['status'] === 'unsubscribed' ? 'unsubscribed' : 'active';
            }
        }

        if ($status === 'unsubscribed') {
            $email = $result['email'] ?? null;
            $message = 'Tu suscripción al newsletter ha sido cancelada. Ya no recibirás novedades en ' . ($email ?? 'tu correo registrado') . '.';
            SessionHelper::setFlash('success', $message);
            SessionHelper::pushSiteToast('success', $message);
        } elseif ($token !== '') {
            $message = 'No pudimos validar el enlace de cancelación. Es posible que ya haya sido utilizado.';
            SessionHelper::setFlash('warning', $message);
            SessionHelper::pushSiteToast('warning', $message);
        } else {
            $message = 'El enlace de cancelación no es válido.';
            SessionHelper::setFlash('error', $message);
            SessionHelper::pushSiteToast('error', $message);
        }

        $page_title = 'Preferencias de newsletter';
        $page_description = 'Actualiza tu suscripción a las novedades de Lucatón.';
        include VIEWS_PATH . '/public/newsletter-unsubscribe.php';
    }

    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'xmlhttprequest';
    }

    private function respondJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function queueSubscriptionIntent(string $email): string
    {
        $target = $this->resolveRedirectTarget();

        $_SESSION['newsletter_subscribe_intent'] = [
            'email' => $email,
            'requested_at' => time(),
            'target' => $target
        ];

        $_SESSION['intended_url'] = $this->absoluteUrl($target);

        return $target;
    }

    private function resolveRedirectTarget(): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $defaultPath = '/';
        $query = '';
        $anchor = '#newsletter-signup';

        $basePath = parse_url(APP_URL, PHP_URL_PATH) ?? '';
        $basePath = rtrim($basePath, '/');

        if ($referer !== '') {
            $parsed = parse_url($referer);
            $host = $parsed['host'] ?? '';
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';

            if ($host === '' || strcasecmp($host, $currentHost) === 0) {
                $path = $parsed['path'] ?? $defaultPath;
                $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

                if ($basePath !== '' && str_starts_with($path, $basePath)) {
                    $path = substr($path, strlen($basePath));
                    if ($path === false || $path === '') {
                        $path = $defaultPath;
                    }
                }

                if ($path === '' || $path[0] !== '/') {
                    $path = '/' . ltrim($path, '/');
                }

                if (preg_match('#/(login|registro)$#i', $path)) {
                    $path = $defaultPath;
                    $query = '';
                }

                return $path . $query . $anchor;
            }
        }

        return $defaultPath . $anchor;
    }

    private function redirectBackToNewsletter(?string $target = null): void
    {
        $path = $target ?? $this->resolveRedirectTarget();
        Router::redirect($path);
        exit;
    }

    private function absoluteUrl(string $path): string
    {
        $baseUrl = rtrim(APP_URL, '/');
        if ($path === '') {
            return $baseUrl . '/';
        }

        if ($path[0] === '#') {
            return $baseUrl . '/' . $path;
        }

        if ($path[0] === '/') {
            return $baseUrl . $path;
        }

        return $baseUrl . '/' . $path;
    }

    private function composeName(array $user): ?string
    {
        $parts = array_filter([
            $user['first_name'] ?? null,
            $user['last_name'] ?? null,
        ]);

        $name = trim(implode(' ', $parts));
        if ($name !== '') {
            return $name;
        }

        if (!empty($user['name'])) {
            return trim((string)$user['name']);
        }

        return null;
    }
}
