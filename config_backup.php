<?php

set_time_limit(120);
ini_set('max_execution_time', 120);
ini_set('default_socket_timeout', 60);

date_default_timezone_set('Asia/Shanghai');

define('DATA_DIR', 'data');
define('USERS_FILE', DATA_DIR . '/users.json');
define('RECORDS_FILE', DATA_DIR . '/records.json');
define('CONFIG_FILE', DATA_DIR . '/config.json');
define('SHARES_FILE', DATA_DIR . '/shares.json');

// 设置会话过期时间为一周
function configureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', 604800); // 7天
        ini_set('session.cookie_lifetime', 604800); // 7天
    }
}

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', '123456');
define('SITE_NAME', 'AI代码调试系统');

define('ENCRYPTION_KEY', 'MySecureKey2024!@#$%^&*()ABCD');

// AI提示词配置
define('DEFAULT_AI_PROMPT', '请分析以下代码问题，提供详细的调试建议和解决方案：');
define('DEFAULT_CODE_REVIEW_PROMPT', '请对以下代码进行代码审查，指出潜在问题和改进建议：');
define('DEFAULT_OPTIMIZATION_PROMPT', '请分析以下代码的性能瓶颈，提供优化建议：');

function initDataFiles() {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    
    if (!file_exists(USERS_FILE)) {
        $adminUser = [
            'username' => ADMIN_USERNAME,
            'password' => hashPassword(ADMIN_PASSWORD),
            'email' => '',
            'display_name' => '管理员',
            'avatar_url' => '',
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
        file_put_contents(USERS_FILE, json_encode(['users' => [$adminUser]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    if (!file_exists(RECORDS_FILE)) {
        file_put_contents(RECORDS_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    if (!file_exists(CONFIG_FILE)) {
        $config = [
            'site_name' => SITE_NAME,
            'site_description' => '专业的AI代码调试系统',
            'api_key' => '',
            'api_base_url' => '',
            'api_model' => '',
            'smtp_host' => '',
            'smtp_port' => '',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => '',
            'smtp_from_name' => '',
            'announcement_enabled' => false,
            'announcement_text' => '',
            'invite_reward' => 100,
            'ai_prompt_debug' => DEFAULT_AI_PROMPT,
            'ai_prompt_review' => DEFAULT_CODE_REVIEW_PROMPT,
            'ai_prompt_optimization' => DEFAULT_OPTIMIZATION_PROMPT,
            'ai_prompt_custom' => ''
        ];
        file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    if (!file_exists(SHARES_FILE)) {
        file_put_contents(SHARES_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

function getConfig() {
    if (!file_exists(CONFIG_FILE)) {
        return [
            'site_name' => SITE_NAME,
            'site_description' => '专业的AI代码调试系统',
            'api_key' => '',
            'api_base_url' => '',
            'api_model' => '',
            'smtp_host' => '',
            'smtp_port' => '',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => '',
            'smtp_from_name' => '',
            'announcement_enabled' => false,
            'announcement_text' => '',
            'invite_reward' => 100,
            'ai_prompt_debug' => DEFAULT_AI_PROMPT,
            'ai_prompt_review' => DEFAULT_CODE_REVIEW_PROMPT,
            'ai_prompt_optimization' => DEFAULT_OPTIMIZATION_PROMPT,
            'ai_prompt_custom' => ''
        ];
    }
    
    $config = json_decode(file_get_contents(CONFIG_FILE), true);
    return $config ?: [];
}

function saveConfig($config) {
    return file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function getUsers() {
    if (!file_exists(USERS_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(USERS_FILE), true);
    return $data['users'] ?? [];
}

function saveUsers($users) {
    return file_put_contents(USERS_FILE, json_encode(['users' => $users], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function getRecords() {
    if (!file_exists(RECORDS_FILE)) {
        return [];
    }
    return json_decode(file_get_contents(RECORDS_FILE), true) ?: [];
}

function saveRecords($records) {
    return file_put_contents(RECORDS_FILE, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function generateId() {
    return uniqid('', true);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function getUserById($user_id) {
    $users = getUsers();
    foreach ($users as $user) {
        if ($user['username'] === $user_id) {
            return $user;
        }
    }
    return null;
}

function getUserPoints($user_id) {
    $user = getUserById($user_id);
    return $user['points'] ?? 0;
}

function updateUserPoints($user_id, $points) {
    $users = getUsers();
    foreach ($users as &$user) {
        if ($user['username'] === $user_id) {
            $user['points'] = $points;
            break;
        }
    }
    return saveUsers($users);
}

function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function callAIAnalysis($problem, $code, $prompt_type = 'debug') {
    $config = getConfig();
    
    if (empty($config['api_key']) || empty($config['api_base_url'])) {
        return 'API配置不完整，请检查系统配置。';
    }
    
    // 根据提示词类型选择相应的提示词
    $prompt_templates = [
        'debug' => $config['ai_prompt_debug'] ?? DEFAULT_AI_PROMPT,
        'review' => $config['ai_prompt_review'] ?? DEFAULT_CODE_REVIEW_PROMPT,
        'optimization' => $config['ai_prompt_optimization'] ?? DEFAULT_OPTIMIZATION_PROMPT,
        'custom' => $config['ai_prompt_custom'] ?? ''
    ];
    
    $selected_prompt = $prompt_templates[$prompt_type] ?? $prompt_templates['debug'];
    
    // 如果选择自定义提示词且为空，则使用默认调试提示词
    if ($prompt_type === 'custom' && empty($selected_prompt)) {
        $selected_prompt = $prompt_templates['debug'];
    }
    
    $prompt = "{$selected_prompt}\n\n问题描述：{$problem}\n\n代码：\n```\n{$code}\n```";
    
    // 这里应该是调用AI API的实际代码
    // 示例返回：
    return "AI分析结果（使用提示词类型：{$prompt_type}）：\n\n" . substr($prompt, 0, 200) . "...";
}

// 其他现有函数...
?>