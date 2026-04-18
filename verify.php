<?php
require_once 'config.php';
configureSession();
session_start();
require_once __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'sidebar.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'styles.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['type']) || !isset($_SESSION['verify_token'])) {
    header('Location: profile.php');
    exit;
}

$type = $_GET['type'];
$token = $_SESSION['verify_token'];
$verification = getEmailVerification($token);

if (!$verification || time() > $verification['expires_at']) {
    header('Location: profile.php?message=验证链接已过期或无效');
    exit;
}

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    
    if (empty($code)) {
        $message = '请输入验证码';
        $message_type = 'error';
    } elseif ($code !== $verification['code']) {
        $message = '验证码错误';
        $message_type = 'error';
    } else {
        // 验证成功，根据类型执行相应操作
        if ($type === 'email') {
            // 更新邮箱
            $users = getUsers();
            $email = $_SESSION['verify_email'];
            $display_name = $_SESSION['verify_display_name'];
            $avatar_url = $_SESSION['verify_avatar_url'];
            
            foreach ($users as $id => $user) {
                if ($user['username'] === $_SESSION['user_id']) {
                    $users[$id]['display_name'] = $display_name;
                    $users[$id]['email'] = $email;
                    $users[$id]['avatar_url'] = $avatar_url;
                    saveUsers($users);
                    $_SESSION['username'] = $display_name;
                    break;
                }
            }
            
            $message = '邮箱验证成功，个人资料已更新';
            $message_type = 'success';
        } elseif ($type === 'password') {
            // 更新密码
            $users = getUsers();
            $new_password = $_SESSION['verify_new_password'];
            
            foreach ($users as $id => $user) {
                if ($user['username'] === $_SESSION['user_id']) {
                    $users[$id]['password'] = hashPassword($new_password);
                    saveUsers($users);
                    break;
                }
            }
            
            $message = '密码修改成功';
            $message_type = 'success';
        }
        
        // 清理验证信息
        deleteEmailVerification($token);
        unset($_SESSION['verify_token']);
        unset($_SESSION['verify_email']);
        unset($_SESSION['verify_display_name']);
        unset($_SESSION['verify_avatar_url']);
        unset($_SESSION['verify_new_password']);
        
        // 3秒后跳转到个人资料页
        echo '<script>setTimeout(function() { window.location.href = "profile.php?message=' . urlencode($message) . '"; }, 3000);</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮箱验证 - <?php echo htmlspecialchars($config['site_name'] ?? 'AI代码调试系统'); ?></title>
    <?php renderStyles(); ?>
    <style>
        .verify-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .dark-mode .verify-container {
            background: var(--dark-bg);
            color: var(--text-light);
        }

        .verify-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .verify-header h2 {
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-dark);
            font-weight: bold;
        }

        .dark-mode .form-group label {
            color: var(--text-light);
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--light-bg);
            color: var(--text-dark);
            font-size: 16px;
        }

        .dark-mode .form-group input {
            background: var(--dark-bg);
            color: var(--text-light);
            border-color: var(--border-color);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .message.success {
            background: var(--success-color);
            color: white;
        }

        .message.error {
            background: var(--danger-color);
            color: white;
        }

        .resend-link {
            text-align: center;
            margin-top: 15px;
        }

        .resend-link a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php renderSidebar('profile'); ?>

    <div class="main-content">
        <div class="content-header">
            <h2>邮箱验证</h2>
            <button class="theme-toggle" onclick="toggleDarkMode()">🌙 深色模式</button>
        </div>

        <div class="verify-container">
            <div class="verify-header">
                <h2>请输入验证码</h2>
                <p>我们已向您的邮箱发送了验证码，请查收</p>
                <p class="email-display">邮箱：<?php echo htmlspecialchars($verification['email']); ?></p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="code">验证码</label>
                    <input type="text" id="code" name="code" placeholder="请输入6位验证码" required maxlength="6">
                </div>
                <button type="submit" class="btn">验证</button>
            </form>

            <div class="resend-link">
                <a href="#" onclick="alert('验证码已重新发送'); return false;">未收到验证码？点击重新发送</a>
            </div>
        </div>
    </div>

    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // 加载深色模式设置
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }

        // 自动聚焦到验证码输入框
        document.getElementById('code').focus();

        // 限制输入为数字
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>