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

// 处理代码调试请求 - 客户端API调用版本
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['debug_code'])) {
    header('Content-Type: application/json');
    $title = trim($_POST['title']);
    $problem = trim($_POST['problem']);
    $code = trim($_POST['code']);
    $evaluation_result = trim($_POST['evaluation_result']);
    
    if (empty($title) || empty($problem) || empty($code) || empty($evaluation_result)) {
                echo json_encode(['success' => false, 'message' => '请填写所有必填字段']);
                exit;
            } else {
        // 计算代码行数和所需积分
        $code_lines = calculateCodeLines($code);
        $required_points = calculateRequiredPoints($code_lines);

        // 检查积分是否足够
        if (!canAffordAnalysisByCode($_SESSION['user_id'], $code)) {
                    echo json_encode(['success' => false, 'message' => "积分不足，分析{$code_lines}行代码需要{$required_points}积分。请先签到获取积分。"]);
                    exit;
            } else {
            $records = getRecords();
            $users = getUsers();
            
            // 创建新记录（状态为pending，等待客户端API调用完成）
            $record_id = generateId();
            $newRecord = [
                'id' => $record_id,
                'user_id' => $_SESSION['user_id'],
                'title' => $title,
                'problem' => $problem,
                'code' => $code,
                'evaluation_result' => $evaluation_result,
                'ai_response' => '',
                'status' => 'pending', // 等待客户端API调用
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // 保存记录（不扣积分，等待客户端完成）
            $records[$record_id] = $newRecord;
            
            if (saveRecords($records)) {
                            // 返回记录ID给客户端，让客户端进行API调用
                            $_SESSION['pending_record_id'] = $record_id;
                            $_SESSION['pending_code_lines'] = $code_lines;
                            $_SESSION['pending_required_points'] = $required_points;
                
                            echo json_encode(['success' => true, 'record_id' => $record_id]);
                            exit;
                        } else {
                echo json_encode(['success' => false, 'message' => '保存记录失败，请稍后重试']);
                exit;
            }
        }
    }
}

// 提供API配置给客户端（不包含完整的API密钥）
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_api_config'])) {
    header('Content-Type: application/json');
    $config = getConfig();
    $api_key = getConfigValue($config, 'api_key');
    
    if (empty($api_key)) {
        echo json_encode(['success' => false, 'message' => 'API密钥未配置']);
        exit;
    }
    
    // 只返回API密钥的前几位和后几位，用于验证
    $key_length = strlen($api_key);
    $masked_key = substr($api_key, 0, 8) . '...' . substr($api_key, -4);
    
    echo json_encode([
        'success' => true,
        'api_key' => $api_key, // 实际返回完整密钥用于客户端调用
        'api_base_url' => getConfigValue($config, 'api_base_url') ?: 'https://api.openai.com/v1',
        'api_model' => getConfigValue($config, 'api_model') ?: 'gpt-3.5-turbo',
        'masked_key' => $masked_key,
        'key_length' => $key_length
    ]);
    exit;
}

// 处理客户端上传的AI分析结果
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_ai_result'])) {
    header('Content-Type: application/json');
    $record_id = trim($_POST['record_id']);
    $ai_response = trim($_POST['ai_response']);
    
    if (empty($record_id) || empty($ai_response)) {
        echo json_encode(['success' => false, 'message' => '缺少必要参数']);
        exit;
    }
    
    $records = getRecords();
    
    if (!isset($records[$record_id]) || $records[$record_id]['user_id'] !== $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => '记录不存在或无权访问']);
        exit;
    }
    
    // 检查AI响应是否包含错误
    $error_keywords = ['API请求失败', 'cURL错误', 'HTTP 错误', '响应格式错误'];
    $is_error = false;
    foreach ($error_keywords as $keyword) {
        if (strpos($ai_response, $keyword) !== false) {
            $is_error = true;
            break;
        }
    }
    
    if ($is_error || empty($ai_response)) {
        // AI分析失败，删除记录，不扣积分
        unset($records[$record_id]);
        saveRecords($records);
        echo json_encode(['success' => false, 'message' => 'AI分析失败：' . ($ai_response ?: '无响应')]);
        exit;
    }
    
    // AI分析成功，更新记录并扣除积分
    $code_lines = calculateCodeLines($records[$record_id]['code']);
    $required_points = calculateRequiredPoints($code_lines);
    
    if (deductAnalysisPointsByCode($_SESSION['user_id'], $records[$record_id]['code'])) {
        $records[$record_id]['ai_response'] = $ai_response;
        $records[$record_id]['status'] = 'completed';
        $records[$record_id]['updated_at'] = date('Y-m-d H:i:s');
        
        if (saveRecords($records)) {
            echo json_encode(['success' => true, 'message' => "代码分析完成！已扣除{$required_points}积分（{$code_lines}行代码）。", 'record_id' => $record_id]);
        } else {
            // 记录保存失败，返还积分
            $current_points = getUserPoints($_SESSION['user_id']);
            updateUserPoints($_SESSION['user_id'], $current_points + $required_points);
            echo json_encode(['success' => false, 'message' => '保存记录失败，积分已返还，请稍后重试']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '积分扣除过程中出现异常，请稍后重试']);
        exit;
    }
    exit;
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

        .dark-mode .code-block {
            background: #2d3748 !important;
            color: #f8f9fa !important;
            border-color: #4a5568 !important;
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
        
        /* MathJax 样式 */
        .mjx-chtml {
            font-size: 1.1em !important;
        }
        
        /* 行内公式样式 */
        .mjx-chtml[display="inline"] {
            vertical-align: baseline;
        }
        
        /* 块级公式样式 */
        .mjx-chtml[display="block"] {
            text-align: center;
            margin: 1em 0;
        }
        
        /* 公式示例样式 */
        .formula-examples {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid var(--primary-color);
        }

        .dark-mode .formula-examples {
            background: #4a5568;
        }

        .formula-examples ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .formula-examples li {
            margin: 5px 0;
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
            <form method="POST" action="" id="debug-form">
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
                
                <!-- API配置信息（从服务器获取） -->
                <div class="form-group" id="api-config-section">
                    <label>API配置信息</label>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #007bff;">
                        <small style="color: #666;">
                            <strong>使用系统配置的API：</strong><br>
                            API密钥：<?php echo getConfigValue(getConfig(), 'api_key') ? '已配置' : '未配置'; ?><br>
                            基础URL：<?php echo getConfigValue(getConfig(), 'api_base_url') ?: 'https://api.openai.com/v1'; ?><br>
                            模型：<?php echo getConfigValue(getConfig(), 'api_model') ?: 'gpt-3.5-turbo'; ?>
                        </small>
                    </div>
                    <small style="color: #666; font-size: 12px;">API调用将在客户端进行，使用系统配置的密钥</small>
                </div>
                
                <div class="cost-info">
                    💰 分析费用: <strong id="required-points">30</strong> 积分 (<span id="code-lines">0</span> 行代码)
                    <br>当前积分: <strong><?php echo getUserPoints($_SESSION['user_id']); ?></strong>
                    <div id="insufficient-warning" style="color: #dc3545; display: none;">积分不足！请先签到获取积分。</div>
                </div>

                <script>
                function calculateCost() {
                    const code = document.getElementById('code').value;
                    const lines = code.split('\n').filter(line => line.trim() !== '').length;
                    const basePoints = 30;
                    const freeLines = 200;
                    const extraChargeLines = 100;
                    const extraChargePoints = 10;

                    let requiredPoints = basePoints;
                    if (lines > freeLines) {
                        const extraLines = lines - freeLines;
                        const extraCharges = Math.ceil(extraLines / extraChargeLines);
                        requiredPoints += extraCharges * extraChargePoints;
                    }

                    document.getElementById('code-lines').textContent = lines;
                    document.getElementById('required-points').textContent = requiredPoints;

                    const currentPoints = <?php echo getUserPoints($_SESSION['user_id']); ?>;
                    const warning = document.getElementById('insufficient-warning');
                    const submitBtn = document.getElementById('submit-btn');

                    if (currentPoints >= requiredPoints) {
                        warning.style.display = 'none';
                        submitBtn.disabled = false;
                    } else {
                        warning.style.display = 'block';
                        submitBtn.disabled = true;
                    }
                }

                // 监听代码输入变化
                document.getElementById('code').addEventListener('input', calculateCost);
                // 页面加载时计算一次
                calculateCost();
                </script>
                
                <button type="button" class="btn btn-primary" id="submit-btn" onclick="submitAnalysis()">
                    <?php echo canAffordAnalysis($_SESSION['user_id']) ? '提交分析（本地API调用）' : '积分不足'; ?>
                </button>
                
                <!-- 进度显示区域 -->
                <div id="progress-section" style="display: none; margin-top: 20px;">
                    <div class="message info" id="progress-message">正在创建记录...</div>
                    <div class="progress-bar" style="background: #f0f0f0; height: 10px; border-radius: 5px; margin: 10px 0;">
                        <div id="progress-fill" style="background: #007bff; height: 100%; width: 0%; border-radius: 5px; transition: width 0.3s;"></div>
                    </div>
                    <div id="progress-text">0%</div>
                </div>
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
                <h4>📐 数学公式示例</h4>
                <div class="formula-examples">
                    <p><strong>行内公式：</strong> $O(\log(\text{Range}) \cdot N^2)$ $\rightarrow$ 优化</p>
                    <p><strong>块级公式：</strong></p>
                    $$\int_0^\infty e^{-x^2} \, dx = \frac{\sqrt{\pi}}{2}$$
                    <p><strong>常用语法：</strong></p>
                    <ul>
                        <li>分数：$\frac{a}{b}$</li>
                        <li>上标下标：$x^2$, $x_{sub}$</li>
                        <li>求和积分：$\sum_{i=1}^n x_i$, $\int_a^b f(x) \, dx$</li>
                        <li>希腊字母：$\alpha, \beta, \gamma, \delta$</li>
                        <li>文本混排：$\text{时间复杂度} O(n\log n)$</li>
                    </ul>
                </div>                
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
        
        // 客户端API调用功能
        async function submitAnalysis() {
            const title = document.getElementById('title').value;
            const problem = document.getElementById('problem').value;
            const code = document.getElementById('code').value;
            const evaluationResult = document.getElementById('evaluation_result').value;
            
            // 验证输入
            if (!title || !problem || !code || !evaluationResult) {
                alert('请填写所有必填字段');
                return;
            }
            
            // 显示进度
            showProgress('正在获取API配置...', 0);
            
            try {
                // 1. 从服务器获取API配置
                const configResponse = await fetch('dashboard.php?get_api_config=1');
                const configResult = await configResponse.json();
                
                if (!configResult.success) {
                    throw new Error(configResult.message);
                }
                
                const apiKey = configResult.api_key;
                const apiBaseUrl = configResult.api_base_url;
                const apiModel = configResult.api_model;
                
                showProgress('API配置获取成功，正在创建记录...', 10);
                
                // 2. 创建记录（不扣积分）
                const formData = new FormData();
                formData.append('debug_code', '1');
                formData.append('title', title);
                formData.append('problem', problem);
                formData.append('code', code);
                formData.append('evaluation_result', evaluationResult);
                
                const createResponse = await fetch('dashboard.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                if (!createResponse.ok) {
                    throw new Error('创建记录失败');
                }
                
                // 获取服务器返回的record_id
                const createResult = await createResponse.json();
                
                if (!createResult.success) {
                    throw new Error(createResult.message);
                }
                
                const recordId = createResult.record_id;
                
                if (!recordId) {
                    throw new Error('无法获取记录ID，请重新提交');
                }
                
                showProgress('记录创建成功，正在调用AI API...', 30);
                
                // 3. 客户端调用AI API
                const aiResponse = await callAIApiLocally(code, problem, evaluationResult, apiKey, apiBaseUrl, apiModel);
                
                showProgress('AI分析完成，正在上传结果...', 80);
                
                // 4. 上传AI分析结果到服务器
                const uploadData = new FormData();
                uploadData.append('upload_ai_result', '1');
                uploadData.append('record_id', recordId);
                uploadData.append('ai_response', aiResponse);
                
                const uploadResponse = await fetch('dashboard.php', {
                    method: 'POST',
                    body: uploadData
                });
                
                // 检查响应内容类型，确保是JSON
                const contentType = uploadResponse.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const responseText = await uploadResponse.text();
                    
                    // 检查是否是重定向到登录页面
                    if (responseText.includes('login.php') || responseText.includes('<!DOCTYPE')) {
                        throw new Error('会话已过期，请重新登录');
                    }
                    
                    // 检查是否是PHP错误页面
                    if (responseText.includes('Parse error') || responseText.includes('Fatal error')) {
                        throw new Error('服务器内部错误，请联系管理员');
                    }
                    
                    throw new Error('服务器返回了非JSON响应: ' + responseText.substring(0, 200));
                }
                
                const result = await uploadResponse.json();
                
                if (result.success) {
                    showProgress('分析完成！', 100);
                    setTimeout(() => {
                        window.location.href = 'records.php?id=' + result.record_id;
                    }, 2000);
                } else {
                    throw new Error(result.message);
                }
                
            } catch (error) {
                showProgress('分析失败: ' + error.message, 0, true);
                console.error('分析失败:', error);
            }
        }

        
        // 本地调用AI API
                async function callAIApiLocally(code, problem, evaluationResult, apiKey, apiBaseUrl, apiModel = 'gpt-3.5-turbo') {
            const apiUrl = `${apiBaseUrl.replace(/\/$/, '')}/chat/completions`;
            let fullContent = '';
            let messages = [
                {
                    role: 'user',
                    content: `你是一个专业代码分析员，你需要根据用户的问题和代码结果，分析代码的问题所在，注意请不要给出最后的代码。请分析以下代码：\n\n代码：\n\`\`\`\n${code}\n\`\`\`\n\n问题描述：${problem}\n\n评测结果：${evaluationResult}`
                }
            ];

            while (true) {
                const requestData = {
                    model: apiModel,
                    messages: messages,
                    max_tokens: 16384,
                    temperature: 0.7
                };

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${apiKey}`
                    },
                    body: JSON.stringify(requestData)
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`API请求失败 (HTTP ${response.status}): ${errorText}`);
                }

                const data = await response.json();

                if (!data.choices || !data.choices[0] || !data.choices[0].message) {
                    throw new Error('API响应格式不正确');
                }

                const content = data.choices[0].message.content.trim();
                fullContent += (fullContent ? '\n' : '') + content;

                if (data.choices[0].finish_reason === 'length') {
                    messages.push({ role: 'assistant', content: content });
                    messages.push({ role: 'user', content: '请继续刚才的分析，不要重复之前的内容，直接从截断的地方开始写。' });
                } else {
                    break;
                }
            }
            return fullContent.trim();
        }
        
        // 显示进度
        function showProgress(message, percent, isError = false) {
            const progressSection = document.getElementById('progress-section');
            const progressMessage = document.getElementById('progress-message');
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            
            progressSection.style.display = 'block';
            progressMessage.textContent = message;
            progressFill.style.width = percent + '%';
            progressText.textContent = percent + '%';
            
            if (isError) {
                progressMessage.className = 'message error';
                progressFill.style.background = '#dc3545';
            } else {
                progressMessage.className = 'message success';
                progressFill.style.background = '#007bff';
            }
        }
        
        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            highlightCode();
            
            // 检查是否有待处理的记录
            <?php if (isset($_SESSION['pending_record_id'])): ?>
            showProgress('检测到待处理的记录，请重新提交分析', 0, true);
            <?php unset($_SESSION['pending_record_id']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>