<?php
// userModel.php: Handles user data operations

class UserModel {
    private static $db;

    public static function connect() {
        if (self::$db == null) {
            self::$db = new PDO('pgsql:host=localhost;dbname=untgestion', 'user', 'password');
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$db;
    }

    public static function getUserByUsername($username) {
        $stmt = self::connect()->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getUserById($userId) {
        $stmt = self::connect()->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
