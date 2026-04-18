<?php

require_once 'config.php';
configureSession();

require_once 'components/sidebar.php';
require_once 'components/styles.php';

session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_ai_prompt_config'])) {
        $config = getConfig();
        $config['ai_prompt_system'] = $_POST['ai_prompt_system'] ?? '';
        $config['ai_prompt_user'] = $_POST['ai_prompt_user'] ?? '';
        saveConfig($config);
        setMessage('AI prompt configuration saved!', 'success');
        header('Location: admin.php');
        exit;
    }


    if (isset($_POST['save_api_config'])) {
        $api_key = trim($_POST['api_key']);
        $api_base_url = trim($_POST['api_base_url']);
        $api_model = trim($_POST['api_model']);

        $config = getConfig();
        $config['api_key'] = $api_key;
        $config['api_base_url'] = $api_base_url;
        $config['api_model'] = $api_model;

        if (saveConfig($config)) {
            setMessage("API配置保存成功", 'success');
        } else {
            setMessage("API配置保存失败", 'error');
        }
    }


    if (isset($_POST['save_smtp_config'])) {
        $email_service = trim($_POST['email_service']);
        $smtp_password = trim($_POST['smtp_password']);
        $smtp_from_email = trim($_POST['smtp_from_email']);
        $smtp_from_name = trim($_POST['smtp_from_name']);
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = trim($_POST['smtp_port']);
        $smtp_username = trim($_POST['smtp_username']);
        $smtp_secure = trim($_POST['smtp_secure']);

        $config = getConfig();
        $config['email_service'] = $email_service;
        $config['smtp_password'] = $smtp_password;
        $config['smtp_from_email'] = $smtp_from_email;
        $config['smtp_from_name'] = $smtp_from_name;
        $config['smtp_host'] = $smtp_host;
        $config['smtp_port'] = $smtp_port;
        $config['smtp_username'] = $smtp_username;
        $config['smtp_secure'] = $smtp_secure;

        if (saveConfig($config)) {
            setMessage("邮件配置保存成功", 'success');
        } else {
            setMessage("邮件配置保存失败", 'error');
        }
    }


    if (isset($_POST['save_system_config'])) {
        $site_name = trim($_POST['site_name']);
        $site_description = trim($_POST['site_description']);
        $analysis_cost = (int)$_POST['analysis_cost'];
        $signin_reward = (int)$_POST['signin_reward'];
        $invite_reward = (int)$_POST['invite_reward'];
        $update_repo = trim($_POST['update_repo']);
        $update_branch = trim($_POST['update_branch']);
        $announcement_enabled = isset($_POST['announcement_enabled']) ? trim($_POST['announcement_enabled']) : '0';
        $announcement_text = trim($_POST['announcement_text']);
        $turnstile_site_key = trim($_POST['turnstile_site_key']);
        $turnstile_secret_key = trim($_POST['turnstile_secret_key']);
        $github_client_id = trim($_POST['github_client_id']);
        $github_client_secret = trim($_POST['github_client_secret']);
        $github_redirect_uri = trim($_POST['github_redirect_uri']);
        $indexnow_enabled = isset($_POST['indexnow_enabled']) ? trim($_POST['indexnow_enabled']) : '0';

        $config = getConfig();
        $config['site_name'] = $site_name;
        $config['site_description'] = $site_description;
        $config['analysis_cost'] = $analysis_cost;
        $config['signin_reward'] = $signin_reward;
        $config['invite_reward'] = $invite_reward;
        $config['update_repo'] = $update_repo;
        $config['update_branch'] = $update_branch;
        $config['announcement_enabled'] = $announcement_enabled;
        $config['announcement_text'] = $announcement_text;
        $config['turnstile_site_key'] = $turnstile_site_key;
        $config['turnstile_secret_key'] = $turnstile_secret_key;
        $config['github_client_id'] = $github_client_id;
        $config['github_client_secret'] = $github_client_secret;
        $config['github_redirect_uri'] = $github_redirect_uri;
        $config['indexnow_enabled'] = $indexnow_enabled;

        if (saveConfig($config)) {
            setMessage("系统设置保存成功", 'success');
        } else {
            setMessage("系统设置保存失败", 'error');
        }
    }


    if (isset($_POST['save_seo_config'])) {
        $seo_keywords = trim($_POST['seo_keywords']);
        $seo_description = trim($_POST['seo_description']);
        $seo_author = trim($_POST['seo_author']);
        $seo_copyright = trim($_POST['seo_copyright']);
        $seo_robots = trim($_POST['seo_robots']);
        $seo_og_title = trim($_POST['seo_og_title']);
        $seo_og_description = trim($_POST['seo_og_description']);
        $seo_og_image = trim($_POST['seo_og_image']);

        $config = getConfig();
        $config['seo_keywords'] = $seo_keywords;
        $config['seo_description'] = $seo_description;
        $config['seo_author'] = $seo_author;
        $config['seo_copyright'] = $seo_copyright;
        $config['seo_robots'] = $seo_robots;
        $config['seo_og_title'] = $seo_og_title;
        $config['seo_og_description'] = $seo_og_description;
        $config['seo_og_image'] = $seo_og_image;

        if (saveConfig($config)) {
            setMessage("SEO配置保存成功", 'success');
        } else {
            setMessage("SEO配置保存失败", 'error');
        }
    }


    if (isset($_POST['test_api'])) {
        $test_code = "function test() { return 'hello'; }";
        $test_desc = "测试API连接";

        $result = callAIAnalysis($test_code, $test_desc);
        if (strpos($result, 'API请求失败') === false) {
            setMessage("API连接测试成功: " . substr($result, 0, 100), 'success');
        } else {
            setMessage("API连接测试失败: " . $result, 'error');
        }
    }


    if (isset($_POST['test_smtp'])) {
        $to_email = trim($_POST['test_email']);
        if (empty($to_email)) {
            setMessage("请输入测试邮箱地址", 'error');
        } else {
            $config = getConfig();
            $email_service = getConfigValue($config, 'email_service', 'resend');
            $service_name = $email_service === 'resend' ? 'Resend API' : 'SMTP服务器';
            
            $subject = "{$service_name}测试 - AI代码调试系统";
            $message = "这是一封{$service_name}测试邮件，发送时间: " . date('Y-m-d H:i:s');

            if (sendEmail($to_email, $subject, $message)) {
                setMessage("{$service_name}测试成功，邮件已发送到 {$to_email}", 'success');
            } else {
                setMessage("{$service_name}测试失败，请检查配置", 'error');
            }
        }
    }


    if (isset($_POST['perform_update'])) {
        $config = getConfig();
        $repo = getConfigValue($config, 'update_repo', 'Gaozx1/aidebug');
        $branch = getConfigValue($config, 'update_branch', 'main');
        $updateResult = autoUpdateFromGithub($repo, $branch);
        if ($updateResult['success']) {
            setMessage('更新成功：' . $updateResult['message'], 'success');
        } else {
            setMessage('更新失败：' . $updateResult['message'], 'error');
        }
    }


    if (isset($_POST['generate_redeem_code'])) {
        $points = (int)$_POST['points'];
        if ($points > 0) {
            $code = generateRedeemCode($points, $_SESSION['username']);
            setMessage("兑换码生成成功: {$code}", 'success');
        } else {
            setMessage("积分数量必须大于0", 'error');
        }
    }


    if (isset($_POST['bulk_generate'])) {
        $points = (int)$_POST['bulk_points'];
        $count = (int)$_POST['bulk_count'];
        if ($points > 0 && $count > 0 && $count <= 50) {
            $codes = [];
            for ($i = 0; $i < $count; $i++) {
                $codes[] = generateRedeemCode($points, $_SESSION['username']);
            }
            setMessage("批量生成成功！生成 {$count} 个 {$points} 积分的兑换码", 'success');
        } else {
            setMessage("参数无效：积分必须大于0，数量在1-50之间", 'error');
        }
    }


    if (isset($_POST['delete_code'])) {
        $code_to_delete = trim($_POST['delete_code']);
        $redeem_codes = getRedeemCodes();
        if (isset($redeem_codes[$code_to_delete])) {
            unset($redeem_codes[$code_to_delete]);
            saveRedeemCodes($redeem_codes);
            setMessage("兑换码 {$code_to_delete} 已删除", 'success');
        } else {
            setMessage("兑换码不存在", 'error');
        }
    }
}

$config = getConfig();
$users = getUsers();
$records = getRecords();
$redeem_codes = getRedeemCodes();
$active_codes = getActiveRedeemCodes();

$total_users = count($users);
$total_records = count($records);
$total_points = 0;
foreach ($users as $user) {
    $total_points += $user['points'] ?? 0;
}
$used_codes = count($redeem_codes) - count($active_codes);

$today = date('Y-m-d');
$today_users = 0;
foreach ($users as $user) {
    if (isset($user['last_login']) && substr($user['last_login'], 0, 10) === $today) {
        $today_users++;
    }
}

$api_key = isset($config['api_key']) ? $config['api_key'] : '';
$api_base_url = isset($config['api_base_url']) ? $config['api_base_url'] : 'https://api.openai.com/v1';
$api_model = isset($config['api_model']) ? $config['api_model'] : 'gpt-3.5-turbo';

$smtp_host = isset($config['smtp_host']) ? $config['smtp_host'] : 'smtp.example.com';
$smtp_port = isset($config['smtp_port']) ? $config['smtp_port'] : '587';
$smtp_username = isset($config['smtp_username']) ? $config['smtp_username'] : '';
$smtp_password = isset($config['smtp_password']) ? $config['smtp_password'] : '';
$smtp_from_email = isset($config['smtp_from_email']) ? $config['smtp_from_email'] : '';
$smtp_from_name = isset($config['smtp_from_name']) ? $config['smtp_from_name'] : 'AI代码调试系统';

$site_name = isset($config['site_name']) ? $config['site_name'] : 'AI代码调试系统';
$site_description = isset($config['site_description']) ? $config['site_description'] : '专业的AI代码调试和分析平台';
$analysis_cost = isset($config['analysis_cost']) ? $config['analysis_cost'] : 30;
$signin_reward = isset($config['signin_reward']) ? $config['signin_reward'] : 50;
$invite_reward = isset($config['invite_reward']) ? $config['invite_reward'] : 100;
$announcement_enabled = isset($config['announcement_enabled']) ? $config['announcement_enabled'] : '0';
$announcement_text = isset($config['announcement_text']) ? $config['announcement_text'] : '';
$announcement_modal_enabled = $announcement_enabled;
$announcement_modal_title = '系统公告';
$announcement_modal_content = $announcement_text;
$update_repo = isset($config['update_repo']) ? $config['update_repo'] : 'Gaozx1/aidebug';
$update_branch = isset($config['update_branch']) ? $config['update_branch'] : 'main';
$turnstile_site_key = getConfigValue($config, 'turnstile_site_key', '');
$turnstile_secret_key = getConfigValue($config, 'turnstile_secret_key', '');
$github_client_id = getConfigValue($config, 'github_client_id', '');
$github_client_secret = getConfigValue($config, 'github_client_secret', '');
$github_redirect_uri = getConfigValue($config, 'github_redirect_uri', '');
$indexnow_enabled = getConfigValue($config, 'indexnow_enabled', '0');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - AI代码调试系统</title>
    <?php renderStyles(); ?>
</head>
<body>
    <?php renderSidebar('admin'); ?>

    <div class="main-content">
        <div class="content-header">
            <h2>管理后台</h2>
            <button class="theme-toggle" onclick="toggleDarkMode()">🌙 深色模式</button>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- 统计信息 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">总用户数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_records; ?></div>
                <div class="stat-label">总记录数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_points; ?></div>
                <div class="stat-label">总积分</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($redeem_codes); ?></div>
                <div class="stat-label">兑换码总数</div>
            </div>
        </div>

        <!-- 选项卡 -->
        <div class="admin-section">
            <div class="tab-buttons">
                <button class="tab-button" onclick="switchTab('ai-prompt-tab', event)">AI 提示词配置</button>
                <button class="tab-button active" onclick="switchTab('api-tab', event)">API配置</button>
                <button class="tab-button" onclick="switchTab('smtp-tab', event)">邮箱配置</button>
                <button class="tab-button" onclick="switchTab('system-tab', event)">系统设置</button>
                <button class="tab-button" onclick="switchTab('seo-tab', event)">SEO配置</button>
                <button class="tab-button" onclick="switchTab('update-tab', event)">系统更新</button>
                <button class="tab-button" onclick="switchTab('redeem-tab', event)">兑换码管理</button>
                <button class="tab-button" onclick="switchTab('users-tab', event)">用户管理</button>
                <button class="tab-button" onclick="switchTab('records-tab', event)">记录管理</button>
            </div>

            <div id="ai-prompt-tab" class="tab-content">
                <h3>AI 提示词配置</h3>
                <p>配置 AI 分析提示词。</p>
                
                <form method="POST" class="config-form">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="ai_prompt_system">系统提示词</label>
                        <textarea id="ai_prompt_system" name="ai_prompt_system" rows="4"><?php echo htmlspecialchars(getConfigValue($config, 'ai_prompt_system') ?: '你是一名专业的代码调试助手。'); ?></textarea>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="ai_prompt_user">用户提示词模板</label>
                        <textarea id="ai_prompt_user" name="ai_prompt_user" rows="4"><?php echo htmlspecialchars(getConfigValue($config, 'ai_prompt_user') ?: '代码描述：{description}\n\n代码：\n{code}'); ?></textarea>
                        <small>使用 {description} 和 {code} 作为占位符</small>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" name="save_ai_prompt_config" class="btn btn-primary">保存 AI 提示词配置</button>
                    </div>
                </form>
            </div>

            <div id="api-tab" class="tab-content active">
                <h3>API配置</h3>
                <p>配置AI API连接参数，确保代码分析功能正常工作。</p>

                <form method="POST" class="config-form">
                    <div class="form-group">
                        <label for="api_key">API密钥</label>
                        <input type="password" id="api_key" name="api_key" value="<?php echo htmlspecialchars($api_key); ?>" placeholder="请输入API密钥" required>
                    </div>

                    <div class="form-group">
                        <label for="api_base_url">API基础URL</label>
                        <input type="url" id="api_base_url" name="api_base_url" value="<?php echo htmlspecialchars($api_base_url); ?>" placeholder="https://api.openai.com/v1" required>
                    </div>

                    <div class="form-group">
                        <label for="api_model">模型名称</label>
                        <input type="text" id="api_model" name="api_model" value="<?php echo htmlspecialchars($api_model); ?>" placeholder="gpt-3.5-turbo" required>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" name="save_api_config" class="btn btn-primary">保存API配置</button>
                        <button type="submit" name="test_api" class="btn btn-success">测试API连接</button>
                    </div>
                </form>

                <div class="test-section">
                    <h4>💡 API配置说明</h4>
                    <ul>
                        <li><strong>API密钥</strong>: 从AI服务提供商获取的密钥</li>
                        <li><strong>API基础URL</strong>: API服务的完整地址，如OpenAI的 https://api.openai.com/v1</li>
                        <li><strong>模型名称</strong>: 选择适合的AI模型，gpt-3.5-turbo性价比最高</li>
                    </ul>
                </div>
            </div>

            <!-- 邮件配置 -->
            <div id="smtp-tab" class="tab-content">
                <h3>邮件配置</h3>
                <p>配置邮件发送服务。</p>

                <form method="POST" class="config-form" style="display: block;">
                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="email_service">邮件发送服务</label>
                        <select id="email_service" name="email_service" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="resend" <?php echo (getConfigValue($config, 'email_service') === 'resend') ? 'selected' : ''; ?>>Resend API</option>
                            <option value="smtp" <?php echo (getConfigValue($config, 'email_service') === 'smtp') ? 'selected' : ''; ?>>SMTP服务器</option>
                        </select>
                    </div>

                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="smtp_password">Resend API密钥 / SMTP密码</label>
                        <input type="password" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($smtp_password); ?>" placeholder="re_... 或 SMTP密码" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="smtp_from_email">发件人邮箱</label>
                        <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($smtp_from_email); ?>" placeholder="noreply@example.com" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="smtp_from_name">发件人名称</label>
                        <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($smtp_from_name); ?>" placeholder="AI代码调试系统" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="smtp_host">SMTP服务器地址</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" placeholder="smtp.example.com" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <small>仅在选择SMTP服务器时需要</small>
                    </div>

                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="smtp_port">SMTP服务器端口</label>
                        <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" placeholder="587" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <small>仅在选择SMTP服务器时需要</small>
                    </div>

                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="smtp_username">SMTP用户名</label>
                        <input type="text" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($smtp_username); ?>" placeholder="SMTP用户名" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <small>仅在选择SMTP服务器时需要</small>
                    </div>

                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="smtp_secure">SMTP加密方式</label>
                        <select id="smtp_secure" name="smtp_secure" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="tls" <?php echo (getConfigValue($config, 'smtp_secure') === 'tls') ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo (getConfigValue($config, 'smtp_secure') === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                            <option value="" <?php echo (getConfigValue($config, 'smtp_secure') === '') ? 'selected' : ''; ?>>无</option>
                        </select>
                        <small>仅在选择SMTP服务器时需要</small>
                    </div>

                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <button type="submit" name="save_smtp_config" class="btn btn-primary" style="width: 100%;">保存邮件配置</button>
                    </div>

                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="test_email">测试邮箱地址</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="email" id="test_email" name="test_email" placeholder="test@example.com" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <button type="submit" name="test_smtp" class="btn btn-success">测试邮件发送</button>
                        </div>
                    </div>
                </form>

                <div class="test-section">
                    <h4>💡 邮件配置说明</h4>
                    <ul>
                        <li><strong>Resend API</strong>: 简单易用，无需配置SMTP服务器，只需API密钥</li>
                        <li><strong>SMTP服务器</strong>: 传统邮件发送方式，需要完整的SMTP配置</li>
                        <li><strong>Resend API密钥</strong>: 从Resend控制台获取的API密钥，以"re_"开头</li>
                        <li><strong>SMTP配置</strong>: 需要服务器地址、端口、用户名、密码等信息</li>
                        <li><strong>发件人邮箱</strong>: 确保该邮箱已在相应服务中验证</li>
                    </ul>
                </div>
            </div>

            <!-- 系统设置 -->
            <div id="system-tab" class="tab-content">
                <h3>系统设置</h3>
                <p>配置系统基本参数和积分规则。</p>

                <form method="POST" class="config-form">
                    <div class="form-group">
                        <label for="site_name">网站名称</label>
                        <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="site_description">网站描述</label>
                        <textarea id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($site_description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="analysis_cost">单次分析消耗积分</label>
                        <input type="number" id="analysis_cost" name="analysis_cost" value="<?php echo $analysis_cost; ?>" min="1" max="1000" required>
                    </div>

                    <div class="form-group">
                        <label for="signin_reward">每日签到奖励积分</label>
                        <input type="number" id="signin_reward" name="signin_reward" value="<?php echo $signin_reward; ?>" min="1" max="1000" required>
                    </div>

                    <div class="form-group">
                        <label for="invite_reward">邀请好友奖励积分</label>
                        <input type="number" id="invite_reward" name="invite_reward" value="<?php echo $invite_reward; ?>" min="1" max="1000" required>
                    </div>

                    <div class="form-group">
                        <label for="update_repo">自动更新仓库</label>
                        <input type="text" id="update_repo" name="update_repo" value="<?php echo htmlspecialchars($update_repo); ?>" placeholder="Gaozx1/aidebug">
                    </div>

                    <div class="form-group">
                        <label for="update_branch">自动更新分支</label>
                        <input type="text" id="update_branch" name="update_branch" value="<?php echo htmlspecialchars($update_branch); ?>" placeholder="main">
                    </div>

                    <div class="form-group">
                        <label for="announcement_enabled">启用系统公告</label>
                        <select id="announcement_enabled" name="announcement_enabled" required>
                            <option value="0" <?php echo $announcement_enabled === '0' ? 'selected' : ''; ?>>关闭</option>
                            <option value="1" <?php echo $announcement_enabled === '1' ? 'selected' : ''; ?>>开启</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="announcement_text">系统公告内容</label>
                        <textarea id="announcement_text" name="announcement_text" rows="3" placeholder="请输入公告内容"><?php echo htmlspecialchars($announcement_text); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="turnstile_site_key">Cloudflare Turnstile 站点密钥</label>
                        <input type="text" id="turnstile_site_key" name="turnstile_site_key" value="<?php echo htmlspecialchars($turnstile_site_key); ?>" placeholder="0x...">
                        <small>从 Cloudflare Turnstile 获取</small>
                    </div>

                    <div class="form-group">
                        <label for="turnstile_secret_key">Cloudflare Turnstile 秘密密钥</label>
                        <input type="password" id="turnstile_secret_key" name="turnstile_secret_key" value="<?php echo htmlspecialchars($turnstile_secret_key); ?>" placeholder="0x...">
                        <small>从 Cloudflare Turnstile 获取，请妥善保管</small>
                    </div>

                    <div class="form-group">
                        <label for="github_client_id">GitHub Client ID</label>
                        <input type="text" id="github_client_id" name="github_client_id" value="<?php echo htmlspecialchars($github_client_id); ?>" placeholder="GitHub OAuth Client ID">
                    </div>
                    <div class="form-group">
                        <label for="github_client_secret">GitHub Client Secret</label>
                        <input type="password" id="github_client_secret" name="github_client_secret" value="<?php echo htmlspecialchars($github_client_secret); ?>" placeholder="GitHub OAuth Client Secret">
                    </div>
                    <div class="form-group">
                        <label for="github_redirect_uri">GitHub 回调地址</label>
                        <input type="text" id="github_redirect_uri" name="github_redirect_uri" value="<?php echo htmlspecialchars($github_redirect_uri); ?>" placeholder="http://yourdomain/oauth_callback.php?provider=github">
                        <small>请在 GitHub OAuth 应用中配置该回调地址</small>
                    </div>

                    <div class="form-group">
                        <label for="announcement_enabled">启用系统公告（公告弹窗）</label>
                        <select id="announcement_enabled" name="announcement_enabled" required>
                            <option value="0" <?php echo $announcement_enabled === '0' ? 'selected' : ''; ?>>关闭</option>
                            <option value="1" <?php echo $announcement_enabled === '1' ? 'selected' : ''; ?>>开启</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="announcement_text">系统公告内容</label>
                        <textarea id="announcement_text" name="announcement_text" rows="3" placeholder="请输入公告内容"><?php echo htmlspecialchars($announcement_text); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="indexnow_enabled">启用 IndexNow 快速索引</label>
                        <select id="indexnow_enabled" name="indexnow_enabled" required>
                            <option value="0" <?php echo $indexnow_enabled === '0' ? 'selected' : ''; ?>>关闭</option>
                            <option value="1" <?php echo $indexnow_enabled === '1' ? 'selected' : ''; ?>>开启</option>
                        </select>
                        <small>启用后，新内容会自动提交给 Bing 和 Yandex 进行快速索引</small>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" name="save_system_config" class="btn btn-primary">保存系统设置</button>
                    </div>
                </form>

                <div class="test-section">
                    <h4>💡 系统设置说明</h4>
                    <ul>
                        <li><strong>网站名称</strong>: 显示在页面标题和侧边栏的名称</li>
                        <li><strong>网站描述</strong>: 系统的简要描述信息</li>
                        <li><strong>单次分析消耗</strong>: 用户每次代码分析需要消耗的积分</li>
                        <li><strong>每日签到奖励</strong>: 用户每日签到获得的积分</li>
                        <li><strong>邀请好友奖励</strong>: 成功邀请好友后获得的积分</li>
                        <li><strong>Cloudflare Turnstile</strong>: 配置验证码密钥，保护表单免受机器人攻击</li>
                        <li><strong>公告弹窗</strong>: 配置公告内容，每次访问显示，可设置24小时内不提醒</li>
                        <li><strong>IndexNow 快速索引</strong>: 启用后，新内容会自动提交给 Bing 和 Yandex 进行快速索引</li>
                    </ul>
                </div>
            </div>

            <!-- SEO配置 -->
            <div id="seo-tab" class="tab-content">
                <h3>SEO配置</h3>
                <p>配置搜索引擎优化参数，提升网站在搜索引擎中的排名。</p>

                <form method="POST" class="config-form">
                    <div class="form-group">
                        <label for="seo_keywords">关键词 <small style="color: #666;">(多个关键词用逗号分隔)</small></label>
                        <input type="text" id="seo_keywords" name="seo_keywords" value="<?php echo htmlspecialchars(getConfigValue($config, 'seo_keywords') ?: 'AI代码调试,代码分析,编程助手,代码优化'); ?>" placeholder="AI代码调试,代码分析,编程助手,代码优化">
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_description">网站描述</label>
                        <textarea id="seo_description" name="seo_description" rows="3" placeholder="专业的AI代码调试系统，提供智能代码分析和优化建议"><?php echo htmlspecialchars(getConfigValue($config, 'seo_description') ?: '专业的AI代码调试系统，提供智能代码分析和优化建议'); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_author">网站作者</label>
                        <input type="text" id="seo_author" name="seo_author" value="<?php echo htmlspecialchars(getConfigValue($config, 'seo_author') ?: 'AI代码调试系统'); ?>" placeholder="AI代码调试系统">
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_copyright">版权信息</label>
                        <input type="text" id="seo_copyright" name="seo_copyright" value="<?php echo htmlspecialchars(getConfigValue($config, 'seo_copyright') ?: 'Copyright © 2024 AI代码调试系统'); ?>" placeholder="Copyright © 2024 AI代码调试系统">
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_robots">搜索引擎抓取规则</label>
                        <select id="seo_robots" name="seo_robots">
                            <option value="index, follow" <?php echo (getConfigValue($config, 'seo_robots') === 'index, follow') ? 'selected' : ''; ?>>允许索引和跟踪链接</option>
                            <option value="noindex, nofollow" <?php echo (getConfigValue($config, 'seo_robots') === 'noindex, nofollow') ? 'selected' : ''; ?>>禁止索引和跟踪链接</option>
                            <option value="index, nofollow" <?php echo (getConfigValue($config, 'seo_robots') === 'index, nofollow') ? 'selected' : ''; ?>>允许索引但禁止跟踪链接</option>
                            <option value="noindex, follow" <?php echo (getConfigValue($config, 'seo_robots') === 'noindex, follow') ? 'selected' : ''; ?>>禁止索引但允许跟踪链接</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <h4>Open Graph 社交媒体分享配置</h4>
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_og_title">分享标题</label>
                        <input type="text" id="seo_og_title" name="seo_og_title" value="<?php echo htmlspecialchars(getConfigValue($config, 'seo_og_title') ?: getConfigValue($config, 'site_name') ?: 'AI代码调试系统'); ?>" placeholder="AI代码调试系统">
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_og_description">分享描述</label>
                        <textarea id="seo_og_description" name="seo_og_description" rows="2" placeholder="专业的AI代码调试系统，提供智能代码分析和优化建议"><?php echo htmlspecialchars(getConfigValue($config, 'seo_og_description') ?: getConfigValue($config, 'site_description') ?: '专业的AI代码调试系统，提供智能代码分析和优化建议'); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_og_image">分享图片URL</label>
                        <input type="text" id="seo_og_image" name="seo_og_image" value="<?php echo htmlspecialchars(getConfigValue($config, 'seo_og_image') ?: ''); ?>" placeholder="https://example.com/og-image.jpg">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" name="save_seo_config" class="btn btn-primary">保存SEO配置</button>
                    </div>
                </form>
                
                <div class="test-section">
                    <h4>💡 SEO配置说明</h4>
                    <ul>
                        <li><strong>关键词</strong>: 搜索引擎优化关键词，用逗号分隔</li>
                        <li><strong>网站描述</strong>: 搜索引擎显示的网站描述</li>
                        <li><strong>网站作者</strong>: 网站作者或所有者信息</li>
                        <li><strong>版权信息</strong>: 网站版权声明</li>
                        <li><strong>搜索引擎抓取规则</strong>: 控制搜索引擎如何索引网站</li>
                        <li><strong>Open Graph配置</strong>: 社交媒体分享时的显示信息</li>
                    </ul>
                </div>
            </div>

            <!-- 系统更新 -->
            <div id="update-tab" class="tab-content">
                <h3>系统更新</h3>
                <p>从 GitHub 仓库 <strong><?php echo htmlspecialchars($update_repo); ?></strong> 的 <strong><?php echo htmlspecialchars($update_branch); ?></strong> 分支拉取最新版本并更新当前系统（保留本地 data 和 config.php）。</p>
                <form method="POST" class="config-form" style="max-width: 600px;">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" name="perform_update" class="btn btn-danger">立即更新系统</button>
                    </div>
                </form>
                <div class="test-section">
                    <h4>💡 更新说明</h4>
                    <ul>
                        <li>自动更新会下载 GitHub 上所配置分支的最新代码。</li>
                        <li>会保留本地 <code>data/</code> 目录和 <code>config.php</code>，避免覆盖本地配置与用户数据。</li>
                        <li>更新后请刷新页面并检查系统是否正常。</li>
                    </ul>
                </div>
            </div>

            <!-- 兑换码管理 -->
            <div id="redeem-tab" class="tab-content">
                <h3>兑换码管理</h3>
                <p>共 <?php echo count($redeem_codes); ?> 个兑换码，其中 <?php echo count($active_codes); ?> 个未使用</p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 20px 0;">
                    <!-- 单个生成 -->
                    <div>
                        <h4>单个生成</h4>
                        <form method="POST" style="display: flex; gap: 10px; align-items: end;">
                            <div style="flex: 1;">
                                <label style="font-size: 12px;">积分数量</label>
                                <input type="number" name="points" value="100" min="1" max="10000" required style="width: 100%;">
                            </div>
                            <button type="submit" name="generate_redeem_code" class="btn btn-primary">生成</button>
                        </form>
                    </div>

                    <!-- 批量生成 -->
                    <div>
                        <h4>批量生成</h4>
                        <form method="POST" style="display: flex; gap: 10px; align-items: end;">
                            <div style="flex: 1;">
                                <label style="font-size: 12px;">积分数量</label>
                                <input type="number" name="bulk_points" value="50" min="1" max="1000" required style="width: 100%;">
                            </div>
                            <div style="flex: 1;">
                                <label style="font-size: 12px;">生成数量</label>
                                <input type="number" name="bulk_count" value="10" min="1" max="50" required style="width: 100%;">
                            </div>
                            <button type="submit" name="bulk_generate" class="btn btn-primary">批量生成</button>
                        </form>
                    </div>
                </div>

                <div class="code-list">
                    <?php if (empty($redeem_codes)): ?>
                        <p style="text-align: center; color: #666; padding: 20px;">暂无兑换码</p>
                    <?php else: ?>
                        <?php foreach ($redeem_codes as $code_data): ?>
                            <div class="code-item">
                                <div class="code-info">
                                    <span class="code-value"><?php echo $code_data['code']; ?></span>
                                    <span style="margin-left: 10px; color: #666;">
                                        <?php echo $code_data['points']; ?> 积分
                                    </span>
                                    <div style="font-size: 12px; color: #999;">
                                        创建: <?php echo $code_data['created_at']; ?> by <?php echo $code_data['created_by']; ?>
                                        <?php if ($code_data['used']): ?>
                                            | 使用: <?php echo $code_data['used_at']; ?> by <?php echo $code_data['used_by']; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 5px; align-items: center;">
                                    <span class="code-status <?php echo $code_data['used'] ? 'status-used' : 'status-active'; ?>">
                                        <?php echo $code_data['used'] ? '已使用' : '未使用'; ?>
                                    </span>
                                    <?php if (!$code_data['used']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="delete_code" value="<?php echo $code_data['code']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;" onclick="return confirm('确定删除兑换码 <?php echo $code_data['code']; ?> 吗？')">删除</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 用户管理 -->
            <div id="users-tab" class="tab-content">
                <h3>用户管理</h3>
                <p>共 <?php echo $total_users; ?> 个用户</p>

                <div class="code-list">
                    <?php foreach ($users as $user): ?>
                        <div class="code-item">
                            <div class="code-info">
                                <strong><?php echo $user['username']; ?></strong>
                                <span style="margin-left: 10px; color: #666;">
                                    <?php echo $user['email']; ?>
                                </span>
                                <div style="font-size: 12px; color: #999;">
                                    积分: <?php echo $user['points'] ?? 0; ?> |
                                    注册: <?php echo $user['created_at']; ?>
                                    <?php if ($user['is_admin']): ?>
                                        <span style="color: var(--danger-color); margin-left: 10px;">管理员</span>
                                    <?php endif; ?>
                                    <?php if (isset($user['last_login'])): ?>
                                        | 最后登录: <?php echo $user['last_login']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 记录管理 -->
            <div id="records-tab" class="tab-content">
                <h3>记录管理</h3>
                <p>共 <?php echo $total_records; ?> 条调试记录</p>

                <div class="code-list">
                    <?php foreach ($records as $record): ?>
                        <div class="code-item">
                            <div class="code-info">
                                <strong><?php echo htmlspecialchars($record['title']); ?></strong>
                                <span style="margin-left: 10px; color: #666;">
                                    用户: <?php echo $record['user_id']; ?>
                                </span>
                                <div style="font-size: 12px; color: #999;">
                                    创建: <?php echo $record['created_at']; ?> |
                                    状态: <?php echo $record['status'] === 'completed' ? '已完成' : '处理中'; ?>
                                </div>
                                <div style="font-size: 12px; margin-top: 5px;">
                                    <?php echo htmlspecialchars($record['problem'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
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

        function switchTab(tabId, event) {

            var tabs = document.querySelectorAll('.tab-content');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }


            var buttons = document.querySelectorAll('.tab-button');
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove('active');
            }


            var selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }


            if (event && event.target) {
                event.target.classList.add('active');
            } else {
                for (var i = 0; i < buttons.length; i++) {
                    var onclickAttr = buttons[i].getAttribute('onclick');
                    if (onclickAttr && onclickAttr.indexOf(tabId) !== -1) {
                        buttons[i].classList.add('active');
                    }
                }
            }
        }




        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.message.error');
            if (errorMessage) {
                const errorText = errorMessage.textContent;
                if (errorText.includes('API')) {
                    switchTab('api-tab');
                } else if (errorText.includes('SMTP')) {
                    switchTab('smtp-tab');
                }
            }
        });
    </script>
</body>
</html>