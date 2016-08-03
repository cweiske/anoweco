<?php
header('HTTP/1.0 500 Internal Server Error');

function error($msg)
{
    header('HTTP/1.0 400 Bad Request');
    header('Content-type: text/plain; charset=utf-8');
    echo $msg . "\n";
    exit(1);
}

function verifyParameter($givenParams, $paramName)
{
    if (!isset($givenParams[$paramName])) {
        error('"' . $paramName . '" parameter missing');
    }
    return $givenParams[$paramName];
}
function verifyUrlParameter($givenParams, $paramName)
{
    verifyParameter($givenParams, $paramName);
    $url = parse_url($givenParams[$paramName]);
    if (!isset($url['scheme'])) {
        error('Invalid URL in "' . $paramName . '" parameter: scheme missing');
    }
    if (!isset($url['host'])) {
        error('Invalid URL in "' . $paramName . '" parameter: host missing');
    }

    return $givenParams[$paramName];
}
function getOptionalParameter($givenParams, $paramName, $default)
{
    if (!isset($givenParams[$paramName])) {
        return $default;
    }
    return $givenParams[$paramName];
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    //verify token
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        error('Authorization HTTP header missing');
    }
    list($bearer, $token) = explode(' ', $_SERVER['HTTP_AUTHORIZATION'], 2);
    if ($bearer !== 'Bearer') {
        error('Authorization header must start with "Bearer"');
    }

    //FIXME: use real decryption
    $data = json_decode($token);
    if ($data === null) {
        error('Invalid token');
    }
    $data = (array) $data;
    $me        = verifyUrlParameter($data, 'me');
    $client_id = verifyUrlParameter($data, 'client_id');
    $scope     = verifyParameter($data, 'scope');

    header('HTTP/1.0 200 OK');
    header('Content-type: application/x-www-form-urlencoded');
    echo http_build_query(
        array(
            'me'        => $me,
            'client_id' => $client_id,
            'scope'     => $scope,
        )
    );

} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //generate token
    $me           = verifyUrlParameter($_POST, 'me');
    $redirect_uri = verifyUrlParameter($_POST, 'redirect_uri');
    $client_id    = verifyUrlParameter($_POST, 'client_id');
    $code         = verifyParameter($_POST, 'code');//auth token
    $state        = getOptionalParameter($_POST, 'state', null);
    //FIXME: check if code and state are set
    //FIXME: check auth endpoint if parameters are valid
    //        and to get the scope
    $scope = 'post';

    //FIXME: use real encryption
    $access_token = '<h1>"\'' . json_encode(
        array(
            'me'        => $me,
            'client_id' => $client_id,
            'scope'     => $scope
        )
    );
    header('HTTP/1.0 200 OK');
    header('Content-type: application/x-www-form-urlencoded');
    echo http_build_query(
        array(
            'access_token' => $access_token,
            'me' => $me,
            'scope' => $scope
        )
    );
}
?>
