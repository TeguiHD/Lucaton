<?php
require_once APP_PATH . '/Models/User.php';

class AdminController {
    public function users() {
        // Verificar que el usuario esté autenticado y sea admin
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Tesis/views/auth/login.php');
            exit;
        }
        
        $user = new User();
        $currentUser = $user->findById($_SESSION['user_id']);
        
        if (!$currentUser || $currentUser['role'] !== 'admin') {
            header('Location: /Tesis/views/dashboard.php');
            exit;
        }
        
        // Obtener todos los usuarios
        $users = $user->getAllUsers();
        
        // Incluir la vista
        include VIEWS_PATH . '/admin/users.php';
    }
}
?>

