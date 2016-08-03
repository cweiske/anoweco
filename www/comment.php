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
$comment = $storage->getComment($id);
if ($comment === null) {
    header('HTTP/1.0 404 Not Found');
    header('Content-Type: text/plain');
    echo "Comment not found\n";
    exit(1);
}

if (isset($comment->properties->content['html'])) {
    $htmlContent = $comment->properties->content['html'];
} else {
    $htmlContent = nl2br($comment->properties->content[0]);
}

$rowComment = $comment->Xrow;
$rowUser    = $comment->user;
render(
    'comment',
    array(
        'json' => $comment->properties,
        'crow' => $rowComment,
        'comment' => $comment,
        'author'  => array(
            'name' => $rowUser->user_name,
            'url'  => Urls::full(Urls::user($rowUser->user_id)),
            'imageurl' => Urls::userImg($rowUser),
        ),
        'htmlContent' => $htmlContent,
        'replyUrl' => Urls::full(
            '/reply.php?url=' . urlencode(Urls::full($rowComment->comment_id))
        ),
    )
);
?>
