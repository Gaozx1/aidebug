
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
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,123,255,0.05) 0%, rgba(23,162,184,0.05) 100%);
            z-index: 0;
        }

        .main-content > * {
            position: relative;
            z-index: 1;
        }

        .main-content h1 {
            font-size: 3.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: bold;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .main-content p {
            font-size: 1.4rem;
            max-width: 800px;
            margin: 0 auto 40px;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .nav-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
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

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto 60px;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            text-align: left;
        }

        .dark-mode .feature-card {
            background: var(--dark-bg);
            color: var(--text-light);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .feature-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .feature-card p {
            font-size: 1rem;
            margin: 0;
            color: var(--text-dark);
        }

        .dark-mode .feature-card p {
            color: var(--text-light);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .footer {
            text-align: center;
            padding: 30px;
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

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .dark-mode .theme-toggle {
            background: var(--dark-bg);
            border-color: var(--border-color);
        }

        .theme-toggle:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .main-content h1 {
                font-size: 2.5rem;
            }
            
            .main-content p {
                font-size: 1.2rem;
            }
            
            .nav-buttons {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .btn {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleDarkMode()" title="切换主题">🌙</button>
    <div class="language-selector" style="position: fixed; top: 20px; right: 80px; z-index: 1000;">
        <button class="language-toggle" style="background: var(--light-bg); border: 1px solid var(--border-color); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; box-shadow: var(--shadow);" title="切换语言">🌍</button>
        <div class="language-dropdown" style="position: absolute; top: 60px; right: 0; background: var(--light-bg); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow); padding: 10px; display: none;">
            <a href="language_switch.php?lang=zh_CN" style="display: block; padding: 8px 15px; text-decoration: none; color: var(--text-dark); transition: background 0.3s; border-radius: 4px;" class="<?php echo getCurrentLanguage() === 'zh_CN' ? 'active' : ''; ?>"><?php echo getCurrentLanguage() === 'zh_CN' ? '✓ ' : ''; ?>中文</a>
            <a href="language_switch.php?lang=en" style="display: block; padding: 8px 15px; text-decoration: none; color: var(--text-dark); transition: background 0.3s; border-radius: 4px;" class="<?php echo getCurrentLanguage() === 'en' ? 'active' : ''; ?>"><?php echo getCurrentLanguage() === 'en' ? '✓ ' : ''; ?>English</a>
        </div>
    </div>
    
    <div class="main-content">
        <h1><?php echo htmlspecialchars($site_name); ?></h1>
        <p>专业的 AI 代码调试与管理系统，帮助您快速定位和解决代码问题。通过智能分析，提升您的编程效率。</p>

        <div class="nav-buttons">
            <a href="login.php" class="btn btn-primary">登录</a>
            <a href="register.php" class="btn btn-secondary">注册</a>
            <a href="sitemap.html" class="btn btn-info">网站介绍</a>
        </div>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>智能代码分析</h3>
                <p>利用AI技术自动分析代码，快速定位bug和性能问题，提供专业的修复建议。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>详细的分析报告</h3>
                <p>生成全面的代码分析报告，包括问题定位、解决方案和优化建议。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>安全可靠</h3>
                <p>本地存储分析数据，保护您的代码隐私，确保数据安全。</p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2026 Oler Debug. All rights reserved.</p>
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

        // 语言选择器
        document.querySelector('.language-toggle').addEventListener('click', function() {
            const dropdown = document.querySelector('.language-dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        // 点击页面其他地方关闭语言选择器
        document.addEventListener('click', function(event) {
            const languageSelector = document.querySelector('.language-selector');
            if (!languageSelector.contains(event.target)) {
                document.querySelector('.language-dropdown').style.display = 'none';
            }
        });
    </script>
</body>
</html>