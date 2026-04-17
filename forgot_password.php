<?php
require_once 'config.php';
configureSession();
session_start();

initDataFiles();

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址。';
    } else {
        $config = getConfig();
        if (!getConfigValue($config, 'email_enabled', '0')) {
            $error = '系统未启用邮件服务，无法发送密码重置邮件。';
        } else {
            list($userId, $user) = findUserByEmail($email);
            if ($user) {
                $users = getUsers();
                $reset_token = bin2hex(random_bytes(32));
                $users[$userId]['reset_token'] = $reset_token;
                $users[$userId]['reset_token_expires'] = date('Y-m-d H:i:s', strtotime('+1 hour'));

                if (saveUsers($users)) {
                    $reset_url = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $reset_token;
                    $subject = '重置您的密码 - AI代码调试系统';
                    $message = "
                    <h2>密码重置请求</h2>
                    <p>您好 {$user['display_name']}</p>
                    <p>我们收到了您关于重置密码的请求。请点击下面链接设置新的密码：</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$reset_url}' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-size: 16px;'>重置密码</a>
                    </p>
                    <p>如果按钮无法点击，请复制以下链接到浏览器地址栏：</p>
                    <p style='background: #f8f9fa; padding: 10px; border-radius: 3px; word-break: break-all;'>{$reset_url}</p>
                    <p>该链接有效期为1小时。</p>
                    <br>
                    <p>AI代码调试系统团队</p>
                    ";

                    if (sendEmail($email, $subject, $message)) {
                        $message = '如果该邮箱已注册，我们已向其发送密码重置邮件，请注意查收。';
                    } else {
                        $error = '邮件发送失败，请稍后重试或联系管理员。';
                    }
                } else {
                    $error = '服务器保存失败，请稍后重试。';
                }
            } else {
                $message = '如果该邮箱已注册，我们已向其发送密码重置邮件，请注意查收。';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>忘记密码 - <?php echo htmlspecialchars(getConfigValue(getConfig(), 'site_name', 'AI代码调试系统')); ?></title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background-color: #f5f5f5; color: #333; }
        .container { max-width: 420px; margin: 80px auto; background: #fff; padding: 36px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        h1 { margin-bottom: 16px; font-size: 28px; color: #222; }
        p { margin-bottom: 24px; color: #555; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 8px; background: #007bff; color: #fff; font-size: 16px; cursor: pointer; }
        .btn:hover { background: #0056d4; }
        .message { margin-bottom: 18px; padding: 12px 16px; border-radius: 8px; background: #e6f4ea; color: #1f6b3d; }
        .error { margin-bottom: 18px; padding: 12px 16px; border-radius: 8px; background: #fbeaea; color: #a61d2f; }
        .links { text-align: center; margin-top: 18px; }
        .links a { color: #007bff; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>忘记密码</h1>
        <p>输入注册邮箱，我们将发送密码重置链接给您。</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">注册邮箱</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn">发送重置链接</button>
        </form>

        <div class="links">
            <a href="login.php">返回登录</a> | <a href="register.php">注册新账号</a>
        </div>
    </div>
</body>
</html>
