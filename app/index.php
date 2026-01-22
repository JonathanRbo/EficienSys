<?php
/**
 * EficienSys - Index
 * Redireciona para dashboard ou login
 */

require_once 'config/session.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
