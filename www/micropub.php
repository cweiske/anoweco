<?php
namespace anoweco;
/**
 * Micropub endpoint that stores comments in the database
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
header('HTTP/1.0 500 Internal Server Error');
require 'www-header.php';

/**
 * Send out a micropub error
 *
 * @param string $status      HTTP status code line
 * @param string $code        One of the allowed status types:
 *                            - forbidden
 *                            - insufficient_scope
 *                            - invalid_request
 *                            - not_found
 * @param string $description
 */
function mpError($status, $code, $description)
{
    header($status);
    header('Content-Type: application/json');
    echo json_encode(
        ['error' => $code, 'error_description' => $description]
    ) . "\n";
    exit(1);
}

function validateToken($token)
{
    $ctx = stream_context_create(
        array(
            'http' => array(
                'header' => array(
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json',
                ),
                'ignore_errors' => true,
            ),
        )
    );
    //FIXME: make hard-coded token server URL configurable
    $res = @file_get_contents(Urls::full('/token.php'), false, $ctx);
    list($dummy, $code, $msg) = explode(' ', $http_response_header[0]);
    if ($code != 200) {
        mpError(
            'HTTP/1.0 403 Forbidden',
            'forbidden',
            'Error verifying bearer token: ' . trim($res)
        );
    }

    $data = json_decode($res, true);
    //FIXME: they spit out non-micropub json error responess
    verifyParameter($data, 'me');
    verifyParameter($data, 'client_id');
    verifyParameter($data, 'scope');

    return [$data['me'], $data['client_id'], $data['scope']];
}

function handleCreate($json, $token)
{
    list($me, $client_id, $scope) = validateToken($token);
    $userId = Urls::userId($me);
    if ($userId === null) {
        mpError(
            'HTTP/1.0 403 Forbidden',
            'forbidden',
            'Invalid user URL'
        );
    }
    $storage = new Storage();
    $rowUser = $storage->getUser($userId);
    if ($rowUser === null) {
        mpError(
            'HTTP/1.0 403 Forbidden',
            'forbidden',
            'User not found: ' . $userId
        );
    }

    $storage = new Storage();
    $lb      = new Linkback();
    try {
        $id = $storage->addComment($json, $userId);
        $lb->ping($id);

        header('HTTP/1.0 201 Created');
        header('Location: ' . Urls::full(Urls::comment($id)));
        exit();
    } catch (\Exception $e) {
        if ($e->getCode() == 400) {
            mpError(
                'HTTP/1.0 400 Bad Request',
                'invalid_request',
                $e->getMessage()
            );
        }

        mpError(
            'HTTP/1.0 500 Internal Server Error',
            'this_violates_the_spec',
            $e->getMessage()
        );
        exit();
    }
}

function getTokenFromHeader()
{
    if (isset($_SERVER['HTTP_AUTHORIZATION'])
        && $_SERVER['HTTP_AUTHORIZATION'] != ''
    ) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    } else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])
        && $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] != ''
    ) {
        //php-cgi has it there
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } else {
        mpError(
            'HTTP/1.0 403 Forbidden', 'forbidden',
            'Authorization HTTP header missing'
        );
    }
    if (strpos($auth, ' ') === false) {
        mpError(
            'HTTP/1.0 403 Forbidden', 'forbidden',
            'Authorization header must start with "Bearer "'
        );
    }
    list($bearer, $token) = explode(' ', $auth, 2);
    if ($bearer !== 'Bearer') {
        mpError(
            'HTTP/1.0 403 Forbidden', 'forbidden',
            'Authorization header must start with "Bearer "'
        );
    }
    return trim($token);
}


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET['q'])) {
        mpError(
            'HTTP/1.1 400 Bad Request',
            'invalid_request',
            'Parameter "q" missing.'
        );
    } else if ($_GET['q'] === 'config') {
        header('HTTP/1.0 200 OK');
        header('Content-Type: application/json');
        echo '{}';
        exit();
    } else if ($_GET['q'] === 'syndicate-to') {
        header('HTTP/1.0 200 OK');
        header('Content-Type: application/json');
        echo '{}';
        exit();
    } else {
        //FIXME: maybe implement $q=source
        header('HTTP/1.1 501 Not Implemented');
        header('Content-Type: text/plain');
        echo 'Unsupported "q" value: ' . $_GET['q'] . "\n";
        exit();
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SERVER['CONTENT_TYPE'])) {
        mpError(
            'HTTP/1.1 400 Bad Request',
            'invalid_request',
            'Content-Type header missing.'
        );
    }
    list($ctype) = explode(';', $_SERVER['CONTENT_TYPE'], 2);
    $ctype = trim($ctype);
    if ($ctype == 'application/x-www-form-urlencoded'
        || $ctype == 'multipart/form-data'
    ) {
        if (!isset($_POST['action'])) {
            $_POST['action'] = 'create';
        }
        if ($_POST['action'] != 'create') {
            header('HTTP/1.1 501 Not Implemented');
            header('Content-Type: text/plain');
            echo "Creation of posts supported only\n";
            exit();
        }

        $data = $_POST;
        $base = (object) [
            'type' => ['h-entry'],
        ];
        if (isset($data['h'])) {
            $base->type = ['h-' . $data['h']];
            unset($data['h']);
        }
        if (isset($data['access_token'])) {
            $token = $data['access_token'];
            unset($data['access_token']);
        } else {
            $token = getTokenFromHeader();
        }
        //reserved properties
        foreach (['q', 'url', 'action'] as $key) {
            if (isset($data[$key])) {
                $base->$key = $data[$key];
                unset($data[$key]);
            }
        }
        //"mp-" reserved for future use
        foreach ($data as $key => $value) {
            if (substr($key, 0, 3) == 'mp-') {
                $base->$key = $value;
                unset($data[$key]);
            } else if (!is_array($value)) {
                //convert to array
                $data[$key] = [$value];
            }
        }
        $json = $base;
        $json->properties = (object) $data;
        handleCreate($json, $token);
    } else if ($ctype == 'application/json') {
        $input = file_get_contents('php://input');
        $json  = json_decode($input);
        if ($json === null) {
            mpError(
                'HTTP/1.1 400 Bad Request',
                'invalid_request',
                'Invalid JSON'
            );
        }
        $token = getTokenFromHeader();
        handleCreate($json, $token);
    } else {
        mpError(
            'HTTP/1.1 400 Bad Request',
            'invalid_request',
            'Unsupported POST content type'
        );
    }
} else {
    mpError(
        'HTTP/1.0 400 Bad Request',
        'invalid_request',
        'Unsupported HTTP request method'
    );
}
?>