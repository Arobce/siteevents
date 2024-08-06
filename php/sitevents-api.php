<?php
// site-events-api.php
require_once __DIR__ . '\sitevents-db.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sitevents_db";
$tablename = "events";

header('Content-Type: application/json');

try {
    // Initialize database and table and get connection instance
    $conn = initializeDatabase($servername, $username, $password, $dbname, $tablename);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database initialization failed', 'error' => $e->getMessage()]);
    exit;
}

// Get the raw POST data
$data = file_get_contents('php://input');
$events = json_decode($data, true);

if ($events === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

// Capture $_SERVER information
$user_info = [
    'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
    'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? '',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
    'SERVER_NAME' => $_SERVER['SERVER_NAME'],
    'SERVER_PROTOCOL' => $_SERVER['SERVER_PROTOCOL'],
    'QUERY_STRING' => $_SERVER['QUERY_STRING'],
    'HTTP_ACCEPT_LANGUAGE' => $_SERVER['HTTP_ACCEPT_LANGUAGE'],
];

if (is_array($events)) {
    try {
        $stmt = $conn->prepare("INSERT INTO $tablename (type, timestamp, details, user_info) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception($conn->error);
        }

        foreach ($events as $event) {
            $type = $event['type'];
            $timestamp = $event['timestamp'];
            $details = json_encode($event['details']);
            $user_info_json = json_encode($user_info);
            $stmt->bind_param("siss", $type, $timestamp, $details, $user_info_json);
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
        }
        $stmt->close();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to store events', 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
}

$conn->close();
