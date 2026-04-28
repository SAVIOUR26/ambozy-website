<?php
require_once dirname(__DIR__) . '/includes/auth.php';
logout_user();
header('Location: /admin/login.php');
exit;
