<?php
// includes/functions.php
require_once __DIR__ . '/config.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function send_reset_email($email, $token) {
    $reset_link = "http://localhost/reset-password.php?token=$token";
    // TODO: Envoyer un vrai mail via PHPMailer
    echo "<p>Un email de réinitialisation a été envoyé à $email : <a href='$reset_link'>$reset_link</a></p>";
}

function validate_password_strength($password) {
    $length = strlen($password) >= 8;
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number = preg_match('@[0-9]@', $password);
    $special = preg_match('@[\W]@', $password);

    return $length && $uppercase && $lowercase && $number && $special;
}
?>
