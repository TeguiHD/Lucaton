<?php
/**
 * Definición centralizada de rutas para Lucatón
 */

$router = new Router();

// === RUTAS PÚBLICAS ===
$router->get('/', 'HomeController@index');
$router->get('/campanas', 'CampaignController@index');
$router->get('/campana/crear', 'CampaignController@create');
$router->get('/campana/{username}/{slug}/donaciones', 'DonationController@list');
$router->get('/campana/{username}', 'CampaignController@showCreatorProfile');
$router->get('/noticias', 'NewsController@index');
$router->get('/noticias/{slug}', 'NewsController@show');
$router->get('/faq', 'PageController@faq');
$router->get('/vision', 'PageController@vision');
$router->get('/terminos', 'PageController@terms');
$router->get('/privacidad', 'PageController@privacy');
$router->get('/cookies', 'PageController@cookies');
$router->get('/codigo-conducta', 'PageController@codeOfConduct');
$router->get('/estado', 'PageController@status');
$router->get('/reportar', 'PageController@report');
$router->get('/contacto', 'PageController@contact');
$router->get('/ayuda', 'PageController@help');

// Autenticación (pantallas públicas)
$router->get('/login', 'AuthController@showLogin');
$router->get('/registro', 'AuthController@showRegister');
$router->get('/recuperar', 'AuthController@showForgotPassword');
$router->get('/recuperar/restablecer/{token}', 'AuthController@showResetPassword');

// Formularios públicos que requieren CSRF
$router->group(['middleware' => 'csrf'], function($router) {
    $router->post('/login', 'AuthController@login');
    $router->post('/registro', 'AuthController@register');
    $router->post('/logout', 'AuthController@logout');
    $router->post('/recuperar', 'AuthController@sendResetLink');
    $router->post('/recuperar/restablecer/{token}', 'AuthController@resetPassword');
    $router->post('/newsletter', 'NewsletterController@subscribe');
    $router->post('/reportar', 'SupportController@store');
});

$router->get('/newsletter/desuscribir/{token}', 'NewsletterController@unsubscribe');

// === RUTAS PROTEGIDAS DE USUARIO ===
$router->group(['middleware' => 'auth'], function($router) {
    $router->get('/panel', 'UserController@dashboard');
    $router->get('/perfil', 'UserController@profile');
    $router->get('/mis-campanas', 'CampaignController@myCampaigns');
    $router->get('/mis-donaciones', 'UserController@donations');
    $router->get('/mis-estadisticas', 'UserController@statistics');
    $router->get('/campana/{id}/editar', 'CampaignController@edit');
    $router->get('/notificaciones', 'NotificationController@history');
    $router->get('/api/notifications', 'NotificationController@index');
    $router->get('/api/notifications/summary', 'NotificationController@summary');
    $router->get('/api/mis-campanas/resumen', 'UserController@campaignMetrics');

    $router->group(['middleware' => 'csrf'], function($router) {
        $router->post('/campana/crear', 'CampaignController@store');
        $router->post('/campana/{id}/editar', 'CampaignController@update');
        $router->post('/campana/{id}/apelar', 'CampaignController@appeal');
        $router->post('/campana/{username}/{slug}/actualizaciones', 'CampaignUpdateController@store');
        $router->post('/api/notifications/mark-read', 'NotificationController@markRead');
        $router->post('/api/notifications/delete', 'NotificationController@delete');
        $router->post('/api/ai/generate-text', 'AIController@generateText');
        $router->post('/api/ai/improve-text', 'AIController@improveText');
        $router->post('/api/ai/generate-image', 'AIController@generateImage');
        $router->post('/api/ai/moderate', 'AIController@moderate');
        $router->post('/api/feedback', 'FeedbackController@store');
        $router->post('/perfil', 'UserController@updateProfile');
        $router->post('/perfil/preferencias', 'UserController@updatePreferences');
        $router->post('/perfil/seguridad', 'UserController@updateSecurity');
        $router->post('/perfil/password', 'UserController@updatePassword');
    });

    $router->get('/file/ai/{id}', 'AIController@serveFile');
});

$router->get('/campana/{username}/{slug}', 'CampaignController@show');

// === RUTAS DE ADMINISTRACIÓN ===
$router->group(['middleware' => 'admin'], function($router) {
    $router->get('/admin', 'AdminController@dashboard');
    $router->get('/admin/campanas', 'AdminController@campaigns');
    $router->get('/admin/campana/{id}', 'AdminController@showCampaign');
    $router->get('/admin/campana/{id}/documento', 'AdminController@downloadCampaignDocument');
    $router->get('/admin/apelaciones', 'AdminController@appeals');
    $router->get('/admin/apelaciones/{id}', 'AdminController@showAppeal');
    $router->get('/admin/apelaciones/{id}/archivo/{fileId}', 'AdminController@downloadAppealFile');
    $router->get('/admin/usuarios', 'AdminController@users');
    $router->get('/admin/usuarios/{id}', 'AdminController@showUser');
    $router->get('/admin/ia', 'AdminController@aiModeration');
    $router->get('/admin/auditoria', 'AdminController@auditLogs');
    $router->get('/admin/estadisticas', 'AdminController@statistics');
    $router->get('/admin/notificaciones', 'NotificationAdminController@index');
    $router->get('/admin/notificaciones/historial', 'NotificationAdminController@history');
    $router->get('/admin/newsletter', 'NewsletterAdminController@index');
    $router->get('/admin/news', 'NewsAdminController@index');
    $router->get('/admin/news/create', 'NewsAdminController@create');
    $router->get('/admin/news/{id}/edit', 'NewsAdminController@edit');
    $router->get('/admin/reportes', 'AdminController@supportTickets');

    $router->group(['middleware' => 'csrf'], function($router) {
        $router->post('/admin/campana/{id}/aprobar', 'AdminController@approveCampaign');
        $router->post('/admin/campana/{id}/rechazar', 'AdminController@rejectCampaign');
        $router->post('/admin/apelaciones/{id}/resolver', 'AdminController@resolveAppeal');
        $router->post('/admin/notificaciones', 'NotificationAdminController@store');
        $router->post('/admin/newsletter/enviar', 'NewsletterAdminController@send');
        $router->post('/admin/usuarios/{id}/reset-password', 'AdminController@resetUserPassword');
        $router->post('/admin/usuarios/{id}/role', 'AdminController@updateUserRole');
        $router->post('/admin/news', 'NewsAdminController@store');
        $router->post('/admin/news/{id}/update', 'NewsAdminController@update');
        $router->post('/admin/news/{id}/delete', 'NewsAdminController@destroy');
    });
});

// === RUTAS DE API PÚBLICAS ===
$router->group(['middleware' => 'csrf'], function($router) {
    $router->post('/api/upload', 'UploadController@handle');
    $router->post('/api/campanas/{identifier}/compartir', 'CampaignController@registerShare');
    $router->post('/api/donate/{id}', 'DonationController@simulate');
});

return $router;
