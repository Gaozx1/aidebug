"<?php

header('Content-Type: text/plain; charset=utf-8');
echo "开始清理项目代码...\n";

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
$processedCount = 0;

foreach ($files as $file) {
    if ($file->isDir()) continue;

    $filePath = $file->getRealPath();
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);

    if ($extension === 'php') {
        $content = file_get_contents($filePath);


        $content = preg_replace('!/\*.*?\*/!s', '', $content);



        $content = preg_replace('/(?<!:)\/\/[^\n]*/', '', $content);


        $content = preg_replace('/\n{3,}/', "\n\n", $content);


        $content = preg_replace('/[ \t]+$/m', '', $content);

        if (file_put_contents($filePath, $content) !== false) {
            echo "已清理: $filePath\n";
            $processedCount++;
        }
    }
}

echo "\n清理完成！共处理 $processedCount 个文件。\n";
echo "请立即删除 cleaner.php 以确保系统安全。";
