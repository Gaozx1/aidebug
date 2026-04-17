<?php

require_once 'config.php';

if (!file_exists('components')) {
    mkdir('components', 0755, true);
}

$styles_content = '<?php

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

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
    <?php
}
?>';

if (!file_exists('components/styles.php')) {
    file_put_contents('components/styles.php', $styles_content);
}

$sidebar_content = '<?php

function renderSidebar($current_page = \'\') {
    $username = $_SESSION[\'username\'] ?? \'\';
    $user_id = $_SESSION[\'user_id\'] ?? \'\';
    $is_admin = $_SESSION[\'is_admin\'] ?? false;
    $points = getUserPoints($user_id);


    $records = getRecords();
    $userRecords = [];
    foreach ($records as $record) {
        if ($record[\'user_id\'] === $user_id) {
            $userRecords[] = $record;
        }
    }
    usort($userRecords, function($a, $b) {
        return strtotime($b[\'created_at\']) - strtotime($a[\'created_at\']);
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
                <li><a href="records_list.php" class="<?php echo $current_page === \'records\' ? \'active\' : \'\'; ?>">查看记录</a></li>
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

require_once 'components/sidebar.php';
require_once 'components/styles.php';
configureSession();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_code'])) {
    $code = trim($_POST['code']);
    $result = useRedeemCode($code, $_SESSION['user_id']);

    if ($result['success']) {
        setMessage($result['message'], 'success');
    } else {
        setMessage($result['message'], 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>积分兑换 - AI代码调试系统</title>
    <?php renderStyles(); ?>
</head>
<body>
    <?php renderSidebar('redeem'); ?>

    <div class="main-content">
        <div class="content-header">
            <h2>积分兑换</h2>
            <button class="theme-toggle" onclick="toggleDarkMode()">🌙 深色模式</button>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="redeem-section" style="background: white; padding: 30px; border-radius: 10px; box-shadow: var(--shadow); margin-bottom: 30px;">
            <h3>🎫 兑换积分</h3>
            <p>输入兑换码即可获得相应积分奖励！</p>

            <form method="POST" style="margin-top: 20px;">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="code" placeholder="请输入兑换码" required
                           style="flex: 1; padding: 12px; border: 1px solid var(--border-color); border-radius: 5px;">
                    <button type="submit" name="redeem_code" class="btn btn-primary" style="padding: 12px 30px;">
                        立即兑换
                    </button>
                </div>
            </form>
        </div>

        <div class="redeem-info" style="background: white; padding: 30px; border-radius: 10px; box-shadow: var(--shadow);">
            <h3>💡 如何获取兑换码？</h3>
            <ul style="margin-left: 20px; line-height: 2;">
                <li>参与官方活动获得</li>
                <li>联系管理员获取</li>
                <li>关注系统公告</li>
            </ul>

            <div style="margin-top: 20px; padding: 15px; background: var(--light-bg); border-radius: 5px;">
                <strong>当前积分: <?php echo getUserPoints($_SESSION['user_id']); ?></strong>
            </div>
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
    </script>
</body>
</html>