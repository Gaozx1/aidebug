<?php
require_once 'config.php';
configureSession();

// 清理过期分享
cleanupExpiredShares();

$share_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$share_id) {
    header('Location: index.php');
    exit;
}

$share = getShare($share_id);
if (!$share || !$share['is_active']) {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>分享不存在 - AI代码调试系统</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #dc3545; font-size: 18px; margin-bottom: 20px; }
            .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="error">分享链接不存在或已过期</div>
        <a href="index.php" class="btn">返回首页</a>
    </body>
    </html>';
    exit;
}

$records = getRecords();
$record = isset($records[$share['record_id']]) ? $records[$share['record_id']] : null;

if (!$record) {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>记录不存在 - AI代码调试系统</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #dc3545; font-size: 18px; margin-bottom: 20px; }
            .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="error">分享的记录不存在</div>
        <a href="index.php" class="btn">返回首页</a>
    </body>
    </html>';
    exit;
}

// 更新查看次数
updateShareViewCount($share_id);

$config = getConfig();
$site_name = $config['site_name'] ?? 'AI 代码调试系统';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($record['title'] ?? '分享记录'); ?> - <?php echo htmlspecialchars($site_name); ?></title>
    <meta name="description" content="分享的AI代码调试记录：<?php echo htmlspecialchars($record['title'] ?? '代码调试记录'); ?>">
    <meta name="keywords" content="AI代码调试,代码分析,编程助手,分享记录">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?php echo htmlspecialchars($record['title'] ?? '分享记录'); ?> - <?php echo htmlspecialchars($site_name); ?>">
    <meta property="og:description" content="分享的AI代码调试记录">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <!-- MathJax Configuration -->
    <script>
    window.MathJax = {
      tex: {
        inlineMath: [['$', '$'], ['\\(', '\\)']],
        displayMath: [['$$', '$$'], ['\\[', '\\]']],
        processEnvironments: true,
        macros: {
          "RR": "\\mathbb{R}",
          "NN": "\\mathbb{N}",
          "ZZ": "\\mathbb{Z}",
          "QQ": "\\mathbb{Q}",
          "CC": "\\mathbb{C}",
          "FF": "\\mathbb{F}",
          "PP": "\\mathbb{P}",
          "EE": "\\mathbb{E}",
          "dd": "\\mathrm{d}",
          "ee": "\\mathrm{e}",
          "ii": "\\mathrm{i}",
          "oo": "\\infty",
          "eps": "\\varepsilon"
        }
      },
      options: {
        skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre'],
        ignoreHtmlClass: 'tex2jax_ignore',
        processHtmlClass: 'tex2jax_process'
      },
      loader: {
        load: ['[tex]/color']
      }
    };
    </script>
    <script id="MathJax-script" async src="mathjax/es5/tex-mml-chtml.js"></script>
    
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
            padding: 20px;
        }

        .dark-mode body {
            background-color: var(--dark-bg);
            color: var(--text-light);
        }

        .share-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            text-align: center;
        }

        .dark-mode .share-header {
            background: var(--dark-bg);
        }

        .share-header h1 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 24px;
        }

        .share-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .share-stat {
            background: var(--light-bg);
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
        }

        .dark-mode .share-stat {
            background: #4a5568;
        }

        .record-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .dark-mode .record-content {
            background: var(--dark-bg);
        }

        .record-section {
            margin-bottom: 30px;
        }

        .record-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            border-left: 3px solid var(--primary-color);
            padding-left: 10px;
        }

        .code-block {
            background: #f8f9fa;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }

        .dark-mode .code-block {
            background: #2d3748;
            border-color: #4a5568;
        }

        .ai-response {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid var(--primary-color);
        }

        .dark-mode .ai-response {
            background: #4a5568;
        }

        .share-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: var(--info-color);
        }

        .btn-secondary:hover {
            background: #138496;
        }

        .mjx-chtml {
            font-size: 1.1em !important;
        }

        .mjx-chtml[display="inline"] {
            vertical-align: baseline;
        }

        .mjx-chtml[display="block"] {
            text-align: center;
            margin: 1em 0;
        }

        .latex-formula {
            font-family: "Times New Roman", serif;
            font-style: italic;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 3px;
            margin: 5px 0;
            display: inline-block;
        }

        .dark-mode .latex-formula {
            background: #4a5568;
            color: var(--text-light);
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1000;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .share-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .record-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">切换主题</button>
    
    <div class="share-header">
        <h1><?php echo htmlspecialchars($record['title'] ?? '未命名记录'); ?></h1>
        <p><?php echo htmlspecialchars($record['description'] ?? '分享的AI代码调试记录'); ?></p>
        
        <div class="share-info">
            <div class="share-stat">
                <strong>创建时间：</strong><?php echo htmlspecialchars($record['created_at'] ?? '未知'); ?>
            </div>
            <div class="share-stat">
                <strong>分享时间：</strong><?php echo htmlspecialchars($share['created_at']); ?>
            </div>
            <div class="share-stat">
                <strong>查看次数：</strong><?php echo $share['view_count']; ?>
            </div>
            <div class="share-stat">
                <strong>过期时间：</strong><?php echo htmlspecialchars($share['expires_at']); ?>
            </div>
        </div>
    </div>

    <div class="record-content">
        <?php if (isset($record['problem'])): ?>
        <div class="record-section">
            <h3>题目</h3>
            <div><?php echo markdownToHtml($record['problem']); ?></div>
        </div>
        <?php endif; ?>

        <?php if (isset($record['code'])): ?>
        <div class="record-section">
            <h3>代码</h3>
            <div class="code-block">
                <pre><code><?php echo htmlspecialchars($record['code']); ?></code></pre>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($record['evaluation_result'])): ?>
        <div class="record-section">
            <h3>评测结果</h3>
            <div><?php echo markdownToHtml($record['evaluation_result']); ?></div>
        </div>
        <?php endif; ?>

        <?php if (isset($record['analysis_result'])): ?>
        <div class="record-section">
            <h3>分析结果</h3>
            <div><?php echo markdownToHtml($record['analysis_result']); ?></div>
        </div>
        <?php endif; ?>

        <?php if (isset($record['solution'])): ?>
        <div class="record-section">
            <h3>解决方案</h3>
            <div><?php echo markdownToHtml($record['solution']); ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($record['ai_response'])): ?>
        <div class="record-section">
            <h3>AI分析结果</h3>
            <div class="ai-response">
                <?php echo markdownToHtml($record['ai_response']); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>


    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }

        // 加载保存的主题
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>