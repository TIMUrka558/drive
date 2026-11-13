<?php
/**
 * MySQL compatibility shim for PHP 8
 * Replaces deprecated mysql_* functions with mysqli_* equivalents
 * The game logic is NOT changed — only the database driver is updated.
 */

global $GLOBALS;

// Connection stored globally
function mysql_connect($host, $user, $pass) {
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die("<p>not connect base! " . $conn->connect_error . "</p>");
    }
    $GLOBALS['__mysql_conn'] = $conn;
    return $conn;
}

function mysql_select_db($db, $conn = null) {
    $c = $conn ?? $GLOBALS['__mysql_conn'];
    return $c->select_db($db);
}

function mysql_query($query, $conn = null) {
    $c = $conn ?? $GLOBALS['__mysql_conn'];
    return $c->query($query);
}

function mysql_fetch_array($result, $type = MYSQLI_BOTH) {
    if (!$result || !($result instanceof mysqli_result)) return false;
    return $result->fetch_array($type);
}

function mysql_fetch_row($result) {
    if (!$result || !($result instanceof mysqli_result)) return false;
    return $result->fetch_row();
}

function mysql_fetch_assoc($result) {
    if (!$result || !($result instanceof mysqli_result)) return false;
    return $result->fetch_assoc();
}

function mysql_num_rows($result) {
    if (!$result || !($result instanceof mysqli_result)) return 0;
    return $result->num_rows;
}

function mysql_affected_rows($conn = null) {
    $c = $conn ?? $GLOBALS['__mysql_conn'];
    return $c->affected_rows;
}

function mysql_insert_id($conn = null) {
    $c = $conn ?? $GLOBALS['__mysql_conn'];
    return $c->insert_id;
}

function mysql_real_escape_string($str, $conn = null) {
    $c = $conn ?? $GLOBALS['__mysql_conn'];
    return $c->real_escape_string($str);
}

function mysql_error($conn = null) {
    $c = $conn ?? $GLOBALS['__mysql_conn'];
    return $c ? $c->error : '';
}

function mysql_close($conn = null) {
    $c = $conn ?? $GLOBALS['__mysql_conn'];
    return $c ? $c->close() : true;
}

function mysql_result($result, $row, $field = 0) {
    if (!$result || !($result instanceof mysqli_result)) return false;
    $result->data_seek($row);
    $r = $result->fetch_array(MYSQLI_BOTH);
    return $r[$field] ?? false;
}

function mysql_free_result($result) {
    if ($result && $result instanceof mysqli_result) {
        $result->free();
    }
}
