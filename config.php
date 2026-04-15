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
        $adminUser = [
            'username' => ADMIN_USERNAME,
            'password' => hashPassword(ADMIN_PASSWORD),
            'email' => '',
            'points' => 1000,
            'is_admin' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s'),
            'invite_code' => '',
            'invited_by' => '',
            'signin_streak' => 0,
            'last_signin' => '',
            'total_signins' => 0
        ];
        saveUsers([generateId() => $adminUser]);
    }
    

    $defaultConfig = [
        'api_key' => '',
        'api_base_url' => 'https://api.openai.com/v1',
        'api_model' => 'gpt-3.5-turbo',
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => '',
        'smtp_from_name' => 'AI代码调试系统',
        'site_name' => 'AI代码调试系统',
        'site_description' => '专业的AI代码调试和分析平台',
        'analysis_cost' => 30,
        'signin_reward' => 50,
        'invite_reward' => 100,
        'announcement_enabled' => '0',
        'announcement_text' => '',
        'update_repo' => 'Gaozx1/aidebug',
        'update_branch' => 'main',
        'turnstile_site_key' => 'your_site_key_here',  // 添加：Cloudflare Turnstile 站点密钥
        'turnstile_secret_key' => 'your_secret_key_here'  // 添加：Cloudflare Turnstile 秘密密钥
    ];
    
    if (!file_exists(CONFIG_FILE)) {
        saveConfig($defaultConfig);
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
            $config[$k] = $v;
        }
    } else {
        $config[$key] = $value;
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
        'text' => getConfigValue($config, 'announcement_text', '')
    ];
}

function renderAnnouncementBanner() {
    $announcement = getAnnouncementData();
    if ($announcement['enabled'] === '1' && !empty($announcement['text'])) {
        echo '<div class="announcement-banner">' . htmlspecialchars($announcement['text']) . '</div>';
    }
}


function sendEmail($to, $subject, $message) {
    $config = getConfig();
    

    if (!getConfigValue($config, 'email_enabled', '0')) {
        return false;
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
        $mail->SMTPSecure = $smtp_config['secure'];
        $mail->Port = $smtp_config['port'];

        $mail->setFrom($smtp_config['from_email'], $smtp_config['from_name']);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
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
        return false;
    }
    
    if ($http_code == 200) {
        return true;
    } else {
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
        return false;
    }
    

    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '220') {
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
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($username) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($password) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '235') {
        fclose($socket);
        return false;
    }
    

    fputs($socket, "MAIL FROM: <{$from_email}>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return false;
    }
    
    fputs($socket, "RCPT TO: <{$to}>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return false;
    }
    
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '354') {
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
        echo '<div class="message">' . htmlspecialchars($message) . '</div>';
    }
}


function markdownToHtml($markdown) {
    $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);


    $codeBlocks = [];
    $markdown = preg_replace_callback('/```(\w+)?\n([\s\S]*?)\n```/m', function ($matches) use (&$codeBlocks) {
        $id = count($codeBlocks);
        $codeBlocks[] = '<pre><code class="language-' . ($matches[1] ?: 'text') . '">' . htmlspecialchars($matches[2]) . '</code></pre>';
        return "[[CODE_BLOCK_$id]]";
    }, $markdown);


    $latexBlocks = [];
    $markdown = preg_replace_callback('/\$\$([\s\S]*?)\$\$/m', function($matches) use (&$latexBlocks) {
        $id = count($latexBlocks);
        $latexBlocks[] = '<div class="math-display">' . htmlspecialchars($matches[1]) . '</div>';
        return "[[LATEX_BLOCK_$id]]";
    }, $markdown);
    $markdown = preg_replace_callback('/\\\\\[(.*?)\\\\\]/s', function($matches) use (&$latexBlocks) {
        $id = count($latexBlocks);
        $latexBlocks[] = '<div class="math-display">' . htmlspecialchars($matches[1]) . '</div>';
        return "[[LATEX_BLOCK_$id]]";
    }, $markdown);
    $markdown = preg_replace_callback('/\\\\\((.*?)\\\\\)/s', function($matches) use (&$latexBlocks) {
        $id = count($latexBlocks);
        $latexBlocks[] = '<span class="math-inline">' . htmlspecialchars($matches[1]) . '</span>';
        return "[[LATEX_INLINE_$id]]";
    }, $markdown);


    $markdown = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $markdown);
    $markdown = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $markdown);
    $markdown = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $markdown);
    $markdown = preg_replace('/^\* (.*)$/m', '<li>$1</li>', $markdown);
    $markdown = preg_replace('/^- (.*)$/m', '<li>$1</li>', $markdown);
    $markdown = preg_replace('/^(\d+)\. (.*)$/m', '<li>$1. $2</li>', $markdown);
    $markdown = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown);
    $markdown = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $markdown);
    $markdown = preg_replace('/`(.*?)`/', '<code>$1</code>', $markdown);
    $markdown = preg_replace('/\n\n/', '</p><p>', $markdown);
    $markdown = '<p>' . $markdown . '</p>';
    $markdown = preg_replace('/<p>(<li>.*<\/li>)<\/p>/s', '<ul>$1</ul>', $markdown);
    $markdown = preg_replace('/<p>(<h[1-6]>.*<\/h[1-6]>)<\/p>/s', '$1', $markdown);
    $markdown = preg_replace('/<p>(<ul>.*<\/ul>)<\/p>/s', '$1', $markdown);
    $markdown = preg_replace('/<p>(<pre>.*<\/pre>)<\/p>/s', '$1', $markdown);

    foreach ($codeBlocks as $id => $block) {
        $markdown = str_replace("[[CODE_BLOCK_$id]]", $block, $markdown);
    }
    foreach ($latexBlocks as $id => $block) {
        $markdown = str_replace("[[LATEX_BLOCK_$id]]", $block, $markdown);
    }
    $latexInlines = preg_grep('/^.*LATEX_INLINE.*$/', array_keys($latexBlocks));
    foreach ($latexInlines as $id) {
        $markdown = str_replace("[[LATEX_INLINE_$id]]", $latexBlocks[$id], $markdown);
    }

    return $markdown;
}


function callAIAnalysis($code, $description) {
    $config = getConfig();
    $api_key = getConfigValue($config, 'api_key');
    $api_base_url = getConfigValue($config, 'api_base_url', 'https://api.openai.com/v1');
    $api_model = getConfigValue($config, 'api_model', 'gpt-3.5-turbo');

    if (empty($api_key)) {
        return 'API密钥未配置';
    }

    $url = rtrim($api_base_url, '/') . '/chat/completions';

    $messages = [
        [
            'role' => 'system',
            'content' => '你是一个专业的代码调试助手。请分析用户提供的代码，找出潜在的错误、改进建议和最佳实践。回复格式：先总结问题，然后详细说明每个问题，最后给出修复后的代码。'
        ],
        [
            'role' => 'user',
            'content' => "代码描述：{$description}\n\n代码：\n{$code}"
        ]
    ];

    $data = [
        'model' => $api_model,
        'messages' => $messages,
        'max_tokens' => 2000,
        'temperature' => 0.7
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return 'API请求失败：' . $error;
    }

    if ($http_code != 200) {
        return 'API请求失败，HTTP状态码：' . $http_code . '，响应：' . $response;
    }

    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    } else {
        return 'API响应格式错误';
    }
}


function callAIAnalysisBatched($code, $description, $config) {
    return callAIAPI($code, $description, $config);
}
function callAIAPI($code, $description, $config) {
    $api_key = getConfigValue($config, 'api_key');
    $api_base_url = getConfigValue($config, 'api_base_url', 'https://api.openai.com/v1');
    $api_model = getConfigValue($config, 'api_model', 'gpt-3.5-turbo');

    if (empty($api_key)) {
        return 'API密钥未配置';
    }

    $url = rtrim($api_base_url, '/') . '/chat/completions';

    $messages = [
        [
            'role' => 'system',
            'content' => '你是一个专业的代码调试助手。请分析用户提供的代码，找出潜在的错误、改进建议和最佳实践。回复格式：先总结问题，然后详细说明每个问题，最后给出修复后的代码。'
        ],
        [
            'role' => 'user',
            'content' => "代码描述：{$description}\n\n代码：\n{$code}"
        ]
    ];

    $data = [
        'model' => $api_model,
        'messages' => $messages,
        'max_tokens' => 2000,
        'temperature' => 0.7
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return 'API请求失败：' . $error;
    }

    if ($http_code != 200) {
        return 'API请求失败，HTTP状态码：' . $http_code . '，响应：' . $response;
    }

    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    } else {
        return 'API响应格式错误';
    }
}




function getUserPoints($username) {
    $users = getUsers();
    foreach ($users as $user) {
        if ($user['username'] == $username) {
            return $user['points'] ?? 0;
        }
    }
    return 0;
}

function updateUserPoints($username, $points) {
    $users = getUsers();
    foreach ($users as $id => $user) {
        if ($user['username'] == $username) {
            $users[$id]['points'] = $points;
            saveUsers($users);
            return true;
        }
    }
    return false;
}

function canAffordAnalysis($username) {
    $config = getConfig();
    $cost = getConfigValue($config, 'analysis_cost', 30);
    $userPoints = getUserPoints($username);
    return $userPoints >= $cost;
}

function deductAnalysisPoints($username) {
    $config = getConfig();
    $cost = getConfigValue($config, 'analysis_cost', 30);
    $userPoints = getUserPoints($username);
    if ($userPoints >= $cost) {
        return updateUserPoints($username, $userPoints - $cost);
    }
    return false;
}


function calculateCodeLines($code) {
    $lines = explode("\n", $code);
    $nonEmptyLines = array_filter($lines, function($line) {
        return trim($line) !== '';
    });
    return count($nonEmptyLines);
}


function calculateRequiredPoints($code_lines) {
    $config = getConfig();
    $baseCost = getConfigValue($config, 'analysis_cost', 30);
    if ($code_lines <= 10) {
        return $baseCost;
    } elseif ($code_lines <= 50) {
        return $baseCost * 2;
    } elseif ($code_lines <= 100) {
        return $baseCost * 3;
    } else {
        return $baseCost * 4;
    }
}


function canAffordAnalysisByCode($username, $code) {
    $code_lines = calculateCodeLines($code);
    $requiredPoints = calculateRequiredPoints($code_lines);
    $userPoints = getUserPoints($username);
    return $userPoints >= $requiredPoints;
}


function deductAnalysisPointsByCode($username, $code) {
    $code_lines = calculateCodeLines($code);
    $requiredPoints = calculateRequiredPoints($code_lines);
    $userPoints = getUserPoints($username);
    if ($userPoints >= $requiredPoints) {
        return updateUserPoints($username, $userPoints - $requiredPoints);
    }
    return false;
}

function addSigninPoints($username) {
    $config = getConfig();
    $reward = getConfigValue($config, 'signin_reward', 50);
    $userPoints = getUserPoints($username);
    return updateUserPoints($username, $userPoints + $reward);
}




function getUserInviteCodes($username) {
    $users = getUsers();
    $inviteCodes = [];
    foreach ($users as $user) {
        if (isset($user['invited_by']) && $user['invited_by'] == $username) {
            $inviteCodes[] = $user['invite_code'];
        }
    }
    return $inviteCodes;
}



function generateInviteCode($username) {
    $code = strtoupper(substr(md5(uniqid($username, true)), 0, 8));
    $users = getUsers();
    foreach ($users as $id => $user) {
        if ($user['username'] == $username) {
            $users[$id]['invite_code'] = $code;
            saveUsers($users);
            return $code;
        }
    }
    return false;
}

function useInviteCode($code, $newUsername) {
    $users = getUsers();
    foreach ($users as $id => $user) {
        if ($user['invite_code'] == $code) {
            $users[$id]['points'] += 100; // 邀请奖励
            $newUser = [
                'username' => $newUsername,
                'password' => '', // 需要设置
                'email' => '',
                'points' => 100, // 新用户初始积分
                'is_admin' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'last_login' => date('Y-m-d H:i:s'),
                'invite_code' => '',
                'invited_by' => $user['username'],
                'signin_streak' => 0,
                'last_signin' => '',
                'total_signins' => 0
            ];
            $users[generateId()] = $newUser;
            saveUsers($users);
            return true;
        }
    }
    return false;
}


function generateRedeemCode($points, $creator = 'admin') {
    $code = strtoupper(substr(md5(uniqid($creator . $points, true)), 0, 10));
    $redeem_codes = getRedeemCodes();
    $redeem_codes[$code] = [
        'points' => $points,
        'creator' => $creator,
        'created_at' => date('Y-m-d H:i:s'),
        'used' => false,
        'used_by' => '',
        'used_at' => ''
    ];
    saveRedeemCodes($redeem_codes);
    return $code;
}

function useRedeemCode($code, $username) {
    $redeem_codes = getRedeemCodes();
    if (isset($redeem_codes[$code]) && !$redeem_codes[$code]['used']) {
        $points = $redeem_codes[$code]['points'];
        $userPoints = getUserPoints($username);
        updateUserPoints($username, $userPoints + $points);
        $redeem_codes[$code]['used'] = true;
        $redeem_codes[$code]['used_by'] = $username;
        $redeem_codes[$code]['used_at'] = date('Y-m-d H:i:s');
        saveRedeemCodes($redeem_codes);
        return $points;
    }
    return false;
}

function getRedeemCodes() {
    return loadEncryptedData(DATA_DIR . '/redeem_codes.json');
}

function saveRedeemCodes($redeem_codes) {
    return saveEncryptedData(DATA_DIR . '/redeem_codes.json', $redeem_codes);
}

function getActiveRedeemCodes() {
    $redeem_codes = getRedeemCodes();
    $active = [];
    foreach ($redeem_codes as $code => $data) {
        if (!$data['used']) {
            $active[$code] = $data;
        }
    }
    return $active;
}


function autoUpdateFromGithub($repo, $branch = 'main') {
    $url = "https://api.github.com/repos/{$repo}/contents?ref={$branch}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AI Code Debug System');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        return ['success' => false, 'message' => '无法获取GitHub文件列表'];
    }

    $files = json_decode($response, true);
    if (!$files) {
        return ['success' => false, 'message' => 'GitHub响应格式错误'];
    }

    $updatedFiles = [];
    foreach ($files as $file) {
        if ($file['type'] == 'file' && in_array($file['name'], ['index.php', 'login.php', 'user.php', 'admin.php', 'config.php', 'components/sidebar.php', 'components/styles.php'])) {
            $fileUrl = $file['download_url'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fileUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $content = curl_exec($ch);
            curl_close($ch);

            if ($content && file_put_contents($file['name'], $content) !== false) {
                $updatedFiles[] = $file['name'];
            }
        }
    }

    return ['success' => true, 'message' => '更新成功，文件：' . implode(', ', $updatedFiles)];
}
// Cloudflare Turnstile 验证码验证函数
function verifyTurnstile($token) {
    $config = getConfig();
    $secret_key = getConfigValue($config, 'turnstile_secret_key');

    if (empty($secret_key)) {
        return true; // 如果未配置密钥，跳过验证
    }

    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => $secret_key,
        'response' => $token
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === false) {
        return false;
    }

    $response = json_decode($result, true);
    return isset($response['success']) && $response['success'];
}?>