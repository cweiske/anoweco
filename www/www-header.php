<?php
require_once __DIR__ . '/../src/anoweco/autoload.php';

\Twig_Autoloader::register();

$loader = new \Twig_Loader_Filesystem(__DIR__ . '/../data/templates/');
$twig = new \Twig_Environment(
    $loader,
    array(
        //'cache' => '/path/to/compilation_cache',
        'debug' => true
    )
);
//$twig->addExtension(new Twig_Extension_Debug());

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
function error($description, $status = 'HTTP/1.0 400 Bad Request')
{
    header($status);
    header('Content-Type: text/plain');
    echo $description . "\n";
    exit(1);
}

function render($tplname, $vars = array(), $return = false)
{
    $template = $GLOBALS['twig']->loadTemplate($tplname . '.htm');

    if ($return) {
        return $template->render($vars);
    } else {
        echo $template->render($vars);
    }
}
?>