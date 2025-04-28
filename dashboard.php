<?php
require_once 'db_connect.php';


$stats = [
    'users' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'],
    'posts' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM posts"))['count'],
    'total_likes' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(likes) as total FROM engagement"))['total'] ?? 0,
    'total_shares' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(shares) as total FROM engagement"))['total'] ?? 0,
    'total_comments' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(comments) as total FROM engagement"))['total'] ?? 0
];


$recent_posts = mysqli_query($conn, "
    SELECT 
        posts.id, 
        posts.content, 
        post_types.type_name as post_type, 
        posts.created_at,
        users.username,
        users.profile_picture,
        engagement.likes,
        engagement.shares,
        engagement.comments
    FROM posts
    JOIN users ON posts.user_id = users.id
    JOIN engagement ON posts.id = engagement.post_id
    JOIN post_types ON posts.post_type_id = post_types.id
    ORDER BY posts.created_at DESC
    LIMIT 5
");


$post_types = mysqli_query($conn, "SELECT id, type_name FROM post_types");


$top_influencers = mysqli_query($conn, "
    SELECT 
        users.id,
        users.username,
        users.profile_picture,
        SUM(engagement.likes + engagement.shares * 2 + engagement.comments * 1.5) as influence_score
    FROM users
    JOIN posts ON users.id = posts.user_id
    JOIN engagement ON posts.id = engagement.post_id
    GROUP BY users.id
    ORDER BY influence_score DESC
    LIMIT 5
");


$user_growth = mysqli_query($conn, "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as new_users,
        SUM(COUNT(*)) OVER (ORDER BY DATE(created_at)) as total_users
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");


$content_analysis = mysqli_query($conn, "
    SELECT 
        post_types.type_name,
        COUNT(posts.id) as post_count,
        AVG(engagement.likes) as avg_likes,
        AVG(engagement.shares) as avg_shares,
        AVG(engagement.comments) as avg_comments
    FROM post_types
    LEFT JOIN posts ON post_types.id = posts.post_type_id
    LEFT JOIN engagement ON posts.id = engagement.post_id
    GROUP BY post_types.id
    ORDER BY post_count DESC
");


$growth_dates = [];
$new_users_data = [];
$total_users_data = [];

while($row = mysqli_fetch_assoc($user_growth)) {
    $growth_dates[] = date('M j', strtotime($row['date']));
    $new_users_data[] = $row['new_users'];
    $total_users_data[] = $row['total_users'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enhanced Social Media Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0072ff;
            --secondary: #00c6ff;
            --dark: #1a2a4a;
            --light: #f8f9fa;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
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
        
        header {
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .posts-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .post {
            display: flex;
            gap: 1rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .post:last-child {
            border-bottom: none;
        }
        
        .post-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .post-content {
            flex: 1;
        }
        
        .post-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .post-username {
            font-weight: 600;
            color: var(--dark);
        }
        
        .post-time {
            font-size: 0.8rem;
            color: #777;
        }
        
        .post-text {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .post-stats {
            display: flex;
            gap: 1.5rem;
        }
        
        .post-stat {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.9rem;
            color: #555;
        }
        
        .post-type {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            background: var(--light);
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--primary);
            margin-top: 0.5rem;
        }
        
        .add-post-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .submit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #0062cc;
            transform: translateY(-2px);
        }

        /* Analytics Section Styles */
        .analytics-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .analytics-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .analytics-title {
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
        }
        
        .influencer-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .influencer-card:last-child {
            border-bottom: none;
        }
        
        .influencer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .influencer-details {
            flex: 1;
        }
        
        .influencer-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.2rem;
        }
        
        .influencer-score {
            font-weight: 600;
            color: var(--primary);
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
            min-height: 250px;
        }
        
        .content-metrics {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .content-metric {
            text-align: center;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .metric-value {
            font-weight: 600;
            color: var(--primary);
        }
        
        .metric-label {
            font-size: 0.8rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">Social Dashboard</div>
            <a href="reports.php" class="btn">View Reports</a>
        </header>
        
       
        <form class="add-post-form" method="POST" action="add_post.php">
            <h2 class="section-title">➕ Add New Post</h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="post_type">Post Type</label>
                    <select id="post_type" name="post_type" required>
                        <?php while($type = mysqli_fetch_assoc($post_types)): ?>
                            <option value="<?= $type['type_name'] ?>"><?= ucfirst($type['type_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="likes">Likes</label>
                    <input type="number" id="likes" name="likes" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="shares">Shares</label>
                    <input type="number" id="shares" name="shares" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="comments">Comments</label>
                    <input type="number" id="comments" name="comments" min="0" value="0">
                </div>
            </div>
            <button type="submit" class="submit-btn">Submit Post</button>
        </form>
        
       
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['posts'] ?></div>
                <div class="stat-label">Total Posts</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_likes'] ?></div>
                <div class="stat-label">Total Likes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_shares'] ?></div>
                <div class="stat-label">Total Shares</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_comments'] ?></div>
                <div class="stat-label">Total Comments</div>
            </div>
        </div>
        
     
        <div class="analytics-section">
            <!-- Top Influencers -->
            <div class="analytics-card">
                <h2 class="analytics-title">🌟 Top Influencers</h2>
                <?php mysqli_data_seek($top_influencers, 0); ?>
                <?php while($influencer = mysqli_fetch_assoc($top_influencers)): ?>
                    <div class="influencer-card">
                        <img src="<?= $influencer['profile_picture'] ?: 'https://via.placeholder.com/40' ?>" 
                             alt="<?= htmlspecialchars($influencer['username']) ?>" class="influencer-avatar">
                        <div class="influencer-details">
                            <div class="influencer-name"><?= htmlspecialchars($influencer['username']) ?></div>
                            <div class="influencer-score">Score: <?= number_format($influencer['influence_score']) ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="analytics-card">
                <h2 class="analytics-title">📈 User Growth (30 Days)</h2>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
            
            
            <div class="analytics-card">
                <h2 class="analytics-title">📊 Content Analysis</h2>
                <?php while($content = mysqli_fetch_assoc($content_analysis)): ?>
                    <div>
                        <h3><?= ucfirst($content['type_name']) ?> (<?= $content['post_count'] ?>)</h3>
                        <div class="content-metrics">
                            <div class="content-metric">
                                <div class="metric-value"><?= number_format($content['avg_likes'], 1) ?></div>
                                <div class="metric-label">Avg Likes</div>
                            </div>
                            <div class="content-metric">
                                <div class="metric-value"><?= number_format($content['avg_shares'], 1) ?></div>
                                <div class="metric-label">Avg Shares</div>
                            </div>
                            <div class="content-metric">
                                <div class="metric-value"><?= number_format($content['avg_comments'], 1) ?></div>
                                <div class="metric-label">Avg Comments</div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        
      
        <div class="posts-container">
            <h2 class="section-title">📝 Recent Posts</h2>
            <?php while($post = mysqli_fetch_assoc($recent_posts)): ?>
                <div class="post">
                    <img src="<?= $post['profile_picture'] ?: 'https://via.placeholder.com/50' ?>" alt="<?= $post['username'] ?>" class="post-avatar">
                    <div class="post-content">
                        <div class="post-header">
                            <span class="post-username"><?= htmlspecialchars($post['username']) ?></span>
                            <span class="post-time"><?= date('M j, Y g:i a', strtotime($post['created_at'])) ?></span>
                        </div>
                        <p class="post-text"><?= htmlspecialchars($post['content']) ?></p>
                        <span class="post-type"><?= ucfirst($post['post_type']) ?></span>
                        <div class="post-stats">
                            <span class="post-stat">👍 <?= $post['likes'] ?> Likes</span>
                            <span class="post-stat">🔄 <?= $post['shares'] ?> Shares</span>
                            <span class="post-stat">💬 <?= $post['comments'] ?> Comments</span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // User Growth Chart
        const ctx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($growth_dates) ?>,
                datasets: [
                    {
                        label: 'New Users',
                        data: <?= json_encode($new_users_data) ?>,
                        backgroundColor: 'rgba(0, 114, 255, 0.2)',
                        borderColor: 'rgba(0, 114, 255, 1)',
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Total Users',
                        data: <?= json_encode($total_users_data) ?>,
                        backgroundColor: 'rgba(0, 198, 255, 0.2)',
                        borderColor: 'rgba(0, 198, 255, 1)',
                        borderWidth: 2,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>