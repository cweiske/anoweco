<?php
namespace anoweco;
require 'www-header.php';

if (!isset($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: text/plain');
    echo "id parameter missing\n";
    exit(1);
}
if (!is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: text/plain');
    echo "Invalid id parameter value\n";
    exit(1);
}

$id = intval($_GET['id']);

$storage = new Storage();
$rowUser    = $storage->getUser($id);
if ($rowUser === null) {
    header('HTTP/1.0 404 Not Found');
    header('Content-Type: text/plain');
    echo "User not found\n";
    exit(1);
}

render(
    'user',
    array(
        'baseurl' => Urls::full('/'),
        'name'    => $rowUser->user_name,
        'url'     => Urls::full(Urls::user($rowUser->user_id)),
        'imageurl' => Urls::userImg($rowUser),
    )
);
?>
