<?php
// dashboard.php - 重新设计的控制台页面

// 引入配置文件
require_once 'config.php';

// 会话检查和用户验证代码保持不变
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 处理代码调试请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['debug_code'])) {
    $title = trim($_POST['title']);
    $problem = trim($_POST['problem']);
    $code = trim($_POST['code']);
    $evaluation_result = trim($_POST['evaluation_result']);
    
    if (empty($title) || empty($problem) || empty($code) || empty($evaluation_result)) {
        setMessage('请填写所有必填字段', 'error');
    } else {
        // 检查积分是否足够
        if (!canAffordAnalysis($_SESSION['user_id'])) {
            setMessage('积分不足，分析一次需要30积分。请先签到获取积分。', 'error');
        } else {
            $records = getRecords();
            $users = getUsers();
            
            // 创建新记录
            $record_id = generateId();
            $newRecord = [
                'id' => $record_id,
                'user_id' => $_SESSION['user_id'],
                'title' => $title,
                'problem' => $problem,
                'code' => $code,
                'evaluation_result' => $evaluation_result,
                'ai_response' => '',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // 扣除积分
            if (deductAnalysisPoints($_SESSION['user_id'])) {
                // 调用AI分析
                $ai_response = callAIAnalysis($code, $problem);
                $newRecord['ai_response'] = $ai_response;
                $newRecord['status'] = 'completed';
                $newRecord['updated_at'] = date('Y-m-d H:i:s');
                
                // 保存记录
                $records[$record_id] = $newRecord;
                
                if (saveRecords($records)) {
                    setMessage('代码分析完成！已扣除30积分。', 'success');
                    // 跳转到记录详情页面
                    header('Location: records.php?id=' . $record_id);
                    exit;
                } else {
                    // 保存失败，返还积分
                    $current_points = getUserPoints($_SESSION['user_id']);
                    updateUserPoints($_SESSION['user_id'], $current_points + 30);
                    setMessage('提交失败，积分已返还，请稍后重试', 'error');
                }
            } else {
                setMessage('积分扣除失败，请稍后重试', 'error');
            }
        }
    }
}

// 获取用户的调试记录
$records = getRecords();
$userRecords = [];

foreach ($records as $record) {
    if ($record['user_id'] === $_SESSION['user_id']) {
        $userRecords[] = $record;
    }
}

// 按创建时间倒序排列
usort($userRecords, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// 获取最近5条记录用于侧边栏显示
$recentRecords = array_slice($userRecords, 0, 5);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>控制台 - AI代码调试系统</title>
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
        
        /* 侧边栏样式 */
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
        
        /* 主内容区域 */
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
        
        .debug-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .dark-mode .debug-form {
            background: var(--dark-bg);
            color: var(--text-light);
        }
        
        .debug-form h2 {
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--text-dark);
        }

        .dark-mode .form-group label {
            color: var(--text-light);
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            background: white;
            color: var(--text-dark);
        }

        .dark-mode .form-group input,
        .dark-mode .form-group textarea {
            background: #4a5568;
            color: var(--text-light);
            border-color: #718096;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        
        .announcement-banner {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .announcement-banner strong {
            color: #856404;
        }
        
        .cost-info {
            background: var(--warning-color);
            padding: 12px 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
            font-size: 14px;
            color: #856404;
        }
        
        .btn {
            padding: 12px 30px;
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
            transform: translateY(-2px);
        }
        
        .btn-primary:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
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
        
        /* 代码块样式修复 */
        .code-block {
            background: #1e1e1e !important;
            color: #d4d4d4 !important;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
            border: 1px solid #333;
        }
        
        .code-block code {
            background: transparent !important;
            color: #d4d4d4 !important;
            padding: 0 !important;
        }
        
        /* 最近记录样式 */
        .recent-records {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .dark-mode .recent-records {
            background: var(--dark-bg);
        }
        
        .recent-records h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        
        .record-item {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 10px;
        }
        
        .record-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .record-title {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .record-preview {
            font-size: 12px;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark-mode .record-preview {
            color: #a0aec0;
        }
        
        .view-all {
            text-align: center;
            margin-top: 15px;
        }
        
        .view-all a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        /* Markdown样式修复 */
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
        
        .markdown-content pre code {
            background: transparent !important;
            color: #d4d4d4 !important;
            padding: 0 !important;
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
                <li><a href="signin.php" class="btn-success" style="color: white; text-align: center;">每日签到</a></li>
                <li><a href="#debug-form" class="active">代码调试</a></li>
                <li><a href="records.php">查看所有记录</a></li>
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
                <?php foreach ($recentRecords as $record): ?>
                    <div class="record-item">
                        <div class="record-title"><?php echo htmlspecialchars($record['title']); ?></div>
                        <div class="record-preview"><?php echo htmlspecialchars(substr($record['problem'], 0, 50)); ?>...</div>
                        <a href="records.php?id=<?php echo $record['id']; ?>" style="font-size: 12px; color: var(--primary-color);">查看详情</a>
                    </div>
                <?php endforeach; ?>
                <div class="view-all">
                    <a href="records.php">查看全部记录 →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 主内容区域 -->
    <div class="main-content">
        <div class="content-header">
            <h2>代码调试控制台</h2>
            <button class="theme-toggle" onclick="toggleDarkMode()">🌙 深色模式</button>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <?php renderAnnouncementBanner(); ?>
        
        <div class="debug-form" id="debug-form">
            <h2>提交代码调试</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">问题标题 <span style="color: red;">*</span></label>
                    <input type="text" id="title" name="title" placeholder="请输入问题标题" required>
                </div>
                
                <div class="form-group">
                    <label for="problem">题目 <span style="color: red;">*</span></label>
                    <textarea id="problem" name="problem" placeholder="请描述题目要求和需要解决的问题" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="code">代码 <span style="color: red;">*</span></label>
                    <textarea id="code" name="code" placeholder="请粘贴您的代码" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="evaluation_result">评测结果 <span style="color: red;">*</span></label>
                    <textarea id="evaluation_result" name="evaluation_result" placeholder="请描述代码的评测结果或测试用例运行情况" required></textarea>
                </div>
                
                <div class="cost-info">
                    💰 每次分析消耗 <strong>30 积分</strong>，当前积分: <strong><?php echo getUserPoints($_SESSION['user_id']); ?></strong>
                    <?php if (!canAffordAnalysis($_SESSION['user_id'])): ?>
                        <br><span style="color: #dc3545;">积分不足！请先签到获取积分。</span>
                    <?php endif; ?>
                </div>
                
                <button type="submit" name="debug_code" class="btn btn-primary" <?php echo !canAffordAnalysis($_SESSION['user_id']) ? 'disabled' : ''; ?>>
                    <?php echo canAffordAnalysis($_SESSION['user_id']) ? '提交分析' : '积分不足'; ?>
                </button>
            </form>
        </div>
        
        <div class="recent-records">
            <h3>功能说明</h3>
            <div class="markdown-content">
                <h4>📝 如何使用</h4>
                <p>1. 填写问题标题、题目描述、代码内容和评测结果</p>
                <p>2. 每次分析需要消耗 <strong>30 积分</strong></p>
                <p>3. 点击"提交分析"获取AI代码调试建议</p>
                
                <h4>🎯 支持的功能</h4>
                <ul>
                    <li>代码语法分析和错误检测</li>
                    <li>性能优化建议</li>
                    <li>代码风格改进</li>
                    <li>算法优化建议</li>
                </ul>
                
                <h4>💡 示例代码格式</h4>
                <div class="code-block">
// 示例代码
function calculateSum($numbers) {
    $sum = 0;
    foreach ($numbers as $number) {
        $sum += $number;
    }
    return $sum;
}
                </div>
            </div>
        </div>
    </div>

    <script>
        // 深色模式切换
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
        
        // 检查本地存储的深色模式设置
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            document.querySelector('.theme-toggle').textContent = '☀️ 浅色模式';
        }
        
        // 代码高亮示例（可以集成highlight.js等库）
        function highlightCode() {
            const codeBlocks = document.querySelectorAll('.code-block');
            codeBlocks.forEach(block => {
                block.innerHTML = block.textContent;
            });
        }
        
        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            highlightCode();
        });
    </script>
</body>
</html>