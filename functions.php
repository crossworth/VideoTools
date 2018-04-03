<?php
/**
 * User: Pedro Henrique
 * Date: 02/01/2018
 * Time: 03:03 PM
 */

function ListDirsFromPath($path) {
    return glob($path . '/*', GLOB_ONLYDIR);
}

function MoveDir($source, $target) {
    if (is_dir($source)) {

        if (!file_exists($target)) {
            mkdir($target, 0777);
        }

        $dir = dir($source);

        while (false !== ($entry = $dir->read())) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $reg = $source . '/' . $entry;

            // recursive move
            if (is_dir($reg)) {
                MoveDir($reg, $target . '/' . $entry);
                continue;
            }

            // move the file
            rename($reg, $target . '/' . $entry);
        }

        $dir->close();
    }
}


function GetLastPartFromPath($path) {
    return basename($path);
}

function FileStatsFromDirs($dirs, $type = "*") {
    $stats = [
        'number' => 0,
        'file_size_sum' => 0,
        'files' => []
    ];

    foreach ($dirs as $dir) {
        $files = glob($dir . "/" . $type);
        $stats['number'] += count($files);

        foreach ($files as $file) {
            $stats['file_size_sum'] += filesize($file);
            $stats['files'][] = $file;
        }
    }

    return $stats;
}

function FormatBytes($size, $precision = 2) {
    if ($size == 0) {
        return "0 MB";
    }

    $base = log($size, 1024);
    $suffixes = array('', 'KB', 'MB', 'GB', 'TB');

    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

function CleanAndPrintLine($text) {
    $numColumnsConsole = 100; // default to 100

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec('mode con', $outputCmd);

        foreach ($outputCmd as $line) {
            if (strpos(strtolower($line), 'columns') !== false) {
                $line = str_ireplace("\t", "", $line);
                $line = str_ireplace("Columns:", "", $line);
                $line = str_ireplace(" ", "", $line);
                $numColumnsConsole = intval($line);
            }
        }
    } else {
        $numColumnsConsole = exec('tput cols');
    }

    $outputText = "\r" . str_pad($text, $numColumnsConsole - 1, ' ', STR_PAD_RIGHT);
    echo $outputText;
}

function OpenURLDefaultBrowser($url) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec('start ' . escapeshellcmd($url), $output);
    } else {
        echo "\n\n" . $url . "\n\n";
    }
}