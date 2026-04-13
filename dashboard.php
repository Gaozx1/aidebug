<?php



require_once 'config.php';


session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


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

        $code_lines = calculateCodeLines($code);
        $required_points = calculateRequiredPoints($code_lines);


        if (!canAffordAnalysisByCode($_SESSION['user_id'], $code)) {
                    echo json_encode(['success' => false, 'message' => "积分不足，分析{$code_lines}行代码需要{$required_points}积分。请先签到获取积分。"]);
                    exit;
            } else {
            $records = getRecords();
            $users = getUsers();
            

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
            

            $records[$record_id] = $newRecord;
            
            if (saveRecords($records)) {

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


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_api_config'])) {
    header('Content-Type: application/json');
    $config = getConfig();
    $api_key = getConfigValue($config, 'api_key');
    
    if (empty($api_key)) {
        echo json_encode(['success' => false, 'message' => 'API密钥未配置']);
        exit;
    }
    

    $key_length = strlen($api_key);
    $masked_key = substr($api_key, 0, 8) . '...' . substr($api_key, -4);
    
    echo json_encode([
        'success' => true,
        'api_key' => $api_key,
        'api_base_url' => getConfigValue($config, 'api_base_url') ?: 'https://api.openai.com/v1',
        'api_model' => getConfigValue($config, 'api_model') ?: 'gpt-3.5-turbo',
        'masked_key' => $masked_key,
        'key_length' => $key_length
    ]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_current_points'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'points' => getUserPoints($_SESSION['user_id'])
    ]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_analysis_config'])) {
    header('Content-Type: application/json');
    $config = getConfig();
    echo json_encode([
        'success' => true,
        'config' => [
            'basePoints' => isset($config['analysis_cost']) ? (int)$config['analysis_cost'] : 20,
            'freeLines' => 200,
            'extraChargeLines' => 100,
            'extraChargePoints' => 10
        ]
    ]);
    exit;
}


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
    

    $error_keywords = ['API请求失败', 'cURL错误', 'HTTP 错误', '响应格式错误'];
    $is_error = false;
    foreach ($error_keywords as $keyword) {
        if (strpos($ai_response, $keyword) !== false) {
            $is_error = true;
            break;
        }
    }
    
    if ($is_error || empty($ai_response)) {

        unset($records[$record_id]);
        saveRecords($records);
        echo json_encode(['success' => false, 'message' => 'AI分析失败：' . ($ai_response ?: '无响应')]);
        exit;
    }
    

    $code_lines = calculateCodeLines($records[$record_id]['code']);
    $required_points = calculateRequiredPoints($code_lines);
    
    if (deductAnalysisPointsByCode($_SESSION['user_id'], $records[$record_id]['code'])) {
        $records[$record_id]['ai_response'] = $ai_response;
        $records[$record_id]['status'] = 'completed';
        $records[$record_id]['updated_at'] = date('Y-m-d H:i:s');
        
        if (saveRecords($records)) {
            echo json_encode(['success' => true, 'message' => "代码分析完成！已扣除{$required_points}积分（{$code_lines}行代码）。", 'record_id' => $record_id]);
        } else {

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


$records = getRecords();
$userRecords = [];

foreach ($records as $record) {
    if ($record['user_id'] === $_SESSION['user_id']) {
        $userRecords[] = $record;
    }
}


usort($userRecords, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});


$recentRecords = array_slice($userRecords, 0, 5);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oler Debug</title>   
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
                
                <div class="cost-info">
                    💰 分析费用: <strong id="required-points">30</strong> 积分 (<span id="code-lines">0</span> 行代码)
                    <br>当前积分: <strong id="current-points-display"><?php echo getUserPoints($_SESSION['user_id']); ?></strong>
                    <div id="insufficient-warning" style="color: #dc3545; display: none;">积分不足！请先签到获取积分。</div>
                </div>

                <script>
                async function getCurrentPoints() {
                    try {
                        const response = await fetch('dashboard.php?get_current_points=1');
                        const result = await response.json();
                        if (result.success) {
                            return result.points;
                        }
                    } catch (error) {
                        console.error('获取积分失败:', error);
                    }
                    return <?php echo getUserPoints($_SESSION['user_id']); ?>;
                }

                async function getAnalysisConfig() {
                    try {
                        const response = await fetch('dashboard.php?get_analysis_config=1');
                        const result = await response.json();
                        if (result.success) {
                            return result.config;
                        }
                    } catch (error) {
                        console.error('获取分析配置失败:', error);
                    }
                    return { basePoints: 20, freeLines: 200, extraChargeLines: 100, extraChargePoints: 10 };
                }

                async function calculateCost() {
                    const code = document.getElementById('code').value;
                    const lines = code.split('\n').filter(line => line.trim() !== '').length;
                    const config = await getAnalysisConfig();
                    const basePoints = config.basePoints || 20;
                    const freeLines = config.freeLines || 200;
                    const extraChargeLines = config.extraChargeLines || 100;
                    const extraChargePoints = config.extraChargePoints || 10;

                    let requiredPoints = basePoints;
                    if (lines > freeLines) {
                        const extraLines = lines - freeLines;
                        const extraCharges = Math.ceil(extraLines / extraChargeLines);
                        requiredPoints += extraCharges * extraChargePoints;
                    }

                    document.getElementById('code-lines').textContent = lines;
                    document.getElementById('required-points').textContent = requiredPoints;

                    const currentPoints = await getCurrentPoints();
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

                function updatePointsDisplay() {
                    getCurrentPoints().then(points => {
                        const currentPointsElement = document.getElementById('current-points-display');
                        const sidebarPointsElement = document.querySelector('.points-display');
                        
                        if (currentPointsElement && currentPointsElement.textContent !== points.toString()) {
                            currentPointsElement.textContent = points;
                        }
                        if (sidebarPointsElement && sidebarPointsElement.textContent !== '积分: ' + points) {
                            sidebarPointsElement.textContent = '积分: ' + points;
                        }
                    });
                }

                document.getElementById('code').addEventListener('input', calculateCost);

                calculateCost();

                updatePointsDisplay();
                
                setInterval(updatePointsDisplay, 3000);
                
                window.addEventListener('load', function() {
                    setTimeout(updatePointsDisplay, 100);
                });
                
                if (performance.navigation.type === 1) {
                    setTimeout(updatePointsDisplay, 500);
                }
                
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) {
                        setTimeout(updatePointsDisplay, 300);
                    }
                });
                
                window.addEventListener('focus', function() {
                    setTimeout(updatePointsDisplay, 200);
                });
                </script>
                
                <button type="button" class="btn btn-primary" id="submit-btn" onclick="submitAnalysis()">
                    <?php echo canAffordAnalysis($_SESSION['user_id']) ? '提交分析' : '积分不足'; ?>
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
        

        function highlightCode() {
            const codeBlocks = document.querySelectorAll('.code-block');
            codeBlocks.forEach(block => {
                block.innerHTML = block.textContent;
            });
        }
        

        async function submitAnalysis() {
            const title = document.getElementById('title').value;
            const problem = document.getElementById('problem').value;
            const code = document.getElementById('code').value;
            const evaluationResult = document.getElementById('evaluation_result').value;
            

            if (!title || !problem || !code || !evaluationResult) {
                alert('请填写所有必填字段');
                return;
            }
            

            showProgress('正在获取API配置...', 0);
            
            try {

                const configResponse = await fetch('dashboard.php?get_api_config=1');
                const configResult = await configResponse.json();
                
                if (!configResult.success) {
                    throw new Error(configResult.message);
                }
                
                const apiKey = configResult.api_key;
                const apiBaseUrl = configResult.api_base_url;
                const apiModel = configResult.api_model;
                
                showProgress('API配置获取成功，正在创建记录...', 10);
                

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
                

                const createResult = await createResponse.json();
                
                if (!createResult.success) {
                    throw new Error(createResult.message);
                }
                
                const recordId = createResult.record_id;
                
                if (!recordId) {
                    throw new Error('无法获取记录ID，请重新提交');
                }
                
                showProgress('记录创建成功，正在调用AI API...', 30);
                

                const aiResponse = await callAIApiLocally(code, problem, evaluationResult, apiKey, apiBaseUrl, apiModel);
                
                showProgress('AI分析完成，正在上传结果...', 80);
                

                const uploadData = new FormData();
                uploadData.append('upload_ai_result', '1');
                uploadData.append('record_id', recordId);
                uploadData.append('ai_response', aiResponse);
                
                const uploadResponse = await fetch('dashboard.php', {
                    method: 'POST',
                    body: uploadData
                });
                

                const contentType = uploadResponse.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const responseText = await uploadResponse.text();
                    

                    if (responseText.includes('login.php') || responseText.includes('<!DOCTYPE')) {
                        throw new Error('会话已过期，请重新登录');
                    }
                    

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

        

                async function callAIApiLocally(code, problem, evaluationResult, apiKey, apiBaseUrl, apiModel = 'gpt-3.5-turbo') {
            const apiUrl = `${apiBaseUrl.replace(/\/$/, '')}/chat/completions`;
            let fullContent = '';
            let messages = [
                {
                    role: 'user',
                    content: `你是一个专业代码分析员，同时也是一个小男娘。请分析以下代码：\n\n代码：\n\`\`\`\n${code}\n\`\`\`\n\n问题描述：${problem}\n\n评测结果：${evaluationResult}`
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
        

        document.addEventListener('DOMContentLoaded', function() {
            highlightCode();
            

            <?php if (isset($_SESSION['pending_record_id'])): ?>
            showProgress('检测到待处理的记录，请重新提交分析', 0, true);
            <?php unset($_SESSION['pending_record_id']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>