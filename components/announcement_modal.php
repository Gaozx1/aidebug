<?php
function renderAnnouncementModal() {
    $config = getConfig();
    $announcement_enabled = isset($config['announcement_enabled']) && $config['announcement_enabled'] === '1';

    if (!$announcement_enabled) {
        return;
    }

    $announcement_text = $config['announcement_text'] ?? '';

    if (empty($announcement_text)) {
        return;
    }

    // 检查是否在24小时内已经关闭过
    $announcement_closed_key = 'announcement_closed_' . md5($announcement_text);
    $announcement_closed = isset($_COOKIE[$announcement_closed_key]) && $_COOKIE[$announcement_closed_key] === '1';

    if ($announcement_closed) {
        return;
    }
?>
<style>
    .announcement-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        font-family: 'Arial', sans-serif;
    }

    .announcement-modal-content {
        background: white;
        border-radius: 10px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        position: relative;
        text-align: center;
    }

    .dark-mode .announcement-modal-content {
        background: #2d3748;
        color: #e2e8f0;
    }

    .announcement-modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }

    .announcement-modal-close:hover {
        background: #f0f0f0;
        color: #333;
    }

    .dark-mode .announcement-modal-close:hover {
        background: #4a5568;
        color: #e2e8f0;
    }

    .announcement-modal h2 {
        margin-bottom: 15px;
        color: #007bff;
        font-size: 24px;
    }

    .announcement-modal p {
        margin-bottom: 20px;
        line-height: 1.6;
        color: #666;
    }

    .dark-mode .announcement-modal p {
        color: #a0aec0;
    }

    .announcement-modal .btn {
        display: inline-block;
        padding: 10px 20px;
        background: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin: 5px;
        transition: background 0.3s;
        border: none;
        cursor: pointer;
    }

    .announcement-modal .btn:hover {
        background: #0056b3;
    }

    .announcement-modal .btn-secondary {
        background: #6c757d;
    }

    .announcement-modal .btn-secondary:hover {
        background: #545b62;
    }

    .announcement-modal-buttons {
        margin-top: 20px;
    }
</style>

<div class="announcement-modal" id="announcementModal">
    <div class="announcement-modal-content">
        <button class="announcement-modal-close" onclick="closeAnnouncementModal()">&times;</button>
        <h2>系统公告</h2>
        <p><?php echo nl2br(htmlspecialchars($announcement_text)); ?></p>
        <div class="announcement-modal-buttons">
            <button class="btn btn-secondary" onclick="closeAnnouncementModal(true)">24小时内不再显示</button>
        </div>
    </div>
</div>

<script>
function closeAnnouncementModal(dontShowAgain = false) {
    const modal = document.getElementById('announcementModal');
    modal.style.display = 'none';

    if (dontShowAgain) {
        // 设置24小时后过期的cookie
        const expires = new Date();
        expires.setTime(expires.getTime() + (24 * 60 * 60 * 1000)); // 24小时
        document.cookie = '<?php echo $announcement_closed_key; ?>=1; expires=' + expires.toUTCString() + '; path=/';
    }
}

// 页面加载后显示弹窗
document.addEventListener('DOMContentLoaded', function() {
    // 延迟1秒显示，避免页面加载时的干扰
    setTimeout(function() {
        const modal = document.getElementById('announcementModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }, 1000);
});
</script>
<?php
}
?>