<?php
class AIController {
    public function generateText() {
        if (!SessionHelper::checkRateLimit('ai_text', RATE_LIMIT_AI_REQUESTS, RATE_LIMIT_WINDOW)) {
            http_response_code(429);
            echo json_encode(['error' => 'Límite de IA alcanzado']);
            return;
        }
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Generación de texto no configurada']);
    }

    public function generateImage() {
        if (!SessionHelper::checkRateLimit('ai_image', RATE_LIMIT_AI_REQUESTS, RATE_LIMIT_WINDOW)) {
            http_response_code(429);
            echo json_encode(['error' => 'Límite de IA alcanzado']);
            return;
        }
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Generación de imagen no configurada']);
    }

    public function moderate() {
        if (!SessionHelper::checkRateLimit('ai_moderate', RATE_LIMIT_AI_REQUESTS, RATE_LIMIT_WINDOW)) {
            http_response_code(429);
            echo json_encode(['error' => 'Límite de IA alcanzado']);
            return;
        }
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Moderación no configurada']);
    }

    public function serveFile($id) {
        http_response_code(404);
        echo 'Archivo no encontrado';
    }
}
?>

