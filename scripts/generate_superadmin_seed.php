<?php
/**
 * Script de utilidad para calcular el hash de un correo autorizado
 * y usarlo en SUPERADMIN_SEEDS_HASHED.
 *
 * Uso: php scripts/generate_superadmin_seed.php correo@ejemplo.com
 *
 * Requiere que config/bootstrap.php inicialice las variables de entorno
 * SUPERADMIN_SEEDS_ALGO y (opcionalmente) SUPERADMIN_SEEDS_SECRET.
 */

require_once __DIR__ . '/../config/bootstrap.php';

if ($argc < 2) {
    fwrite(STDERR, "Uso: php scripts/generate_superadmin_seed.php correo@ejemplo.com\n");
    exit(1);
}

$email = strtolower(trim((string)$argv[1]));
if ($email === '') {
    fwrite(STDERR, "Error: correo no válido.\n");
    exit(1);
}

$algo = strtolower(env('SUPERADMIN_SEEDS_ALGO', 'sha256'));
if (!in_array($algo, hash_algos(), true)) {
    fwrite(STDERR, "Error: algoritmo '{$algo}' no soportado. Revisa SUPERADMIN_SEEDS_ALGO.\n");
    exit(1);
}

$secret = env('SUPERADMIN_SEEDS_SECRET', '');
$hash = $secret !== ''
    ? hash_hmac($algo, $email, $secret)
    : hash($algo, $email);

fwrite(STDOUT, $hash . PHP_EOL);
