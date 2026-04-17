<?php
require_once 'config.php';
configureSession();
session_start();

initDataFiles();
$config = getConfig();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';

    // 验证验证码
    if (!verifyTurnstile($turnstile_token)) {
        $error = '验证码验证失败，请重试';
    } elseif (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        $users = getUsers();
        
        if (isset($users[$username])) {
            $user = $users[$username];
            if (verifyPassword($password, $user['password'])) {

                $config = getConfig();
                $email_enabled = false;
                if (isset($config['email_enabled'])) {
                    if (is_array($config['email_enabled'])) {
                        $email_enabled = $config['email_enabled']['config_value'] == '1';
                    } else {
                        $email_enabled = $config['email_enabled'] == '1';
                    }
                }
                
                if ($email_enabled && (!isset($user['email_verified']) || !$user['email_verified'])) {

                    $_SESSION['temp_user'] = $username;
                    header('Location: verify_pending.php');
                    exit;
                }
                
                $_SESSION['user_id'] = $username;
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                setMessage('登录成功！', 'success');
                header('Location: dashboard.php');
                exit;
            } else {
                $error = '密码错误';
            }
        } else {
            $error = '用户不存在';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?php echo htmlspecialchars($config['site_name'] ?? 'AI代码调试系统'); ?></title>
    <meta name="description" content="登录到AI代码调试系统，享受专业的代码分析和调试服务。">
    <meta name="keywords" content="登录,AI代码调试,代码分析,开发者工具">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/login.php'); ?>">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
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
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }

        .oauth-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .oauth-btn {
            flex: 1;
            text-align: center;
            display: inline-block;
            padding: 12px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .oauth-btn.github {
            background: #24292f;
        }

        .oauth-btn:hover {
            opacity: 0.9;
        }
        
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #007bff;
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>用户登录</h1>
            <p>欢迎回到AI代码调试系统</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars(getConfigValue(getConfig(), 'turnstile_site_key')); ?>"></div>
            </div>

            <button type="submit" class="btn">登录</button>
        </form>

        <div style="text-align: right; margin-top: 10px;">
            <a href="forgot_password.php">忘记密码？</a>
        </div>

        <div class="oauth-buttons">
            <a class="oauth-btn github" href="oauth_start.php?provider=github">使用 GitHub 登录</a>
        </div>
        
        <div class="links">
            <a href="register.php">还没有账号？立即注册</a> | 
            <a href="index.php">返回首页</a>
        </div>
    </div>
</body>
</html>