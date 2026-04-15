<?php

function renderSidebar($current_page = '') {
    $username = $_SESSION['username'] ?? '';
    $user_id = $_SESSION['user_id'] ?? '';
    $is_admin = $_SESSION['is_admin'] ?? false;
    $points = getUserPoints($user_id);

    $config = getConfig();
    $site_name = $config['site_name'] ?? 'AI 代码调试系统';


    $records = getRecords();
    $userRecords = [];
    foreach ($records as $record) {
        if ($record['user_id'] === $user_id) {
            $userRecords[] = $record;
        }
    }
    usort($userRecords, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $recentRecords = array_slice($userRecords, 0, 5);
    ?>

    <div class="sidebar">
        <div class="sidebar-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <img src="/favicon.ico" alt="Logo" style="width: 40px; height: 40px;">
                <h1><?php echo htmlspecialchars($site_name); ?></h1>
            </div>
            <div class="user-info">
                <p>欢迎，<?php echo htmlspecialchars($username); ?></p>
                <div class="points-display">积分: <?php echo $points; ?></div>
            </div>
        </div>

        <div class="sidebar-section">
            <h3>快速操作</h3>
            <ul class="sidebar-nav">
                <li><a href="signin.php" class="<?php echo $current_page === 'signin' ? 'active' : ''; ?>" style="background: white; color: #28a745; border: 2px solid #28a745; text-align: center;">每日签到</a></li>
                <li><a href="dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">代码调试</a></li>
                <li><a href="invite.php" class="<?php echo $current_page === 'invite' ? 'active' : ''; ?>">邀请好友</a></li>
                <li><a href="redeem.php" class="<?php echo $current_page === 'redeem' ? 'active' : ''; ?>">积分兑换</a></li>
                <?php if ($is_admin): ?>
                    <li><a href="admin.php" class="<?php echo $current_page === 'admin' ? 'active' : ''; ?>">管理后台</a></li>
                <?php endif; ?>
                <li><a href="logout.php">退出登录</a></li>
            </ul>
        </div>

        <div class="sidebar-section">
            <h3>最近记录</h3>
            <?php if (empty($recentRecords)): ?>
                <p style="color: #666; font-style: italic; text-align: center;">暂无记录</p>
            <?php else: ?>
                <?php foreach ($recentRecords as $record): ?>
                    <div class="record-item">
                        <div class="record-title"><?php echo htmlspecialchars($record['title']); ?></div>
                        <div class="record-preview"><?php echo htmlspecialchars(substr($record['problem'], 0, 50)); ?>...</div>
                        <a href="records.php?id=<?php echo $record['id']; ?>" style="font-size: 12px; color: var(--primary-color);">查看详情</a>
                    </div>
                <?php endforeach; ?>
                <div class="view-all">
                    <a href="records_list.php">查看全部记录 →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>