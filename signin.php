<?php
// signin.php - 每日签到页面

require_once 'config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 处理签到请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    $config = getConfig();
    $signin_reward = isset($config['signin_reward']) ? (int)$config['signin_reward'] : 50;
    
    if (addSigninPoints($_SESSION['user_id'])) {
        setMessage("签到成功！获得{$signin_reward}积分。", 'success');
        header('Location: dashboard.php');
        exit;
    } else {
        setMessage('今天已经签到过了，请明天再来！', 'error');
    }
}

// 检查今天是否已签到
$users = getUsers();
$today = date('Y-m-d');
$already_signed = isset($users[$_SESSION['user_id']]['last_signin']) && 
                 $users[$_SESSION['user_id']]['last_signin'] === $today;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>每日签到 - AI代码调试系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .signin-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
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
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s;
            display: inline-block;
            width: 100%;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
        }
        
        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-primary:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
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
    </style>
</head>
<body>
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
            <h2>+50 积分</h2>
            <p>每日签到奖励</p>
        </div>
        
        <form method="POST" action="">
            <?php if ($already_signed): ?>
                <button type="button" class="btn btn-primary" disabled>今日已签到</button>
                <p style="margin-top: 10px; color: #666;">明天再来获取积分吧！</p>
            <?php else: ?>
                <button type="submit" name="signin" class="btn btn-primary">立即签到</button>
            <?php endif; ?>
        </form>
        
        <div class="user-info">
            <p>当前积分: <strong><?php echo getUserPoints($_SESSION['user_id']); ?></strong></p>
            <p>每次分析消耗: <strong>30 积分</strong></p>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="dashboard.php" style="color: #007bff; text-decoration: none;">返回控制台</a>
        </div>
    </div>
</body>
</html>