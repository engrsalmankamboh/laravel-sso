# Laravel SSO Package Configuration Guide

This guide will help you configure the Laravel SSO package for your application.

## Environment Variables Setup

Add these variables to your `.env` file:

### Google OAuth Configuration
```env
SSO_GOOGLE_CLIENT_ID=your_google_client_id_here
SSO_GOOGLE_CLIENT_SECRET=your_google_client_secret_here
SSO_GOOGLE_REDIRECT=https://yourdomain.com/social/google/callback
```

### Facebook OAuth Configuration
```env
SSO_FACEBOOK_CLIENT_ID=your_facebook_client_id_here
SSO_FACEBOOK_CLIENT_SECRET=your_facebook_client_secret_here
SSO_FACEBOOK_REDIRECT=https://yourdomain.com/social/facebook/callback
SSO_FACEBOOK_API=v18.0
```

### Apple Sign-In Configuration
```env
SSO_APPLE_CLIENT_ID=your_apple_client_id_here
SSO_APPLE_TEAM_ID=your_apple_team_id_here
SSO_APPLE_KEY_ID=your_apple_key_id_here
SSO_APPLE_PRIVATE_KEY=path_to_your_private_key.p8
SSO_APPLE_REDIRECT=https://yourdomain.com/social/apple/callback
```

### GitHub OAuth Configuration
```env
SSO_GITHUB_CLIENT_ID=your_github_client_id_here
SSO_GITHUB_CLIENT_SECRET=your_github_client_secret_here
SSO_GITHUB_REDIRECT=https://yourdomain.com/social/github/callback
```

### LinkedIn OAuth Configuration
```env
SSO_LINKEDIN_CLIENT_ID=your_linkedin_client_id_here
SSO_LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret_here
SSO_LINKEDIN_REDIRECT=https://yourdomain.com/social/linkedin/callback
```

### Twitter OAuth Configuration
```env
SSO_TWITTER_CLIENT_ID=your_twitter_client_id_here
SSO_TWITTER_CLIENT_SECRET=your_twitter_client_secret_here
SSO_TWITTER_REDIRECT=https://yourdomain.com/social/twitter/callback
```

### Discord OAuth Configuration
```env
SSO_DISCORD_CLIENT_ID=your_discord_client_id_here
SSO_DISCORD_CLIENT_SECRET=your_discord_client_secret_here
SSO_DISCORD_REDIRECT=https://yourdomain.com/social/discord/callback
```

### Microsoft OAuth Configuration
```env
SSO_MICROSOFT_CLIENT_ID=your_microsoft_client_id_here
SSO_MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret_here
SSO_MICROSOFT_REDIRECT=https://yourdomain.com/social/microsoft/callback
```

### Mobile Deep Link Configuration
```env
SSO_IOS_DEEPLINK=yourapp
SSO_ANDROID_DEEPLINK=yourapp
```

## Getting OAuth Credentials

### Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API and Google OAuth2 API
4. Go to Credentials → Create Credentials → OAuth 2.0 Client IDs
5. Set Application Type to "Web application"
6. Add authorized redirect URIs:
   - `https://yourdomain.com/social/google/callback`
   - `http://localhost:8000/social/google/callback` (for development)
7. Copy Client ID and Client Secret to your `.env` file

### Facebook OAuth Setup

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app or select existing one
3. Add Facebook Login product to your app
4. Go to Settings → Basic
5. Copy App ID and App Secret to your `.env` file
6. Go to Facebook Login → Settings
7. Add Valid OAuth Redirect URIs:
   - `https://yourdomain.com/social/facebook/callback`
   - `http://localhost:8000/social/facebook/callback` (for development)

### Apple Sign-In Setup

1. Go to [Apple Developer](https://developer.apple.com/)
2. Sign in with your Apple Developer account
3. Go to Certificates, Identifiers & Profiles
4. Create a new App ID with Sign In with Apple capability
5. Create a new private key for Sign In with Apple
6. Download the `.p8` file and note the Key ID
7. Copy Team ID, Key ID, and Client ID to your `.env` file
8. Set the private key path in your `.env` file

### GitHub OAuth Setup

1. Go to [GitHub Settings → Developer settings → OAuth Apps](https://github.com/settings/developers)
2. Click "New OAuth App"
3. Fill in the application details:
   - Application name: Your app name
   - Homepage URL: Your app URL
   - Authorization callback URL: `https://yourdomain.com/social/github/callback`
4. Click "Register application"
5. Copy Client ID and Client Secret to your `.env` file

### LinkedIn OAuth Setup

1. Go to [LinkedIn Developers](https://www.linkedin.com/developers/)
2. Create a new app
3. Go to Auth tab
4. Add OAuth 2.0 redirect URLs:
   - `https://yourdomain.com/social/linkedin/callback`
   - `http://localhost:8000/social/linkedin/callback` (for development)
5. Copy Client ID and Client Secret to your `.env` file

### Twitter OAuth Setup

1. Go to [Twitter Developer Portal](https://developer.twitter.com/)
2. Create a new app
3. Go to App settings → Authentication settings
4. Enable OAuth 2.0
5. Set App permissions to "Read"
6. Add callback URLs:
   - `https://yourdomain.com/social/twitter/callback`
   - `http://localhost:8000/social/twitter/callback` (for development)
7. Copy Client ID and Client Secret to your `.env` file

### Discord OAuth Setup

1. Go to [Discord Developer Portal](https://discord.com/developers/applications)
2. Create a new application
3. Go to OAuth2 tab
4. Add redirect URIs:
   - `https://yourdomain.com/social/discord/callback`
   - `http://localhost:8000/social/discord/callback` (for development)
5. Copy Client ID and Client Secret to your `.env` file

### Microsoft OAuth Setup

1. Go to [Azure Portal](https://portal.azure.com/)
2. Go to Azure Active Directory → App registrations
3. Click "New registration"
4. Fill in the application details
5. Go to Authentication tab
6. Add redirect URIs:
   - `https://yourdomain.com/social/microsoft/callback`
   - `http://localhost:8000/social/microsoft/callback` (for development)
7. Copy Application (client) ID and create a new client secret
8. Copy both to your `.env` file

## Mobile App Deep Link Setup

### iOS Configuration

Add this to your `Info.plist`:

```xml
<key>CFBundleURLTypes</key>
<array>
    <dict>
        <key>CFBundleURLName</key>
        <string>com.yourapp.sso</string>
        <key>CFBundleURLSchemes</key>
        <array>
            <string>yourapp</string>
        </array>
    </dict>
</array>
```

### Android Configuration

Add this to your `AndroidManifest.xml`:

```xml
<activity android:name=".MainActivity">
    <intent-filter>
        <action android:name="android.intent.action.VIEW" />
        <category android:name="android.intent.category.DEFAULT" />
        <category android:name="android.intent.category.BROWSABLE" />
        <data android:scheme="yourapp" />
    </intent-filter>
</activity>
```

## Database Setup

### User Model Migration

Create a migration to add OAuth fields to your users table:

```bash
php artisan make:migration add_oauth_fields_to_users_table
```

Add these fields to the migration:

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('provider')->nullable();
        $table->string('provider_id')->nullable();
        $table->string('avatar')->nullable();
        $table->string('oauth_access_token')->nullable();
        $table->string('oauth_refresh_token')->nullable();
        $table->timestamp('oauth_expires_at')->nullable();
        
        // Add indexes for better performance
        $table->index(['provider', 'provider_id']);
        $table->index('provider');
    });
}
```

### User Model Updates

Update your User model to include the new fields:

```php
class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'avatar',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_expires_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'oauth_expires_at' => 'datetime',
    ];
}
```

## Configuration File

The package configuration file `config/laravel-sso.php` will be published automatically. You can customize it:

```php
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
            'private_key'  => env('SSO_APPLE_PRIVATE_KEY'),
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
            'scopes'        => 'r_liteprofile r_emailaddress',
        ],
        'twitter' => [
            'client_id'     => env('SSO_TWITTER_CLIENT_ID'),
            'client_secret' => env('SSO_TWITTER_CLIENT_SECRET'),
            'redirect'      => env('SSO_TWITTER_REDIRECT'),
            'scopes'        => 'tweet.read users.read offline.access',
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
```

## Security Considerations

### HTTPS Requirements
- Always use HTTPS in production for OAuth redirects
- OAuth providers require secure redirect URIs
- Set `SESSION_SECURE_COOKIE=true` in production

### Redirect URI Validation
- Restrict redirect URIs in your OAuth provider settings
- Only allow your domain's callback URLs
- Remove development URLs from production settings

### Client Secret Security
- Never expose client secrets in frontend code
- Store private keys securely
- Use environment variables for sensitive data

### State Parameter
- The package automatically generates state parameters
- This prevents CSRF attacks on OAuth flows
- State parameters are validated automatically

## Testing Configuration

### Local Development
```env
APP_URL=http://localhost:8000
SSO_GOOGLE_REDIRECT=http://localhost:8000/social/google/callback
SSO_FACEBOOK_REDIRECT=http://localhost:8000/social/facebook/callback
SSO_APPLE_REDIRECT=http://localhost:8000/social/apple/callback
SSO_GITHUB_REDIRECT=http://localhost:8000/social/github/callback
SSO_LINKEDIN_REDIRECT=http://localhost:8000/social/linkedin/callback
SSO_TWITTER_REDIRECT=http://localhost:8000/social/twitter/callback
SSO_DISCORD_REDIRECT=http://localhost:8000/social/discord/callback
SSO_MICROSOFT_REDIRECT=http://localhost:8000/social/microsoft/callback
```

### Production
```env
APP_URL=https://yourdomain.com
SSO_GOOGLE_REDIRECT=https://yourdomain.com/social/google/callback
SSO_FACEBOOK_REDIRECT=https://yourdomain.com/social/facebook/callback
SSO_APPLE_REDIRECT=https://yourdomain.com/social/apple/callback
SSO_GITHUB_REDIRECT=https://yourdomain.com/social/github/callback
SSO_LINKEDIN_REDIRECT=https://yourdomain.com/social/linkedin/callback
SSO_TWITTER_REDIRECT=https://yourdomain.com/social/twitter/callback
SSO_DISCORD_REDIRECT=https://yourdomain.com/social/discord/callback
SSO_MICROSOFT_REDIRECT=https://yourdomain.com/social/microsoft/callback
```

## Troubleshooting

### Common Issues

1. **"Invalid redirect URI" error**
   - Check that redirect URIs match exactly in OAuth provider settings
   - Ensure HTTPS is used in production

2. **"Client not configured" error**
   - Verify all environment variables are set
   - Check that the configuration file is published

3. **Deep links not working**
   - Verify deep link schemes are configured correctly
   - Check mobile app configuration
   - Test with actual mobile devices

4. **Token exchange failures**
   - Verify client secrets are correct
   - Check network connectivity to OAuth providers
   - Ensure redirect URIs match exactly

### Debug Mode

Enable debug mode to see detailed error messages:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Check Laravel logs for detailed error information:

```bash
tail -f storage/logs/laravel.log
```

## Support

If you encounter issues:

1. Check the Laravel logs for error details
2. Verify all configuration values are correct
3. Test with a simple OAuth flow first
4. Check OAuth provider documentation
5. Open an issue on the package repository

## Next Steps

After configuration:

1. Test OAuth flows with each provider
2. Implement user management logic
3. Add error handling and user feedback
4. Test mobile deep link integration
5. Deploy to production with proper security settings
