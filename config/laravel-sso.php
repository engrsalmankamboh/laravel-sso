<?php

return [
    'providers' => [
        'google' => [
            'client_id'     => env('SSO_GOOGLE_CLIENT_ID'),
            'client_secret' => env('SSO_GOOGLE_CLIENT_SECRET'),
            'redirect'      => env('SSO_GOOGLE_REDIRECT'),
            'scopes'        => 'openid email profile',
        ],
        'facebook' => [
            'client_id'     => env('SSO_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('SSO_FACEBOOK_CLIENT_SECRET'),
            'redirect'      => env('SSO_FACEBOOK_REDIRECT'),
            'api_version'   => env('SSO_FACEBOOK_API', 'v18.0'),
            'scopes'        => 'email,public_profile',
        ],
        'apple' => [
            'client_id'    => env('SSO_APPLE_CLIENT_ID'),
            'team_id'      => env('SSO_APPLE_TEAM_ID'),
            'key_id'       => env('SSO_APPLE_KEY_ID'),
            'private_key'  => env('SSO_APPLE_PRIVATE_KEY'), // raw PEM or file path
            'redirect'     => env('SSO_APPLE_REDIRECT'),
            'aud'          => 'https://appleid.apple.com',
        ],
        'github' => [
            'client_id'     => env('SSO_GITHUB_CLIENT_ID'),
            'client_secret' => env('SSO_GITHUB_CLIENT_SECRET'),
            'redirect'      => env('SSO_GITHUB_REDIRECT'),
            'scopes'        => 'read:user user:email',
        ],
        'linkedin' => [
            'client_id'     => env('SSO_LINKEDIN_CLIENT_ID'),
            'client_secret' => env('SSO_LINKEDIN_CLIENT_SECRET'),
            'redirect'      => env('SSO_LINKEDIN_REDIRECT'),
            'scopes'        => 'openid,profile,email',
        ],
        'twitter' => [
            'client_id'     => env('SSO_TWITTER_CLIENT_ID'),
            'client_secret' => env('SSO_TWITTER_CLIENT_SECRET'),
            'redirect'      => env('SSO_TWITTER_REDIRECT'),
            'scopes'        => 'tweet.read users.read offline.access'
        ],
        'discord' => [
            'client_id'     => env('SSO_DISCORD_CLIENT_ID'),
            'client_secret' => env('SSO_DISCORD_CLIENT_SECRET'),
            'redirect'      => env('SSO_DISCORD_REDIRECT'),
            'scopes'        => 'identify email',
        ],
        'microsoft' => [
            'client_id'     => env('SSO_MICROSOFT_CLIENT_ID'),
            'client_secret' => env('SSO_MICROSOFT_CLIENT_SECRET'),
            'redirect'      => env('SSO_MICROSOFT_REDIRECT'),
            'scopes'        => 'openid profile email User.Read',
        ],
    ],

    'platforms' => [
        'default' => 'web',
        'web' => [
            'requires_postmessage' => true,
            'deep_link_scheme'     => null,
            'callback_path'        => '/social/{provider}/callback',
        ],
        'ios' => [
            'requires_postmessage' => false,
            'deep_link_scheme'     => env('SSO_IOS_DEEPLINK'),
            'callback_path'        => '/social/{provider}/callback',
        ],
        'android' => [
            'requires_postmessage' => false,
            'deep_link_scheme'     => env('SSO_ANDROID_DEEPLINK'),
            'callback_path'        => '/social/{provider}/callback',
        ],
    ],
];
