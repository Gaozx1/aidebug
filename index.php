<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI代码调试系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #ffffff;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            padding: 40px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .auth-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
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
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        
        .feature-card h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #666;
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            color: #666;
            border-top: 1px solid #e0e0e0;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AI代码调试系统</h1>
            <p>智能代码分析与调试平台</p>
        </div>
        
        <?php
        session_start();
        if (isset($_SESSION['message'])) {
            echo '<div class="message ' . $_SESSION['message_type'] . '">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        
        if (isset($_SESSION['user_id'])) {
            echo '<div style="text-align: center; margin: 20px 0;">
                    <h2>欢迎回来, ' . $_SESSION['username'] . '!</h2>
                    <div style="margin: 20px 0;">
                        <a href="dashboard.php" class="btn btn-primary">进入控制台</a>
                        <a href="logout.php" class="btn btn-secondary">退出登录</a>
                    </div>
                  </div>';
        } else {
            echo '<div class="auth-buttons">
                    <a href="login.php" class="btn btn-primary">登录</a>
                    <a href="register.php" class="btn btn-secondary">注册</a>
                  </div>';
        }
        ?>
        
        <div class="features">
            <div class="feature-card">
                <h3>智能代码分析</h3>
                <p>使用AI技术分析您的代码，提供详细的调试建议和优化方案</p>
            </div>
            <div class="feature-card">
                <h3>邮件通知</h3>
                <p>通过SMTP邮件服务及时通知调试结果和重要信息</p>
            </div>
            <div class="feature-card">
                <h3>管理员配置</h3>
                <p>管理员可以配置系统参数和查看所有用户的调试记录</p>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2024 AI代码调试系统. 保留所有权利.</p>
        </div>
    </div>
</body>
</html>