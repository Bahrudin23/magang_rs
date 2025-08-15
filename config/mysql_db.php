<?php
date_default_timezone_set('Asia/Jakarta');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'magang';
$pdo  = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
