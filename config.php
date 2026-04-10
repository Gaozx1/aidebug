<?php
// 增加PHP执行时间限制
set_time_limit(120); // 增加到120秒
ini_set('max_execution_time', 120);
ini_set('default_socket_timeout', 60);

date_default_timezone_set('Asia/Shanghai'); // 使用北京时区

define('DATA_DIR', 'data');
define('USERS_FILE', DATA_DIR . '/users.json');
define('RECORDS_FILE', DATA_DIR . '/records.json');
define('CONFIG_FILE', DATA_DIR . '/config.json');

// 系统配置
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', '123456');
define('SITE_NAME', 'AI代码调试系统');

// 加密密钥（请修改此密钥）
define('ENCRYPTION_KEY', 'MySecureKey2024!@#$%^&*()ABCD');

// 初始化数据目录和文件
function initDataFiles() {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    
    // 初始化用户文件
    if (!file_exists(USERS_FILE)) {
        $adminPassword = hashPassword(ADMIN_PASSWORD);
        $defaultUsers = [
            'admin' => [
                'username' => 'admin',
                'email' => 'admin@system.com',
                'password' => $adminPassword,
                'is_admin' => true,
                'email_verified' => true,
                'verification_token' => null,
                'verification_expires' => null,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        saveEncryptedData(USERS_FILE, $defaultUsers);
    }
    
    // 初始化配置文件
    $defaultConfig = [
        // 网站系统配置
        'site_name' => ['config_value' => 'AI代码调试系统', 'description' => '网站名称'],
        'site_description' => ['config_value' => '智能代码分析与调试平台', 'description' => '网站描述'],
        'admin_email' => ['config_value' => 'admin@system.com', 'description' => '管理员邮箱'],
        'max_file_size' => ['config_value' => '10', 'description' => '最大文件上传大小（MB）'],
        'email_enabled' => ['config_value' => '0', 'description' => '邮件通知开关（0=关闭，1=开启）'],
        'debug_mode' => ['config_value' => '0', 'description' => '调试模式开关（0=关闭，1=开启）'],
        
        // API配置（合并AI配置和API配置）
        'api_key' => ['config_value' => '', 'description' => 'AI服务API密钥（如OpenAI API Key）'],
        'api_model' => ['config_value' => 'gpt-3.5-turbo', 'description' => 'AI模型名称（如gpt-3.5-turbo、gpt-4）'],
        'api_base_url' => ['config_value' => 'https://api.openai.com/v1', 'description' => 'API服务的基础URL'],
        'api_timeout' => ['config_value' => '30', 'description' => 'API请求超时时间（秒）'],
        'api_retry_count' => ['config_value' => '3', 'description' => '失败请求的重试次数'],
        'api_rate_limit' => ['config_value' => '60', 'description' => '每分钟最大请求次数'],
        'api_max_tokens' => ['config_value' => '1000', 'description' => 'API响应最大token数'],
        'api_temperature' => ['config_value' => '0.7', 'description' => 'API响应随机性（0-1）'],
        'api_notes' => ['config_value' => '', 'description' => 'API配置备注信息'],
        
        // SMTP邮件配置
        'smtp_host' => ['config_value' => 'smtp.qq.com', 'description' => 'SMTP服务器地址'],
        'smtp_port' => ['config_value' => '587', 'description' => 'SMTP端口号'],
        'smtp_username' => ['config_value' => '', 'description' => 'SMTP用户名'],
        'smtp_password' => ['config_value' => '', 'description' => 'SMTP密码'],
        'smtp_secure' => ['config_value' => 'tls', 'description' => 'SMTP加密方式（tls/ssl）'],
        'smtp_from_email' => ['config_value' => '', 'description' => '发件人邮箱'],
        'smtp_from_name' => ['config_value' => 'AI代码调试系统', 'description' => '发件人名称'],
        
        // 更新配置
        'update_repo' => ['config_value' => 'Gaozx1/aidebug', 'description' => '自动更新仓库地址，格式 user/repo'],
        'update_branch' => ['config_value' => 'main', 'description' => '自动更新分支名称'],
        
        // 公告系统配置
        'announcement_enabled' => ['config_value' => '0', 'description' => '启用系统公告（0=关闭，1=开启）'],
        'announcement_text' => ['config_value' => '', 'description' => '系统公告内容'],
        
        // Markdown支持配置
        'markdown_enabled' => ['config_value' => '1', 'description' => '启用Markdown支持（0=关闭，1=开启）']
    ];
    
    if (!file_exists(CONFIG_FILE)) {
        saveEncryptedData(CONFIG_FILE, $defaultConfig);
    }
}

// 初始化数据文件
initDataFiles();

// 哈希密码
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// 验证密码
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 保存数据到JSON文件（简化版本，避免加密问题）
function saveEncryptedData($filename, $data) {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);
    return file_put_contents($filename, $jsonData) !== false;
}

// 从JSON文件读取数据（简化版本）
function loadEncryptedData($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    
    $jsonData = file_get_contents($filename);
    if ($jsonData === false) {
        return [];
    }
    
    $data = json_decode($jsonData, true);
    return is_array($data) ? $data : [];
}

// 获取用户数据
function getUsers() {
    return loadEncryptedData(USERS_FILE);
}

// 保存用户数据
function saveUsers($users) {
    return saveEncryptedData(USERS_FILE, $users);
}

// 获取调试记录
function getRecords() {
    return loadEncryptedData(RECORDS_FILE);
}

// 保存调试记录
function saveRecords($records) {
    return saveEncryptedData(RECORDS_FILE, $records);
}

// 获取系统配置
function getConfig() {
    return loadEncryptedData(CONFIG_FILE);
}

// 保存系统配置
function saveConfig($config) {
    return saveEncryptedData(CONFIG_FILE, $config);
}

// 辅助函数：安全获取配置值（支持新旧结构）
function getConfigValue($config, $key, $default = '') {
    if (!isset($config[$key])) {
        return $default;
    }
    
    // 如果是新结构（包含config_value）
    if (is_array($config[$key]) && isset($config[$key]['config_value'])) {
        return $config[$key]['config_value'];
    }
    
    // 如果是旧结构（直接是值）
    return $config[$key];
}

function getAnnouncementData() {
    $config = getConfig();
    return [
        'enabled' => getConfigValue($config, 'announcement_enabled', '0'),
        'text' => trim(getConfigValue($config, 'announcement_text', ''))
    ];
}

function renderAnnouncementBanner() {
    $announcement = getAnnouncementData();
    if ($announcement['enabled'] === '1' && !empty($announcement['text'])) {
        echo '<div class="announcement-banner">';
        echo '<strong>系统公告：</strong> ' . nl2br(htmlspecialchars($announcement['text']));
        echo '</div>';
    }
}

// 发送邮件函数（使用Resend API）
function sendEmail($to, $subject, $message) {
    $config = getConfig();
    
    // 如果邮件功能未启用，直接返回成功
    if (!getConfigValue($config, 'email_enabled', '0')) {
        return true;
    }
    
    // 获取Resend配置
    $api_key = getConfigValue($config, 'smtp_password');
    $from_email = getConfigValue($config, 'smtp_from_email') ?: 'noreply@system.com';
    
    // 使用Resend API发送邮件
    return sendEmailWithResendAPI($to, $subject, $message, $api_key, $from_email);
}

// SMTP邮件发送函数
function sendSMTPEmail($to, $subject, $message, $config) {
    $smtp_host = getConfigValue($config, 'smtp_host');
    $smtp_port = getConfigValue($config, 'smtp_port');
    $smtp_username = getConfigValue($config, 'smtp_username');
    $smtp_password = getConfigValue($config, 'smtp_password');
    $smtp_secure = getConfigValue($config, 'smtp_secure');
    $from_email = getConfigValue($config, 'smtp_from_email') ?: $smtp_username;
    $from_name = getConfigValue($config, 'smtp_from_name');
    
    // 检查是否使用Resend API
    if ($smtp_username == 'resend' || strpos($smtp_password, 're_') === 0) {
        return sendEmailWithResendAPI($to, $subject, $message, $smtp_password, $from_email);
    }
    
    // 使用PHPMailer（需要先检查是否可用）
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return sendEmailWithPHPMailer($to, $subject, $message, [
            'host' => $smtp_host,
            'port' => $smtp_port,
            'username' => $smtp_username,
            'password' => $smtp_password,
            'secure' => $smtp_secure,
            'from_email' => $from_email,
            'from_name' => $from_name
        ]);
    }
    
    // 使用fsockopen实现SMTP
    return sendEmailWithSocket($to, $subject, $message, [
        'host' => $smtp_host,
        'port' => $smtp_port,
        'username' => $smtp_username,
        'password' => $smtp_password,
        'secure' => $smtp_secure,
        'from_email' => $from_email,
        'from_name' => $from_name
    ]);
}

// 使用PHPMailer发送SMTP邮件
function sendEmailWithPHPMailer($to, $subject, $message, $smtp_config) {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // 服务器设置
        $mail->isSMTP();
        $mail->Host = $smtp_config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_config['username'];
        $mail->Password = $smtp_config['password'];
        $mail->SMTPSecure = $smtp_config['secure'] == 'tls' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtp_config['port'];
        
        // 发件人
        $mail->setFrom($smtp_config['from_email'], $smtp_config['from_name']);
        
        // 收件人
        $mail->addAddress($to);
        
        // 内容
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("PHPMailer错误: {$mail->ErrorInfo}");
        return false;
    }
}

// 使用Resend API发送邮件
function sendEmailWithResendAPI($to, $subject, $message, $api_key, $from_email) {
    $url = 'https://api.resend.com/emails';
    
    $data = [
        'from' => $from_email,
        'to' => [$to],
        'subject' => $subject,
        'html' => $message
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 注意：生产环境应设置为true并提供CA证书
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error) {
        error_log("Resend API错误: {$error}");
        return false;
    }
    
    if ($http_code == 200) {
        return true;
    } else {
        error_log("Resend API响应: HTTP {$http_code} - {$response}");
        return false;
    }
}

// 使用fsockopen发送SMTP邮件
function sendEmailWithSocket($to, $subject, $message, $smtp_config) {
    $host = $smtp_config['host'];
    $port = $smtp_config['port'];
    $username = $smtp_config['username'];
    $password = $smtp_config['password'];
    $secure = $smtp_config['secure'];
    $from_email = $smtp_config['from_email'];
    $from_name = $smtp_config['from_name'];
    
    // 根据端口和安全类型选择连接方式
    if ($port == 465 || $secure == 'ssl') {
        $socket = fsockopen("ssl://{$host}", $port, $errno, $errstr, 30);
    } elseif ($port == 587 || $secure == 'tls') {
        $socket = fsockopen($host, $port, $errno, $errstr, 30);
    } else {
        $socket = fsockopen($host, $port, $errno, $errstr, 30);
    }
    
    if (!$socket) {
        error_log("SMTP连接失败: {$errno} - {$errstr}");
        return false;
    }
    
    // 读取初始响应
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '220') {
        error_log("SMTP连接响应异常: {$response}");
        fclose($socket);
        return false;
    }
    
    // 发送EHLO
    fputs($socket, "EHLO localhost\r\n");
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') break; // 多行响应结束
    }
    
    // 如果是TLS且端口587，发送STARTTLS
    if (($port == 587 || $secure == 'tls') && strpos($response, 'STARTTLS') !== false) {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '220') {
            error_log("STARTTLS失败: {$response}");
            fclose($socket);
            return false;
        }
        
        // 启用TLS
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        // 重新发送EHLO
        fputs($socket, "EHLO localhost\r\n");
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
    }
    
    // 认证
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
        error_log("AUTH LOGIN失败: {$response}");
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($username) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
        error_log("用户名认证失败: {$response}");
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($password) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '235') {
        error_log("密码认证失败: {$response}");
        fclose($socket);
        return false;
    }
    
    // 发送邮件
    fputs($socket, "MAIL FROM: <{$from_email}>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        error_log("MAIL FROM失败: {$response}");
        fclose($socket);
        return false;
    }
    
    fputs($socket, "RCPT TO: <{$to}>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        error_log("RCPT TO失败: {$response}");
        fclose($socket);
        return false;
    }
    
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '354') {
        error_log("DATA失败: {$response}");
        fclose($socket);
        return false;
    }
    
    // 发送邮件内容
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "To: {$to}\r\n";
    $headers .= "Subject: {$subject}\r\n";
    
    fputs($socket, "{$headers}\r\n{$message}\r\n.\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        error_log("邮件发送失败: {$response}");
        fclose($socket);
        return false;
    }
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return true;
}
// 设置消息函数（用于显示成功/错误消息）
// 生成唯一ID函数
function generateId() {
    return uniqid('', true) . '_' . mt_rand(1000, 9999);
}

function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// 获取并清除消息函数
function getMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// 显示消息函数（用于在页面中显示消息）
function displayMessage() {
    $message = getMessage();
    if ($message) {
        $type_class = '';
        switch ($message['type']) {
            case 'success':
                $type_class = 'success';
                break;
            case 'error':
                $type_class = 'error';
                break;
            case 'warning':
                $type_class = 'warning';
                break;
            default:
                $type_class = 'info';
        }
        
        echo '<div class="message ' . $type_class . '">' . htmlspecialchars($message['text']) . '</div>';
    }
}

// 增强的Markdown和LaTeX转换函数
function markdownToHtml($markdown) {
    $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);

    // 先处理代码块，避免后续转换影响代码内容
    $codeBlocks = [];
    $markdown = preg_replace_callback('/```(\w+)?\n([\s\S]*?)\n```/m', function ($matches) use (&$codeBlocks) {
        $langClass = $matches[1] ? 'language-' . $matches[1] : '';
        $content = htmlspecialchars($matches[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $placeholder = '___CODE_BLOCK_' . count($codeBlocks) . '___';
        $codeBlocks[$placeholder] = '<pre><code class="' . $langClass . '">' . $content . '</code></pre>';
        return $placeholder;
    }, $markdown);

    // 先保存 LaTeX 内容，保持原始公式标记
    $latexBlocks = [];
    $markdown = preg_replace_callback('/\$\$([\s\S]*?)\$\$/m', function($matches) use (&$latexBlocks) {
        $placeholder = '___LATEX_BLOCK_' . count($latexBlocks) . '___';
        $latexBlocks[$placeholder] = '$$' . $matches[1] . '$$';
        return $placeholder;
    }, $markdown);
    $markdown = preg_replace_callback('/\\\\\[(.*?)\\\\\]/s', function($matches) use (&$latexBlocks) {
        $placeholder = '___LATEX_BLOCK_' . count($latexBlocks) . '___';
        $latexBlocks[$placeholder] = '\\[' . $matches[1] . '\\]';
        return $placeholder;
    }, $markdown);
    $markdown = preg_replace_callback('/\\\\\((.*?)\\\\\)/s', function($matches) use (&$latexBlocks) {
        $placeholder = '___LATEX_INLINE_' . count($latexBlocks) . '___';
        $latexBlocks[$placeholder] = '\\(' . $matches[1] . '\\)';
        return $placeholder;
    }, $markdown);
    $markdown = preg_replace_callback('/\$(?!\$)(.+?)\$/s', function($matches) use (&$latexBlocks) {
        $placeholder = '___LATEX_INLINE_' . count($latexBlocks) . '___';
        $latexBlocks[$placeholder] = '$' . $matches[1] . '$';
        return $placeholder;
    }, $markdown);

    $markdown = htmlspecialchars($markdown, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // 行内代码
    $markdown = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $markdown);

    // 标题
    $markdown = preg_replace('/^######\s+(.+)$/m', '<h6>$1</h6>', $markdown);
    $markdown = preg_replace('/^#####\s+(.+)$/m', '<h5>$1</h5>', $markdown);
    $markdown = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $markdown);
    $markdown = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $markdown);
    $markdown = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $markdown);
    $markdown = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $markdown);

    // 粗体与斜体
    $markdown = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $markdown);
    $markdown = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $markdown);

    // 链接
    $markdown = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $markdown);

    // 引用块
    $markdown = preg_replace('/^>\s+(.+)$/m', '<blockquote>$1</blockquote>', $markdown);

    // 列表
    $markdown = preg_replace_callback('/(?:^|\n)((?:- .+(?:\n|$))+)/', function ($matches) {
        $items = preg_replace('/^- /m', '', trim($matches[1]));
        $items = preg_replace('/^(.+)$/m', '<li>$1</li>', $items);
        return "\n<ul>\n" . $items . "\n</ul>\n";
    }, $markdown);

    // 分段处理
    $parts = preg_split('/\n{2,}/', $markdown);
    $html = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        if (preg_match('/^<(h[1-6]|ul|pre|blockquote|code)/', $part)) {
            $html[] = $part;
        } else {
            $html[] = '<p>' . nl2br($part) . '</p>';
        }
    }
    $markdown = implode("\n", $html);

    // 替换回 LaTeX 和代码块
    foreach ($latexBlocks as $placeholder => $latexText) {
        $markdown = str_replace($placeholder, $latexText, $markdown);
    }
    foreach ($codeBlocks as $placeholder => $codeHtml) {
        $markdown = str_replace($placeholder, $codeHtml, $markdown);
    }

    return '<div class="markdown-content">' . $markdown . '</div>';
}


// 调用AI分析代码
function callAIAnalysis($code, $description) {
    $config = getConfig();
    
    // 安全获取配置值
    $api_key = getConfigValue($config, 'api_key');
    
    // 如果没有配置API密钥，使用模拟响应
    if (empty($api_key)) {
        $responses = [
            "代码结构清晰，建议添加更多注释以提高可读性。",
            "检测到潜在的性能问题，建议优化循环结构。",
            "代码逻辑正确，但存在一些边界情况需要处理。",
            "建议使用更合适的算法来提升效率。",
            "代码风格良好，符合最佳实践。"
        ];
        return $responses[array_rand($responses)];
    }
    
    // 使用真实的AI API
    return callAIAPI($code, $description, $config);
}
function callAIAPI($code, $description, $config) {
    // 使用更安全的方式获取配置
    $api_key = getConfigValue($config, 'api_key');
    $api_base_url = getConfigValue($config, 'api_base_url');
    $model = getConfigValue($config, 'api_model');
    
    if (empty($api_key) || empty($api_base_url)) {
        return "API配置不完整，请检查管理后台设置";
    }
    
    // 验证API基础URL格式
    if (!filter_var($api_base_url, FILTER_VALIDATE_URL)) {
        return "API基础URL格式不正确";
    }
    
    // 构建完整的API URL
    $api_url = rtrim($api_base_url, '/') . '/chat/completions';
    
    // 准备请求数据
    $data = [
        'model' => $model ?: 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'user', 
                'content' => "请分析以下代码：\n\n代码：\n```\n{$code}\n```\n\n问题描述：{$description}"
            ]
        ],
        'max_tokens' => 1000,
        'temperature' => 0.7
    ];
    
    // 准备HTTP头
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    
    // 使用优化后的cURL配置
    $ch = curl_init();
    
    // 基础配置
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // 优化超时设置（基于测试结果调整）
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 总超时60秒
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); // 连接超时15秒
    
    // SSL配置
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // 网络优化配置
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AI-Code-Debug-System/1.0');
    curl_setopt($ch, CURLOPT_ENCODING, '');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // 安全关闭cURL
    if (function_exists('curl_close')) {
        curl_close($ch);
    }
    
    // 错误处理
    if ($response === false) {
        return "API请求失败: " . $error;
    }
    
    if ($http_code !== 200) {
        return "API返回错误: HTTP {$http_code}";
    }
    
    // 解析响应数据
    $response_data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return "API响应解析失败";
    }
    
    if (!isset($response_data['choices'][0]['message']['content'])) {
        return "API响应格式不正确";
    }
    
    return trim($response_data['choices'][0]['message']['content']);
}



// 积分系统功能
function getUserPoints($username) {
    $users = getUsers();
    if (!isset($users[$username])) {
        return 0;
    }
    return isset($users[$username]['points']) ? $users[$username]['points'] : 0;
}

function updateUserPoints($username, $points) {
    $users = getUsers();
    if (!isset($users[$username])) {
        return false;
    }
    $users[$username]['points'] = $points;
    return saveUsers($users);
}

function canAffordAnalysis($username) {
    return getUserPoints($username) >= 30;
}

function deductAnalysisPoints($username) {
    $current_points = getUserPoints($username);
    if ($current_points < 30) {
        return false;
    }
    return updateUserPoints($username, $current_points - 30);
}

function addSigninPoints($username) {
    $users = getUsers();
    if (!isset($users[$username])) {
        return false;
    }
    
    // 检查今天是否已经签到
    $today = date('Y-m-d');
    if (isset($users[$username]['last_signin']) && $users[$username]['last_signin'] === $today) {
        return false; // 今天已经签到
    }
    
    $current_points = getUserPoints($username);
    $users[$username]['points'] = $current_points + 50;
    $users[$username]['last_signin'] = $today;
    
    return saveUsers($users);
}
// 邀请功能



function getUserInviteCodes($username) {
    $users = getUsers();
    return isset($users[$username]['invite_codes']) ? $users[$username]['invite_codes'] : [];
}
// 在config.php中添加以下函数

// 邀请功能
function generateInviteCode($username) {
    $code = strtoupper(substr(md5($username . time() . rand(1000,9999)), 0, 8));
    $users = getUsers();
    
    if (!isset($users[$username]['invite_codes'])) {
        $users[$username]['invite_codes'] = [];
    }
    
    $users[$username]['invite_codes'][] = [
        'code' => $code,
        'created_at' => date('Y-m-d H:i:s'),
        'use_count' => 0,
        'used_by' => null,
        'used_at' => null
    ];
    
    saveUsers($users);
    return $code;
}

function useInviteCode($code, $newUsername) {
    $users = getUsers();
    $code = strtoupper(trim($code));
    
    foreach ($users as $username => $user) {
        if (isset($user['invite_codes'])) {
            foreach ($user['invite_codes'] as &$invite) {
                if ($invite['code'] === $code) {
                    $invite['use_count'] = isset($invite['use_count']) ? $invite['use_count'] + 1 : 1;
                    $invite['used_by'] = $newUsername;
                    $invite['used_at'] = date('Y-m-d H:i:s');
                    
                    // 给邀请者奖励积分
                    $invite_reward = (int)getConfigValue(getConfig(), 'invite_reward', 100);
                    if (!isset($users[$username]['points'])) {
                        $users[$username]['points'] = 0;
                    }
                    $users[$username]['points'] += $invite_reward;
                    
                    // 给新用户初始积分
                    if (!isset($users[$newUsername]['points'])) {
                        $users[$newUsername]['points'] = 50;
                    }
                    
                    saveUsers($users);
                    return $username; // 返回邀请者用户名
                }
            }
        }
    }
    
    return false;
}

// 兑换码功能
function generateRedeemCode($points, $creator = 'admin') {
    $code = strtoupper('REDEEM-' . substr(md5(time() . rand(1000,9999)), 0, 6));
    $redeem_codes = getRedeemCodes();
    
    $redeem_codes[$code] = [
        'code' => $code,
        'points' => (int)$points,
        'created_by' => $creator,
        'created_at' => date('Y-m-d H:i:s'),
        'used' => false,
        'used_by' => null,
        'used_at' => null
    ];
    
    saveRedeemCodes($redeem_codes);
    return $code;
}

function useRedeemCode($code, $username) {
    $redeem_codes = getRedeemCodes();
    $users = getUsers();
    $code = strtoupper(trim($code));
    
    if (!isset($redeem_codes[$code])) {
        return ['success' => false, 'message' => '兑换码不存在'];
    }
    
    $redeem_code = $redeem_codes[$code];
    
    if ($redeem_code['used']) {
        return ['success' => false, 'message' => '兑换码已被使用'];
    }
    
    // 使用兑换码
    $redeem_codes[$code]['used'] = true;
    $redeem_codes[$code]['used_by'] = $username;
    $redeem_codes[$code]['used_at'] = date('Y-m-d H:i:s');
    
    // 给用户添加积分
    if (!isset($users[$username]['points'])) {
        $users[$username]['points'] = 0;
    }
    $users[$username]['points'] += $redeem_code['points'];
    
    saveRedeemCodes($redeem_codes);
    saveUsers($users);
    
    return [
        'success' => true, 
        'message' => '兑换成功！获得 ' . $redeem_code['points'] . ' 积分',
        'points' => $redeem_code['points']
    ];
}

function getRedeemCodes() {
    $file_path = 'data/redeem_codes.json';
    if (!file_exists($file_path)) {
        return [];
    }
    $content = file_get_contents($file_path);
    return $content ? json_decode($content, true) : [];
}

function saveRedeemCodes($redeem_codes) {
    $file_path = 'data/redeem_codes.json';
    file_put_contents($file_path, json_encode($redeem_codes, JSON_PRETTY_PRINT));
}

function getActiveRedeemCodes() {
    $redeem_codes = getRedeemCodes();
    $active_codes = [];
    
    foreach ($redeem_codes as $code => $data) {
        if (!$data['used']) {
            $active_codes[$code] = $data;
        }
    }
    
    return $active_codes;
}
<<<<<<< HEAD

function downloadFile($url, $destination, &$error = null) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AI-Code-Debug-System/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($data === false || $httpCode !== 200) {
            $error = $curlError ? $curlError : "HTTP {$httpCode}";
            return false;
        }

        return file_put_contents($destination, $data) !== false;
    }

    $data = @file_get_contents($url);
    if ($data === false) {
        $error = '无法下载文件';
        return false;
    }

    return file_put_contents($destination, $data) !== false;
}

function extractZipFile($zipPath, $destination, &$error = null) {
    if (!class_exists('ZipArchive')) {
        $error = 'ZipArchive 扩展不可用';
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        $error = '无法打开 ZIP 文件';
        return false;
    }

    if (!$zip->extractTo($destination)) {
        $zip->close();
        $error = '解压 ZIP 文件失败';
        return false;
    }

    $zip->close();
    return true;
}

function isExcludedPath($relativePath, $excludePatterns) {
    $relative = str_replace('\\', '/', trim($relativePath, '/'));
    foreach ($excludePatterns as $pattern) {
        $pattern = str_replace('\\', '/', trim($pattern, '/'));
        if ($pattern === '') {
            continue;
        }
        if ($relative === $pattern || strpos($relative, $pattern . '/') === 0) {
            return true;
        }
    }
    return false;
}

function copyDirectory($source, $destination, $excludePatterns = [], &$error = null) {
    if (!is_dir($source)) {
        $error = '源目录不存在: ' . $source;
        return false;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $subPath = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));
        if (isExcludedPath($subPath, $excludePatterns)) {
            continue;
        }

        $targetPath = $destination . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);

        if ($item->isDir()) {
            if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true)) {
                $error = '无法创建目录: ' . $targetPath;
                return false;
            }
        } else {
            $dir = dirname($targetPath);
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                $error = '无法创建目录: ' . $dir;
                return false;
            }
            if (!copy($item->getPathname(), $targetPath)) {
                $error = '复制文件失败: ' . $item->getPathname();
                return false;
            }
        }
    }

    return true;
}

function getGithubRepoInfo($repo, &$error = null) {
    $apiUrl = "https://api.github.com/repos/{$repo}";

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AI-Code-Debug-System/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            $error = $curlError ? $curlError : "GitHub API 返回 HTTP {$httpCode}";
            return false;
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: AI-Code-Debug-System/1.0\r\n',
                'timeout' => 30,
            ]
        ]);
        $response = @file_get_contents($apiUrl, false, $context);
        if ($response === false) {
            $error = '无法访问 GitHub API';
            return false;
        }
    }

    $repoInfo = json_decode($response, true);
    if (!is_array($repoInfo) || isset($repoInfo['message'])) {
        $message = is_array($repoInfo) && isset($repoInfo['message']) ? $repoInfo['message'] : '无法解析 GitHub API 返回内容';
        $error = 'GitHub 仓库检查失败：' . $message;
        return false;
    }

    return $repoInfo;
}

function autoUpdateFromGithub($repo, $branch = 'main') {
    $rootPath = __DIR__;
    $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'aidebug_update_' . time();
    $zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'update.zip';
    $extractDir = $tmpDir . DIRECTORY_SEPARATOR . 'package';

    if (!mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
        return ['success' => false, 'message' => '无法创建临时目录'];
    }

    $repoInfo = getGithubRepoInfo($repo, $error);
    if ($repoInfo === false) {
        return ['success' => false, 'message' => $error];
    }

    if (empty($branch) && isset($repoInfo['default_branch'])) {
        $branch = $repoInfo['default_branch'];
    }

    $zipUrl = "https://codeload.github.com/{$repo}/zip/{$branch}";
    if (!downloadFile($zipUrl, $zipPath, $error)) {
        return ['success' => false, 'message' => '下载更新包失败: ' . $error . '，请检查仓库地址和分支设置'];
    }

    if (!extractZipFile($zipPath, $extractDir, $error)) {
        return ['success' => false, 'message' => '解压更新包失败: ' . $error];
    }

    $entries = scandir($extractDir);
    $sourceDir = '';
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $extractDir . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($path)) {
            $sourceDir = $path;
            break;
        }
    }

    if (!$sourceDir) {
        return ['success' => false, 'message' => '更新包结构异常'];
    }

    $exclude = [
        'data',
        '.git',
        'config.php'
    ];

    if (!copyDirectory($sourceDir, $rootPath, $exclude, $error)) {
        return ['success' => false, 'message' => '复制更新文件失败: ' . $error];
    }

    return ['success' => true, 'message' => '更新完成，请检查页面是否正常。'];
}
?>
=======
?>
>>>>>>> e196356d439b1cb692291e3118e9ce002ab17fb6
