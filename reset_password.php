<?php
require_once 'config.php';
configureSession();
session_start();

initDataFiles();

$token = trim($_GET['token'] ?? '');
$error = '';
$message = '';
$user = null;
$userId = null;

if (!empty($token)) {
    $users = getUsers();
    foreach ($users as $id => $item) {
        if (!empty($item['reset_token']) && $item['reset_token'] === $token) {
            $user = $item;
            $userId = $id;
            break;
        }
    }
}

if (!$user) {
    $error = '无效或已过期的重置链接。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = '请输入新密码并确认。';
    } elseif ($new_password !== $confirm_password) {
        $error = '两次输入的密码不一致。';
    } elseif (strlen($new_password) < 6) {
        $error = '密码长度至少为6位。';
    } else {
        $expires = isset($user['reset_token_expires']) ? strtotime($user['reset_token_expires']) : 0;
        if ($expires === 0 || time() > $expires) {
            $error = '重置链接已过期，请重新申请。';
        } else {
            $users[$userId]['password'] = hashPassword($new_password);
            unset($users[$userId]['reset_token']);
            unset($users[$userId]['reset_token_expires']);
            if (saveUsers($users)) {
                $message = '密码重置成功，请使用新密码登录。';
                $user = null;
            } else {
                $error = '保存失败，请稍后重试。';
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
    <title>重置密码 - <?php echo htmlspecialchars(getConfigValue(getConfig(), 'site_name', 'AI代码调试系统')); ?></title>
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
        <h1>重置密码</h1>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($user): ?>
            <p>请输入新密码并确认，以完成密码重置。</p>
            <form method="POST" action="?token=<?php echo htmlspecialchars(urlencode($token)); ?>">
                <div class="form-group">
                    <label for="new_password">新密码</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">确认新密码</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">保存新密码</button>
            </form>
        <?php else: ?>
            <p>如果重置页面没有自动跳转，请点击下面链接返回登录。</p>
        <?php endif; ?>

        <div class="links">
            <a href="login.php">返回登录</a>
        </div>
    </div>
</body>
</html>
