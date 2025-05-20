<?php
// db.php: Conexión a la base de datos PostgreSQL

/*class Database {
    private static $db;

    // Conectar a la base de datos
    public static function connect() {
        if (self::$db == null) {
            try {
                $dsn = 'pgsql:host=localhost;dbname=gestor_recursos'; // Cambia 'localhost' y 'gestor_recursos' según tu configuración
                $username = 'postgres';  // Reemplaza con tu nombre de usuario de PostgreSQL
                $password = '221151029';  // Reemplaza con tu contraseña de PostgreSQL
                self::$db = new PDO($dsn, $username, $password);
                self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                // Mostrar detalles del error solo en desarrollo
                if ($_SERVER['SERVER_NAME'] == 'localhost') {
                    die("Error de conexión: " . $e->getMessage() . " | " . $e->getCode());
                } else {
                    die("Error al conectar con la base de datos.");
                }
            }
        }
        return self::$db;
    }
}*/
class Database {
    private static $db;
    
    /**
     * Conectar a la base de datos utilizando la cadena de conexión proporcionada
     * o variables de entorno si están disponibles
     * @return PDO Instancia de conexión a la base de datos
     */
    public static function connect() {
        if (self::$db == null) {
            try {
                // Cadena de conexión principal
                $connectionString = getenv('DATABASE_URL') ?: 'postgresql://postgres:GjSVMWgIEnybhCzdCFRxLdUogrWSTQKO@caboose.proxy.rlwy.net:50491/railway';
                
                // Extraer componentes de la cadena de conexión
                $parsed = self::parseConnectionString($connectionString);
                
                $host = $parsed['host'];
                $port = $parsed['port'];
                $dbname = $parsed['dbname'];
                $username = $parsed['username'];
                $password = $parsed['password'];
                
                // Crear la cadena de conexión para PDO
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
                
                // Opciones de PDO para mejorar el rendimiento y la seguridad
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                // Conectar a la base de datos
                self::$db = new PDO($dsn, $username, $password, $options);
                
                // Registro de conexión exitosa (opcional)
                error_log("Conexión establecida con la base de datos en $host");
            } catch (PDOException $e) {
                // Registro detallado del error
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                
                // Mensaje de error para el usuario (puedes personalizar esto)
                die("Error de conexión: " . $e->getMessage());
            }
        }
        
        return self::$db;
    }
    
    /**
     * Parsea una cadena de conexión PostgreSQL en sus componentes
     * @param string $connectionString La cadena de conexión a parsear
     * @return array Un array asociativo con los componentes de la conexión
     */
    private static function parseConnectionString($connectionString) {
        $result = [
            'host' => '',
            'port' => '',
            'dbname' => '',
            'username' => '',
            'password' => ''
        ];
        
        // Extraer el protocolo (postgresql://)
        $withoutProtocol = preg_replace('/^postgresql:\/\//', '', $connectionString);
        
        // Separar credenciales de la URL
        $parts = explode('@', $withoutProtocol, 2);
        
        if (count($parts) == 2) {
            // Extraer usuario y contraseña
            $credentials = explode(':', $parts[0], 2);
            $result['username'] = $credentials[0];
            $result['password'] = isset($credentials[1]) ? $credentials[1] : '';
            
            // Extraer host, puerto y nombre de la base de datos
            $hostParts = explode('/', $parts[1], 2);
            $hostPort = explode(':', $hostParts[0], 2);
            
            $result['host'] = $hostPort[0];
            $result['port'] = isset($hostPort[1]) ? $hostPort[1] : '5432';
            $result['dbname'] = isset($hostParts[1]) ? $hostParts[1] : '';
            
            // Eliminar parámetros adicionales de la URL si existen
            if (strpos($result['dbname'], '?') !== false) {
                $result['dbname'] = substr($result['dbname'], 0, strpos($result['dbname'], '?'));
            }
        }
        
        return $result;
    }
    
    /**
     * Cierra la conexión a la base de datos
     */
    public static function close() {
        self::$db = null;
    }
}
?>
