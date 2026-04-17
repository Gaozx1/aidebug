<?php
require_once 'config.php';
configureSession();
session_start();

$provider = isset($_GET['provider']) ? strtolower(trim($_GET['provider'])) : '';
$config = getConfig();
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_provider'] = $provider;

// 检查是否是绑定模式（已登录用户）
$is_binding = isset($_SESSION['user_id']);
if ($is_binding) {
    $_SESSION['oauth_binding'] = true;
}

if ($provider === 'github') {
    $client_id = getConfigValue($config, 'github_client_id');
    $redirect_uri = getConfigValue($config, 'github_redirect_uri');
    if (empty($client_id) || empty($redirect_uri)) {
        exit('GitHub OAuth 未配置，请在后台填写 Client ID、Client Secret 和回调地址。');
    }
    $params = http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => 'read:user user:email',
        'state' => $state,
        'allow_signup' => 'true'
    ]);
    header('Location: https://github.com/login/oauth/authorize?' . $params);
    exit;
}

exit('无效的 OAuth 提供商。');
