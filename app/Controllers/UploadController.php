<?php
class UploadController {
    public function handle() {
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Subidas no implementadas']);
    }
}
?>

