<?php
/**
 * User: Pedro Henrique
 * Date: 06/01/2018
 * Time: 01:51 PM
 */


$code = $_GET['code'];

file_put_contents('oauth_code.txt', $code);

?>
<!DOCTYPE html>
<html>
<head>
    <title>OAuth Youtube uploader</title>
    <meta charset="utf-8">
    <link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
    <style type="text/css">
        * {
            font-family: 'Quicksand', Arial, Sans-serif;
        }

        #text_center {
            font-size: 28px;
        }

        #div_center {
            width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
    </style>
</head>
<body>
<center id="text_center">Você já pode fechar essa página.</center><br>
<div id="div_center">
    <small>Código OAuth</small>
    <pre>
        <?php echo $code;?>
    </pre>
</div>
</body>
</html>
