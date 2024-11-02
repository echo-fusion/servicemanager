<?php

return [
    'allow_override' => false, // Set to true to allow overriding services.
    'dependencies' => [ // Define your service dependencies here.
        'MyServiceInterface' => 'MyService', // Service mapping.
    ],
];