<?php
session_start();
require_once 'config.php';


initDataFiles();


if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $invite_code = isset($_POST['invite_code']) ? trim($_POST['invite_code']) : '';
    

    if (empty($username) || empty($email) || empty($password)) {
        $error = '请填写所有必填字段';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } elseif ($password !== $confirm_password) {
        $error = '两次输入的密码不一致';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少6位';
    } else {
        $users = getUsers();
        

        if (isset($users[$username])) {
            $error = '用户名已存在';
        } else {

            $emailExists = false;
            foreach ($users as $user) {
                if ($user['email'] === $email) {
                    $emailExists = true;
                    break;
                }
            }
            
            if ($emailExists) {
                $error = '邮箱已被注册';
            } else {

                $inviter_username = null;
                if (!empty($invite_code)) {
                    $inviter_username = useInviteCode($invite_code, $username);
                    if (!$inviter_username) {
                        $error = '邀请码无效或已被使用';
                    }
                }
                
                if (empty($error)) {

                    $users = getUsers();

                    $users[$username] = [
                        'id' => generateId(),
                        'username' => $username,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'points' => !empty($invite_code) ? 50 : 0,
                        'is_admin' => false,
                        'created_at' => date('Y-m-d H:i:s'),
                        'last_login' => null
                    ];
                    
                    if (saveUsers($users)) {
                        if (!empty($invite_code)) {
                            $success = "注册成功！使用邀请码获得50初始积分。";
                        } else {
                            $success = "注册成功！";
                        }
                    } else {
                        $error = '注册失败，请稍后重试';
                    }
                }
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
    <title>注册 - AI代码调试系统</title>
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --warning-color: #ffc107;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        
        .invite-code-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid var(--warning-color);
        }
        
        .invite-code-section h4 {
            color: var(--warning-color);
            margin-bottom: 5px;
        }
        
        .invite-code-section p {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>用户注册</h1>
            <p>创建您的AI代码调试账户</p>
        </div>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo $success; ?></div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="login.php" class="btn btn-primary">立即登录</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">邮箱地址</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">确认密码</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="invite-code-section">
                    <h4>🎁 邀请码（选填）</h4>
                    <p>使用邀请码可获得50初始积分！</p>
                    <input type="text" name="invite_code" placeholder="请输入邀请码">
                </div>
                
                <button type="submit" class="btn btn-primary">注册账户</button>
            </form>
            
            <div class="login-link">
                已有账户？<a href="login.php">立即登录</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>