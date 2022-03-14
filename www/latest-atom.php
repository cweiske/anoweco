<?php
namespace anoweco;
require 'www-header.php';

$storage = new Storage();
$comments = $storage->listLatest();

foreach ($comments as $comment) {
    $comment->url = Urls::comment($comment->comment_id);
    $comment->domain = parse_url($comment->comment_of_url, PHP_URL_HOST);
}

$vars = [
    'baseUrl'     => getBaseUrl(),
    'comments'    => $comments,
    'lastComment' => $comments[0],
];
header('Content-Type: application/atom+xml');
render('latest-atom', $vars);
?>
