<?php
/**
 * User: Pedro Henrique
 * Date: 02/01/2018
 * Time: 03:09 PM
 */

// Current User Profile Folder
define('USER_PROFILE', $_SERVER['USERPROFILE']);

define('CONVERTED_OUTPUT_FOLDER', 'D:/records/convertidos');
define('CONVERT_FOLDER', 'D:/records');

$skipFoldersConvert = [
    CONVERT_FOLDER . "/convertidos",
    CONVERT_FOLDER . "/tmp",
    CONVERT_FOLDER . "/Desktop",
    CONVERT_FOLDER . "/youtube_upload"
];

// My Videos
// Default folder that NVidia Shadow Play store the videos
define('MOVE_FROM_FOLDER', USER_PROFILE . '\Videos');
define('MOVE_TO_FOLDER', 'D:/records');

