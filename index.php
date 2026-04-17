
<?php
require_once 'config.php';
configureSession();
session_start();
require_once 'components/styles.php';

initDataFiles();

// 如果用户已登录，重定向到仪表板
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// 获取系统配置
$config = getConfig();
$site_name = $config['site_name'] ?? 'AI 代码调试系统';
$announcement = $config['announcement'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_name); ?> - AI代码调试与管理系统</title>
    <meta name="description" content="专业的AI代码调试系统，帮助开发者快速定位和解决代码问题。通过智能分析，提升编程效率，支持多种编程语言。">
    <meta name="keywords" content="AI代码调试,代码分析,编程助手,bug修复,代码优化,开发者工具">
    <meta name="author" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <meta property="og:title" content="<?php echo htmlspecialchars($site_name); ?> - AI代码调试与管理系统">
    <meta property="og:description" content="专业的AI代码调试系统，帮助开发者快速定位和解决代码问题。通过智能分析，提升编程效率。">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/favicon.ico'); ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($site_name); ?> - AI代码调试与管理系统">
    <meta name="twitter:description" content="专业的AI代码调试系统，帮助开发者快速定位和解决代码问题。">
    <link rel="canonical" href="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="sitemap" type="application/xml" href="/sitemap.php">
    <?php renderStyles(); ?>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
            text-align: center;
        }

        .main-content h1 {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: bold;
        }

        .main-content p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto 40px;
            color: var(--text-dark);
        }

        .nav-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: var(--success-color);
            color: white;
        }

        .btn-secondary:hover {
            background: #218838;
        }

        .btn-info {
            background: var(--info-color);
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: var(--dark-bg);
            color: var(--text-light);
            margin-top: auto;
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

        .dark-mode .main-content p {
            color: var(--text-light);
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h1><?php echo htmlspecialchars($site_name); ?></h1>
        <p>专业的 AI 代码调试与管理系统，帮助您快速定位和解决代码问题。通过智能分析，提升您的编程效率。</p>

        <div class="nav-buttons">
            <a href="login.php" class="btn btn-primary">登录</a>
            <a href="register.php" class="btn btn-secondary">注册</a>
            <a href="sitemap.html" class="btn btn-info">网站介绍</a>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2026 Oler Debug. All rights reserved.</p>
    </div>

    <script>
        // 简单的深色模式切换（如果需要）
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