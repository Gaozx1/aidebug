<?php
require_once 'config.php';
configureSession();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 清理过期分享
cleanupExpiredShares();

// 获取用户的所有分享
$userShares = getUserShares($_SESSION['user_id']);

// 处理删除操作
if (isset($_POST['action']) && $_POST['action'] === 'delete_share' && isset($_POST['share_id'])) {
    if (deleteShare($_POST['share_id'])) {
        setMessage('分享链接已删除', 'success');
    } else {
        setMessage('删除分享失败', 'error');
    }
    header('Location: shares.php');
    exit;
}

// 获取记录信息用于显示
$records = getRecords();
$config = getConfig();
$site_name = $config['site_name'] ?? 'AI 代码调试系统';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分享管理 - <?php echo htmlspecialchars($site_name); ?></title>
    <meta name="description" content="管理您的AI代码调试记录分享">
    <meta name="keywords" content="分享管理,AI代码调试,记录分享">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --text-light: #f8f9fa;
            --text-dark: #343a40;
            --border-color: #dee2e6;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .dark-mode {
            --light-bg: #2d3748;
            --dark-bg: #1a202c;
            --text-light: #f7fafc;
            --text-dark: #e2e8f0;
            --border-color: #4a5568;
            --shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
            padding: 20px;
        }

        .dark-mode body {
            background-color: var(--dark-bg);
            color: var(--text-light);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .dark-mode .header {
            background: var(--dark-bg);
        }

        .header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            background: var(--light-bg);
            padding: 15px 25px;
            border-radius: 8px;
            text-align: center;
            min-width: 120px;
        }

        .dark-mode .stat-item {
            background: #4a5568;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .shares-list {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .dark-mode .shares-list {
            background: var(--dark-bg);
        }

        .share-item {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .share-item:last-child {
            border-bottom: none;
        }

        .share-info {
            flex: 1;
            min-width: 300px;
        }

        .share-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .share-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .dark-mode .share-meta {
            color: #a0aec0;
        }

        .share-url {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            word-break: break-all;
        }

        .dark-mode .share-url {
            background: #4a5568;
        }

        .share-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
            color: white;
        }

        .btn-primary {
            background: var(--primary-color);
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-danger {
            background: var(--danger-color);
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .dark-mode .empty-state {
            color: #a0aec0;
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1000;
        }

        .navigation {
            text-align: center;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .share-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .share-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">切换主题</button>
    
    <div class="container">
        <div class="header">
            <h1>分享管理</h1>
            <p>管理您创建的分享链接</p>
            
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($userShares); ?></div>
                    <div>活跃分享</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo array_sum(array_column($userShares, 'view_count')); ?></div>
                    <div>总查看次数</div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div style="background: <?php echo $_SESSION['message_type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; 
                     color: <?php echo $_SESSION['message_type'] === 'success' ? '#155724' : '#721c24'; ?>; 
                     padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid <?php echo $_SESSION['message_type'] === 'success' ? '#28a745' : '#dc3545'; ?>;">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="shares-list">
            <?php if (empty($userShares)): ?>
                <div class="empty-state">
                    <h3>暂无分享记录</h3>
                    <p>您还没有创建任何分享链接</p>
                    <a href="records_list.php" class="btn btn-primary" style="margin-top: 15px;">去创建分享</a>
                </div>
            <?php else: ?>
                <?php foreach ($userShares as $share): ?>
                    <?php $record = isset($records[$share['record_id']]) ? $records[$share['record_id']] : null; ?>
                    <div class="share-item">
                        <div class="share-info">
                            <div class="share-title">
                                <?php echo $record ? htmlspecialchars($record['title']) : '记录已删除'; ?>
                            </div>
                            <div class="share-meta">
                                查看次数: <?php echo $share['view_count']; ?> | 
                                创建时间: <?php echo date('Y-m-d H:i', strtotime($share['created_at'])); ?> | 
                                过期时间: <?php echo date('Y-m-d H:i', strtotime($share['expires_at'])); ?>
                            </div>
                            <div class="share-url">
                                <?php echo generateShareUrl($share['share_id']); ?>
                            </div>
                        </div>
                        <div class="share-actions">
                            <button onclick="copyShareLink('<?php echo generateShareUrl($share['share_id']); ?>')" 
                                    class="btn btn-primary">复制链接</button>
                            <a href="records.php?id=<?php echo $share['record_id']; ?>" 
                               class="btn" style="background: var(--info-color);">查看记录</a>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="delete_share">
                                <input type="hidden" name="share_id" value="<?php echo $share['share_id']; ?>">
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('确定要删除此分享链接吗？删除后其他人将无法访问此记录。')">
                                    删除
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="navigation">
            <a href="dashboard.php" class="btn btn-primary">返回控制台</a>
            <a href="records_list.php" class="btn" style="background: var(--info-color); margin-left: 10px;">查看所有记录</a>
        </div>
    </div>

    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }

        // 加载保存的主题
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }

        // 复制分享链接
        function copyShareLink(url) {
            navigator.clipboard.writeText(url).then(function() {
                alert('分享链接已复制到剪贴板！');
            }, function() {
                // 兼容旧版浏览器
                var textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('分享链接已复制到剪贴板！');
            });
        }
    </script>
</body>
</html>