<?php
// site-events-db.php

function initializeDatabase($servername, $username, $password, $dbname, $tablename) {
    $conn = new mysqli($servername, $username, $password);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database if it doesn't exist
    $db_selected = $conn->select_db($dbname);
    if (!$db_selected) {
        $create_db_sql = "CREATE DATABASE $dbname";
        if ($conn->query($create_db_sql) === TRUE) {
            $conn->select_db($dbname);
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
    } else {
        $conn->select_db($dbname);
    }

    // Create base table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS $tablename (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        timestamp BIGINT NOT NULL
    )";
    if ($conn->query($create_table_sql) === FALSE) {
        throw new Exception("Error creating table: " . $conn->error);
    }

    return $conn;
}

function ensureColumnsExist($conn, $tablename, $columns) {
    foreach ($columns as $column => $type) {
        $result = $conn->query("SHOW COLUMNS FROM $tablename LIKE '$column'");
        if ($result->num_rows == 0) {
            $conn->query("ALTER TABLE $tablename ADD $column $type");
        }
    }
}

?>
