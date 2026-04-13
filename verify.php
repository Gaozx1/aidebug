<?php
session_start();
require_once 'config.php';


initDataFiles();

$error = '';
$success = '';


if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    $users = getUsers();
    
    $user_found = false;
    foreach ($users as $username => $user) {
        if (isset($user['verification_token']) && $user['verification_token'] === $token) {
            $user_found = true;
            

            if (strtotime($user['verification_expires']) < time()) {
                $error = '验证链接已过期，请重新注册或联系管理员。';
                break;
            }
            

            $users[$username]['email_verified'] = true;
            $users[$username]['verification_token'] = null;
            $users[$username]['verification_expires'] = null;
            $users[$username]['verified_at'] = date('Y-m-d H:i:s');
            
            if (saveUsers($users)) {
                $success = '邮箱验证成功！您现在可以登录系统了。';
                

                if (isset($_SESSION['temp_user']) && $_SESSION['temp_user'] === $username) {
                    $_SESSION['user_id'] = $username;
                    $_SESSION['username'] = $username;
                    $_SESSION['is_admin'] = $user['is_admin'];
                    unset($_SESSION['temp_user']);
                }
            } else {
                $error = '验证失败，请稍后重试或联系管理员。';
            }
            break;
        }
    }
    
    if (!$user_found) {
        $error = '无效的验证链接，请检查链接是否正确。';
    }
} else {
    $error = '缺少验证参数。';
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮箱验证 - AI代码调试系统</title>
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
        
        .verify-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .verify-header {
            margin-bottom: 30px;
        }
        
        .verify-header h1 {
            color: #333;
            margin-bottom: 10px;
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
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <h1>邮箱验证</h1>
            <p>AI代码调试系统</p>
        </div>
        
        <?php if ($success): ?>
            <div class="message success">
                <?php echo $success; ?>
            </div>
            <div style="margin-top: 30px;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-primary">进入控制台</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">立即登录</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">返回首页</a>
            </div>
        <?php elseif ($error): ?>
            <div class="message error">
                <?php echo $error; ?>
            </div>
            <div style="margin-top: 30px;">
                <a href="register.php" class="btn btn-primary">重新注册</a>
                <a href="index.php" class="btn btn-secondary">返回首页</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>