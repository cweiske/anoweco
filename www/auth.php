<?php
require_once 'Net/URL2.php';

header('IndieAuth: authorization_endpoint');
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    //var_dump($_GET);die();
    $token = 'keinthema';
    $url = new Net_URL2($_GET['redirect_uri']);
    $url->setQueryVariable('code', $token);
    $url->setQueryVariable('me', $_GET['me']);
    $url->setQueryVariable('state', $_GET['state']);
    header('Location: ' . $url->getURL());
    exit();
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
}
?>
