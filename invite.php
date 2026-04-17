<?php

require_once 'config.php';
configureSession();


if (!file_exists('components/sidebar.php')) {
    file_put_contents('components/sidebar.php', $sidebar_content);
}

require_once 'components/sidebar.php';
require_once 'components/styles.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invite'])) {
    $users = getUsers();
    $currentUser = null;
    foreach ($users as $id => $user) {
        if ($id === $_SESSION['user_id']) {
            $currentUser = $user;
            break;
        }
    }

    if ($currentUser && empty($currentUser['invite_code'])) {
        $inviteCode = generateInviteCode($_SESSION['user_id']);
        setMessage('邀请码生成成功：' . $inviteCode, 'success');
    } else {
        setMessage('您已经有一个邀请码了，无需重复生成', 'error');
    }
}

$inviteCodes = getUserInviteCodes($_SESSION['user_id']);

// 获取用户的邀请码
$users = getUsers();
$userInviteCode = '';
foreach ($users as $id => $user) {
    if ($id === $_SESSION['user_id']) {
        $userInviteCode = $user['invite_code'] ?? '';
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邀请好友 - Oler Debug</title>
    <?php renderStyles(); ?>
</head>
<body>
    <?php renderSidebar('invite'); ?>

    <div class="main-content">
        <div class="content-header">
            <h2>邀请好友</h2>
            <button class="theme-toggle" onclick="toggleDarkMode()">🌙 深色模式</button>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="invite-stats" style="background: white; padding: 30px; border-radius: 10px; box-shadow: var(--shadow); margin-bottom: 30px;">
            <h3>📊 邀请统计</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: var(--primary-color);"><?php echo count($inviteCodes); ?></div>
                    <div style="color: #666; margin-top: 5px;">已邀请人数</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: var(--success-color);"><?php echo count($inviteCodes) * 100; ?></div>
                    <div style="color: #666; margin-top: 5px;">获得积分</div>
                </div>
            </div>
        </div>

        <div class="invite-codes" style="background: white; padding: 30px; border-radius: 10px; box-shadow: var(--shadow);">
            <h3>我的邀请码</h3>

            <?php if (empty($userInviteCode)): ?>
                <p style="text-align: center; color: #666; padding: 20px;">您还没有生成邀请码</p>
            <?php else: ?>
                <div class="code-item" style="border: 1px solid var(--border-color); border-radius: 5px; padding: 20px; margin-bottom: 15px; background: #f8f9fa;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 24px; color: var(--primary-color); font-family: monospace;"><?php echo $userInviteCode; ?></strong>
                            <div style="margin-top: 10px;">
                                <span style="background: var(--success-color); color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px;">
                                    ♾️ 无限次使用
                                </span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <button onclick="copyToClipboard('<?php echo $userInviteCode; ?>')" class="btn btn-primary" style="margin-bottom: 10px;">
                                📋 复制邀请码
                            </button>
                            <br>
                            <small style="color: #666;">邀请链接：<br><?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/register.php?invite=' . $userInviteCode); ?></small>
                        </div>
                    </div>
                </div>
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
    </script>
</body>
</html>