<?php declare(strict_types=1);

use thans\jwt\provider\storage\Tp5;

return [
    'secret'      => env('JWT_SECRET','b9793c215f6b59a28c0b23e3ce2cb165'),
    //Asymmetric key
    'public_key'  => env('JWT_PUBLIC_KEY'),
    'private_key' => env('JWT_PRIVATE_KEY'),
    'password'    => env('JWT_PASSWORD'),
    //JWT time to live
    'ttl'         => env('JWT_TTL', 31536000),
    //Refresh time to live
    'refresh_ttl' => env('JWT_REFRESH_TTL', 31536000),
    //JWT hashing algorithm
    'algo'        => env('JWT_ALGO', 'HS256'),
    'blacklist_storage' => Tp5::class,
];
