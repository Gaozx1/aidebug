<?php

require_once 'config.php';
configureSession();
require_once __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'sidebar.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'styles.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';

    // 验证验证码
    if (!verifyTurnstile($turnstile_token)) {
        setMessage('验证码验证失败，请重试', 'error');
        header('Location: signin.php');
        exit;
    }

    $config = getConfig();
    $signin_reward = isset($config['signin_reward']) ? (int)$config['signin_reward'] : 50;

    // 检查是否已经签到
    $users = getUsers();
    $today = date('Y-m-d');
    $user_id = $_SESSION['user_id'];
    
    if (isset($users[$user_id]['last_signin']) && $users[$user_id]['last_signin'] === $today) {
        setMessage('今天已经签到过了，请明天再来！', 'error');
    } else {
        if (addSigninPoints($user_id)) {
            setMessage("签到成功！获得{$signin_reward}积分。", 'success');
            header('Location: dashboard.php');
            exit;
        } else {
            setMessage('签到失败，请稍后重试！', 'error');
        }
    }
}

$users = getUsers();
$today = date('Y-m-d');
$already_signed = isset($users[$_SESSION['user_id']]['last_signin']) &&
                 $users[$_SESSION['user_id']]['last_signin'] === $today;

$config = getConfig();
$signin_reward = getConfigValue($config, 'signin_reward', 50);
$analysis_cost = getConfigValue($config, 'analysis_cost', 30);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>每日签到 - AI代码调试系统</title>
    <?php renderStyles(); ?>
    <style>
        .signin-page {
            max-width: 720px;
            margin: 0 auto;
        }

        .signin-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }

        .signin-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .signin-header p {
            color: #666;
            margin-bottom: 30px;
        }

        .points-reward {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .points-reward h2 {
            color: #28a745;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .user-info {
            margin-top: 20px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 5px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .message.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .signin-container {
                padding: 30px;
            }
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body class="">
    <?php renderSidebar('signin'); ?>
    <div class="main-content">
        <div class="signin-page">
            <div class="signin-container">
                <div class="signin-header">
                    <h1>每日签到</h1>
                    <p>坚持签到，获取更多积分</p>
                </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="points-reward">
            <h2>+<?php echo $signin_reward; ?> 积分</h2>
            <p>每日签到奖励</p>
        </div>

        <form method="POST" action="">
            <?php if ($already_signed): ?>
                <button type="button" class="btn btn-primary" disabled>今日已签到</button>
                <p style="margin-top: 10px; color: #666;">明天再来获取积分吧！</p>
            <?php else: ?>
                <div class="form-group" style="margin-bottom: 15px;">
                    <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars(getConfigValue(getConfig(), 'turnstile_site_key')); ?>"></div>
                </div>
                <button type="submit" name="signin" class="btn btn-primary">立即签到</button>
            <?php endif; ?>
        </form>

        <div class="user-info">
            <p>当前积分: <strong><?php echo getUserPoints($_SESSION['user_id']); ?></strong></p>
            <p>每次分析消耗: <strong><?php echo $analysis_cost; ?> 积分</strong></p>
        </div>

        <div style="margin-top: 20px;">
            <a href="dashboard.php" style="color: #007bff; text-decoration: none;">返回控制台</a>
        </div>
    </div>
    </div>
</body>
</html>