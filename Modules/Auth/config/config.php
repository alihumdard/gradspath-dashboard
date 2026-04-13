<?php

return [
    'guard' => 'web',

    'registration' => [
        'default_role' => 'student',
        'allowed_roles' => [
            'student',
            'mentor',
        ],
    ],
];
