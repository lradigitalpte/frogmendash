<?php

return [
    'website' => [
        // Controls the public customer panel mounted at `/` and the Website plugin.
        'enabled' => (bool) env('WEBSITE_ENABLED', false),
    ],

    'blog' => [
        // Defaults to following the website flag unless explicitly overridden.
        'enabled' => (bool) env('BLOG_ENABLED', env('WEBSITE_ENABLED', false)),
    ],
];
