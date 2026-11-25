<?php

use Kirby\Cms\Page;
use Kirby\Cms\User;

$kirby = require __DIR__ . '/app.php';
$kirby->impersonate('kirby');

foreach (['Robin', 'Sam'] as $name) {
    $email = strtolower($name) . '@example.test';
    if (!$kirby->users()->findBy('email', $email)) {
        User::create([
            'email' => $email,
            'name' => $name,
            'role' => 'admin',
            'password' => trim(file_get_contents(__DIR__ . '/.password')),
        ]);
    }
}

if (!$kirby->site()->content()->has('title')) {
    $kirby->site()->update(['title' => 'Magazine']);
}
foreach (['field-notes' => 'Field Notes', 'weekend-guide' => 'Weekend Guide'] as $slug => $title) {
    if (!$kirby->page($slug)) {
        Page::create(['slug' => $slug, 'template' => 'default', 'content' => ['title' => $title]])->changeStatus('listed');
    }
}
