<?php
/**
 * IFEN Toolbox Admin - Déconnexion
 */
require_once __DIR__ . '/includes/config.php';

logoutAdmin();

header('Location: ' . url('login.php'));
exit;
