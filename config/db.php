<?php
$host = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: 'localhost';
$user = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'railway';
$port = (int)(getenv('MYSQLPORT') ?: 3306);

$conn = mysqli_connect($host, $user, $pass, $name, $port);
if (!$conn) {
    die("DB Connection Failed: " . mysqli_connect_error());
}
?>
