<?php
class DonationController {
    public function simulate($id) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'message' => 'Donación simulada (stub)',
            'campaign_id' => $id
        ]);
    }
}
?>