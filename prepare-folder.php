<?php
/**
 * User: Pedro Henrique
 * Date: 02/01/2018
 * Time: 03:03 PM
 */

require_once  './functions.php';
require_once  './settings.php';

$dirsToMove = ListDirsFromPath(MOVE_FROM_FOLDER);
$stats = FileStatsFromDirs($dirsToMove);

echo "Folders to move: " . count($dirsToMove) . "\n";
echo "Files to move: " . $stats['number'] . "\n";
echo "Size: " . FormatBytes($stats['file_size_sum']) . "\n";

foreach ($dirsToMove as $dir) {
    CleanAndPrintLine("Moving folder " . $dir);
    MoveDir($dir, MOVE_TO_FOLDER . "/" . GetLastPartFromPath($dir));
}

CleanAndPrintLine("All folders were moved");
echo "\n";