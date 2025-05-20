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



// db.php: Conexión a la base de datos PostgreSQL en Railway


// db.php: Conexión a la base de datos PostgreSQL en Railway

class Database {
    private static $db;

    // Conectar a la base de datos
    public static function connect() {
        if (self::$db == null) {
            try {
                // Usar las variables de entorno
                $host = 'hopper.proxy.rlwy.net';  // 'hopper.proxy.rlwy.net'
                $port = '31530';    // '31530'
                $dbname = 'railway';             // 'railway'
                $username = 'postgres';         // 'postgres'
                $password =  '221151029';

                // Crear la cadena de conexión
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

                // Conectar a la base de datos
                self::$db = new PDO($dsn, $username, $password);
                self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Error de conexión: " . $e->getMessage());
            }
        }
        return self::$db;
    }
}

?>
