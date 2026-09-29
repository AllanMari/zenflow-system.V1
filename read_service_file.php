<?php
$filePath = 'c:\\Users\\asus\\Documents\\1 Spa appointment\\spa\\app\\Services\\SalesAnalyticsService.php';
$lines = file($filePath, FILE_IGNORE_NEW_LINES);
$startLine = 1801;
$endLine = 2764;

for ($i = $startLine - 1; $i < min($endLine, count($lines)); $i++) {
    echo $lines[$i] . PHP_EOL;
}
?>
