<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Gestión de Usuarios';
$current_page = $current_page ?? 'admin-users';
$users = $users ?? [];
$userStats = $userStats ?? [];
$filters = $filters ?? [];
$selectedRole = $filters['role'] ?? 'all';
$searchQuery = $filters['search'] ?? '';
$isSuperAdmin = SessionHelper::isSuperAdmin();
$currentAdminId = (int)SessionHelper::getUserId();
$csrfToken = htmlspecialchars(SessionHelper::getCSRFToken());
?>

<?php ob_start(); ?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        <article class="bg-white shadow-soft rounded-2xl p-5 border border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Usuarios totales</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">
                <?= number_format($userStats['total'] ?? 0, 0, ',', '.') ?>
            </p>
        </article>
        <article class="bg-white shadow-soft rounded-2xl p-5 border border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Administradores</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">
                <?= number_format($userStats['admins'] ?? 0, 0, ',', '.') ?>
            </p>
        </article>
        <article class="bg-white shadow-soft rounded-2xl p-5 border border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Superadministradores</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">
                <?= number_format($userStats['superadmins'] ?? 0, 0, ',', '.') ?>
            </p>
        </article>
        <article class="bg-white shadow-soft rounded-2xl p-5 border border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Activos</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">
                <?= number_format($userStats['active'] ?? 0, 0, ',', '.') ?>
            </p>
        </article>
        <article class="bg-white shadow-soft rounded-2xl p-5 border border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pendientes</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">
                <?= number_format($userStats['pending'] ?? 0, 0, ',', '.') ?>
            </p>
        </article>
    </section>

    <section class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
        <form method="GET" action="<?= Router::url('admin/usuarios') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                <select id="role" name="role" class="form-select block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                    <?php
                    $roleOptions = [
                        'all' => 'Todos',
                        'admin' => 'Administradores',
                        'superadmin' => 'Superadministradores',
                        'user' => 'Usuarios regulares',
                    ];
                    foreach ($roleOptions as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $selectedRole === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input id="search" name="search" type="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Nombre, usuario o correo" class="form-input block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500" />
            </div>
            <div class="md:col-span-3 flex items-center gap-3">
                <button type="submit" class="btn-primary">Aplicar filtros</button>
                <a href="<?= Router::url('admin/usuarios') ?>" class="text-sm text-gray-500 hover:text-gray-700">Restablecer</a>
            </div>
        </form>
    </section>

    <section class="bg-white shadow-soft rounded-3xl border border-gray-100 overflow-hidden">
        <header class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Listado de usuarios</h2>
        </header>

        <?php if (empty($users)): ?>
            <div class="px-6 py-12 text-center text-sm text-gray-600">
                No se encontraron usuarios con los criterios actuales.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                            <?php if ($isSuperAdmin): ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#<?= (int)$user['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        <a href="<?= Router::url('admin/usuarios/' . (int)$user['id']) ?>" class="hover:text-copihue-600 transition">
                                            <?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? 'Usuario')) ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($user['username'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($user['username']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        <?= htmlspecialchars($user['email'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $roleSlug = strtolower($user['role'] ?? 'user');
                                    switch ($roleSlug) {
                                        case 'superadmin':
                                            $roleBadgeClass = 'bg-amber-100 text-amber-700';
                                            break;
                                        case 'admin':
                                            $roleBadgeClass = 'bg-violet-100 text-violet-700';
                                            break;
                                        default:
                                            $roleBadgeClass = 'bg-gray-100 text-gray-600';
                                            break;
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $roleBadgeClass ?>">
                                        <?= htmlspecialchars($user['role_name'] ?? ucfirst($user['role'] ?? 'user')) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if (!empty($user['created_at'])): ?>
                                        <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                    <?php if (!empty($user['last_login_at'])): ?>
                                        <div class="text-xs text-gray-400">
                                            Último acceso: <?= date('d/m/Y H:i', strtotime($user['last_login_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <?php if ($isSuperAdmin): ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($roleSlug === 'superadmin' || (int)$user['id'] === $currentAdminId): ?>
                                            <span class="text-xs text-gray-400">Acción restringida</span>
                                        <?php else: ?>
                                            <div class="flex flex-col gap-2">
                                                <form method="POST" action="<?= Router::url('admin/usuarios/' . $user['id'] . '/role') ?>" class="flex items-center gap-2">
                                                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                                    <select name="role" class="rounded-lg border-gray-300 text-xs focus:border-copihue-500 focus:ring-copihue-500">
                                                        <option value="user" <?= $roleSlug === 'user' ? 'selected' : '' ?>>Usuario</option>
                                                        <option value="admin" <?= $roleSlug === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                                    </select>
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md bg-white border border-gray-200 text-xs font-medium text-gray-600 hover:text-copihue-600">Actualizar</button>
                                                </form>
                                                <form method="POST" action="<?= Router::url('admin/usuarios/' . $user['id'] . '/reset-password') ?>" onsubmit="return confirm('¿Restablecer la contraseña de <?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? $user['email'] ?? 'este usuario')) ?>?');">
                                                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md bg-red-50 border border-red-200 text-xs font-medium text-red-700 hover:bg-red-100">Restablecer contraseña</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>
