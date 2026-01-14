<?php
require_once __DIR__ . '../..//backend/auth.php';
logout();
header('Location: login.php');
