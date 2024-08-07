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

// Ensure necessary columns exist
$details_columns = array_reduce($events, function($carry, $event) {
    foreach ($event['details'] as $key => $value) {
        $carry[$key] = 'TEXT';
    }
    return $carry;
}, []);

$user_info_columns = array_reduce(array_keys($user_info), function($carry, $key) {
    $carry[$key] = 'TEXT';
    return $carry;
}, []);

ensureColumnsExist($conn, $tablename, $details_columns);
ensureColumnsExist($conn, $tablename, $user_info_columns);

if (is_array($events)) {
    try {
        foreach ($events as $event) {
            $columns = ['type', 'timestamp'];
            $placeholders = ['?', '?'];
            $values = [$event['type'], $event['timestamp']];

            foreach ($event['details'] as $key => $value) {
                $columns[] = $key;
                $placeholders[] = '?';
                $values[] = $value;
            }

            foreach ($user_info as $key => $value) {
                $columns[] = $key;
                $placeholders[] = '?';
                $values[] = $value;
            }

            $stmt = $conn->prepare("INSERT INTO $tablename (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")");
            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(str_repeat('s', count($values)), ...$values);
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            $stmt->close();
        }
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
?>
