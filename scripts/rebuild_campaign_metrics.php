<?php
require_once __DIR__ . '/../config/bootstrap.php';

try {
    $donations = new Donation();
    $campaignId = null;
    if ($argc > 1) {
        $candidate = (int)$argv[1];
        if ($candidate > 0) {
            $campaignId = $candidate;
        }
    }

    $donations->rebuildCampaignAggregates($campaignId);

    if ($campaignId !== null) {
        echo "Recalculadas las métricas para la campaña #{$campaignId}." . PHP_EOL;
    } else {
        echo "Recalculadas las métricas para todas las campañas con donaciones." . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Error al recalcular métricas: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
