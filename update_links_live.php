<?php
$f = __DIR__ . '/data/articles.json';
if (!file_exists($f)) {
    die("File not found on live server.");
}
$d = file_get_contents($f);

// Replace product links
$d = str_replace('https://www.asianindomesin.com/mesin-vacuum-frying-181394', 'https://asianindomachine.com/katalog.html', $d);
$d = str_replace('https://www.asianindomesin.com/product', 'https://asianindomachine.com/katalog.html', $d);

// Replace blog links
$d = preg_replace('/href="https?:\/\/www\.asianindomesin\.com\/blog\/[^"]+"/', 'href="https://asianindomachine.com/blog.html"', $d);
$d = preg_replace('/href=\\\\"https?:\/\/www\.asianindomesin\.com\/blog\/[^"]+\\\\"/', 'href=\"https://asianindomachine.com/blog.html\"', $d);

file_put_contents($f, $d);
echo "SUCCESS: Links in live articles.json have been updated!";
