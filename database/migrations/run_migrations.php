<?php
/**
 * Script para ejecutar migraciones de base de datos
 * Ejecuta todas las migraciones SQL en orden
 */

// Cargar configuración
require_once __DIR__ . '/../../config/bootstrap.php';

try {
    // Obtener conexión a la base de datos
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "🚀 Iniciando migraciones de base de datos...\n";
    
    // Crear tabla de migraciones si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Obtener migraciones ya ejecutadas
    $stmt = $pdo->query("SELECT migration_name FROM migrations");
    $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener archivos de migración
    $migrationFiles = glob(__DIR__ . '/*.sql');
    sort($migrationFiles);
    
    $executed = 0;
    $skipped = 0;
    
    foreach ($migrationFiles as $file) {
        $migrationName = basename($file, '.sql');
        
        if (in_array($migrationName, $executedMigrations)) {
            echo "⏭️  Saltando migración ya ejecutada: {$migrationName}\n";
            $skipped++;
            continue;
        }
        
        echo "📄 Ejecutando migración: {$migrationName}\n";
        
        try {
            // Leer y ejecutar el archivo SQL
            $sql = file_get_contents($file);
            $pdo->exec($sql);
            
            // Registrar migración como ejecutada
            $stmt = $pdo->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
            $stmt->execute([$migrationName]);
            
            echo "✅ Migración completada: {$migrationName}\n";
            $executed++;
            
        } catch (PDOException $e) {
            echo "❌ Error en migración {$migrationName}: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    echo "\n🎉 Migraciones completadas!\n";
    echo "📊 Estadísticas:\n";
    echo "   - Ejecutadas: {$executed}\n";
    echo "   - Saltadas: {$skipped}\n";
    echo "   - Total: " . count($migrationFiles) . "\n";
    
    // Mostrar información de las tablas creadas
    echo "\n📋 Tablas en la base de datos:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "   - {$table}: {$count} registros\n";
    }
    
} catch (Exception $e) {
    echo "💥 Error fatal: " . $e->getMessage() . "\n";
    exit(1);
}