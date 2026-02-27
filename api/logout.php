<?php
/**
 * API de Logout
 */
session_start();
session_unset();
session_destroy();

setcookie('vj_remember', '', time() - 3600, '/');

header("Location: ../index.php");
exit;
