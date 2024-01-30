<?php

// Include database credentials from config.php
require_once('config.php');

// Connect to MySQL
$mysqli = new mysqli($host, $username, $password, $database, $port);
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Perform the database query
$query = "SELECT * FROM testtable";
$result = $mysqli->query($query);

// Prepare data for JSON encoding
$data = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $result->free();
} else {
    echo "Error executing query: " . $mysqli->error;
}

// Close the MySQL connection
$mysqli->close();

// Send data as JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
