<?php

require_once 'config.php';
configureSession();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$record_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$record_id) {
    setMessage('记录ID不存在', 'error');
    header('Location: dashboard.php');
    exit;
}

$records = getRecords();
if (!isset($records[$record_id]) || $records[$record_id]['user_id'] !== $_SESSION['user_id']) {
    setMessage('记录不存在或无权访问', 'error');
    header('Location: dashboard.php');
    exit;
}

$record = $records[$record_id];

$userRecords = [];
foreach ($records as $r) {
    if ($r['user_id'] === $_SESSION['user_id']) {
        $userRecords[] = $r;
    }
}
usort($userRecords, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recentRecords = array_slice($userRecords, 0, 5);

// 获取当前记录的分享信息
$userShares = getUserShares($_SESSION['user_id']);
$currentShare = null;
foreach ($userShares as $share) {
    if ($share['record_id'] === $record_id) {
        $currentShare = $share;
        break;
    }
}

// 处理分享操作
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create_share') {
        $share_id = createShare($record_id, $_SESSION['user_id']);
        setMessage('分享链接已创建', 'success');
        header("Location: records.php?id=$record_id");
        exit;
    } elseif ($_POST['action'] === 'delete_share' && isset($_POST['share_id'])) {
        if (deleteShare($_POST['share_id'])) {
            setMessage('分享链接已删除', 'success');
        } else {
            setMessage('删除分享失败', 'error');
        }
        header("Location: records.php?id=$record_id");
        exit;
    }
}

// 获取当前记录的分享信息
$userShares = getUserShares($_SESSION['user_id']);
$currentShare = null;
foreach ($userShares as $share) {
    if ($share['record_id'] === $record_id) {
        $currentShare = $share;
        break;
    }
}

// 处理分享操作
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create_share') {
        $share_id = createShare($record_id, $_SESSION['user_id']);
        setMessage('分享链接已创建', 'success');
        header("Location: records.php?id=$record_id");
        exit;
    } elseif ($_POST['action'] === 'delete_share' && isset($_POST['share_id'])) {
        if (deleteShare($_POST['share_id'])) {
            setMessage('分享链接已删除', 'success');
        } else {
            setMessage('删除分享失败', 'error');
        }
        header("Location: records.php?id=$record_id");
        exit;
    }
}
?>

<?php
function generateShareUrl($share_id) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host . '/share.php?id=' . $share_id;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>记录详情 - AI代码调试系统</title>
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
            transition: background-color 0.3s, color 0.3s;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }


        .sidebar {
            width: 280px;
            background: white;
            box-shadow: var(--shadow);
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .dark-mode .sidebar {
            background: var(--dark-bg);
            color: var(--text-light);
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .sidebar-header h1 {
            color: var(--primary-color);
            font-size: 20px;
            margin-bottom: 10px;
        }

        .user-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .points-display {
            background: var(--success-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }

        .sidebar-section {
            margin-bottom: 30px;
        }

        .sidebar-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 16px;
            border-left: 3px solid var(--primary-color);
            padding-left: 10px;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 8px;
        }

        .sidebar-nav a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: var(--text-dark);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .dark-mode .sidebar-nav a {
            color: var(--text-light);
        }

        .sidebar-nav a:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }

        .dark-mode .sidebar-nav a:hover {
            background: #4a5568;
        }

        .sidebar-nav a.active {
            background: var(--primary-color);
            color: white;
        }


        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .theme-toggle {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .record-detail {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .dark-mode .record-detail {
            background: var(--dark-bg);
            color: var(--text-light);
        }

        .record-section {
            margin-bottom: 30px;
        }

        .record-section h2 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .code-block {
            background: #1e1e1e !important;
            color: #d4d4d4 !important;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            white-space: pre-wrap;
            border: 1px solid #333;
        }

        .dark-mode .code-block {
            background: #2d3748 !important;
            color: #f8f9fa !important;
            border-color: #4a5568 !important;
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

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .record-meta {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }

        .dark-mode .record-meta {
            background: #4a5568;
            color: var(--text-light);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .message.success {
            background: #d4edda;
            border-color: var(--success-color);
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            border-color: var(--danger-color);
            color: #721c24;
        }


        .markdown-content {
            line-height: 1.6;
        }

        .markdown-content h1, .markdown-content h2, .markdown-content h3 {
            margin: 20px 0 10px 0;
            color: var(--text-dark);
        }

        .dark-mode .markdown-content h1,
        .dark-mode .markdown-content h2,
        .dark-mode .markdown-content h3 {
            color: var(--text-light);
        }

        .markdown-content p {
            margin-bottom: 15px;
        }

        .markdown-content code:not(pre code) {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #d63384;
        }

        .dark-mode .markdown-content code:not(pre code) {
            background: #4a5568;
            color: #fbb6ce;
        }

        .markdown-content pre {
            background: #1e1e1e !important;
            color: #d4d4d4 !important;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #333;
            margin: 15px 0;
        }

        .dark-mode .markdown-content pre {
            background: #2d3748 !important;
            border-color: #4a5568 !important;
            color: #f8f9fa !important;
        }

        .markdown-content pre code {
            background: transparent !important;
            color: #d4d4d4 !important;
            padding: 0 !important;
        }

        .dark-mode .markdown-content pre code {
            color: #f8f9fa !important;
        }

        .markdown-content blockquote {
            border-left: 4px solid var(--primary-color);
            padding-left: 15px;
            margin: 15px 0;
            color: #666;
            font-style: italic;
        }

        .dark-mode .markdown-content blockquote {
            color: #a0aec0;
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

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="">
    <!-- 侧边栏 -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>AI代码调试系统</h1>
            <div class="user-info">
                <p>欢迎，<?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <div class="points-display">积分: <?php echo getUserPoints($_SESSION['user_id']); ?></div>
            </div>
        </div>

        <div class="sidebar-section">
            <h3>快速操作</h3>
            <ul class="sidebar-nav">
                <li><a href="signin.php" style="background: white; color: #28a745; border: 2px solid #28a745; text-align: center;">每日签到</a></li>
                <li><a href="dashboard.php">返回控制台</a></li>
                <li><a href="records_list.php">查看所有记录</a></li>
                <li><a href="invite.php">邀请好友</a></li>
                <?php if ($_SESSION['is_admin']): ?>
                    <li><a href="admin.php">管理后台</a></li>
                <?php endif; ?>
                <li><a href="logout.php">退出登录</a></li>
            </ul>
        </div>

        <div class="sidebar-section">
            <h3>最近记录</h3>
            <?php if (empty($recentRecords)): ?>
                <p style="color: #666; font-style: italic; text-align: center;">暂无记录</p>
            <?php else: ?>
                <?php foreach ($recentRecords as $r): ?>
                    <div class="record-item">
                        <div class="record-title"><?php echo htmlspecialchars($r['title']); ?></div>
                        <div class="record-preview"><?php echo htmlspecialchars(substr($r['problem'], 0, 50)); ?>...</div>
                        <a href="records.php?id=<?php echo $r['id']; ?>" style="font-size: 12px; color: var(--primary-color);">查看详情</a>
                    </div>
                <?php endforeach; ?>
                <div class="view-all">
                    <a href="records_list.php">查看全部记录 →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 主内容区域 -->
    <div class="main-content">
        <div class="content-header">
            <h2>记录详情</h2>
            <button class="theme-toggle" onclick="toggleDarkMode()">🌙 深色模式</button>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="record-detail">
            <!-- 分享功能 -->
            <div class="record-section" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                <h2 style="margin-top: 0;">分享此记录</h2>
                <?php if ($currentShare): ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                            <div style="background: #e8f5e8; padding: 10px 15px; border-radius: 5px; font-size: 14px; flex: 1;">
                                <strong>✅ 已分享</strong><br>
                                <small>查看次数: <?php echo $currentShare['view_count']; ?> | 
                                创建时间: <?php echo date('Y-m-d H:i', strtotime($currentShare['created_at'])); ?> | 
                                过期时间: <?php echo date('Y-m-d H:i', strtotime($currentShare['expires_at'])); ?></small>
                            </div>
                            <button onclick="copyShareLink('<?php echo generateShareUrl($currentShare['share_id']); ?>')" 
                                    class="btn btn-primary" style="margin: 0;">
                                📋 复制分享链接
                            </button>
                            <form method="post" style="margin: 0;">
                                <input type="hidden" name="action" value="delete_share">
                                <input type="hidden" name="share_id" value="<?php echo $currentShare['share_id']; ?>">
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('确定要删除分享链接吗？删除后其他人将无法访问此记录。')">
                                    🗑️ 删除分享
                                </button>
                            </form>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            <strong>分享链接：</strong>
                            <code style="background: #e9ecef; padding: 2px 5px; border-radius: 3px;">
                                <?php echo generateShareUrl($currentShare['share_id']); ?>
                            </code>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <p style="margin-bottom: 15px;">创建分享链接，让其他人无需登录即可查看此记录</p>
                        <form method="post">
                            <input type="hidden" name="action" value="create_share">
                            <button type="submit" class="btn btn-primary" style="font-size: 16px; padding: 10px 20px;">
                                📤 创建分享链接
                            </button>
                        </form>
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">
                            分享链接有效期为30天，可随时删除
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="record-section">
                <h2>问题标题</h2>
                <p><?php echo htmlspecialchars($record['title']); ?></p>
            </div>

            <div class="record-section">
                <h2>题目</h2>
                <?php echo markdownToHtml($record['problem']); ?>
            </div>

            <div class="record-section">
                <h2>代码</h2>
                <div class="code-block"><?php echo htmlspecialchars($record['code']); ?></div>
            </div>

            <div class="record-section">
                <h2>评测结果</h2>
                <?php echo markdownToHtml($record['evaluation_result']); ?>
            </div>

            <?php if (!empty($record['ai_response'])): ?>
            <div class="record-section">
                <h2>AI分析结果</h2>
                <div class="ai-response">
                    <?php echo markdownToHtml($record['ai_response']); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="record-meta">
                <p><strong>创建时间：</strong><?php echo $record['created_at']; ?></p>
                <p><strong>更新时间：</strong><?php echo $record['updated_at']; ?></p>
                <p><strong>状态：</strong><?php echo $record['status'] === 'completed' ? '已完成' : '处理中'; ?></p>
            </div>

            <div style="margin-top: 20px;">
                <a href="dashboard.php" class="btn btn-primary">返回控制台</a>
            </div>
        </div>
    </div>

    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const button = document.querySelector('.theme-toggle');
            if (document.body.classList.contains('dark-mode')) {
                button.textContent = '☀️ 浅色模式';
                localStorage.setItem('darkMode', 'enabled');
            } else {
                button.textContent = '🌙 深色模式';
                localStorage.setItem('darkMode', 'disabled');
            }
        }

        function copyShareLink(url) {
            navigator.clipboard.writeText(url).then(function() {
                alert('分享链接已复制到剪贴板');
            }, function(err) {
                console.error('复制失败: ', err);
            });
        }

        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            document.querySelector('.theme-toggle').textContent = '☀️ 浅色模式';
        }
    </script>

    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const button = document.querySelector('.theme-toggle');
            if (document.body.classList.contains('dark-mode')) {
                button.textContent = '☀️ 浅色模式';
                localStorage.setItem('darkMode', 'enabled');
            } else {
                button.textContent = '🌙 深色模式';
                localStorage.setItem('darkMode', 'disabled');
            }
        }

        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            document.querySelector('.theme-toggle').textContent = '☀️ 浅色模式';
        }

        // 复制分享链接
        function copyShareLink(url) {
            navigator.clipboard.writeText(url).then(function() {
                alert('分享链接已复制到剪贴板！');
            }, function() {
                // 兼容旧版浏览器
                var textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('分享链接已复制到剪贴板！');
            });
        }
    </script>

</body>
</html>