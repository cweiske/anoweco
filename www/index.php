<?php
namespace anoweco;
require 'www-header.php';

require __DIR__ . '/../data/config.php';
render(
    'index',
    array(
        'title'   => $title,
        'baseurl' => Urls::full('/'),
    )
);
?>