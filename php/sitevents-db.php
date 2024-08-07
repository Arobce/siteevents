<?php
// site-events-db.php

function initializeDatabase($servername, $username, $password, $dbname, $tablename, $userinfo_table) {
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

    // Create user_info table if it doesn't exist
    $create_userinfo_table_sql = "CREATE TABLE IF NOT EXISTS $userinfo_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        remote_addr VARCHAR(45),
        user_agent TEXT,
        request_method VARCHAR(10),
        request_uri TEXT,
        http_referer TEXT,
        script_name TEXT,
        server_name TEXT,
        server_protocol VARCHAR(10),
        query_string TEXT,
        http_accept_language TEXT,
        UNIQUE KEY unique_info (remote_addr, user_agent, request_method, request_uri, http_referer, script_name, server_name, server_protocol, query_string, http_accept_language)
    )";
    if ($conn->query($create_userinfo_table_sql) === FALSE) {
        throw new Exception("Error creating user_info table: " . $conn->error);
    }

    // Create base table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS $tablename (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        timestamp BIGINT NOT NULL,
        user_info_id INT,
        FOREIGN KEY (user_info_id) REFERENCES $userinfo_table(id)
    )";
    if ($conn->query($create_table_sql) === FALSE) {
        throw new Exception("Error creating events table: " . $conn->error);
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
