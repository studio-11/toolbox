<?php
/**
 * IFEN Toolbox - Déconnexion
 * ==========================
 */

require_once __DIR__ . '/includes/config.php';

// Déconnecter l'utilisateur
logout();

// Rediriger vers la page de login
header('Location: login.php');
exit;
?>
