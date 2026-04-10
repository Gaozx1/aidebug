<?php
/**
 * SMTP功能测试脚本
 * 用于检查PHP环境的SMTP支持情况
 */

echo "=== SMTP功能环境检查 ===\n\n";

// 1. 检查必需扩展
$required_extensions = ['openssl', 'sockets'];
$optional_extensions = ['curl'];

echo "1. PHP扩展检查：\n";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ {$ext} - 已安装\n";
    } else {
        echo "   ❌ {$ext} - 未安装\n";
    }
}

foreach ($optional_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ {$ext} - 已安装（可选）\n";
    } else {
        echo "   ⚠️ {$ext} - 未安装（可选）\n";
    }
}

// 2. 检查函数可用性
echo "\n2. 函数可用性检查：\n";
$required_functions = ['fsockopen', 'stream_socket_client', 'stream_socket_enable_crypto'];
foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "   ✅ {$func}() - 可用\n";
    } else {
        echo "   ❌ {$func}() - 不可用\n";
    }
}

// 3. 检查网络连接
echo "\n3. 网络连接测试：\n";
$test_servers = [
    ['smtp.qq.com', 587, 'QQ邮箱'],
    ['smtp.gmail.com', 587, 'Gmail'],
    ['smtp.163.com', 465, '163邮箱']
];

foreach ($test_servers as $server) {
    list($host, $port, $name) = $server;
    $timeout = 5;
    
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($socket) {
        echo "   ✅ {$name} ({$host}:{$port}) - 可连接\n";
        fclose($socket);
    } else {
        echo "   ❌ {$name} ({$host}:{$port}) - 连接失败: {$errstr}\n";
    }
}

// 4. 检查PHP配置
echo "\n4. PHP配置检查：\n";
$ini_settings = [
    'allow_url_fopen',
    'default_socket_timeout',
    'openssl.cafile',
    'openssl.capath'
];

foreach ($ini_settings as $setting) {
    $value = ini_get($setting);
    echo "   {$setting} = {$value}\n";
}

// 5. 检查mail()函数
echo "\n5. mail()函数测试：\n";
if (function_exists('mail')) {
    echo "   ✅ mail()函数可用\n";
    
    // 测试发送简单邮件
    $test_email = 'test@example.com';
    $test_subject = 'SMTP功能测试';
    $test_message = '这是一封测试邮件，用于验证mail()函数是否正常工作。';
    
    $result = @mail($test_email, $test_subject, $test_message);
    if ($result) {
        echo "   ✅ mail()函数测试发送成功\n";
    } else {
        echo "   ⚠️ mail()函数测试发送失败（可能需配置sendmail）\n";
    }
} else {
    echo "   ❌ mail()函数不可用\n";
}

// 6. 系统信息
echo "\n6. 系统信息：\n";
echo "   PHP版本: " . PHP_VERSION . "\n";
echo "   操作系统: " . PHP_OS . "\n";
echo "   Web服务器: " . ($_SERVER['SERVER_SOFTWARE'] ?? '未知') . "\n";

// 7. 建议
echo "\n7. 安装建议：\n";

if (!extension_loaded('openssl')) {
    echo "   🔧 需要安装openssl扩展：\n";
    echo "      Windows: 取消php.ini中;extension=openssl的注释\n";
    echo "      Linux: sudo apt-get install php-openssl\n";
}

if (!extension_loaded('sockets')) {
    echo "   🔧 需要安装sockets扩展：\n";
    echo "      Windows: 取消php.ini中;extension=sockets的注释\n";
    echo "      Linux: sudo apt-get install php-sockets\n";
}

echo "\n=== 测试完成 ===\n";
?>