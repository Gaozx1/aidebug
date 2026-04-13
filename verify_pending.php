<?php
session_start();
require_once 'config.php';


initDataFiles();


if (!isset($_SESSION['temp_user'])) {
    header('Location: login.php');
    exit;
}


$users = getUsers();
$username = $_SESSION['temp_user'];
$user = isset($users[$username]) ? $users[$username] : null;

if (!$user) {
    unset($_SESSION['temp_user']);
    header('Location: login.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {

    $users[$username]['verification_token'] = bin2hex(random_bytes(32));
    $users[$username]['verification_expires'] = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    if (saveUsers($users)) {

        $verification_url = "http://" . $_SERVER['HTTP_HOST'] . "/verify.php?token=" . $users[$username]['verification_token'];
        $subject = "请验证您的邮箱 - AI代码调试系统";
        $message = "
        <h2>邮箱验证提醒</h2>
        <p>亲爱的 {$username}，</p>
        <p>您请求重新发送邮箱验证邮件。请点击下面的链接验证您的邮箱地址：</p>
        <p style='text-align: center; margin: 30px 0;'>
            <a href='{$verification_url}' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-size: 16px;'>验证邮箱</a>
        </p>
        <p>如果按钮无法点击，请复制以下链接到浏览器地址栏：</p>
        <p style='background: #f8f9fa; padding: 10px; border-radius: 3px; word-break: break-all;'>{$verification_url}</p>
        <p><strong>注意：</strong>此验证链接将在24小时后失效。</p>
        <br>
        <p>AI代码调试系统团队</p>
        ";
        
        if (sendEmail($user['email'], $subject, $message)) {
            $success = '验证邮件已重新发送到您的邮箱，请查收。';
        } else {
            $error = '邮件发送失败，请稍后重试或联系管理员。';
        }
    } else {
        $error = '操作失败，请稍后重试。';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮箱验证中 - AI代码调试系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .pending-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .pending-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .pending-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        
        .steps {
            margin: 30px 0;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            margin: 5px;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="pending-container">
        <div class="pending-header">
            <h1>邮箱验证中</h1>
            <p>请完成邮箱验证以继续使用系统</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="message success">
                <?php echo $success; ?>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="message error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>验证邮件已发送</h3>
            <p>我们已向 <strong><?php echo htmlspecialchars($user['email']); ?></strong> 发送了验证邮件，请查收并完成验证。</p>
        </div>
        
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <div>
                    <strong>打开您的邮箱</strong><br>
                    <span style="color: #666;">登录到您注册时使用的邮箱账户</span>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div>
                    <strong>查找验证邮件</strong><br>
                    <span style="color: #666;">邮件主题为"请验证您的邮箱 - AI代码调试系统"</span>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div>
                    <strong>点击验证链接</strong><br>
                    <span style="color: #666;">点击邮件中的验证按钮或链接完成验证</span>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <form method="POST" action="" style="display: inline-block;">
                <button type="submit" name="resend_verification" class="btn btn-primary">重新发送验证邮件</button>
            </form>
            <a href="login.php" class="btn btn-secondary">返回登录</a>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; text-align: center;">
            <p style="color: #666; margin: 0;">
                <strong>没有收到邮件？</strong> 请检查垃圾邮件文件夹，或确保您输入的邮箱地址正确。
            </p>
        </div>
    </div>
</body>
</html>