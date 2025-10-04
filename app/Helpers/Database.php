<?php
/**
 * Database Helper - Conexión PDO y utilidades de base de datos
 * Implementa singleton pattern para conexión única y segura
 */

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    private function __construct() {
        $this->host = DB_HOST;
        $this->port = DB_PORT;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;

        $this->connect();
    }

    /**
     * Obtener instancia única de la base de datos
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establecer conexión PDO
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                1002 => "SET NAMES {$this->charset} COLLATE {$this->charset}_unicode_ci" // PDO::MYSQL_ATTR_INIT_COMMAND
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }

    /**
     * Obtener conexión PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Ejecutar consulta preparada
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params));
            
            // En desarrollo, mostrar el error específico
            if (defined('APP_ENV') && APP_ENV === 'development') {
                throw new Exception("Database query failed: " . $e->getMessage());
            }
            
            // En producción, mostrar error genérico pero loggear el específico
            throw new Exception("Database query failed. Please try again.");
        }
    }

    /**
     * Obtener un solo registro
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Obtener múltiples registros
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insertar registro y obtener ID
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->connection->lastInsertId();
    }

    /**
     * Actualizar registros
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        $params = [];
        
        // Usar parámetros nombrados para los datos
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        $setClause = implode(', ', $set);
        
        // Convertir parámetros WHERE posicionales a nombrados
        $whereNamed = $where;
        $whereParamIndex = 0;
        while (strpos($whereNamed, '?') !== false) {
            $paramName = ":where_param_" . $whereParamIndex;
            $whereNamed = preg_replace('/\?/', $paramName, $whereNamed, 1);
            if (isset($whereParams[$whereParamIndex])) {
                $params[$paramName] = $whereParams[$whereParamIndex];
            }
            $whereParamIndex++;
        }
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereNamed}";
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Eliminar registros
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Revertir transacción
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * Verificar si tabla existe
     */
    public function tableExists($tableName) {
        static $cache = [];

        if (array_key_exists($tableName, $cache)) {
            return $cache[$tableName];
        }

        $result = $this->fetch(
            "SELECT COUNT(*) AS total
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?",
            [$tableName]
        );

        $exists = ((int)($result['total'] ?? 0)) > 0;
        $cache[$tableName] = $exists;

        return $exists;
    }

    public function columnExists($tableName, $columnName) {
        static $cache = [];

        $cacheKey = $tableName . '.' . $columnName;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $result = $this->fetch(
            "SELECT COUNT(*) AS total
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            [$tableName, $columnName]
        );

        $exists = ((int)($result['total'] ?? 0)) > 0;
        $cache[$cacheKey] = $exists;

        return $exists;
    }

    /**
     * Obtener información de la base de datos
     */
    public function getDatabaseInfo() {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->dbname,
            'charset' => $this->charset,
            'connection_status' => $this->connection ? 'Connected' : 'Disconnected'
        ];
    }

    /**
     * Prevenir clonación
     */
    private function __clone() {}

    /**
     * Prevenir deserialización
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Función helper global para acceso rápido a la base de datos
 */
function db() {
    return Database::getInstance();
}
