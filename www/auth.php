<?php
namespace anoweco;
/**
 * IndieAuth endpoint
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
header('HTTP/1.0 500 Internal Server Error');
require 'www-header.php';

function getOrCreateUser($mode, $name, $imageurl, $email)
{
    if ($mode == 'anonymous') {
        $name     = 'Anonymous';
        $email    = '';
        $imageurl = '';
    } else {
        if ($name == '') {
            $name = 'Anonymous';
        }
    }
    if ($imageurl == '') {
        $imageurl = getImageUrl($email);
    }

    $storage = new Storage();
    $id = $storage->findUser($name, $imageurl);
    if ($id !== null) {
        return $id;
    }
    $id = $storage->createUser($name, $imageurl);
    return $id;
}

function getImageUrl($email)
{
    //FIXME: libravatar
    return Urls::userImg((object)['user_imageurl' => '']);
}

header('IndieAuth: authorization_endpoint');
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (count($_GET) == 0) {
        //no parameters - show the index page
        header('HTTP/1.0 200 OK');
        header('Content-Type: text/html');
        render('auth-index', ['baseurl' => Urls::full('/')]);
        exit();
    }

    $me            = verifyUrlParameter($_GET, 'me');
    $redirect_uri  = verifyUrlParameter($_GET, 'redirect_uri');
    $client_id     = verifyUrlParameter($_GET, 'client_id');
    $state         = getOptionalParameter($_GET, 'state', null);
    $response_type = getOptionalParameter($_GET, 'response_type', 'id');
    $scope         = getOptionalParameter($_GET, 'scope', null);

    $id = array(
        'mode'     => 'anonymous',
        'name'     => '',
        'imageurl' => '',
    );
    $userbaseurl = Urls::full('/user/');
    if (substr($me, 0, strlen($userbaseurl)) == $userbaseurl) {
        //actual user URL - loads his data
        $userid = substr($me, strrpos($me, '/') + 1, -4);
        if (intval($userid) == $userid) {
            $storage = new Storage();
            $rowUser = $storage->getUser($userid);
            if ($rowUser !== null) {
                $id['mode']     = 'data';
                $id['name']     = $rowUser->user_name;
                $id['imageurl'] = $rowUser->user_imageurl;
                if ($id['imageurl'] == Urls::userImg()) {
                    $id['imageurl'] = '';
                }
            }
        }
    }

    //let the user choose his identity
    header('HTTP/1.0 200 OK');
    render(
        'auth-choose',
        array(
            'auth' => array(
                'redirect_uri'  => $redirect_uri,
                'client_id'     => $client_id,
                'state'         => $state,
                'response_type' => $response_type,
                'scope'         => $scope,
            ),
            'id' => $id,
            'formaction' => '/auth.php?action=login',
        )
    );
    exit();
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_GET['action']) && $_GET['action'] == 'login') {
        //log the user in
        $auth = $_POST['auth'];
        $redirect_uri  = verifyUrlParameter($auth, 'redirect_uri');
        $client_id     = verifyUrlParameter($auth, 'client_id');
        $state         = getOptionalParameter($auth, 'state', null);
        $response_type = getOptionalParameter($auth, 'response_type', 'id');
        $scope         = getOptionalParameter($auth, 'scope', null);

        $id = $_POST['id'];
        verifyParameter($id, 'mode');

        $userId = getOrCreateUser(
            $id['mode'], trim($id['name']), trim($id['imageurl']),
            trim($id['email'])
        );
        $me = Urls::full(Urls::user($userId));

        $code = base64_encode(
            http_build_query(
                [
                    'emoji'     => '\360\237\222\251',
                    'me'        => $me,
                    'scope'     => $scope,
                    'signature' => 'FIXME',
                ]
            )
        );

        //redirect back to client
        $url = new \Net_URL2($redirect_uri);
        $url->setQueryVariable('code', $code);
        $url->setQueryVariable('me', $me);
        $url->setQueryVariable('state', $state);
        header('Location: ' . $url->getURL());
        exit();
    } else {
        //auth code verification
        $code         = base64_decode(verifyParameter($_POST, 'code'));
        $redirect_uri = verifyUrlParameter($_POST, 'redirect_uri');
        $client_id    = verifyUrlParameter($_POST, 'client_id');
        $state        = getOptionalParameter($_POST, 'state', null);

        parse_str($code, $codeParts);
        $emoji     = verifyParameter($codeParts, 'emoji');
        $signature = verifyParameter($codeParts, 'signature');
        $me        = verifyUrlParameter($codeParts, 'me');
        if ($emoji != '\360\237\222\251') {
            error('Dog poo missing');
        }
        if ($signature != 'FIXME') {
            error('Invalid signature');
        }
        header('HTTP/1.0 200 OK');
        header('Content-type: application/x-www-form-urlencoded');
        echo http_build_query(['me' => $me]);
        exit();
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
    //indieauth.com makes a HEAD request at first
    header('HTTP/1.0 200 OK');
    exit();
} else {
    error('Unsupported HTTP request method');
}
?>
