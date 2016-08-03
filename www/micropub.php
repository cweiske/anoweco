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
 * Send out an error
 *
 * @param string $status      HTTP status code line
 * @param string $code        One of the allowed status types:
 *                            - forbidden
 *                            - insufficient_scope
 *                            - invalid_request
 *                            - not_found
 * @param string $description
 */
function error($status, $code, $description)
{
    header($status);
    header('Content-Type: application/json');
    echo json_encode(
        ['error' => $code, 'error_description' => $description]
    ) . "\n";
    exit(1);
}

function handleCreate($json)
{
    if (!isset($json->properties->{'in-reply-to'})) {
        error(
            'HTTP/1.0 400 Bad Request',
            'invalid_request',
            'Only replies accepted'
        );
    }
    //FIXME: read bearer token
    //FIXME: get user ID
    $storage = new Storage();
    try {
        $id = $storage->addComment($json, 0);

        header('HTTP/1.0 201 Created');
        header('Location: ' . Urls::full(Urls::comment($id)));
        exit();
    } catch (\Exception $e) {
        //FIXME: return correct status code
        header('HTTP/1.0 500 Internal Server Error');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET['q'])) {
        error(
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
        error(
            'HTTP/1.1 400 Bad Request',
            'invalid_request',
            'Content-Type header missing.'
        );
    }
    $ctype = $_SERVER['CONTENT_TYPE'];
    if ($ctype == 'application/x-www-form-urlencoded') {
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
        //reserved properties
        foreach (['access_token', 'q', 'url', 'action'] as $key) {
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
        handleCreate($json);
    } else if ($ctype == 'application/javascript') {
        $input = file_get_contents('php://stdin');
        $json  = json_decode($input);
        if ($json === null) {
            error(
                'HTTP/1.1 400 Bad Request',
                'invalid_request',
                'Invalid JSON'
            );
        }
        handleCreate($json);
    } else {
        error(
            'HTTP/1.1 400 Bad Request',
            'invalid_request',
            'Unsupported POST content type'
        );
    }
} else {
    error(
        'HTTP/1.0 400 Bad Request',
        'invalid_request',
        'Unsupported HTTP request method'
    );
}
?>