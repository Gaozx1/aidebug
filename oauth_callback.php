<?php
require_once 'config.php';
configureSession();
session_start();

function httpPost($url, $data, $headers = []) {
    $options = [
        'http' => [
            'header' => array_merge([
                'Content-type: application/x-www-form-urlencoded'
            ], $headers),
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 60,
        ]
    ];
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

function httpGet($url, $headers = []) {
    $options = [
        'http' => [
            'header' => implode("\r\n", $headers),
            'method' => 'GET',
            'timeout' => 60,
        ]
    ];
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

$provider = isset($_GET['provider']) ? strtolower(trim($_GET['provider'])) : '';
$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$state = isset($_GET['state']) ? trim($_GET['state']) : '';

if (empty($provider) || empty($code) || empty($state) || !isset($_SESSION['oauth_state'])) {
    exit('OAuth 回调参数错误。');
}

if ($state !== $_SESSION['oauth_state']) {
    exit('OAuth 状态校验失败。');
}

$config = getConfig();
$userInfo = null;
$is_binding = isset($_SESSION['oauth_binding']) && $_SESSION['oauth_binding'];

// 如果是绑定模式，需要用户已登录
if ($is_binding && !isset($_SESSION['user_id'])) {
    exit('绑定模式需要先登录。');
}

if ($provider === 'github') {
    $client_id = getConfigValue($config, 'github_client_id');
    $client_secret = getConfigValue($config, 'github_client_secret');
    $redirect_uri = getConfigValue($config, 'github_redirect_uri');
    if (empty($client_id) || empty($client_secret) || empty($redirect_uri)) {
        exit('GitHub OAuth 未完全配置。');
    }

    $tokenResponse = httpPost('https://github.com/login/oauth/access_token', [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code,
        'redirect_uri' => $redirect_uri,
        'state' => $state,
    ], ['Accept: application/json']);

    $tokenData = json_decode($tokenResponse, true);
    if (empty($tokenData['access_token'])) {
        exit('GitHub 令牌获取失败。');
    }

    $accessToken = $tokenData['access_token'];
    $userJson = httpGet('https://api.github.com/user', [
        'User-Agent: PHP OAuth Client',
        'Accept: application/vnd.github+json',
        'Authorization: token ' . $accessToken,
    ]);
    $userData = json_decode($userJson, true);
    if (empty($userData['id'])) {
        exit('无法读取 GitHub 用户信息。');
    }

    $email = '';
    if (!empty($userData['email'])) {
        $email = $userData['email'];
    } else {
        $emailsJson = httpGet('https://api.github.com/user/emails', [
            'User-Agent: PHP OAuth Client',
            'Accept: application/vnd.github+json',
            'Authorization: token ' . $accessToken,
        ]);
        $emailsData = json_decode($emailsJson, true);
        if (is_array($emailsData)) {
            foreach ($emailsData as $item) {
                if (!empty($item['primary']) && !empty($item['email'])) {
                    $email = $item['email'];
                    break;
                }
            }
        }
    }

    $userInfo = [
        'provider' => 'github',
        'id' => (string)$userData['id'],
        'login' => $userData['login'] ?? 'github_user',
        'display_name' => $userData['name'] ?? ($userData['login'] ?? 'GitHub 用户'),
        'email' => $email,
        'avatar_url' => $userData['avatar_url'] ?? ''
    ];
}

if (empty($userInfo)) {
    exit('不支持的 OAuth 提供商。');
}

if ($is_binding) {
    // 绑定模式：将OAuth信息添加到当前用户
    $current_username = $_SESSION['user_id'];
    list($userId, $currentUser) = findUserByUsername($current_username);

    if (!$currentUser) {
        exit('用户不存在。');
    }

    $bound_providers = $currentUser['oauth_bindings'] ?? [];

    // 检查是否已被其他用户绑定
    list($existingId, $existingUser) = findUserByOAuth($userInfo['provider'], $userInfo['id']);
    if ($existingUser && $existingUser['username'] !== $current_username) {
        exit('该第三方账户已被其他用户绑定。');
    }

    // 检查是否已经绑定了这个提供商
    if (isset($bound_providers[$userInfo['provider']])) {
        exit('您已经绑定了该第三方账户。');
    }

    // 添加新的OAuth绑定
    $bound_providers[$userInfo['provider']] = $userInfo['id'];
    $users = getUsers();
    $users[$userId]['oauth_bindings'] = $bound_providers;

    // 如果是第一个OAuth绑定，更新display_name和avatar_url
    if (count($bound_providers) == 1) {
        $users[$userId]['display_name'] = $userInfo['display_name'];
        $users[$userId]['avatar_url'] = $userInfo['avatar_url'];
    }

    saveUsers($users);

    // 清理会话
    unset($_SESSION['oauth_binding']);

    header('Location: profile.php?message=' . urlencode(ucfirst($userInfo['provider']) . ' 账户绑定成功'));
    exit;
}

list($foundId, $foundUser) = findUserByOAuth($userInfo['provider'], $userInfo['id']);
if ($foundUser) {
    $user = $foundUser;
} else {
    list($emailUserId, $emailUser) = findUserByEmail($userInfo['email']);
    if ($emailUser) {
        // 将OAuth信息添加到现有用户
        $bound_providers = $emailUser['oauth_bindings'] ?? [];
        $bound_providers[$userInfo['provider']] = $userInfo['id'];
        $emailUser['oauth_bindings'] = $bound_providers;
        $emailUser['display_name'] = $userInfo['display_name'];
        $emailUser['avatar_url'] = $userInfo['avatar_url'];
        $users = getUsers();
        $users[$emailUserId] = $emailUser;
        saveUsers($users);
        $user = $emailUser;
    } else {
        list($newUserId, $newUser) = createOAuthUser(
            $userInfo['provider'],
            $userInfo['id'],
            $userInfo['login'],
            $userInfo['email'],
            $userInfo['display_name'],
            $userInfo['avatar_url']
        );
        $user = $newUser;
    }
}

$_SESSION['user_id'] = $user['username'];
$_SESSION['username'] = $user['display_name'] ?? $user['username'];
$_SESSION['is_admin'] = !empty($user['is_admin']);
updateUserLastLogin($user['username']);

header('Location: dashboard.php');
exit;
