<?php
// logout.php
session_start();
session_unset();
session_destroy();

// Redirige vers la page de connexion après déconnexion
header("Location: login.php");
exit;
?>
