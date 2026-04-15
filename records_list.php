<?php

require_once 'config.php';


if (!file_exists('components/sidebar.php')) {
    file_put_contents('components/sidebar.php', $sidebar_content);
}

require_once 'components/sidebar.php';
require_once 'components/styles.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$records = getRecords();
$userRecords = [];

foreach ($records as $record) {
    if ($record['user_id'] === $_SESSION['user_id']) {
        $userRecords[] = $record;
    }
}

usort($userRecords, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$recentRecords = array_slice($userRecords, 0, 5);

$records_per_page = 10;
$total_records = count($userRecords);
$total_pages = ceil($total_records / $records_per_page);

$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;

$offset = ($current_page - 1) * $records_per_page;
$paginated_records = array_slice($userRecords, $offset, $records_per_page);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>所有调试记录 - AI代码调试系统</title>
    <?php renderStyles(); ?>
</head>
<body>
    <?php renderSidebar('records'); ?>

    <div class="main-content">
        <div class="content-header">
            <h2>所有调试记录</h2>
            <button class="theme-toggle" onclick="toggleDarkMode()">🌙 深色模式</button>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="records-stats" style="background: white; padding: 20px; border-radius: 10px; box-shadow: var(--shadow); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>总记录数: <?php echo $total_records; ?></strong>
                    <?php if ($total_records > 0): ?>
                        <span style="margin-left: 15px; color: #666;">
                            第 <?php echo $current_page; ?> 页 / 共 <?php echo $total_pages; ?> 页
                        </span>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-primary">新建调试</a>
                </div>
            </div>
        </div>

        <div class="records-list">
            <?php if (empty($userRecords)): ?>
                <div class="empty-state">
                    <h3>📝 暂无调试记录</h3>
                    <p>您还没有创建任何调试记录，开始您的第一次代码分析吧！</p>
                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 15px;">开始调试</a>
                </div>
            <?php else: ?>
                <?php foreach ($paginated_records as $record): ?>
                    <div class="record-item">
                        <div class="record-header">
                            <h3 class="record-title"><?php echo htmlspecialchars($record['title']); ?></h3>
                            <div class="record-meta">
                                <span class="record-status <?php echo $record['status'] === 'completed' ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $record['status'] === 'completed' ? '已完成' : '处理中'; ?>
                                </span>
                                <span style="margin-left: 10px;"><?php echo $record['created_at']; ?></span>
                            </div>
                        </div>

                        <div class="record-preview">
                            <strong>题目:</strong> <?php echo htmlspecialchars(substr($record['problem'], 0, 150)); ?><?php echo strlen($record['problem']) > 150 ? '...' : ''; ?>
                        </div>

                        <div class="record-preview">
                            <strong>代码预览:</strong>
                            <code style="background: #f1f1f1; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace;">
                                <?php echo htmlspecialchars(substr($record['code'], 0, 100)); ?><?php echo strlen($record['code']) > 100 ? '...' : ''; ?>
                            </code>
                        </div>

                        <?php if (!empty($record['ai_response'])): ?>
                            <div class="record-preview">
                                <strong>AI分析:</strong> <?php echo htmlspecialchars(substr($record['ai_response'], 0, 200)); ?><?php echo strlen($record['ai_response']) > 200 ? '...' : ''; ?>
                            </div>
                        <?php endif; ?>

                        <div class="record-actions">
                            <a href="records.php?id=<?php echo $record['id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 12px;">查看详情</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- 分页导航 -->
                <?php if ($total_pages > 1): ?>
                    <div style="padding: 20px; text-align: center; border-top: 1px solid var(--border-color);">
                        <div style="display: inline-flex; gap: 5px;">
                            <?php if ($current_page > 1): ?>
                                <a href="records_list.php?page=1" class="btn" style="padding: 8px 12px;">首页</a>
                                <a href="records_list.php?page=<?php echo $current_page - 1; ?>" class="btn" style="padding: 8px 12px;">上一页</a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="records_list.php?page=<?php echo $i; ?>"
                                   class="btn <?php echo $i == $current_page ? 'btn-primary' : ''; ?>"
                                   style="padding: 8px 12px;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="records_list.php?page=<?php echo $current_page + 1; ?>" class="btn" style="padding: 8px 12px;">下一页</a>
                                <a href="records_list.php?page=<?php echo $total_pages; ?>" class="btn" style="padding: 8px 12px;">末页</a>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 10px; font-size: 12px; color: #666;">
                            显示 <?php echo $offset + 1; ?>-<?php echo min($offset + $records_per_page, $total_records); ?> 条，共 <?php echo $total_records; ?> 条记录
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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


        function searchRecords() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const records = document.querySelectorAll('.record-item');

            records.forEach(record => {
                const title = record.querySelector('.record-title').textContent.toLowerCase();
                const content = record.querySelector('.record-preview').textContent.toLowerCase();

                if (title.includes(searchTerm) || content.includes(searchTerm)) {
                    record.style.display = 'block';
                } else {
                    record.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>