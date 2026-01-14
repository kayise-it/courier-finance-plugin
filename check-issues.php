<?php
$file = __DIR__ . '/newSQL.sql';
$sql = file_get_contents($file);
$lines = explode("\n", $sql);

$issues = [];
foreach ($lines as $i => $line) {
    if (strpos($line, 'INSERT INTO wp_kit_waybills') !== false && strpos($line, 'city_id') !== false) {
        // Check if SELECT has the pattern with city_id
        if (!preg_match("/SELECT '[^']+',(\d+),(\d+),(\d+),/", $line)) {
            // Check what pattern it actually has
            if (preg_match("/SELECT '[^']+',(\d+),(\d+),/", $line, $m)) {
                $issues[] = [
                    'line' => $i + 1,
                    'pattern' => 'dir=' . $m[1] . ', next=' . $m[2],
                    'preview' => substr($line, 0, 150)
                ];
            }
        }
    }
}

echo count($issues) . " issues found:\n";
foreach ($issues as $issue) {
    echo "Line {$issue['line']}: {$issue['pattern']}\n";
    echo "  " . $issue['preview'] . "...\n\n";
}

