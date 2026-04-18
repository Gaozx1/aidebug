<?php
require_once 'config.php';
configureSession();
session_start();
require_once __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'sidebar.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'styles.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$users = getUsers();
$user = null;
foreach ($users as $u) {
    if ($u['username'] === $_SESSION['user_id']) {
        $user = $u;
        break;
    }
}

if (!$user) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = 'info';

if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $display_name = trim($_POST['display_name']);
        $email = trim($_POST['email']);
        $avatar_url = trim($_POST['avatar_url']);

        if (empty($display_name)) {
            $message = '昵称不能为空';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = '邮箱格式不正确';
            $message_type = 'error';
        } else {
            // 检查邮箱是否已被其他用户使用
            $email_taken = false;
            foreach ($users as $id => $u) {
                if ($u['username'] !== $user['username'] && !empty($u['email']) && strcasecmp($u['email'], $email) === 0) {
                    $email_taken = true;
                    break;
                }
            }

            if ($email_taken) {
                $message = '该邮箱已被其他用户使用';
                $message_type = 'error';
            } elseif (empty($user['email']) || strcasecmp($user['email'], $email) !== 0) {
                // 邮箱有变更，需要验证
                $code = generateVerificationCode();
                $token = generateVerificationToken($email);
                
                if (saveEmailVerification($email, $code, $token)) {
                    if (sendEmailVerification($email, $code)) {
                        $_SESSION['verify_email'] = $email;
                        $_SESSION['verify_token'] = $token;
                        $_SESSION['verify_display_name'] = $display_name;
                        $_SESSION['verify_avatar_url'] = $avatar_url;
                        header('Location: verify.php?type=email');
                        exit;
                    } else {
                        $message = '邮件发送失败，请稍后重试';
                        $message_type = 'error';
                    }
                } else {
                    $message = '验证信息保存失败，请稍后重试';
                    $message_type = 'error';
                }
            } else {
                // 邮箱未变更，直接更新
                foreach ($users as $id => $u) {
                    if ($u['username'] === $user['username']) {
                        $users[$id]['display_name'] = $display_name;
                        $users[$id]['email'] = $email;
                        $users[$id]['avatar_url'] = $avatar_url;
                        saveUsers($users);
                        $_SESSION['username'] = $display_name;
                        $message = '个人资料更新成功';
                        $message_type = 'success';
                        $user = $users[$id];
                        break;
                    }
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!verifyPassword($current_password, $user['password'])) {
            $message = '当前密码错误';
            $message_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = '新密码至少6位';
            $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = '新密码与确认密码不匹配';
            $message_type = 'error';
        } elseif (empty($user['email'])) {
            $message = '请先绑定邮箱';
            $message_type = 'error';
        } else {
            // 密码修改需要邮箱验证
            $code = generateVerificationCode();
            $token = generateVerificationToken($user['email']);
            
            if (saveEmailVerification($user['email'], $code, $token)) {
                if (sendEmailVerification($user['email'], $code)) {
                    $_SESSION['verify_token'] = $token;
                    $_SESSION['verify_new_password'] = $new_password;
                    header('Location: verify.php?type=password');
                    exit;
                } else {
                    $message = '邮件发送失败，请稍后重试';
                    $message_type = 'error';
                }
            } else {
                $message = '验证信息保存失败，请稍后重试';
                $message_type = 'error';
            }
        }
    }
} elseif (isset($_POST['set_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 6) {
        $message = '密码至少6位';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = '密码与确认密码不匹配';
        $message_type = 'error';
    } else {
        foreach ($users as $id => $u) {
            if ($u['username'] === $user['username']) {
                $users[$id]['password'] = hashPassword($new_password);
                saveUsers($users);
                $message = '密码设置成功';
                $message_type = 'success';
                $user = $users[$id];
                break;
            }
        }
    }
} elseif (isset($_POST['unbind_provider'])) {
    $provider_to_unbind = $_POST['unbind_provider'];
    $bound_providers = $user['oauth_bindings'] ?? [];

    if (isset($bound_providers[$provider_to_unbind])) {
        unset($bound_providers[$provider_to_unbind]);
        foreach ($users as $id => $u) {
            if ($u['username'] === $user['username']) {
                $users[$id]['oauth_bindings'] = $bound_providers;
                saveUsers($users);
                $message = ucfirst($provider_to_unbind) . ' 账户解绑成功';
                $message_type = 'success';
                $user = $users[$id];
                break;
            }
        }
    } else {
        $message = '未找到要解绑的账户';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人主页 - <?php echo htmlspecialchars($config['site_name'] ?? 'AI代码调试系统'); ?></title>
    <?php renderStyles(); ?>
    <style>
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .dark-mode .profile-container {
            background: var(--dark-bg);
            color: var(--text-light);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: block;
            object-fit: cover;
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: var(--light-bg);
        }

        .dark-mode .form-section {
            background: var(--dark-bg);
            border-color: var(--border-color);
        }

        .form-section h3 {
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .dark-mode .form-section h3 {
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-dark);
            font-weight: bold;
        }

        .dark-mode .form-group label {
            color: var(--text-light);
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--light-bg);
            color: var(--text-dark);
        }

        .dark-mode .form-group input {
            background: var(--dark-bg);
            color: var(--text-light);
            border-color: var(--border-color);
        }

        .btn {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .message.success {
            background: var(--success-color);
            color: white;
        }

        .message.error {
            background: var(--danger-color);
            color: white;
        }

        .oauth-info {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .dark-mode .oauth-info {
            background: var(--dark-bg);
            border-color: var(--border-color);
        }

        .oauth-btn {
            display: inline-block;
            padding: 10px 15px;
            margin: 5px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .oauth-btn.github {
            background: #24292f;
        }

        .oauth-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php renderSidebar('profile'); ?>

    <div class="main-content">
        <div class="content-header">
            <h2>个人主页</h2>
            <button class="theme-toggle" onclick="toggleDarkMode()">🌙 深色模式</button>
        </div>

        <div class="profile-container">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="profile-header">
                <?php if (!empty($user['avatar_url'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="头像" class="avatar">
                <?php else: ?>
                    <div class="avatar" style="background: #007bff; display: flex; align-items: center; justify-content: center; color: white; font-size: 40px;">
                        <?php echo htmlspecialchars(substr(($user['display_name'] ?? $user['username']), 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?></h3>
                <p>@<?php echo htmlspecialchars($user['username']); ?></p>
            </div>

            <div class="form-section">
                <h3>第三方账户绑定</h3>
                <p>绑定第三方账户可以快速登录，无需记住密码。您可以同时绑定多个账户。</p>

                <?php
                $bound_providers = $user['oauth_bindings'] ?? [];
                $available_providers = ['github'];
                ?>

                <?php if (!empty($bound_providers)): ?>
                    <h4>已绑定账户</h4>
                    <?php foreach ($bound_providers as $provider => $oauth_id): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px;">
                            <span><?php echo htmlspecialchars(ucfirst($provider)); ?> 账户已绑定</span>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="unbind_provider" value="<?php echo htmlspecialchars($provider); ?>">
                                <button type="submit" class="btn" style="background: #dc3545; padding: 5px 10px; font-size: 12px;" onclick="return confirm('确定要解绑 <?php echo htmlspecialchars(ucfirst($provider)); ?> 账户吗？')">解绑</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <h4>可绑定账户</h4>
                <div>
                    <?php foreach ($available_providers as $provider): ?>
                        <?php if (!isset($bound_providers[$provider])): ?>
                            <a class="oauth-btn <?php echo $provider; ?>" href="oauth_start.php?provider=<?php echo $provider; ?>" style="margin-right: 10px;">绑定 <?php echo ucfirst($provider); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="POST" class="form-section">
                <h3>基本信息</h3>
                <div class="form-group">
                    <label for="display_name">昵称</label>
                    <input type="text" id="display_name" name="display_name" value="<?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">邮箱</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="avatar_url">头像 URL</label>
                    <input type="url" id="avatar_url" name="avatar_url" value="<?php echo htmlspecialchars($user['avatar_url'] ?? ''); ?>" placeholder="https://example.com/avatar.jpg">
                </div>
                <button type="submit" name="update_profile" class="btn">更新资料</button>
            </form>

            <?php if (empty($user['password'])): ?>
                <form method="POST" class="form-section">
                    <h3>设置密码</h3>
                    <p>设置密码后，您可以使用用户名+密码方式登录。</p>
                    <div class="form-group">
                        <label for="new_password">新密码</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">确认新密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="set_password" class="btn">设置密码</button>
                </form>
            <?php else: ?>
                <form method="POST" class="form-section">
                    <h3>修改密码</h3>
                    <div class="form-group">
                        <label for="current_password">当前密码</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">新密码</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">确认新密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn">修改密码</button>
                </form>
            <?php endif; ?>

            <div class="form-section">
                <h3>账户信息</h3>
                <p><strong>用户名:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>积分:</strong> <?php echo htmlspecialchars($user['points']); ?></p>
                <p><strong>注册时间:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
                <p><strong>最后登录:</strong> <?php echo htmlspecialchars($user['last_login'] ?? '未知'); ?></p>
            </div>
        </div>
    </div>

    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // 加载深色模式设置
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>