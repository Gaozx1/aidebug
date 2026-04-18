<?php

function renderSidebar($current_page = '') {
    $username = $_SESSION['username'] ?? '';
    $user_id = $_SESSION['user_id'] ?? '';
    $is_admin = $_SESSION['is_admin'] ?? false;
    $points = getUserPoints($user_id);

    // 获取用户完整数据
    $users = getUsers();
    $user = null;
    foreach ($users as $u) {
        if ($u['username'] === $user_id) {
            $user = $u;
            break;
        }
    }

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
                <a href="profile.php" class="user-profile-link" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
                    <?php if ($user && !empty($user['avatar_url'])): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="头像" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #007bff; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                            <?php echo htmlspecialchars(substr($user['display_name'] ?? $username, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p style="margin: 0; font-weight: bold;"><?php echo htmlspecialchars($user['display_name'] ?? $username); ?></p>
                        <div class="points-display" style="font-size: 12px;">积分: <?php echo $points; ?></div>
                    </div>
                </a>
            </div>
        </div>

        <div class="sidebar-section">
            <h3><?php echo t('quick_actions'); ?></h3>
            <ul class="sidebar-nav">
                <li><a href="signin.php" class="<?php echo $current_page === 'signin' ? 'active' : ''; ?>" style="background: white; color: #28a745; border: 2px solid #28a745; text-align: center;"><?php echo t('daily_sign_in'); ?></a></li>
                <li><a href="dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>"><?php echo t('code_debug'); ?></a></li>
                <li><a href="profile.php" class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>"><?php echo t('profile'); ?></a></li>
                <li><a href="invite.php" class="<?php echo $current_page === 'invite' ? 'active' : ''; ?>"><?php echo t('invite_friends'); ?></a></li>
                <li><a href="redeem.php" class="<?php echo $current_page === 'redeem' ? 'active' : ''; ?>"><?php echo t('redeem_points'); ?></a></li>
                <?php if ($is_admin): ?>
                    <li><a href="admin.php" class="<?php echo $current_page === 'admin' ? 'active' : ''; ?>"><?php echo t('admin_dashboard'); ?></a></li>
                <?php endif; ?>
                <li><a href="logout.php"><?php echo t('logout'); ?></a></li>
            </ul>
        </div>

        <div class="sidebar-section">
            <h3><?php echo t('recent_records'); ?></h3>
            <?php if (empty($recentRecords)): ?>
                <p style="color: #666; font-style: italic; text-align: center;"><?php echo t('no_records'); ?></p>
            <?php else: ?>
                <?php foreach ($recentRecords as $record): ?>
                    <div class="record-item">
                        <div class="record-title"><?php echo htmlspecialchars($record['title']); ?></div>
                        <div class="record-preview"><?php echo htmlspecialchars(substr($record['problem'], 0, 50)); ?>...</div>
                        <a href="records.php?id=<?php echo $record['id']; ?>" style="font-size: 12px; color: var(--primary-color);"><?php echo t('view_details'); ?></a>
                    </div>
                <?php endforeach; ?>
                <div class="view-all">
                    <a href="records_list.php"><?php echo t('view_all_records'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>