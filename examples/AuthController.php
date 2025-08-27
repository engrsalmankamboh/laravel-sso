<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Muhammadsalman\LaravelSso\Core\SSOManager;
use Muhammadsalman\LaravelSso\Support\DeepLinkHandler;
use Muhammadsalman\LaravelSso\Support\RedirectUrlManager;
use Muhammadsalman\LaravelSso\Exceptions\{
    InvalidPlatformException,
    ProviderNotConfiguredException,
    OAuthHttpException,
    TokenExchangeException,
    UserInfoFetchException
};

/**
 * Example AuthController demonstrating complete SSO integration
 */
class AuthController extends Controller
{
    public function __construct(
        private SSOManager $ssoManager,
        private DeepLinkHandler $deepLinkHandler,
        private RedirectUrlManager $redirectManager
    ) {}

    /**
     * Show login page with platform detection
     */
    public function showLogin(Request $request)
    {
        $platform = $this->detectPlatform($request);
        
        return view('auth.login', [
            'platform' => $platform,
            'providers' => ['google', 'facebook', 'apple']
        ]);
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request)
    {
        $platform = $request->get('platform', $this->detectPlatform($request));
        
        try {
            $redirectUrl = $this->ssoManager->redirectUrl('google', $platform);
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Google OAuth redirect error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to redirect to Google. Please try again.']);
        }
    }

    /**
     * Redirect to Facebook OAuth
     */
    public function redirectToFacebook(Request $request)
    {
        $platform = $request->get('platform', $this->detectPlatform($request));
        
        try {
            $redirectUrl = $this->ssoManager->redirectUrl('facebook', $platform);
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Facebook OAuth redirect error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to redirect to Facebook. Please try again.']);
        }
    }

    /**
     * Redirect to Apple OAuth
     */
    public function redirectToApple(Request $request)
    {
        $platform = $request->get('platform', $this->detectPlatform($request));
        
        try {
            $redirectUrl = $this->ssoManager->redirectUrl('apple', $platform);
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Apple OAuth redirect error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to redirect to Apple. Please try again.']);
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        $code = $request->get('code');
        $platform = $request->get('platform', $this->detectPlatform($request));
        
        if (!$code) {
            return $this->handleOAuthError('Authorization code not received from Google');
        }

        try {
            $userData = $this->ssoManager->verifyCode('google', $code, $platform);
            return $this->processOAuthUser($userData, $platform);
            
        } catch (InvalidPlatformException $e) {
            Log::error('Invalid platform for Google OAuth: ' . $e->getMessage());
            return $this->handleOAuthError('Invalid platform specified');
        } catch (ProviderNotConfiguredException $e) {
            Log::error('Google provider not configured: ' . $e->getMessage());
            return $this->handleOAuthError('Google authentication is not configured');
        } catch (OAuthHttpException $e) {
            Log::error('Google OAuth HTTP error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to communicate with Google');
        } catch (TokenExchangeException $e) {
            Log::error('Google token exchange error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to authenticate with Google');
        } catch (UserInfoFetchException $e) {
            Log::error('Google user info fetch error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to retrieve user information from Google');
        } catch (\Exception $e) {
            Log::error('Unexpected Google OAuth error: ' . $e->getMessage());
            return $this->handleOAuthError('An unexpected error occurred during Google authentication');
        }
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function handleFacebookCallback(Request $request)
    {
        $code = $request->get('code');
        $platform = $request->get('platform', $this->detectPlatform($request));
        
        if (!$code) {
            return $this->handleOAuthError('Authorization code not received from Facebook');
        }

        try {
            $userData = $this->ssoManager->verifyCode('facebook', $code, $platform);
            return $this->processOAuthUser($userData, $platform);
            
        } catch (InvalidPlatformException $e) {
            Log::error('Invalid platform for Facebook OAuth: ' . $e->getMessage());
            return $this->handleOAuthError('Invalid platform specified');
        } catch (ProviderNotConfiguredException $e) {
            Log::error('Facebook provider not configured: ' . $e->getMessage());
            return $this->handleOAuthError('Facebook authentication is not configured');
        } catch (OAuthHttpException $e) {
            Log::error('Facebook OAuth HTTP error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to communicate with Facebook');
        } catch (TokenExchangeException $e) {
            Log::error('Facebook token exchange error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to authenticate with Facebook');
        } catch (UserInfoFetchException $e) {
            Log::error('Facebook user info fetch error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to retrieve user information from Facebook');
        } catch (\Exception $e) {
            Log::error('Unexpected Facebook OAuth error: ' . $e->getMessage());
            return $this->handleOAuthError('An unexpected error occurred during Facebook authentication');
        }
    }

    /**
     * Handle Apple OAuth callback
     */
    public function handleAppleCallback(Request $request)
    {
        $code = $request->get('code');
        $platform = $request->get('platform', $this->detectPlatform($request));
        
        if (!$code) {
            return $this->handleOAuthError('Authorization code not received from Apple');
        }

        try {
            $userData = $this->ssoManager->verifyCode('apple', $code, $platform);
            return $this->processOAuthUser($userData, $platform);
            
        } catch (InvalidPlatformException $e) {
            Log::error('Invalid platform for Apple OAuth: ' . $e->getMessage());
            return $this->handleOAuthError('Invalid platform specified');
        } catch (ProviderNotConfiguredException $e) {
            Log::error('Apple provider not configured: ' . $e->getMessage());
            return $this->handleOAuthError('Apple authentication is not configured');
        } catch (OAuthHttpException $e) {
            Log::error('Apple OAuth HTTP error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to communicate with Apple');
        } catch (TokenExchangeException $e) {
            Log::error('Apple token exchange error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to authenticate with Apple');
        } catch (UserInfoFetchException $e) {
            Log::error('Apple user info fetch error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to retrieve user information from Apple');
        } catch (\Exception $e) {
            Log::error('Unexpected Apple OAuth error: ' . $e->getMessage());
            return $this->handleOAuthError('An unexpected error occurred during Apple authentication');
        }
    }

    /**
     * Generate deep links for all platforms
     */
    public function generateDeepLinks(Request $request)
    {
        $provider = $request->get('provider', 'google');
        $baseUrl = config('app.url');
        
        try {
            $deepLinks = $this->redirectManager->getAllRedirectUrls($provider, $baseUrl);
            
            return response()->json([
                'success' => true,
                'provider' => $provider,
                'deep_links' => $deepLinks,
                'platforms' => array_keys($deepLinks)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Deep link generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Validate deep link URL
     */
    public function validateDeepLink(Request $request)
    {
        $url = $request->get('url');
        $platform = $request->get('platform', 'web');
        
        try {
            $isValid = $this->redirectManager->validateRedirectUrl($url, $platform);
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'platform' => $platform,
                'is_valid' => $isValid
            ]);
            
        } catch (\Exception $e) {
            Log::error('Deep link validation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Extract data from deep link URL
     */
    public function extractDeepLinkData(Request $request)
    {
        $url = $request->get('url');
        $platform = $request->get('platform', 'web');
        
        if ($platform === 'web') {
            return response()->json([
                'success' => false,
                'error' => 'Deep links only available for mobile platforms'
            ], 400);
        }
        
        try {
            $params = $this->deepLinkHandler->extractDeepLinkParams($url, $platform);
            $callbackPath = $this->deepLinkHandler->extractCallbackPath($url, $platform);
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'platform' => $platform,
                'callback_path' => $callbackPath,
                'parameters' => $params
            ]);
            
        } catch (\Exception $e) {
            Log::error('Deep link data extraction error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Process OAuth user data and authenticate
     */
    private function processOAuthUser(array $userData, string $platform)
    {
        try {
            // Find or create user
            $user = $this->findOrCreateUser($userData);
            
            // Log user in
            Auth::login($user);
            
            // Store OAuth tokens if needed
            if (!empty($userData['oauth'])) {
                $this->storeOAuthTokens($user, $userData['oauth']);
            }
            
            // Redirect based on platform
            if ($platform === 'web') {
                return redirect()->intended('/dashboard');
            } else {
                // For mobile platforms, redirect to a mobile-specific page
                return redirect()->route('mobile.dashboard');
            }
            
        } catch (\Exception $e) {
            Log::error('User processing error: ' . $e->getMessage());
            return $this->handleOAuthError('Failed to process user information');
        }
    }

    /**
     * Find or create user from OAuth data
     */
    private function findOrCreateUser(array $userData): User
    {
        $provider = $userData['provider'];
        $userInfo = $userData['userinfo'];
        
        // Try to find user by provider ID first
        $user = User::where('provider', $provider)
                   ->where('provider_id', $userInfo['id'] ?? $userInfo['sub'])
                   ->first();
        
        if ($user) {
            // Update existing user information
            $this->updateUserInfo($user, $userInfo);
            return $user;
        }
        
        // Try to find user by email
        if (!empty($userInfo['email'])) {
            $user = User::where('email', $userInfo['email'])->first();
            
            if ($user) {
                // Link existing user to this provider
                $this->linkUserToProvider($user, $provider, $userInfo);
                return $user;
            }
        }
        
        // Create new user
        return $this->createNewUser($provider, $userInfo);
    }

    /**
     * Create new user from OAuth data
     */
    private function createNewUser(string $provider, array $userInfo): User
    {
        $user = new User();
        $user->name = $userInfo['name'] ?? 'Unknown User';
        $user->email = $userInfo['email'] ?? null;
        $user->provider = $provider;
        $user->provider_id = $userInfo['id'] ?? $userInfo['sub'];
        $user->email_verified_at = $userInfo['email_verified'] ? now() : null;
        $user->password = Hash::make(str_random(16)); // Random password for OAuth users
        
        if (!empty($userInfo['avatar'])) {
            $user->avatar = $userInfo['avatar'];
        }
        
        $user->save();
        
        Log::info("Created new user via {$provider} OAuth", ['user_id' => $user->id]);
        
        return $user;
    }

    /**
     * Update existing user information
     */
    private function updateUserInfo(User $user, array $userInfo): void
    {
        $user->name = $userInfo['name'] ?? $user->name;
        
        if (!empty($userInfo['avatar'])) {
            $user->avatar = $userInfo['avatar'];
        }
        
        if (!empty($userInfo['email']) && $userInfo['email'] !== $user->email) {
            $user->email = $userInfo['email'];
        }
        
        if (!empty($userInfo['email_verified']) && !$user->email_verified_at) {
            $user->email_verified_at = now();
        }
        
        $user->save();
    }

    /**
     * Link existing user to OAuth provider
     */
    private function linkUserToProvider(User $user, string $provider, array $userInfo): void
    {
        $user->provider = $provider;
        $user->provider_id = $userInfo['id'] ?? $userInfo['sub'];
        $user->save();
        
        Log::info("Linked existing user to {$provider} OAuth", ['user_id' => $user->id]);
    }

    /**
     * Store OAuth tokens for the user
     */
    private function storeOAuthTokens(User $user, array $oauthData): void
    {
        // Store tokens in user_oauth_tokens table or similar
        // This is just an example - implement according to your needs
        $user->update([
            'oauth_access_token' => $oauthData['access_token'] ?? null,
            'oauth_refresh_token' => $oauthData['refresh_token'] ?? null,
            'oauth_expires_at' => !empty($oauthData['expires_in']) 
                ? now()->addSeconds($oauthData['expires_in']) 
                : null,
        ]);
    }

    /**
     * Detect platform from request
     */
    private function detectPlatform(Request $request): string
    {
        // Check if platform is explicitly specified
        $platform = $request->get('platform');
        if ($platform && in_array($platform, ['web', 'ios', 'android'])) {
            return $platform;
        }
        
        // Auto-detect from user agent
        $userAgent = strtolower($request->userAgent());
        
        if (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad') || str_contains($userAgent, 'ipod')) {
            return 'ios';
        }
        
        if (str_contains($userAgent, 'android')) {
            return 'android';
        }
        
        return 'web';
    }

    /**
     * Handle OAuth errors consistently
     */
    private function handleOAuthError(string $message)
    {
        Log::warning('OAuth error: ' . $message);
        
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => $message
            ], 400);
        }
        
        return back()->withErrors(['error' => $message]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
}
