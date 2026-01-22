<?php
/**
 * EficienSys - Logout
 */

require_once 'config/session.php';

logoutUser();
header('Location: login.php');
exit;
