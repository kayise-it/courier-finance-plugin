<?php
require_once('../../../wp-load.php');

$file = __DIR__ . '/newSQL.sql';
$sql = file_get_contents($file);

// Find all INSERT statements for waybills
$pattern = '/(INSERT INTO wp_kit_waybills \([^)]+\) SELECT [^F]+ FROM DUAL[^;]+;)/';
preg_match_all($pattern, $sql, $matches);

$fixed = 0;
foreach ($matches[0] as $insert) {
    // Check if column list has city_id
    if (strpos($insert, '(description,direction_id,city_id') !== false) {
        // Check if SELECT has city_id value (pattern: 'desc',dir_id,city_id,del_id)
        if (!preg_match("/SELECT '[^']+',(\d+),(\d+),(\d+),/", $insert)) {
            // Missing city_id value - insert it after direction_id
            $insert_fixed = preg_replace(
                "/(SELECT '[^']+',)(\d+),(\d+,)/",
                '$1$2,9,$3', // Use 9 as default city_id
                $insert,
                1
            );
            $sql = str_replace($insert, $insert_fixed, $sql);
            $fixed++;
        }
    }
}

file_put_contents($file, $sql);
echo "Fixed $fixed INSERT statements\n";

// Also fix latestSQL.sql
$file2 = __DIR__ . '/latestSQL.sql';
$sql2 = file_get_contents($file2);
$fixed2 = 0;
preg_match_all($pattern, $sql2, $matches2);
foreach ($matches2[0] as $insert) {
    if (strpos($insert, '(description,direction_id,city_id') !== false) {
        if (!preg_match("/SELECT '[^']+',(\d+),(\d+),(\d+),/", $insert)) {
            $insert_fixed = preg_replace(
                "/(SELECT '[^']+',)(\d+),(\d+,)/",
                '$1$2,9,$3',
                $insert,
                1
            );
            $sql2 = str_replace($insert, $insert_fixed, $sql2);
            $fixed2++;
        }
    }
}
file_put_contents($file2, $sql2);
echo "Fixed $fixed2 INSERT statements in latestSQL.sql\n";

