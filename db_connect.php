<?php
$conn = mysqli_connect('localhost', 'root', '', 'social_dashboard');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


function executeQuery($conn, $sql, $params = []) {
    // Clear any pending results
    while (mysqli_more_results($conn)) {
        mysqli_next_result($conn);
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    return $stmt;
}


function freeAllResults($conn) {
    while (mysqli_more_results($conn)) {
        mysqli_next_result($conn);
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }
}
?>