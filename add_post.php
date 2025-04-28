<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $username = trim($_POST['username']);
    $content = trim($_POST['content']);
    $post_type_name = $_POST['post_type'];
    $likes = intval($_POST['likes']);
    $shares = intval($_POST['shares']);
    $comments = intval($_POST['comments']);

    
    if (empty($username) || empty($content)) {
        header("Location: dashboard.php?error=empty_fields");
        exit();
    }

    
    $user_id = null;
    $post_type_id = null;
    $post_id = null;
    $error = null;

  
    mysqli_autocommit($conn, false);

    try {
        
        $user_query = executeQuery($conn, "SELECT id FROM users WHERE username = ?", [$username]);
        $user_result = mysqli_stmt_get_result($user_query);
        
        if (mysqli_num_rows($user_result) > 0) {
            $user_row = mysqli_fetch_assoc($user_result);
            $user_id = $user_row['id'];
        } else {
            $email = strtolower(str_replace(' ', '', $username)) . '@example.com';
            $insert_user = executeQuery($conn, "INSERT INTO users (username, email) VALUES (?, ?)", [$username, $email]);
            $user_id = mysqli_insert_id($conn);
        }
        
        
        if (isset($user_result)) {
            mysqli_free_result($user_result);
        }

        $type_query = executeQuery($conn, "SELECT id FROM post_types WHERE type_name = ?", [$post_type_name]);
        $type_result = mysqli_stmt_get_result($type_query);
        
        if (mysqli_num_rows($type_result) === 0) {
            throw new Exception("Invalid post type");
        }
        
        $type_row = mysqli_fetch_assoc($type_result);
        $post_type_id = $type_row['id'];
        mysqli_free_result($type_result);

        
        $insert_post = executeQuery($conn, "INSERT INTO posts (user_id, post_type_id, content) VALUES (?, ?, ?)", 
            [$user_id, $post_type_id, $content]);
        $post_id = mysqli_insert_id($conn);

        executeQuery($conn, "INSERT INTO engagement (post_id, likes, shares, comments) VALUES (?, ?, ?, ?)", 
            [$post_id, $likes, $shares, $comments]);

        mysqli_commit($conn);
        
       
        freeAllResults($conn);

        
        header("Location: dashboard.php?success=1");
        exit();
    } catch (Exception $e) {
       
        mysqli_rollback($conn);
        freeAllResults($conn);
        header("Location: dashboard.php?error=" . urlencode($e->getMessage()));
        exit();
    } finally {
        
        mysqli_autocommit($conn, true);
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>