"<?php

$extensions = ['php', 'js', 'css'];
$dir = new RecursiveDirectoryIterator('.');
$iterator = new RecursiveIteratorIterator($dir);

echo "开始清理代码注释...<br>";

foreach ($iterator as $file) {
    if ($file->isFile() && in_array($file->getExtension(), $extensions)) {
        $filePath = $file->getPathname();


        if (basename($filePath) === 'clean_code.php') continue;

        $content = file_get_contents($filePath);
        $originalContent = $content;


        $content = preg_replace('!/\*.*?\*/!s', '', $content);



        $content = preg_replace('/^\s*\/\/.*$/m', '', $content);
        $content = preg_replace('/(?<!:)\/\/[^\n\r]*$/m', '', $content);


        $content = preg_replace('/<!--.*?-->/s', '', $content);


        $content = preg_replace('/^\s*#.*$/m', '', $content);


        $content = preg_replace("/\n\s*\n\s*\n+/", "\n\n", $content);


        $content = trim($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "已清理: $filePath <br>";
        }
    }
}

echo "<br><b>所有代码清理完成！请立即删除 clean_code.php 以确保安全。</b>";
?>"