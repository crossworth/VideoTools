<?php
/**
 * User: Pedro Henrique
 * Date: 02/01/2018
 * Time: 03:03 PM
 */

require_once  './functions.php';


function ConvertVideoHandBrake($input, $output, $currentNumber = false, $maxNumber = false) {
    $proc = popen('HandBrakeCLI.exe -i "' . $input . '" -o "' . $output . '" 2>&1', 'r');

    $current = "";

    if ($currentNumber != false) {
        $current = "(" . $currentNumber;

        if ($maxNumber != false) {
            $current = $current . "/" . $maxNumber;
        }

        $current = $current . ") ";
    }

    while (!feof($proc))  {
        $content = fread($proc, 4096);

        // Encoding: task 1 of 1, 1.97 % (41.26 fps, avg 50.03 fps, ETA 00h05m53s)

        $pos = strpos($content, 'Encoding: ');
        if ($pos !== false) {
            $pos += 23; // Remove the "Encoding: task 1 of 1, " part
            echo CleanAndPrintLine($current  . GetLastPartFromPath($output) . " - " . substr($content, $pos));
            @flush();
        }
    }
}


$dirs = ListDirsFromPath(CONVERT_FOLDER);
$dirs = array_diff($dirs, $skipFoldersConvert);

$stats = FileStatsFromDirs($dirs);

echo "\n\n";
echo "Folders to convert: " . count($dirs) . "\n";
echo "Files to convert: " . $stats['number'] . "\n";
echo "Size: " . FormatBytes($stats['file_size_sum']) . "\n";
echo "Converting video(s)\n";

$num = 1;
foreach ($stats['files'] as $file) {
    $outputFile = CONVERTED_OUTPUT_FOLDER . '/' . GetLastPartFromPath($file);
    ConvertVideoHandBrake($file, $outputFile, $num, $stats['number']);

    if (file_exists($outputFile) && filesize($outputFile) > 0) {
        unlink($file);
    }

    $num++;
}

CleanAndPrintLine("All video files were converted");
echo "\n";