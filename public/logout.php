<?php
require_once __DIR__ . '/../src/Auth.php';

use MangaDiyari\Core\Auth;

Auth::logout();

header('Location: index.php');
exit;
