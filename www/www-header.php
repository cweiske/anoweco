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