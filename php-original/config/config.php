<?php
return [
    'app_name' => 'Praise Team',
    'app_url' => getenv('PRAISE_APP_URL') ?: 'http://localhost/Praise_team',
    'app_base_path' => '/Praise_team',
    'db' => [
        'host' => getenv('PRAISE_DB_HOST') ?: '127.0.0.1',
        'name' => getenv('PRAISE_DB_NAME') ?: 'praise_team',
        'user' => getenv('PRAISE_DB_USER') ?: 'root',
        'pass' => getenv('PRAISE_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'roles' => ['chair', 'secretary', 'music_director', 'band_director', 'member'],
    'role_labels' => [
        'chair' => 'Praise Team Chair',
        'secretary' => 'Secretary',
        'music_director' => 'Music Director',
        'band_director' => 'Band/Worship Director',
        'member' => 'Member',
    ],
];
