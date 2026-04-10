<?php
// admin.php - 管理员界面（完整版，包含所有配置管理）

require_once 'config.php';

// 引入组件文件
require_once 'components/sidebar.php';
require_once 'components/styles.php';

session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

// 处理各种配置请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // API配置保存
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
    
    // 邮件配置保存
    if (isset($_POST['save_smtp_config'])) {
        $smtp_password = trim($_POST['smtp_password']);
        $smtp_from_email = trim($_POST['smtp_from_email']);
        $smtp_from_name = trim($_POST['smtp_from_name']);
        
        $config = getConfig();
        $config['smtp_password'] = $smtp_password;
        $config['smtp_from_email'] = $smtp_from_email;
        $config['smtp_from_name'] = $smtp_from_name;
        
        if (saveConfig($config)) {
            setMessage("邮件配置保存成功", 'success');
        } else {
            setMessage("邮件配置保存失败", 'error');
        }
    }
    
    // 系统设置保存
    if (isset($_POST['save_system_config'])) {
        $site_name = trim($_POST['site_name']);
        $site_description = trim($_POST['site_description']);
        $analysis_cost = (int)$_POST['analysis_cost'];
        $signin_reward = (int)$_POST['signin_reward'];
        $invite_reward = (int)$_POST['invite_reward'];
        $announcement_enabled = isset($_POST['announcement_enabled']) ? trim($_POST['announcement_enabled']) : '0';
        $announcement_text = trim($_POST['announcement_text']);
        
        $config = getConfig();
        $config['site_name'] = $site_name;
        $config['site_description'] = $site_description;
        $config['analysis_cost'] = $analysis_cost;
        $config['signin_reward'] = $signin_reward;
        $config['invite_reward'] = $invite_reward;
        $config['announcement_enabled'] = $announcement_enabled;
        $config['announcement_text'] = $announcement_text;
        
        if (saveConfig($config)) {
            setMessage("系统设置保存成功", 'success');
        } else {
            setMessage("系统设置保存失败", 'error');
        }
    }
    
    // 测试API连接
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
    
    // 测试SMTP连接
    if (isset($_POST['test_smtp'])) {
        $to_email = trim($_POST['test_email']);
        if (empty($to_email)) {
            setMessage("请输入测试邮箱地址", 'error');
        } else {
            $subject = "SMTP连接测试 - AI代码调试系统";
            $message = "这是一封SMTP连接测试邮件，发送时间: " . date('Y-m-d H:i:s');
            
            if (sendEmail($to_email, $subject, $message)) {
                setMessage("SMTP连接测试成功，邮件已发送到 {$to_email}", 'success');
            } else {
                setMessage("SMTP连接测试失败，请检查配置", 'error');
            }
        }
    }
    
    // 处理生成兑换码请求
    if (isset($_POST['generate_redeem_code'])) {
        $points = (int)$_POST['points'];
        if ($points > 0) {
            $code = generateRedeemCode($points, $_SESSION['username']);
            setMessage("兑换码生成成功: {$code}", 'success');
        } else {
            setMessage("积分数量必须大于0", 'error');
        }
    }
    
    // 批量生成兑换码
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
    
    // 删除兑换码
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

// 获取数据
$config = getConfig();
$users = getUsers();
$records = getRecords();
$redeem_codes = getRedeemCodes();
$active_codes = getActiveRedeemCodes();

// 统计信息
$total_users = count($users);
$total_records = count($records);
$total_points = 0;
foreach ($users as $user) {
    $total_points += $user['points'] ?? 0;
}
$used_codes = count($redeem_codes) - count($active_codes);

// 获取今日活跃用户
$today = date('Y-m-d');
$today_users = 0;
foreach ($users as $user) {
    if (isset($user['last_login']) && substr($user['last_login'], 0, 10) === $today) {
        $today_users++;
    }
}

// 获取当前配置值
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
                <button class="tab-button active" onclick="switchTab('api-tab', event)">API配置</button>
                <button class="tab-button" onclick="switchTab('smtp-tab', event)">Resend配置</button>
                <button class="tab-button" onclick="switchTab('system-tab', event)">系统设置</button>
                <button class="tab-button" onclick="switchTab('redeem-tab', event)">兑换码管理</button>
                <button class="tab-button" onclick="switchTab('users-tab', event)">用户管理</button>
                <button class="tab-button" onclick="switchTab('records-tab', event)">记录管理</button>
            </div>
            
            <!-- API配置 -->
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
            
            <!-- Resend配置 -->
            <div id="smtp-tab" class="tab-content">
                <h3>Resend配置</h3>
                <p>配置Resend邮件服务API。</p>
                
                <form method="POST" class="config-form" style="display: block;">
                    <div class="form-group" style="width: 100%; margin-bottom: 15px;">
                        <label for="smtp_password">Resend API密钥</label>
                        <input type="password" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($smtp_password); ?>" placeholder="re_..." required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
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
                    <h4>💡 Resend配置说明</h4>
                    <ul>
                        <li><strong>API密钥</strong>: 从Resend控制台获取的API密钥，以"re_"开头</li>
                        <li><strong>发件人邮箱</strong>: 已在Resend中验证的邮箱地址</li>
                        <li><strong>发件人名称</strong>: 显示在邮件中的发件人名称</li>
                        <li><strong>域名验证</strong>: 确保域名已在Resend中添加并验证</li>
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
        // 深色模式切换
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
        
        // 检查本地存储的深色模式设置
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            document.querySelector('.theme-toggle').textContent = '☀️ 浅色模式';
        }
       // 选项卡切换
        function switchTab(tabId, event) {
    // 隐藏所有选项卡内容
            var tabs = document.querySelectorAll('.tab-content');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            // 移除所有选项卡按钮的激活状态
            var buttons = document.querySelectorAll('.tab-button');
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove('active');
            }
            
            // 显示选中的选项卡内容
            var selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // 激活选中的选项卡按钮
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
        

        
        // 自动切换到有错误的选项卡
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