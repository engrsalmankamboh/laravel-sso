# Laravel SSO - Social Single Sign-On Package

A comprehensive Laravel package for implementing Social Single Sign-On (SSO) with support for multiple platforms (Web, iOS, Android) and providers (Google, Facebook, Apple).

## Features

- 🔐 **Multi-Provider Support**: Google, Facebook, Apple, GitHub, LinkedIn, Twitter, Discord, Microsoft
- 📱 **Platform-Aware**: Web, iOS, and Android support
- 🔗 **Deep Link Handling**: Native mobile app integration
- 🛡️ **Security**: State parameter, proper OAuth flows
- 🚀 **Easy Integration**: Simple API with Laravel integration
- 📦 **No Dependencies**: Minimal external dependencies

## Installation

### 1. Install via Composer

```bash
composer require muhammadsalman/laravel-sso
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=laravel-sso-config
```

### 3. Configure Environment Variables

Add these to your `.env` file:

```env
# Google OAuth
SSO_GOOGLE_CLIENT_ID=your_google_client_id
SSO_GOOGLE_CLIENT_SECRET=your_google_client_secret
SSO_GOOGLE_REDIRECT=https://yourdomain.com/social/google/callback

# Facebook OAuth
SSO_FACEBOOK_CLIENT_ID=your_facebook_client_id
SSO_FACEBOOK_CLIENT_SECRET=your_facebook_client_secret
SSO_FACEBOOK_REDIRECT=https://yourdomain.com/social/facebook/callback
SSO_FACEBOOK_API=v18.0

# Apple Sign-In
SSO_APPLE_CLIENT_ID=your_apple_client_id
SSO_APPLE_TEAM_ID=your_apple_team_id
SSO_APPLE_KEY_ID=your_apple_key_id
SSO_APPLE_PRIVATE_KEY=path_to_private_key.p8
SSO_APPLE_REDIRECT=https://yourdomain.com/social/apple/callback

# GitHub OAuth
SSO_GITHUB_CLIENT_ID=your_github_client_id
SSO_GITHUB_CLIENT_SECRET=your_github_client_secret
SSO_GITHUB_REDIRECT=https://yourdomain.com/social/github/callback

# LinkedIn OAuth
SSO_LINKEDIN_CLIENT_ID=your_linkedin_client_id
SSO_LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret
SSO_LINKEDIN_REDIRECT=https://yourdomain.com/social/linkedin/callback

# Twitter OAuth
SSO_TWITTER_CLIENT_ID=your_twitter_client_id
SSO_TWITTER_CLIENT_SECRET=your_twitter_client_secret
SSO_TWITTER_REDIRECT=https://yourdomain.com/social/twitter/callback

# Discord OAuth
SSO_DISCORD_CLIENT_ID=your_discord_client_id
SSO_DISCORD_CLIENT_SECRET=your_discord_client_secret
SSO_DISCORD_REDIRECT=https://yourdomain.com/social/discord/callback

# Microsoft OAuth
SSO_MICROSOFT_CLIENT_ID=your_microsoft_client_id
SSO_MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret
SSO_MICROSOFT_REDIRECT=https://yourdomain.com/social/microsoft/callback

# Mobile Deep Links (Optional)
SSO_IOS_DEEPLINK=yourapp
SSO_ANDROID_DEEPLINK=yourapp
```

## Configuration

The package configuration file `config/laravel-sso.php` contains:

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

## Basic Usage

### 1. Generate OAuth Redirect URLs

```php
use Muhammadsalman\LaravelSso\Core\SSOManager;

class AuthController extends Controller
{
    public function __construct(
        private SSOManager $ssoManager
    ) {}

    public function redirectToGoogle(Request $request)
    {
        $platform = $request->get('platform', 'web');
        
        try {
            $redirectUrl = $this->ssoManager->redirectUrl('google', $platform);
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function redirectToFacebook(Request $request)
    {
        $platform = $request->get('platform', 'web');
        
        try {
            $redirectUrl = $this->ssoManager->redirectUrl('facebook', $platform);
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function redirectToApple(Request $request)
    {
        $platform = $request->get('platform', 'web');
        
        try {
            $redirectUrl = $this->ssoManager->redirectUrl('apple', $platform);
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
```

### 2. Handle OAuth Callbacks

```php
public function handleGoogleCallback(Request $request)
{
    $code = $request->get('code');
    $platform = $request->get('platform', 'web');
    
    if (!$code) {
        return back()->withErrors(['error' => 'Authorization code not received']);
    }

    try {
        $userData = $this->ssoManager->verifyCode('google', $code, $platform);
        
        // Handle user data
        $user = $this->findOrCreateUser($userData);
        
        // Log user in
        Auth::login($user);
        
        return redirect()->intended('/dashboard');
        
    } catch (\Exception $e) {
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}

public function handleFacebookCallback(Request $request)
{
    $code = $request->get('code');
    $platform = $request->get('platform', 'web');
    
    if (!$code) {
        return back()->withErrors(['error' => 'Authorization code not received']);
    }

    try {
        $userData = $this->ssoManager->verifyCode('facebook', $code, $platform);
        
        // Handle user data
        $user = $this->findOrCreateUser($userData);
        
        // Log user in
        Auth::login($user);
        
        return redirect()->intended('/dashboard');
        
    } catch (\Exception $e) {
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}

public function handleAppleCallback(Request $request)
{
    $code = $request->get('code');
    $platform = $request->get('platform', 'web');
    
    if (!$code) {
        return back()->withErrors(['error' => 'Authorization code not received']);
    }

    try {
        $userData = $this->ssoManager->verifyCode('apple', $code, $platform);
        
        // Handle user data
        $user = $this->findOrCreateUser($userData);
        
        // Log user in
        Auth::login($user);
        
        return redirect()->intended('/dashboard');
        
    } catch (\Exception $e) {
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}
```

### 3. Advanced Deep Link Handling

```php
use Muhammadsalman\LaravelSso\Support\DeepLinkHandler;
use Muhammadsalman\LaravelSso\Support\RedirectUrlManager;

class DeepLinkController extends Controller
{
    public function __construct(
        private DeepLinkHandler $deepLinkHandler,
        private RedirectUrlManager $redirectManager
    ) {}

    public function generateDeepLinks(Request $request)
    {
        $provider = $request->get('provider', 'google');
        $baseUrl = config('app.url');
        
        try {
            // Generate deep links for all platforms
            $deepLinks = $this->redirectManager->getAllRedirectUrls($provider, $baseUrl);
            
            return response()->json([
                'provider' => $provider,
                'deep_links' => $deepLinks,
                'platforms' => array_keys($deepLinks)
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function validateDeepLink(Request $request)
    {
        $url = $request->get('url');
        $platform = $request->get('platform', 'web');
        
        $isValid = $this->redirectManager->validateRedirectUrl($url, $platform);
        
        return response()->json([
            'url' => $url,
            'platform' => $platform,
            'is_valid' => $isValid
        ]);
    }

    public function extractDeepLinkData(Request $request)
    {
        $url = $request->get('url');
        $platform = $request->get('platform', 'web');
        
        if ($platform === 'web') {
            return response()->json(['error' => 'Deep links only available for mobile platforms']);
        }
        
        $params = $this->deepLinkHandler->extractDeepLinkParams($url, $platform);
        $callbackPath = $this->deepLinkHandler->extractCallbackPath($url, $platform);
        
        return response()->json([
            'url' => $url,
            'platform' => $platform,
            'callback_path' => $callbackPath,
            'parameters' => $params
        ]);
    }
}
```

## Routes

Add these routes to your `routes/web.php`:

```php
// OAuth Redirect Routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/facebook', [AuthController::class, 'redirectToFacebook'])->name('auth.facebook');
Route::get('/auth/apple', [AuthController::class, 'redirectToApple'])->name('auth.apple');

// OAuth Callback Routes
Route::get('/social/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/social/facebook/callback', [AuthController::class, 'handleFacebookCallback'])->name('auth.facebook.callback');
Route::post('/social/apple/callback', [AuthController::class, 'handleAppleCallback'])->name('auth.apple.callback');

// Deep Link Routes (Optional)
Route::prefix('api/deep-links')->group(function () {
    Route::get('/generate', [DeepLinkController::class, 'generateDeepLinks']);
    Route::post('/validate', [DeepLinkController::class, 'validateDeepLink']);
    Route::post('/extract', [DeepLinkController::class, 'extractDeepLinkData']);
});
```

## Frontend Integration

### Web Platform

```html
<!-- Google Sign-In Button -->
<a href="{{ route('auth.google', ['platform' => 'web']) }}" class="btn btn-google">
    Sign in with Google
</a>

<!-- Facebook Sign-In Button -->
<a href="{{ route('auth.facebook', ['platform' => 'web']) }}" class="btn btn-facebook">
    Sign in with Facebook
</a>

<!-- Apple Sign-In Button -->
<a href="{{ route('auth.apple', ['platform' => 'web']) }}" class="btn btn-apple">
    Sign in with Apple
</a>
```

### Mobile Platform Detection

```javascript
// Detect platform and redirect accordingly
function detectPlatform() {
    const userAgent = navigator.userAgent.toLowerCase();
    
    if (/iphone|ipad|ipod/.test(userAgent)) {
        return 'ios';
    } else if (/android/.test(userAgent)) {
        return 'android';
    } else {
        return 'web';
    }
}

// Update OAuth URLs with platform
function updateOAuthUrls() {
    const platform = detectPlatform();
    const buttons = document.querySelectorAll('[data-oauth]');
    
    buttons.forEach(button => {
        const provider = button.dataset.oauth;
        const currentHref = button.href;
        const separator = currentHref.includes('?') ? '&' : '?';
        button.href = currentHref + separator + 'platform=' + platform;
    });
}

// Call on page load
document.addEventListener('DOMContentLoaded', updateOAuthUrls);
```

### Mobile App Integration

For iOS and Android apps, configure your deep link schemes:

#### iOS (Info.plist)
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

#### Android (AndroidManifest.xml)
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

## Error Handling

The package provides specific exceptions for different error scenarios:

```php
use Muhammadsalman\LaravelSso\Exceptions\{
    InvalidPlatformException,
    ProviderNotConfiguredException,
    OAuthHttpException,
    TokenExchangeException,
    UserInfoFetchException
};

try {
    $userData = $this->ssoManager->verifyCode('google', $code, $platform);
} catch (InvalidPlatformException $e) {
    // Handle invalid platform
    Log::error('Invalid platform: ' . $e->getMessage());
} catch (ProviderNotConfiguredException $e) {
    // Handle missing provider configuration
    Log::error('Provider not configured: ' . $e->getMessage());
} catch (OAuthHttpException $e) {
    // Handle OAuth HTTP errors
    Log::error('OAuth HTTP error: ' . $e->getMessage());
} catch (TokenExchangeException $e) {
    // Handle token exchange errors
    Log::error('Token exchange error: ' . $e->getMessage());
} catch (UserInfoFetchException $e) {
    // Handle user info fetch errors
    Log::error('User info fetch error: ' . $e->getMessage());
} catch (\Exception $e) {
    // Handle other errors
    Log::error('Unexpected error: ' . $e->getMessage());
}
```

## Security Considerations

1. **State Parameter**: The package automatically generates and validates state parameters for OAuth flows
2. **HTTPS**: Always use HTTPS in production for OAuth redirects
3. **Client Secrets**: Never expose client secrets in frontend code
4. **Redirect URIs**: Validate and restrict redirect URIs in your OAuth provider settings
5. **Token Storage**: Store tokens securely and implement proper token refresh logic

## Testing

The package includes testing support via Orchestra Testbench:

```bash
composer test
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support, please open an issue on GitHub or contact the maintainer.

## Changelog

### v1.0.0
- Initial release
- Google, Facebook, and Apple OAuth support
- Web, iOS, and Android platform support
- Deep link handling
- Comprehensive error handling
- Laravel integration
