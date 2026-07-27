<?php
$cloudinaryUrl = env('CLOUDINARY_URL');
$parsedUrl = $cloudinaryUrl ? parse_url($cloudinaryUrl) : [];

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    */

    'cloud_url' => $cloudinaryUrl,

    'cloud_name' => $parsedUrl['host'] ?? env('CLOUDINARY_CLOUD_NAME'),
    'api_key'    => $parsedUrl['user'] ?? env('CLOUDINARY_API_KEY'),
    'api_secret' => $parsedUrl['pass'] ?? env('CLOUDINARY_API_SECRET'),

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),

];