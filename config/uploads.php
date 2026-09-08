<?php

return [
    'directory' => __DIR__ . '/../uploads',
    'public_path' => 'uploads',
    'images' => [
        'directory' => __DIR__ . '/../uploads/images',
        'public_path' => 'uploads/images',
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_size' => 8 * 1024 * 1024,
    ],
    'pdfs' => [
        'directory' => __DIR__ . '/../uploads/pdfs',
        'public_path' => 'uploads/pdfs',
        'extensions' => ['pdf'],
        'mimes' => ['application/pdf'],
        'max_size' => 10 * 1024 * 1024,
    ],
    'tickets' => [
        'directory' => __DIR__ . '/../uploads/tickets',
        'public_path' => 'uploads/tickets',
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_size' => 8 * 1024 * 1024,
    ],
];
