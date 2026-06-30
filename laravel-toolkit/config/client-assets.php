<?php

return [
    // Disk from config/filesystems.php — each client site uses its own bucket via AWS_* env.
    'disk' => env('CLIENT_ASSETS_DISK', 's3'),
];
