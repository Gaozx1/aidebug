<?php
require_once 'config.php';
configureSession();
session_start();


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
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';

    // 验证验证码（对爬虫友好）
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $is_bot = strpos(strtolower($user_agent), 'bot') !== false || 
              strpos(strtolower($user_agent), 'crawler') !== false || 
              strpos(strtolower($user_agent), 'spider') !== false || 
              strpos(strtolower($user_agent), 'bing') !== false;
    
    if (!$is_bot && !verifyTurnstile($turnstile_token)) {
        $error = '验证码验证失败，请重试';
    } elseif (empty($username) || empty($email) || empty($password)) {
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
                        // 注册成功后提交首页到IndexNow
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                        submitUrlToIndexNow($protocol . $_SERVER['HTTP_HOST'] . '/');

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
    <title>注册 - <?php echo htmlspecialchars($config['site_name'] ?? 'AI代码调试系统'); ?></title>
    <meta name="description" content="注册AI代码调试系统账号，开始使用专业的代码分析和调试服务。">
    <meta name="keywords" content="注册,AI代码调试,代码分析,开发者工具">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/register.php'); ?>">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
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
            --primary-color: #63b3ed;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 450px;
            position: relative;
        }
        
        .dark-mode .register-container {
            background: var(--dark-bg);
            color: var(--text-light);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .register-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 24px;
            font-weight: bold;
        }
        
        .register-header p {
            color: var(--text-dark);
            font-size: 14px;
        }
        
        .dark-mode .register-header p {
            color: var(--text-light);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-dark);
            font-size: 14px;
        }
        
        .dark-mode .form-group label {
            color: var(--text-light);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--light-bg);
            color: var(--text-dark);
            transition: all 0.3s;
        }
        
        .dark-mode .form-group input {
            background: var(--dark-bg);
            color: var(--text-light);
            border-color: var(--border-color);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .invite-code-section {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid var(--warning-color);
            border: 1px solid var(--border-color);
        }
        
        .dark-mode .invite-code-section {
            background: var(--dark-bg);
            border-color: var(--border-color);
        }
        
        .invite-code-section h4 {
            color: var(--warning-color);
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .invite-code-section p {
            font-size: 13px;
            color: var(--text-dark);
            margin-bottom: 12px;
        }
        
        .dark-mode .invite-code-section p {
            color: var(--text-light);
        }
        
        .invite-code-section input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            background: var(--light-bg);
            color: var(--text-dark);
        }
        
        .dark-mode .invite-code-section input {
            background: var(--dark-bg);
            color: var(--text-light);
            border-color: var(--border-color);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-color: var(--danger-color);
        }
        
        .dark-mode .message.error {
            background: rgba(220, 53, 69, 0.1);
            color: #f8d7da;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-color: var(--success-color);
        }
        
        .dark-mode .message.success {
            background: rgba(40, 167, 69, 0.1);
            color: #d4edda;
        }
        
        .login-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .dark-mode .theme-toggle {
            background: var(--dark-bg);
            border-color: var(--border-color);
        }
        
        .theme-toggle:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .cf-turnstile {
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .register-container {
                padding: 30px 20px;
                margin: 20px;
            }
            
            .register-header h1 {
                font-size: 20px;
            }
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <div class="register-container">
        <button class="theme-toggle" onclick="toggleDarkMode()" title="切换主题">🌙</button>
        
        <div class="register-header">
            <h1>用户注册</h1>
            <p>创建您的AI代码调试账户</p>
        </div>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo $success; ?></div>
            <div style="text-align: center; margin-top: 24px;">
                <a href="login.php" class="btn btn-primary">立即登录</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required placeholder="请输入用户名">
                </div>
                
                <div class="form-group">
                    <label for="email">邮箱地址</label>
                    <input type="email" id="email" name="email" required placeholder="请输入邮箱地址">
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required placeholder="请输入密码（至少6位）">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">确认密码</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="请再次输入密码">
                </div>
                
                <div class="invite-code-section">
                    <h4>🎁 邀请码（选填）</h4>
                    <p>使用邀请码可获得50初始积分！</p>
                    <input type="text" name="invite_code" placeholder="请输入邀请码">
                </div>

                <div class="form-group">
                    <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars(getConfigValue(getConfig(), 'turnstile_site_key')); ?>"></div>
                </div>

                <button type="submit" class="btn btn-primary">注册账户</button>
            </form>
            
            <div class="login-link">
                已有账户？<a href="login.php">立即登录</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // 简单的深色模式切换
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // 加载深色模式设置
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>