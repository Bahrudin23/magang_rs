<?php
date_default_timezone_set('Asia/Jakarta');

$host = '192.168.3.100';
$user = 'dbroot';
$pass = '12345';
$db   = 'db_db';
$pdo  = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
