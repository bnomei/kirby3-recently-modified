<?php

use Kirby\Cms\App;

require __DIR__ . '/kirby/bootstrap.php';

return new App([
    'options' => [
        'home' => 'field-notes',
        'bnomei.recently-modified.cache' => ['prefix' => 'test-recently-modified'],
    ],
    'roots' => [
        'index' => __DIR__ . '/public',
        'site' => __DIR__ . '/site',
        'content' => __DIR__ . '/content',
    ],
]);
