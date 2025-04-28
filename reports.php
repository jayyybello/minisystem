<?php
require_once 'db_connect.php';

// Fetch all posts with engagement metrics
$posts_query = mysqli_query($conn, "
    SELECT 
        posts.id,
        posts.content,
        post_types.type_name as post_type,
        posts.created_at,
        posts.updated_at,
        users.username,
        users.email,
        engagement.likes,
        engagement.shares,
        engagement.comments
    FROM posts
    JOIN users ON posts.user_id = users.id
    JOIN engagement ON posts.id = engagement.post_id
    JOIN post_types ON posts.post_type_id = post_types.id
    ORDER BY posts.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Social Media Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0072ff;
            --secondary: #00c6ff;
            --dark: #1a2a4a;
            --light: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: white;
            color: var(--primary);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .report-title {
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: var(--primary);
            color: white;
            font-weight: 500;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #f1f1f1;
        }
        
        td {
            color: #333;
        }
        
        .engagement-cell {
            display: flex;
            gap: 1rem;
        }
        
        .engagement-stat {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.9rem;
        }
        
        .post-type {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            background: #e9ecef;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Social Dashboard</div>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
        
        <div class="report-card">
            <h2 class="report-title">📊 Reports</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Post Type</th>
                        <th>Content</th>
                        <th>Engagement</th>
                        <th>Posted On</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($post = mysqli_fetch_assoc($posts_query)): ?>
                    <tr>
                        <td><?= htmlspecialchars($post['username']) ?></td>
                        <td><?= htmlspecialchars($post['email']) ?></td>
                        <td><span class="post-type"><?= ucfirst($post['post_type']) ?></span></td>
                        <td><?= htmlspecialchars(substr($post['content'], 0, 50)) . (strlen($post['content']) > 50 ? '...' : '') ?></td>
                        <td class="engagement-cell">
                            <span class="engagement-stat">👍 <?= $post['likes'] ?></span>
                            <span class="engagement-stat">🔄 <?= $post['shares'] ?></span>
                            <span class="engagement-stat">💬 <?= $post['comments'] ?></span>
                        </td>
                        <td><?= date('M j, Y', strtotime($post['created_at'])) ?></td>
                        <td><?= date('M j, Y', strtotime($post['updated_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 