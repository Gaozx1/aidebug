<?php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 加载配置
$config = json_decode(file_get_contents(CONFIG_FILE), true);

if (!$config) {
    die("无法加载配置文件\n");
}

echo "=== Resend API 测试 ===\n\n";

// 使用Resend API发送邮件
$to = 'test@example.com'; // 请替换为你的测试邮箱
$subject = 'Resend API 测试邮件';
$message = '<p>这是一封通过Resend API发送的测试邮件。如果您收到此邮件，说明API配置成功！</p>';

$api_key = $config['smtp_password'];
$from_email = $config['smtp_from_email'];

$result = sendEmailWithResendAPI($to, $subject, $message, $api_key, $from_email);

if ($result) {
    echo "✅ 邮件发送成功！\n";
    echo "请检查收件邮箱是否收到测试邮件。\n";
} else {
    echo "❌ 邮件发送失败！\n";
    echo "请检查API密钥和域名配置。\n";
}

echo "\n=== 测试完成 ===\n";
?>