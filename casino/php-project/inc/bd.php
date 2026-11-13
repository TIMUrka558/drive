<?php
require_once __DIR__ . '/mysql_compat.php';

$bd_server = "127.0.0.1";
$bd_port   = 3306;
$bd_login  = 'beezeuser';
$bd_pass   = 'beezepass123';
$bd_name   = 'beezename';

// Connect using our shim (maps mysql_connect → mysqli)
// Prefer the unix socket: TCP to 127.0.0.1 exhausts the 1024 ephemeral ports (TIME_WAIT) under polling
$bd_socket = '/app/casino/mysql-run/mysql.sock';
if (file_exists($bd_socket)) {
    $__conn = new mysqli('localhost', $bd_login, $bd_pass, $bd_name, $bd_port, $bd_socket);
} else {
    $__conn = new mysqli($bd_server, $bd_login, $bd_pass, $bd_name, $bd_port);
}
if ($__conn->connect_error) {
    die("<p>not connect base! " . htmlspecialchars($__conn->connect_error) . "</p>");
}
$__conn->set_charset("utf8mb4");
$GLOBALS['__mysql_conn'] = $__conn;

// Keep these for backward-compat calls that use the result of mysql_connect:
mysql_query("SET NAMES utf8");
mysql_query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
