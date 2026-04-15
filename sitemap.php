<?php
header('Content-Type: application/xml; charset=utf-8');

// 获取域名
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . '://' . $host;

// 网站页面列表
$pages = [
    [
        'url' => '/index.php',
        'priority' => '1.0',
        'changefreq' => 'weekly'
    ],
    [
        'url' => '/login.php',
        'priority' => '0.8',
        'changefreq' => 'monthly'
    ],
    [
        'url' => '/register.php',
        'priority' => '0.8',
        'changefreq' => 'monthly'
    ],
    [
        'url' => '/redeem.php',
        'priority' => '0.5',
        'changefreq' => 'monthly'
    ]
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $page) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($base_url . $page['url']) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
    echo '    <priority>' . $page['priority'] . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>' . "\n";
?>