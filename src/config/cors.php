<?php

return [
    'paths' => ['api/*'],   // APIルート全体に適用

    'allowed_methods' => ['*'],

    // フロントエンドのURLを許可する
    'allowed_origins' => [
        'http://localhost:5173',  // Vite（Vue.js/React）の開発サーバー
        'http://localhost:3000',  // Create React App の場合
    ],

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
