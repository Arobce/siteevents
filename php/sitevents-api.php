<?php
// site-events-api.php
require_once __DIR__ . '\sitevents-db.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sitevents_db";
$tablename = "events";
$userinfo_table = "user_info";

header('Content-Type: application/json');

try {
    // Initialize database and table and get connection instance
    $conn = initializeDatabase($servername, $username, $password, $dbname, $tablename, $userinfo_table);
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
    'remote_addr' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'http_referer' => $_SERVER['HTTP_REFERER'] ?? '',
    'script_name' => $_SERVER['SCRIPT_NAME'],
    'server_name' => $_SERVER['SERVER_NAME'],
    'server_protocol' => $_SERVER['SERVER_PROTOCOL'],
    'query_string' => $_SERVER['QUERY_STRING'],
    'http_accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'],
];

// Ensure necessary columns exist
$details_columns = array_reduce($events, function($carry, $event) {
    foreach ($event['details'] as $key => $value) {
        $carry[$key] = 'TEXT';
    }
    return $carry;
}, []);

// Check if user_info exists, insert if not, and get the user_info_id
$user_info_columns = array_keys($user_info);
$user_info_placeholders = array_fill(0, count($user_info_columns), '?');
$user_info_values = array_values($user_info);

$user_info_sql = "INSERT INTO $userinfo_table (" . implode(',', $user_info_columns) . ") VALUES (" . implode(',', $user_info_placeholders) . ") ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
$user_info_stmt = $conn->prepare($user_info_sql);
$user_info_stmt->bind_param(str_repeat('s', count($user_info_values)), ...$user_info_values);
$user_info_stmt->execute();
$user_info_id = $conn->insert_id;
$user_info_stmt->close();

ensureColumnsExist($conn, $tablename, $details_columns);

if (is_array($events)) {
    try {
        foreach ($events as $event) {
            $columns = ['type', 'timestamp', 'user_info_id'];
            $placeholders = ['?', '?', '?'];
            $values = [$event['type'], $event['timestamp'], $user_info_id];

            foreach ($event['details'] as $key => $value) {
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
