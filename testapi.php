<?php
require_once 'config.php';

echo "=== API连接测试 ===\n\n";

// 测试配置
$config = getConfig();
echo "API配置检查:\n";
echo "API密钥: " . (empty(getConfigValue($config, 'api_key')) ? "未设置" : "已设置") . "\n";
echo "API地址: " . getConfigValue($config, 'api_base_url') . "\n";
echo "模型: " . getConfigValue($config, 'api_model') . "\n\n";

// 测试API调用
echo "测试API调用...\n";
$test_code = "function hello() { return 'world'; }";
$test_desc = "测试函数";

$start = microtime(true);
$result = callAIAnalysis($test_code, $test_desc);
$end = microtime(true);

$time = round(($end - $start) * 1000, 2);
echo "调用耗时: {$time}ms\n";
echo "结果: " . $result . "\n";

echo "\n=== 测试完成 ===\n";
?>