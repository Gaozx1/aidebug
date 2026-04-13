<?php

set_time_limit(120);
ini_set('max_execution_time', 120);
ini_set('default_socket_timeout', 60);

date_default_timezone_set('Asia/Shanghai');

define('DATA_DIR', 'data');
define('USERS_FILE', DATA_DIR . '/users.json');
define('RECORDS_FILE', DATA_DIR . '/records.json');
define('CONFIG_FILE', DATA_DIR . '/config.json');


define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', '123456');
define('SITE_NAME', 'AI代码调试系统');


define('ENCRYPTION_KEY', 'MySecureKey2024!@#$%^&*()ABCD');


function initDataFiles() {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    

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
    

    $defaultConfig = [

        'site_name' => ['config_value' => 'AI代码调试系统', 'description' => '网站名称'],
        'site_description' => ['config_value' => '智能代码分析与调试平台', 'description' => '网站描述'],
        'admin_email' => ['config_value' => 'admin@system.com', 'description' => '管理员邮箱'],
        'max_file_size' => ['config_value' => '10', 'description' => '最大文件上传大小（MB）'],
        'email_enabled' => ['config_value' => '0', 'description' => '邮件通知开关（0=关闭，1=开启）'],
        'debug_mode' => ['config_value' => '0', 'description' => '调试模式开关（0=关闭，1=开启）'],
        

        'api_key' => ['config_value' => '', 'description' => 'AI服务API密钥（如OpenAI API Key）'],
        'api_model' => ['config_value' => 'gpt-3.5-turbo', 'description' => 'AI模型名称（如gpt-3.5-turbo、gpt-4）'],
        'api_base_url' => ['config_value' => 'https://api.openai.com/v1', 'description' => 'API服务的基础URL'],
        'api_timeout' => ['config_value' => '30', 'description' => 'API请求超时时间（秒）'],
        'api_retry_count' => ['config_value' => '3', 'description' => '失败请求的重试次数'],
        'api_rate_limit' => ['config_value' => '60', 'description' => '每分钟最大请求次数'],
        'api_max_tokens' => ['config_value' => '1000', 'description' => 'API响应最大token数'],
        'api_temperature' => ['config_value' => '0.7', 'description' => 'API响应随机性（0-1）'],
        'api_notes' => ['config_value' => '', 'description' => 'API配置备注信息'],
        

        'smtp_host' => ['config_value' => 'smtp.qq.com', 'description' => 'SMTP服务器地址'],
        'smtp_port' => ['config_value' => '587', 'description' => 'SMTP端口号'],
        'smtp_username' => ['config_value' => '', 'description' => 'SMTP用户名'],
        'smtp_password' => ['config_value' => '', 'description' => 'SMTP密码'],
        'smtp_secure' => ['config_value' => 'tls', 'description' => 'SMTP加密方式（tls/ssl）'],
        'smtp_from_email' => ['config_value' => '', 'description' => '发件人邮箱'],
        'smtp_from_name' => ['config_value' => 'AI代码调试系统', 'description' => '发件人名称'],
        

        'update_enabled' => ['config_value' => '0', 'description' => '启用自动更新（0=关闭，1=开启）'],
        'update_repo' => ['config_value' => 'Gaozx1/aidebug', 'description' => '自动更新仓库地址，格式 user/repo'],
        'update_branch' => ['config_value' => 'main', 'description' => '自动更新分支名称'],
        

        'announcement_enabled' => ['config_value' => '0', 'description' => '启用系统公告（0=关闭，1=开启）'],
        'announcement_text' => ['config_value' => '', 'description' => '系统公告内容'],
        

        'markdown_enabled' => ['config_value' => '1', 'description' => '启用Markdown支持（0=关闭，1=开启）'],
        

        'github_client_id' => ['config_value' => '', 'description' => 'GitHub Client ID'],
        'github_client_secret' => ['config_value' => '', 'description' => 'GitHub Client Secret'],
        'github_redirect_uri' => ['config_value' => '', 'description' => 'GitHub Redirect URI']
    ];
    
    if (!file_exists(CONFIG_FILE)) {
        saveEncryptedData(CONFIG_FILE, $defaultConfig);
    }
}


initDataFiles();


function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}


function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}


function saveEncryptedData($filename, $data) {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($filename, $jsonData) !== false;
}


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


function getUsers() {
    return loadEncryptedData(USERS_FILE);
}


function saveUsers($users) {
    return saveEncryptedData(USERS_FILE, $users);
}


function getRecords() {
    return loadEncryptedData(RECORDS_FILE);
}


function saveRecords($records) {
    return saveEncryptedData(RECORDS_FILE, $records);
}


function getConfig() {
    return loadEncryptedData(CONFIG_FILE);
}


function saveConfig($config) {
    return saveEncryptedData(CONFIG_FILE, $config);
}


function updateConfigValue($key, $value) {
    $config = getConfig();
    
    if (!is_array($config)) {
        $config = [];
    }


    if (is_array($key)) {
        foreach ($key as $k => $v) {
            if (isset($config[$k]) && is_array($config[$k])) {
                $config[$k]['config_value'] = $v;
            } else {
                $config[$k] = ['config_value' => $v, 'description' => ''];
            }
        }
    } else {

        if (isset($config[$key]) && is_array($config[$key])) {
            $config[$key]['config_value'] = $value;
        } else {
            $config[$key] = ['config_value' => $value, 'description' => ''];
        }
    }
    
    return saveConfig($config);
}


function getConfigValue($config, $key, $default = '') {
    if (!isset($config[$key])) {
        return $default;
    }
    

    if (is_array($config[$key]) && isset($config[$key]['config_value'])) {
        return $config[$key]['config_value'];
    }
    

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


function sendEmail($to, $subject, $message) {
    $config = getConfig();
    

    if (!getConfigValue($config, 'email_enabled', '0')) {
        return true;
    }
    

    $api_key = getConfigValue($config, 'smtp_password');
    $from_email = getConfigValue($config, 'smtp_from_email') ?: 'noreply@system.com';
    

    return sendEmailWithResendAPI($to, $subject, $message, $api_key, $from_email);
}


function sendSMTPEmail($to, $subject, $message, $config) {
    $smtp_host = getConfigValue($config, 'smtp_host');
    $smtp_port = getConfigValue($config, 'smtp_port');
    $smtp_username = getConfigValue($config, 'smtp_username');
    $smtp_password = getConfigValue($config, 'smtp_password');
    $smtp_secure = getConfigValue($config, 'smtp_secure');
    $from_email = getConfigValue($config, 'smtp_from_email') ?: $smtp_username;
    $from_name = getConfigValue($config, 'smtp_from_name');
    

    if ($smtp_username == 'resend' || strpos($smtp_password, 're_') === 0) {
        return sendEmailWithResendAPI($to, $subject, $message, $smtp_password, $from_email);
    }
    

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


function sendEmailWithPHPMailer($to, $subject, $message, $smtp_config) {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {

        $mail->isSMTP();
        $mail->Host = $smtp_config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_config['username'];
        $mail->Password = $smtp_config['password'];
        $mail->SMTPSecure = $smtp_config['secure'] == 'tls' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtp_config['port'];
        

        $mail->setFrom($smtp_config['from_email'], $smtp_config['from_name']);
        

        $mail->addAddress($to);
        

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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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


function sendEmailWithSocket($to, $subject, $message, $smtp_config) {
    $host = $smtp_config['host'];
    $port = $smtp_config['port'];
    $username = $smtp_config['username'];
    $password = $smtp_config['password'];
    $secure = $smtp_config['secure'];
    $from_email = $smtp_config['from_email'];
    $from_name = $smtp_config['from_name'];
    

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
    

    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '220') {
        error_log("SMTP连接响应异常: {$response}");
        fclose($socket);
        return false;
    }
    

    fputs($socket, "EHLO localhost\r\n");
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') break;
    }
    

    if (($port == 587 || $secure == 'tls') && strpos($response, 'STARTTLS') !== false) {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '220') {
            error_log("STARTTLS失败: {$response}");
            fclose($socket);
            return false;
        }
        

        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        

        fputs($socket, "EHLO localhost\r\n");
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
    }
    

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

function generateId() {
    return uniqid('', true) . '_' . mt_rand(1000, 9999);
}

function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}


function getMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}


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


function markdownToHtml($markdown) {
    $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);


    $codeBlocks = [];
    $markdown = preg_replace_callback('/```(\w+)?\n([\s\S]*?)\n```/m', function ($matches) use (&$codeBlocks) {
        $langClass = $matches[1] ? 'language-' . $matches[1] : '';
        $content = htmlspecialchars($matches[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $placeholder = '___CODE_BLOCK_' . count($codeBlocks) . '___';
        $codeBlocks[$placeholder] = '<pre><code class="' . $langClass . '">' . $content . '</code></pre>';
        return $placeholder;
    }, $markdown);


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


    $markdown = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $markdown);


    $markdown = preg_replace('/^######\s+(.+)$/m', '<h6>$1</h6>', $markdown);
    $markdown = preg_replace('/^#####\s+(.+)$/m', '<h5>$1</h5>', $markdown);
    $markdown = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $markdown);
    $markdown = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $markdown);
    $markdown = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $markdown);
    $markdown = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $markdown);


    $markdown = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $markdown);
    $markdown = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $markdown);


    $markdown = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $markdown);


    $markdown = preg_replace('/^>\s+(.+)$/m', '<blockquote>$1</blockquote>', $markdown);


    $markdown = preg_replace_callback('/(?:^|\n)((?:- .+(?:\n|$))+)/', function ($matches) {
        $items = preg_replace('/^- /m', '', trim($matches[1]));
        $items = preg_replace('/^(.+)$/m', '<li>$1</li>', $items);
        return "\n<ul>\n" . $items . "\n</ul>\n";
    }, $markdown);


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


    foreach ($latexBlocks as $placeholder => $latexText) {
        $markdown = str_replace($placeholder, $latexText, $markdown);
    }
    foreach ($codeBlocks as $placeholder => $codeHtml) {
        $markdown = str_replace($placeholder, $codeHtml, $markdown);
    }

    return '<div class="markdown-content">' . $markdown . '</div>';
}



function callAIAnalysis($code, $description) {
    $config = getConfig();


    $api_key = getConfigValue($config, 'api_key');


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


    $max_code_length = 8000;
    if (strlen($code) > $max_code_length) {
        return callAIAnalysisBatched($code, $description, $config);
    }


    return callAIAPI($code, $description, $config);
}


function callAIAnalysisBatched($code, $description, $config) {
    $max_code_length = 8000;
    $code_parts = str_split($code, $max_code_length);
    $total_parts = count($code_parts);
    $analysis_results = [];

    foreach ($code_parts as $index => $part) {
        $part_description = $description . "\n\n这是代码的第" . ($index + 1) . "/" . $total_parts . "部分：\n```\n" . $part . "\n```";

        $result = callAIAPI($part, $part_description, $config);


        if (strpos($result, 'API') === 0 || strpos($result, 'cURL') === 0 || strpos($result, '尝试') === 0) {
            return $result;
        }

        $analysis_results[] = "=== 代码部分 " . ($index + 1) . "/" . $total_parts . " 分析 ===\n" . $result;
    }


    $final_analysis = "由于代码较长，已分" . $total_parts . "批进行分析：\n\n" . implode("\n\n", $analysis_results);


    if (strlen($final_analysis) > 12000) {
        $summary_prompt = "请对以下分析结果进行总结和整合：\n\n" . substr($final_analysis, 0, 8000) . "\n\n[内容已截断]";
        $summary = callAIAPI("", $summary_prompt, $config);
        if (strpos($summary, 'API') !== 0 && strpos($summary, 'cURL') !== 0) {
            $final_analysis = "=== 综合分析总结 ===\n" . $summary . "\n\n=== 详细分析 ===\n" . substr($final_analysis, 0, 4000) . "\n\n[详细内容已截断，建议分段查看代码]";
        }
    }

    return $final_analysis;
}
function callAIAPI($code, $description, $config) {

    $api_key = getConfigValue($config, 'api_key');
    $api_base_url = getConfigValue($config, 'api_base_url');
    $model = getConfigValue($config, 'api_model');
    $timeout = getConfigValue($config, 'api_timeout') ?: 60;
    $retry_count = getConfigValue($config, 'api_retry_count') ?: 3;
    $max_tokens = getConfigValue($config, 'api_max_tokens') ?: 1000;
    $temperature = getConfigValue($config, 'api_temperature') ?: 0.7;
    
    if (empty($api_key) || empty($api_base_url)) {
        return "API配置不完整，请检查管理后台设置";
    }
    

    if (!filter_var($api_base_url, FILTER_VALIDATE_URL)) {
        return "API基础URL格式不正确";
    }
    

    $api_url = rtrim($api_base_url, '/') . '/chat/completions';
    

    $data = [
        'model' => $model ?: 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'user', 
                'content' => "请分析以下代码：\n\n代码：\n```\n{$code}\n```\n\n问题描述：{$description}"
            ]
        ],
        'max_tokens' => (int)$max_tokens,
        'temperature' => (float)$temperature
    ];
    

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    

    $attempts = 0;
    $max_attempts = $retry_count;
    $last_error = '';
    $use_fallback_ssl = false;
    
    while ($attempts < $max_attempts) {
        $attempts++;
        

        $ch = curl_init();
        

        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_TRANSFER_ENCODING, false);
        curl_setopt($ch, CURLOPT_HTTP_CONTENT_DECODING, true);
        

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        

        if ($use_fallback_ssl || $attempts > 2) {

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
        } else {

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2 | CURL_SSLVERSION_TLSv1_3);
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384');
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NO_REVOKE);
        }
        

        curl_setopt($ch, CURLOPT_TCP_NODELAY, true);
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 60);
        curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 60);
        

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AI-Code-Debug-System/1.0');
        curl_setopt($ch, CURLOPT_ENCODING, '');
        

        curl_setopt($ch, CURLOPT_BUFFERSIZE, 128000);
        curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1);
        curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 30);
        curl_setopt($ch, CURLOPT_MAX_RECV_SPEED_LARGE, 0);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        

        if (function_exists('curl_close') && version_compare(PHP_VERSION, '8.0', '<')) {
            curl_close($ch);
        }
        

        if ($response !== false && $http_code === 200) {

            $response_data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return "API响应解析失败: " . json_last_error_msg();
            }
            
            if (!isset($response_data['choices'][0]['message']['content'])) {
                return "API响应格式不正确";
            }
            
            $content = trim($response_data['choices'][0]['message']['content']);
            

            if (isset($response_data['choices'][0]['finish_reason']) && $response_data['choices'][0]['finish_reason'] === 'length') {
                $content .= "\n\n[注意：响应因长度限制被截断，建议增加max_tokens设置或简化问题描述]";
            }
            
            return $content;
        }
        

        $last_error = "尝试 {$attempts}/{$max_attempts} 失败: ";
        if ($response === false) {
            $last_error .= "cURL错误 ({$errno}): {$error}";
            

            if (in_array($errno, [35, 51, 53, 54, 55, 56, 58, 59, 60, 64, 66, 77, 80, 81, 82, 83, 90, 91])) {
                $use_fallback_ssl = true;
                $last_error .= " [将使用备用SSL配置重试]";
            }
        } else {
            $last_error .= "HTTP {$http_code}";
        }
        

        if ($attempts >= $max_attempts) {
            return $last_error;
        }
        

        $wait_time = min(pow(2, $attempts - 1), 5);
        sleep($wait_time);
    }
    
    return $last_error;
}




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
    $config = getConfig();
    $analysis_cost = isset($config['analysis_cost']) ? (int)$config['analysis_cost'] : 30;
    return getUserPoints($username) >= $analysis_cost;
}

function deductAnalysisPoints($username) {
    $config = getConfig();
    $analysis_cost = isset($config['analysis_cost']) ? (int)$config['analysis_cost'] : 30;
    
    $current_points = getUserPoints($username);
    if ($current_points < $analysis_cost) {
        return false;
    }
    return updateUserPoints($username, $current_points - $analysis_cost);
}


function calculateCodeLines($code) {
    $lines = explode("\n", $code);
    $non_empty_lines = array_filter($lines, function($line) {
        return trim($line) !== '';
    });
    return count($non_empty_lines);
}


function calculateRequiredPoints($code_lines) {
    $config = getConfig();
    $base_points = isset($config['analysis_cost']) ? (int)$config['analysis_cost'] : 30;
    $free_lines = 200;
    $extra_charge_lines = 100;
    $extra_charge_points = 10;

    if ($code_lines <= $free_lines) {
        return $base_points;
    }


    $extra_lines = $code_lines - $free_lines;
    $extra_charges = ceil($extra_lines / $extra_charge_lines);
    $total_points = $base_points + ($extra_charges * $extra_charge_points);

    return $total_points;
}


function canAffordAnalysisByCode($username, $code) {
    $code_lines = calculateCodeLines($code);
    $required_points = calculateRequiredPoints($code_lines);
    return getUserPoints($username) >= $required_points;
}


function deductAnalysisPointsByCode($username, $code) {
    $code_lines = calculateCodeLines($code);
    $required_points = calculateRequiredPoints($code_lines);
    $current_points = getUserPoints($username);

    if ($current_points < $required_points) {
        return false;
    }

    return updateUserPoints($username, $current_points - $required_points);
}

function addSigninPoints($username) {
    $config = getConfig();
    $signin_reward = isset($config['signin_reward']) ? (int)$config['signin_reward'] : 50;
    
    $users = getUsers();
    if (!isset($users[$username])) {
        return false;
    }
    

    $today = date('Y-m-d');
    if (isset($users[$username]['last_signin']) && $users[$username]['last_signin'] === $today) {
        return false;
    }
    
    $current_points = getUserPoints($username);
    $users[$username]['points'] = $current_points + $signin_reward;
    $users[$username]['last_signin'] = $today;
    
    return saveUsers($users);
}




function getUserInviteCodes($username) {
    $users = getUsers();
    return isset($users[$username]['invite_codes']) ? $users[$username]['invite_codes'] : [];
}



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
                    

                    $invite_reward = (int)getConfigValue(getConfig(), 'invite_reward', 100);
                    if (!isset($users[$username]['points'])) {
                        $users[$username]['points'] = 0;
                    }
                    $users[$username]['points'] += $invite_reward;
                    

                    if (!isset($users[$newUsername]['points'])) {
                        $users[$newUsername]['points'] = 50;
                    }
                    
                    saveUsers($users);
                    return $username;
                }
            }
        }
    }
    
    return false;
}


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
    

    $redeem_codes[$code]['used'] = true;
    $redeem_codes[$code]['used_by'] = $username;
    $redeem_codes[$code]['used_at'] = date('Y-m-d H:i:s');
    

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


function autoUpdateFromGithub($repo, $branch = 'main') {

    if (!preg_match('/^[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+$/', $repo)) {
        return ['success' => false, 'message' => '仓库格式无效，应为 user/repo 格式'];
    }


    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => '服务器不支持 cURL，无法进行自动更新'];
    }


    $api_url = "https://api.github.com/repos/{$repo}";
    $zip_url = "https://github.com/{$repo}/archive/refs/heads/{$branch}.zip";


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-AutoUpdate/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $repo_info = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 404) {
        return ['success' => false, 'message' => 'GitHub 仓库不存在，请检查仓库地址'];
    }

    if ($http_code !== 200) {
        return ['success' => false, 'message' => '无法访问 GitHub API，HTTP 状态码：' . $http_code];
    }

    $repo_data = json_decode($repo_info, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'GitHub API 返回无效数据'];
    }


    $commits_url = "https://api.github.com/repos/{$repo}/commits/{$branch}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $commits_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-AutoUpdate/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $commit_info = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['success' => false, 'message' => '无法获取提交信息，HTTP 状态码：' . $http_code];
    }

    $commit_data = json_decode($commit_info, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($commit_data['sha'])) {
        return ['success' => false, 'message' => '无法解析提交信息'];
    }

    $latest_commit = $commit_data['sha'];
    $commit_message = $commit_data['commit']['message'] ?? '无提交信息';


    $version_file = 'data/version.txt';
    $current_commit = '';
    if (file_exists($version_file)) {
        $current_commit = trim(file_get_contents($version_file));
    }

    if ($current_commit === $latest_commit) {
        return ['success' => true, 'message' => '系统已是最新版本'];
    }


    $temp_zip = 'data/update_temp.zip';
    $update_dir = 'data/update_temp';


    if (file_exists($temp_zip)) unlink($temp_zip);
    if (is_dir($update_dir)) {
        array_map('unlink', glob("$update_dir/*"));
        rmdir($update_dir);
    }


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zip_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-AutoUpdate/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $zip_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['success' => false, 'message' => '无法下载更新包，HTTP 状态码：' . $http_code];
    }

    file_put_contents($temp_zip, $zip_content);


    if (!class_exists('ZipArchive')) {
        unlink($temp_zip);
        return ['success' => false, 'message' => '服务器不支持 ZIP 解压功能'];
    }

    $zip = new ZipArchive();
    if ($zip->open($temp_zip) !== true) {
        unlink($temp_zip);
        return ['success' => false, 'message' => '无法打开更新包'];
    }

    mkdir($update_dir);
    $zip->extractTo($update_dir);
    $zip->close();
    unlink($temp_zip);


    $extracted_dirs = glob("$update_dir/*", GLOB_ONLYDIR);
    if (empty($extracted_dirs)) {
        array_map('unlink', glob("$update_dir/*"));
        rmdir($update_dir);
        return ['success' => false, 'message' => '更新包结构异常'];
    }

    $source_dir = $extracted_dirs[0];


    $backup_files = [
        'data/users.json',
        'data/config.json',
        'data/records.json',
        'data/redeem_codes.json',
        'config.php'
    ];

    $temp_backup = 'data/backup_temp';
    if (!is_dir($temp_backup)) mkdir($temp_backup);

    foreach ($backup_files as $file) {
        if (file_exists($file)) {
            copy($file, $temp_backup . '/' . basename($file));
        }
    }


    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS));
    $updated_files = 0;

    foreach ($iterator as $file) {
        $relative_path = str_replace($source_dir . '/', '', $file->getPathname());


        if (strpos($relative_path, 'data/') === 0 || $relative_path === 'config.php') {
            continue;
        }

        $target_path = $relative_path;
        $target_dir = dirname($target_path);

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        copy($file->getPathname(), $target_path);
        $updated_files++;
    }


    foreach ($backup_files as $file) {
        $backup_path = $temp_backup . '/' . basename($file);
        if (file_exists($backup_path)) {
            copy($backup_path, $file);
        }
    }


    array_map('unlink', glob("$temp_backup/*"));
    rmdir($temp_backup);


    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($update_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($update_dir);


    file_put_contents($version_file, $latest_commit);

    return [
        'success' => true,
        'message' => "更新成功！更新了 {$updated_files} 个文件。最新提交：{$commit_message}"
    ];
}