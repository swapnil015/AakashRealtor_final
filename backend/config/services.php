<?php

return [

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // WhatsApp click-to-chat + optional Cloud API for inquiry alerts.
    'whatsapp' => [
        'phone'         => env('WHATSAPP_PHONE'),
        'cloud_token'   => env('WHATSAPP_CLOUD_API_TOKEN'),
        'cloud_phone_id'=> env('WHATSAPP_CLOUD_PHONE_ID'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v20.0'),
    ],

    'cloudinary' => [
        'cloud_name'    => env('CLOUDINARY_CLOUD_NAME'),
        'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    ],

    'google' => [
        'gtm_id'      => env('GOOGLE_TAG_MANAGER_ID'),
        'analytics'   => env('GOOGLE_ANALYTICS_ID'),
        'place_id'    => env('GOOGLE_PLACE_ID'),
    ],

];
