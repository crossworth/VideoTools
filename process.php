<?php
/**
 * User: Pedro Henrique
 * Date: 02/01/2018
 * Time: 03:20 PM
 */

if (php_sapi_name() != 'cli') {
    die("You must run this script from the terminal: php process.php\n");
}

echo "VideosTools - PedroHenrique.ninja\n";
echo "==========================================\n";
echo "Move, convert and upload videos to youtube\n";
echo "==========================================\n";

require_once './prepare-folder.php';
require_once './convert.php';
require_once './upload.php';

$credentials = json_decode(file_get_contents('./credentials.json'), true);

if (empty($credentials['client_id']) || empty($credentials['client_secret'])) {
    echo "You must provide the client id and client secret!";
    die();
}

$yt = new YoutubeUploader($credentials['client_id'], $credentials['client_secret']);

$stats = FileStatsFromDirs([CONVERTED_OUTPUT_FOLDER], "*.mp4");

echo "\n\n";
echo "Files to upload: " . $stats['number'] . "\n";
echo "Size: " . FormatBytes($stats['file_size_sum']) . "\n";
echo "Uploading video(s)\n";

$num = 1;
foreach($stats['files'] as $file) {
    $ret = $yt->uploadVideo($file, basename($file), 'Vídeo enviado por script');

    if (!empty($ret)) {
        unlink($file);
    }

    $num++;
}

