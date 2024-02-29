<?php

// Include database credentials from config.php
require_once('config.php');

// Define allowed origins for POST requests
$allowedOriginsForPost = array(
    'http://localhost:5173',
    'https://badingo.net',
    // Add other allowed origins as needed
);

// Function to check if the request's origin is allowed
function isOriginAllowed($origin) {
    global $allowedOriginsForPost;
    return in_array($origin, $allowedOriginsForPost);
}

// Get the Origin header from the request
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Check if the request method is OPTIONS (preflight request)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Check if the requesting origin is allowed for POST requests
    if (isOriginAllowed($origin)) {
        // Set CORS headers for preflight requests
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        http_response_code(200);
        exit();
    } else {
        // Origin not allowed
        http_response_code(403); // Forbidden
        exit("Access denied");
    }
}

// Check if the requesting origin is allowed for POST requests
if (in_array($_SERVER['REQUEST_METHOD'], array('POST', 'OPTIONS')) && !isOriginAllowed($origin)) {
    // Requesting origin is not allowed for POST requests
    http_response_code(403); // Forbidden
    exit("Access denied");
}

// Set CORS headers for actual requests (POST, GET)
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read raw data from request body
    $inputJSON = file_get_contents('php://input');

    // Decode JSON data
    $input = json_decode($inputJSON, true);

    // Check if JSON data is properly decoded
    if ($input === null) {
        // JSON decoding failed
        http_response_code(400); // Bad Request
        exit("Invalid JSON data");
    }

    // Extract data from JSON
    $name = $input['name'];
    $email = $input['email'];
    $comment = $input['comment'];

    // Connect to MySQL
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    if ($mysqli->connect_error) {
        die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }

    // Prepare the SQL statement
    $stmt = $mysqli->prepare("INSERT INTO comments (name, email, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $comment);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Comment added successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();

    // Close the MySQL connection
    $mysqli->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Connect to MySQL
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    if ($mysqli->connect_error) {
        die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }

    // Select and return comments
    $query = "SELECT * FROM comments";
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
}
