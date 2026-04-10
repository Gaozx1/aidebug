<?php
/**
 * API监控脚本 - 实时监控API状态
 */

require_once 'config.php';

echo "=== API实时监控 ===\n";
echo "开始时间: " . date('Y-m-d H:i:s') . "\n\n";

$success_count = 0;
$error_count = 0;
$total_time = 0;

for ($i = 1; $i <= 5; $i++) {
    echo "测试 {$i}/5: ";
    
    $test_code = "function test{$i}() { return 'result{$i}'; }";
    $test_desc = "测试函数 {$i}";
    
    $start = microtime(true);
    $result = callAIAnalysis($test_code, $test_desc);
    $end = microtime(true);
    
    $time = round(($end - $start) * 1000, 2);
    $total_time += $time;
    
    if (strpos($result, 'API请求失败') === false) {
        echo "✓ 成功 ({$time}ms)\n";
        $success_count++;
    } else {
        echo "✗ 失败: {$result} ({$time}ms)\n";
        $error_count++;
    }
    
    sleep(2); // 间隔2秒
}

echo "\n=== 监控结果 ===\n";
echo "成功率: " . round(($success_count / 5) * 100, 2) . "%\n";
echo "平均响应时间: " . round($total_time / 5, 2) . "ms\n";
echo "完成时间: " . date('Y-m-d H:i:s') . "\n";
?>