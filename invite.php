<?php

require_once 'config.php';



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
    $inviteCode = generateInviteCode($_SESSION['user_id']);
    setMessage('邀请码生成成功：' . $inviteCode, 'success');
}

$inviteCodes = getUserInviteCodes($_SESSION['user_id']);
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

        <div class="invite-section" style="background: white; padding: 30px; border-radius: 10px; box-shadow: var(--shadow); margin-bottom: 30px;">
            <h3>🎯 邀请奖励</h3>
            <p>每成功邀请一位好友，您将获得 <strong style="color: var(--success-color);">500积分</strong> 奖励！</p>

            <div class="generate-invite" style="margin: 20px 0;">
                <form method="POST">
                    <button type="submit" name="generate_invite" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px;">
                        🎁 生成邀请码
                    </button>
                </form>
            </div>
        </div>

        <div class="invite-codes" style="background: white; padding: 30px; border-radius: 10px; box-shadow: var(--shadow);">
            <h3>我的邀请码</h3>

            <?php if (empty($inviteCodes)): ?>
                <p style="text-align: center; color: #666; padding: 20px;">暂无邀请码</p>
            <?php else: ?>
                <div class="codes-list">
                    <?php foreach ($inviteCodes as $index => $invite): ?>
                        <div class="code-item" style="border: 1px solid var(--border-color); border-radius: 5px; padding: 15px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: between; align-items: center;">
                                <div>
                                    <strong style="font-size: 18px; color: var(--primary-color);"><?php echo $invite['code']; ?></strong>
                                    <span style="margin-left: 10px; font-size: 12px; color: #666;">
                                        创建时间: <?php echo $invite['created_at']; ?>
                                    </span>
                                </div>
                                <div>
                                    <span style="background: var(--success-color); color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px;">
                                        ♾️ 无限次使用
                                    </span>
                                    <?php if (!empty($invite['used_at']) || !empty($invite['use_count'])): ?>
                                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                            最近使用人: <?php echo htmlspecialchars($invite['used_by'] ?? ''); ?><br>
                                            最近使用时间: <?php echo htmlspecialchars($invite['used_at'] ?? ''); ?><br>
                                            使用次数: <?php echo intval($invite['use_count'] ?? 0); ?> 次
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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