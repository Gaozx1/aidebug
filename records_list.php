<?php
// records_list.php - 所有记录列表页面

require_once 'config.php';

// 检查组件目录是否存在，如果不存在则创建
if (!file_exists('components')) {
    mkdir('components', 0755, true);
}

// 创建样式组件文件（如果不存在）
$styles_content = '<?php
// components/styles.php - 统一样式组件

function renderStyles() {
    ?>
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
            transition: background-color 0.3s, color 0.3s;
        }
        
        body {
            font-family: \'Arial\', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        
        /* 侧边栏样式 */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: var(--shadow);
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .dark-mode .sidebar {
            background: var(--dark-bg);
            color: var(--text-light);
        }
        
        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .sidebar-header h1 {
            color: var(--primary-color);
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .points-display {
            background: var(--success-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
        
        .sidebar-section {
            margin-bottom: 30px;
        }
        
        .sidebar-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 16px;
            border-left: 3px solid var(--primary-color);
            padding-left: 10px;
        }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 8px;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: var(--text-dark);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .dark-mode .sidebar-nav a {
            color: var(--text-light);
        }
        
        .sidebar-nav a:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }

        .dark-mode .sidebar-nav a:hover {
            background: #4a5568;
        }
        
        .sidebar-nav a.active {
            background: var(--primary-color);
            color: white;
        }
        
        /* 主内容区域 */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .theme-toggle {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }
        
        .message.success {
            background: #d4edda;
            border-color: var(--success-color);
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            border-color: var(--danger-color);
            color: #721c24;
        }
        
        /* 记录列表样式 */
        .records-list {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .dark-mode .records-list {
            background: var(--dark-bg);
        }
        
        .record-item {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s;
        }

        .dark-mode .record-item {
            border-bottom-color: var(--border-color);
        }
        
        .record-item:hover {
            background: var(--light-bg);
        }

        .dark-mode .record-item:hover {
            background: #4a5568;
        }
        
        .record-item:last-child {
            border-bottom: none;
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .record-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
        
        .record-meta {
            font-size: 12px;
            color: #666;
        }

        .dark-mode .record-meta {
            color: var(--text-light);
        }
        
        .record-preview {
            color: #666;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .dark-mode .record-preview {
            color: var(--text-light);
        }
        
        .record-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-completed {
            background: var(--success-color);
            color: white;
        }
        
        .status-pending {
            background: var(--warning-color);
            color: white;
        }
        
        .record-actions {
            margin-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .dark-mode .empty-state {
            color: var(--text-light);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .record-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
    <?php
}
?>';

if (!file_exists('components/styles.php')) {
    file_put_contents('components/styles.php', $styles_content);
}

// 创建侧边栏组件文件（如果不存在）
$sidebar_content = '<?php
// components/sidebar.php - 统一侧边栏组件

function renderSidebar($current_page = \'\') {
    $username = $_SESSION[\'username\'] ?? \'\';
    $user_id = $_SESSION[\'user_id\'] ?? \'\';
    $is_admin = $_SESSION[\'is_admin\'] ?? false;
    $points = getUserPoints($user_id);
    
    // 获取最近记录
    $records = getRecords();
    $userRecords = [];
    foreach ($records as $record) {
        if ($record[\'user_id\'] === $user_id) {
            $userRecords[] = $record;
        }
    }
    usort($userRecords, function($a, $b) {
        return strtotime($b[\'created_at\']) - strtotime($a[\'created_at\]);
    });
    $recentRecords = array_slice($userRecords, 0, 5);
    ?>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>AI代码调试系统</h1>
            <div class="user-info">
                <p>欢迎，<?php echo htmlspecialchars($username); ?></p>
                <div class="points-display">积分: <?php echo $points; ?></div>
            </div>
        </div>
        
        <div class="sidebar-section">
            <h3>快速操作</h3>
            <ul class="sidebar-nav">
                <li><a href="signin.php" class="<?php echo $current_page === \'signin\' ? \'active\' : \'\'; ?>" style="background: white; color: #28a745; border: 2px solid #28a745; text-align: center;">每日签到</a></li>
                <li><a href="dashboard.php" class="<?php echo $current_page === \'dashboard\' ? \'active\' : \'\'; ?>">代码调试</a></li>
                <li><a href="records_list.php" class="<?php echo $current_page === \'records\' ? \'active\' : \'\'; ?>">查看所有记录</a></li>
                <li><a href="invite.php" class="<?php echo $current_page === \'invite\' ? \'active\' : \'\'; ?>">邀请好友</a></li>
                <li><a href="redeem.php" class="<?php echo $current_page === \'redeem\' ? \'active\' : \'\'; ?>">积分兑换</a></li>
                <?php if ($is_admin): ?>
                    <li><a href="admin.php" class="<?php echo $current_page === \'admin\' ? \'active\' : \'\'; ?>">管理后台</a></li>
                <?php endif; ?>
                <li><a href="logout.php">退出登录</a></li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <h3>最近记录</h3>
            <?php if (empty($recentRecords)): ?>
                <p style="color: #666; font-style: italic; text-align: center;">暂无记录</p>
            <?php else: ?>
                <?php foreach ($recentRecords as $record): ?>
                    <div class="record-item">
                        <div class="record-title"><?php echo htmlspecialchars($record[\'title\']); ?></div>
                        <div class="record-preview"><?php echo htmlspecialchars(substr($record[\'problem\'], 0, 50)); ?>...</div>
                        <a href="records.php?id=<?php echo $record[\'id\']; ?>" style="font-size: 12px; color: var(--primary-color);">查看详情</a>
                    </div>
                <?php endforeach; ?>
                <div class="view-all">
                    <a href="records_list.php">查看全部记录 →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>';

if (!file_exists('components/sidebar.php')) {
    file_put_contents('components/sidebar.php', $sidebar_content);
}

// 引入组件文件
require_once 'components/sidebar.php';
require_once 'components/styles.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 获取用户的调试记录
$records = getRecords();
$userRecords = [];

foreach ($records as $record) {
    if ($record['user_id'] === $_SESSION['user_id']) {
        $userRecords[] = $record;
    }
}

// 按创建时间倒序排列
usort($userRecords, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// 获取最近记录用于侧边栏
$recentRecords = array_slice($userRecords, 0, 5);

// 分页设置
$records_per_page = 10;
$total_records = count($userRecords);
$total_pages = ceil($total_records / $records_per_page);

// 获取当前页码
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;

// 计算分页偏移量
$offset = ($current_page - 1) * $records_per_page;
$paginated_records = array_slice($userRecords, $offset, $records_per_page);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>所有调试记录 - AI代码调试系统</title>
    <?php renderStyles(); ?>
</head>
<body>
    <?php renderSidebar('records'); ?>
    
    <div class="main-content">
        <div class="content-header">
            <h2>所有调试记录</h2>
            <button class="theme-toggle" onclick="toggleDarkMode()">🌙 深色模式</button>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>
        
        <div class="records-stats" style="background: white; padding: 20px; border-radius: 10px; box-shadow: var(--shadow); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>总记录数: <?php echo $total_records; ?></strong>
                    <?php if ($total_records > 0): ?>
                        <span style="margin-left: 15px; color: #666;">
                            第 <?php echo $current_page; ?> 页 / 共 <?php echo $total_pages; ?> 页
                        </span>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-primary">新建调试</a>
                </div>
            </div>
        </div>
        
        <div class="records-list">
            <?php if (empty($userRecords)): ?>
                <div class="empty-state">
                    <h3>📝 暂无调试记录</h3>
                    <p>您还没有创建任何调试记录，开始您的第一次代码分析吧！</p>
                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 15px;">开始调试</a>
                </div>
            <?php else: ?>
                <?php foreach ($paginated_records as $record): ?>
                    <div class="record-item">
                        <div class="record-header">
                            <h3 class="record-title"><?php echo htmlspecialchars($record['title']); ?></h3>
                            <div class="record-meta">
                                <span class="record-status <?php echo $record['status'] === 'completed' ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $record['status'] === 'completed' ? '已完成' : '处理中'; ?>
                                </span>
                                <span style="margin-left: 10px;"><?php echo $record['created_at']; ?></span>
                            </div>
                        </div>
                        
                        <div class="record-preview">
                            <strong>题目:</strong> <?php echo htmlspecialchars(substr($record['problem'], 0, 150)); ?><?php echo strlen($record['problem']) > 150 ? '...' : ''; ?>
                        </div>
                        
                        <div class="record-preview">
                            <strong>代码预览:</strong> 
                            <code style="background: #f1f1f1; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace;">
                                <?php echo htmlspecialchars(substr($record['code'], 0, 100)); ?><?php echo strlen($record['code']) > 100 ? '...' : ''; ?>
                            </code>
                        </div>
                        
                        <?php if (!empty($record['ai_response'])): ?>
                            <div class="record-preview">
                                <strong>AI分析:</strong> <?php echo htmlspecialchars(substr($record['ai_response'], 0, 200)); ?><?php echo strlen($record['ai_response']) > 200 ? '...' : ''; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="record-actions">
                            <a href="records.php?id=<?php echo $record['id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 12px;">查看详情</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- 分页导航 -->
                <?php if ($total_pages > 1): ?>
                    <div style="padding: 20px; text-align: center; border-top: 1px solid var(--border-color);">
                        <div style="display: inline-flex; gap: 5px;">
                            <?php if ($current_page > 1): ?>
                                <a href="records_list.php?page=1" class="btn" style="padding: 8px 12px;">首页</a>
                                <a href="records_list.php?page=<?php echo $current_page - 1; ?>" class="btn" style="padding: 8px 12px;">上一页</a>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="records_list.php?page=<?php echo $i; ?>" 
                                   class="btn <?php echo $i == $current_page ? 'btn-primary' : ''; ?>" 
                                   style="padding: 8px 12px;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="records_list.php?page=<?php echo $current_page + 1; ?>" class="btn" style="padding: 8px 12px;">下一页</a>
                                <a href="records_list.php?page=<?php echo $total_pages; ?>" class="btn" style="padding: 8px 12px;">末页</a>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">
                            显示 <?php echo $offset + 1; ?>-<?php echo min($offset + $records_per_page, $total_records); ?> 条，共 <?php echo $total_records; ?> 条记录
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const button = document.querySelector('.theme-toggle');
            if (document.body.classList.contains('dark-mode')) {
                button.textContent = '☀️ 浅色模式';
                localStorage.setItem('darkMode', 'enabled');
            } else {
                button.textContent = '🌙 深色模式';
                localStorage.setItem('darkMode', 'disabled');
            }
        }
        
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            document.querySelector('.theme-toggle').textContent = '☀️ 浅色模式';
        }
        
        // 搜索功能（可选扩展）
        function searchRecords() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const records = document.querySelectorAll('.record-item');
            
            records.forEach(record => {
                const title = record.querySelector('.record-title').textContent.toLowerCase();
                const content = record.querySelector('.record-preview').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || content.includes(searchTerm)) {
                    record.style.display = 'block';
                } else {
                    record.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>