<?php
/**
 * Logger Helper - Sistema de logging con rotación y niveles
 * Maneja logs de aplicación, errores, auditoría y IA
 */

class Logger {
    const EMERGENCY = 0;
    const ALERT = 1;
    const CRITICAL = 2;
    const ERROR = 3;
    const WARNING = 4;
    const NOTICE = 5;
    const INFO = 6;
    const DEBUG = 7;

    private static $levels = [
        0 => 'EMERGENCY',
        1 => 'ALERT',
        2 => 'CRITICAL',
        3 => 'ERROR',
        4 => 'WARNING',
        5 => 'NOTICE',
        6 => 'INFO',
        7 => 'DEBUG'
    ];

    private static $logPath;
    private static $maxFiles;
    private static $currentLevel;

    /**
     * Inicializar logger
     */
    public static function init() {
        self::$logPath = defined('ROOT_PATH') ? ROOT_PATH . '/' . env('LOG_PATH', 'storage/logs') : 'storage/logs';
        self::$maxFiles = (int)env('LOG_MAX_FILES', 30);
        
        $levelName = strtoupper(env('LOG_LEVEL', 'info'));
        self::$currentLevel = array_search($levelName, self::$levels) ?: self::INFO;

        // Crear directorio de logs si no existe
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }

    /**
     * Log genérico
     */
    public static function log($level, $message, $context = []) {
        if (!isset(self::$logPath)) {
            self::init();
        }

        // Verificar si el nivel está habilitado
        if ($level > self::$currentLevel) {
            return;
        }

        $levelName = self::$levels[$level] ?? 'UNKNOWN';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $logEntry = "[{$timestamp}] {$levelName}: {$message}{$contextStr}" . PHP_EOL;
        
        // Determinar archivo de log
        $logFile = self::getLogFile($level);
        
        // Escribir log
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Rotar logs si es necesario
        self::rotateLogs();
    }

    /**
     * Obtener archivo de log según el nivel
     */
    private static function getLogFile($level) {
        $date = date('Y-m-d');
        
        switch ($level) {
            case self::EMERGENCY:
            case self::ALERT:
            case self::CRITICAL:
            case self::ERROR:
                return self::$logPath . "/error-{$date}.log";
            case self::WARNING:
            case self::NOTICE:
                return self::$logPath . "/warning-{$date}.log";
            case self::INFO:
                return self::$logPath . "/info-{$date}.log";
            case self::DEBUG:
                return self::$logPath . "/debug-{$date}.log";
            default:
                return self::$logPath . "/app-{$date}.log";
        }
    }

    /**
     * Rotar logs antiguos
     */
    private static function rotateLogs() {
        $files = glob(self::$logPath . "/*.log");
        
        if (count($files) > self::$maxFiles) {
            // Ordenar por fecha de modificación
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Eliminar archivos más antiguos
            $filesToDelete = array_slice($files, 0, count($files) - self::$maxFiles);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Métodos de conveniencia para diferentes niveles
     */
    public static function emergency($message, $context = []) {
        self::log(self::EMERGENCY, $message, $context);
    }

    public static function alert($message, $context = []) {
        self::log(self::ALERT, $message, $context);
    }

    public static function critical($message, $context = []) {
        self::log(self::CRITICAL, $message, $context);
    }

    public static function error($message, $context = []) {
        self::log(self::ERROR, $message, $context);
    }

    public static function warning($message, $context = []) {
        self::log(self::WARNING, $message, $context);
    }

    public static function notice($message, $context = []) {
        self::log(self::NOTICE, $message, $context);
    }

    public static function info($message, $context = []) {
        self::log(self::INFO, $message, $context);
    }

    public static function debug($message, $context = []) {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Logs específicos para auditoría
     */
    public static function audit($action, $userId = null, $details = []) {
        $context = [
            'type' => 'audit',
            'action' => $action,
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        self::info("AUDIT: {$action}", $context);
    }

    /**
     * Logs específicos para IA
     */
    public static function ai($action, $model, $tokens = null, $details = []) {
        $context = [
            'type' => 'ai',
            'action' => $action,
            'model' => $model,
            'tokens' => $tokens,
            'details' => $details
        ];
        
        self::info("AI: {$action} with {$model}", $context);
    }

    /**
     * Logs de seguridad
     */
    public static function security($event, $severity = 'medium', $details = []) {
        $context = [
            'type' => 'security',
            'event' => $event,
            'severity' => $severity,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'details' => $details
        ];
        
        $level = $severity === 'high' ? self::ERROR : self::WARNING;
        self::log($level, "SECURITY: {$event}", $context);
    }

    /**
     * Obtener estadísticas de logs
     */
    public static function getStats() {
        $files = glob(self::$logPath . "/*.log");
        $stats = [
            'total_files' => count($files),
            'total_size' => 0,
            'files' => []
        ];

        foreach ($files as $file) {
            $size = filesize($file);
            $stats['total_size'] += $size;
            $stats['files'][] = [
                'name' => basename($file),
                'size' => $size,
                'modified' => filemtime($file)
            ];
        }

        return $stats;
    }
}

// Inicializar logger automáticamente
Logger::init();