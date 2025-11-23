<?php
require_once 'admin_auth.php';

logoutAdmin();
header('Location: login.php');
exit;
