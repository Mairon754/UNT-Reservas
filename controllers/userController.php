<?php
// userController.php: Manages user authentication and profile

require_once 'models/userModel.php';

class UserController {
    public function login($username, $password) {
        $user = UserModel::getUserByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
        return false;
    }

    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        header("Location: login.html");
    }

    public function getUser($userId) {
        return UserModel::getUserById($userId);
    }
}
?>
