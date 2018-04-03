<?php
/**
 * User: Pedro Henrique
 * Date: 02/01/2018
 * Time: 03:03 PM
 */

require_once  './settings.php';
require_once  './functions.php';
require_once  './vendor/autoload.php';

ini_set('memory_limit','10G');


if (!function_exists('bcsub')) {
    function bcsub( $Num1 = '0', $Num2 = '0', $Scale = null ) {
        return $Num1 - $Num2;
    }
}


use bandwidthThrottle\BandwidthThrottle;

class YoutubeUploader {
    private $client = null;
    private $youtube = null;
    private $token = null;

    const TOKEN_FILE = 'oauth_token.json';
    const LOCAL_SERVER_OAUTH = 'localhost:8687';
    const LOCAL_SERVER_SCRIPT_OAUTH = 'oauth_code.php';
    const OAUTH_CODE_FILE = 'oauth_code.txt';

    public function __construct($oauthClientID, $oauthClientSecret, $code = false) {
        $http = new GuzzleHttp\Client(['verify' => getcwd() . '/cacert.pem']);

        $this->client = new Google_Client();
        $this->client->setHttpClient($http);
        $this->client->setClientId($oauthClientID);
        $this->client->setClientSecret($oauthClientSecret);
        $this->client->setRedirectUri("http://" . YoutubeUploader::LOCAL_SERVER_OAUTH . "/" . YoutubeUploader::LOCAL_SERVER_SCRIPT_OAUTH);
        $this->client->setScopes('https://www.googleapis.com/auth/youtube');
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');

        $this->youtube = new Google_Service_YouTube($this->client);

        CleanAndPrintLine("Status: Initializing");

        CleanAndPrintLine("Status: Checking for token");
        if (file_exists("./" . YoutubeUploader::TOKEN_FILE)) {
            $this->readTokenFile();
            $this->setToken();
        } elseif ($code != false) {
            $this->authenticate($code);
        } else {
            $this->getOAuthCode();
        }
    }

    private function getOAuthCode() {
        CleanAndPrintLine("Status: Getting OAuth code");

        if (!empty($this->token)) {
            $this->client->fetchAccessTokenWithRefreshToken($this->token['refresh_token']);
            $this->saveTokenFile();
        } else {
            OpenURLDefaultBrowser($this->getAuthURL());
            $this->startListenerServer();
        }
    }

    public function startListenerServer() {
        @unlink('./' . YoutubeUploader::OAUTH_CODE_FILE);
        CleanAndPrintLine("Status: Starting server");

        $descriptorSpec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $proc = proc_open(PHP_BINARY . ' -S ' . YoutubeUploader::LOCAL_SERVER_OAUTH, $descriptorSpec, $pipes, getcwd());

        $stopServer = false;
        while ($stopServer == false)  {
            CleanAndPrintLine("Status: Waiting for oauth code file");

            if (file_exists('./' . YoutubeUploader::OAUTH_CODE_FILE)) {
                $stopServer = true;
                proc_terminate($proc, 0);
                CleanAndPrintLine("Status: Authenticating");
                $this->authenticate(file_get_contents('./' . YoutubeUploader::OAUTH_CODE_FILE));
            }

            sleep(5);
        }
    }

    public function readTokenFile() {
        $this->token = json_decode(file_get_contents(YoutubeUploader::TOKEN_FILE), true);
    }

    public function saveTokenFile() {
        $this->token = $this->client->getAccessToken();
        file_put_contents(YoutubeUploader::TOKEN_FILE, json_encode($this->token));
    }

    public function setToken() {
        $this->client->setAccessToken($this->token['access_token']);
        $this->client->fetchAccessTokenWithRefreshToken($this->token['refresh_token']);
    }

    public function getAuthURL() {
        return $this->client->createAuthUrl();
    }

    public function authenticate($code) {
        $this->client->fetchAccessTokenWithAuthCode($code);
        $this->saveTokenFile();
        CleanAndPrintLine("Status: Saving token file");
        unlink('./' . YoutubeUploader::OAUTH_CODE_FILE);
    }

    public function uploadVideo($videoPath, $title, $description, $tags = [], $category = 20, $privacyStatus = 'public') {
        try {
            CleanAndPrintLine("Status: Starting upload " . $videoPath);

            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            $snippet->setTags($tags);
            $snippet->setCategoryId($category);

            $status = new Google_Service_YouTube_VideoStatus();
            $status->setPrivacyStatus($privacyStatus);

            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            $chunkSizeBytes = 1 * 1024 * 1024;

            $this->client->setDefer(true);

            $insertRequest = $this->youtube->videos->insert('snippet,status', $video);

            $media = new Google_Http_MediaFileUpload($this->client,
                $insertRequest, 'video/*', null,
                true, $chunkSizeBytes);

            $totalSize = filesize($videoPath);

            $media->setFileSize($totalSize);

            $status = false;
            $handle = fopen($videoPath, "rb");
            $totalUploaded = 0;
            CleanAndPrintLine("Status: Uploading " . $videoPath);

            if (file_exists($videoPath . '_resume.txt')) {
                $resumeINFO = json_decode(file_get_contents($videoPath . '_resume.txt'), true);
                $media->resume($resumeINFO['uri']);
                fseek($handle, $resumeINFO['offset']);
                $totalUploaded = $resumeINFO['total_upload'];
            } else {
                $resumeINFO = [];
                $resumeINFO['offset'] = 0;
                $resumeINFO['uri'] = $media->getResumeUri();
                $resumeINFO['total_upload'] = 0;
            }

            while (!$status && !feof($handle)) {

                $limiting = '';

                // UPLOAD LIMIT
                if (file_exists('./upload_speed_limit_mb.txt')) {
                    $limit = file_get_contents('./upload_speed_limit_mb.txt');

                    $limit = intval($limit);

                    if (!empty($limit) && is_int($limit) && $limit > 0) {
                        $throttle = new BandwidthThrottle();
                        $throttle->setRate($limit, BandwidthThrottle::MEBIBYTES); // Set limit to 100KiB/s
                        $throttle->throttle($handle);

                        $limiting = 'Limiting ' . $limit . ' Mb/s - ';
                    }
                }

                // SEND CHUNK
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
                $totalUploaded += $chunkSizeBytes;

                $percentage =  ($totalUploaded * 100) / $totalSize;
                $percentage = number_format($percentage, 2);

                if (floatval($percentage) >= 100) {
                    $percentage = '100.00';
                }

                // RESUME UPLOAD
                $resumeINFO['offset'] = ftell($handle);;
                $resumeINFO['uri'] = $media->getResumeUri();
                $resumeINFO['total_upload'] = $totalUploaded;
                file_put_contents($videoPath . '_resume.txt', json_encode($resumeINFO));

                CleanAndPrintLine("Status: Uploading " . $limiting . $videoPath . ": " . $percentage . "% - " . FormatBytes($totalUploaded) . "/" . FormatBytes($totalSize));
            }

            fclose($handle);
            @unlink($videoPath . '_resume.txt');

            $videoId = $status['id'];

            CleanAndPrintLine("Status: Uploaded (" . $videoId . ") " . FormatBytes($totalUploaded));
            echo "\n";

            $this->client->setDefer(false);
            return $videoId;
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            $msg = json_decode($msg);

            if (is_object($msg)) {
                if ($msg->error->code == 401) {
                    $this->getOAuthCode();
                    $this->uploadVideo($videoPath, $title, $description, $tags, $category, $privacyStatus);
                }
            } else {
                echo $ex->getMessage();
            }
        }
    }
}
