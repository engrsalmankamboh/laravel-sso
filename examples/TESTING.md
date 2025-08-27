# Laravel SSO Package Testing Guide

This guide covers testing strategies for the Laravel SSO package, including unit tests, integration tests, and manual testing procedures.

## Testing Setup

### 1. Install Testing Dependencies

The package includes testing support via Orchestra Testbench. Ensure you have the required dependencies:

```bash
composer require --dev orchestra/testbench phpunit/phpunit
```

### 2. Test Configuration

Create a `phpunit.xml` file in your project root:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="SSO Package Tests">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>
</phpunit>
```

## Unit Tests

### 1. Platform Service Tests

Create `tests/Unit/Support/PlatformServiceTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use Tests\TestCase;
use Muhammadsalman\LaravelSso\Support\PlatformService;

class PlatformServiceTest extends TestCase
{
    private PlatformService $platformService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = [
            'default' => 'web',
            'web' => [
                'requires_postmessage' => true,
                'deep_link_scheme' => null,
                'callback_path' => '/social/{provider}/callback',
            ],
            'ios' => [
                'requires_postmessage' => false,
                'deep_link_scheme' => 'myapp',
                'callback_path' => '/social/{provider}/callback',
            ],
            'android' => [
                'requires_postmessage' => false,
                'deep_link_scheme' => 'myapp',
                'callback_path' => '/social/{provider}/callback',
            ],
        ];
        
        $this->platformService = new PlatformService($config);
    }

    public function test_default_platform()
    {
        $this->assertEquals('web', $this->platformService->default());
    }

    public function test_supported_platforms()
    {
        $platforms = $this->platformService->getSupportedPlatforms();
        $this->assertContains('web', $platforms);
        $this->assertContains('ios', $platforms);
        $this->assertContains('android', $platforms);
    }

    public function test_platform_validation()
    {
        $this->assertTrue($this->platformService->isSupported('web'));
        $this->assertTrue($this->platformService->isSupported('ios'));
        $this->assertFalse($this->platformService->isSupported('invalid'));
    }

    public function test_callback_path_generation()
    {
        $path = $this->platformService->callbackPath('google', 'web');
        $this->assertEquals('/social/google/callback', $path);
    }

    public function test_deep_link_retrieval()
    {
        $this->assertNull($this->platformService->deepLink('web'));
        $this->assertEquals('myapp', $this->platformService->deepLink('ios'));
        $this->assertEquals('myapp', $this->platformService->deepLink('android'));
    }

    public function test_postmessage_requirement()
    {
        $this->assertTrue($this->platformService->requiresPostMessage('web'));
        $this->assertFalse($this->platformService->requiresPostMessage('ios'));
        $this->assertFalse($this->platformService->requiresPostMessage('android'));
    }

    public function test_redirect_url_generation()
    {
        $webUrl = $this->platformService->getRedirectUrl('https://example.com', 'google', 'web');
        $this->assertEquals('https://example.com/social/google/callback', $webUrl);

        $iosUrl = $this->platformService->getRedirectUrl('https://example.com', 'google', 'ios');
        $this->assertEquals('myapp:///social/google/callback', $iosUrl);
    }

    public function test_redirect_url_validation_error()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Deep link scheme not configured for platform: invalid');
        
        $this->platformService->getRedirectUrl('https://example.com', 'google', 'invalid');
    }
}
```

### 2. Deep Link Handler Tests

Create `tests/Unit/Support/DeepLinkHandlerTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use Tests\TestCase;
use Muhammadsalman\LaravelSso\Support\DeepLinkHandler;
use Muhammadsalman\LaravelSso\Support\PlatformService;

class DeepLinkHandlerTest extends TestCase
{
    private DeepLinkHandler $deepLinkHandler;
    private PlatformService $platformService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = [
            'default' => 'web',
            'ios' => [
                'deep_link_scheme' => 'myapp',
                'callback_path' => '/social/{provider}/callback',
            ],
            'android' => [
                'deep_link_scheme' => 'myapp',
                'callback_path' => '/social/{provider}/callback',
            ],
        ];
        
        $this->platformService = new PlatformService($config);
        $this->deepLinkHandler = new DeepLinkHandler($this->platformService);
    }

    public function test_deep_link_generation()
    {
        $deepLink = $this->deepLinkHandler->generateDeepLink('google', 'ios');
        $this->assertEquals('myapp:///social/google/callback', $deepLink);

        $deepLinkWithParams = $this->deepLinkHandler->generateDeepLink('google', 'ios', ['state' => 'abc123']);
        $this->assertEquals('myapp:///social/google/callback?state=abc123', $deepLinkWithParams);
    }

    public function test_deep_link_validation()
    {
        $validUrl = 'myapp:///social/google/callback';
        $invalidUrl = 'https://example.com/social/google/callback';
        
        $this->assertTrue($this->deepLinkHandler->isValidDeepLink($validUrl, 'ios'));
        $this->assertFalse($this->deepLinkHandler->isValidDeepLink($invalidUrl, 'ios'));
    }

    public function test_deep_link_parameter_extraction()
    {
        $url = 'myapp:///social/google/callback?state=abc123&code=xyz789';
        $params = $this->deepLinkHandler->extractDeepLinkParams($url, 'ios');
        
        $this->assertEquals('abc123', $params['state']);
        $this->assertEquals('xyz789', $params['code']);
    }

    public function test_callback_path_extraction()
    {
        $url = 'myapp:///social/google/callback?state=abc123';
        $path = $this->deepLinkHandler->extractCallbackPath($url, 'ios');
        
        $this->assertEquals('/social/google/callback', $path);
    }

    public function test_unsupported_platform_error()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported platform: invalid');
        
        $this->deepLinkHandler->generateDeepLink('google', 'invalid');
    }

    public function test_missing_deep_link_scheme_error()
    {
        $config = [
            'default' => 'web',
            'ios' => [
                'deep_link_scheme' => null,
                'callback_path' => '/social/{provider}/callback',
            ],
        ];
        
        $platformService = new PlatformService($config);
        $deepLinkHandler = new DeepLinkHandler($platformService);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Deep link scheme not configured for platform: ios');
        
        $deepLinkHandler->generateDeepLink('google', 'ios');
    }
}
```

### 3. SSO Manager Tests

Create `tests/Unit/Core/SSOManagerTest.php`:

```php
<?php

namespace Tests\Unit\Core;

use Tests\TestCase;
use Muhammadsalman\LaravelSso\Core\SSOManager;
use Muhammadsalman\LaravelSso\Support\PlatformService;
use Muhammadsalman\LaravelSso\Exceptions\InvalidPlatformException;

class SSOManagerTest extends TestCase
{
    private SSOManager $ssoManager;
    private PlatformService $platformService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $platformConfig = [
            'default' => 'web',
            'web' => [
                'requires_postmessage' => true,
                'deep_link_scheme' => null,
                'callback_path' => '/social/{provider}/callback',
            ],
            'ios' => [
                'requires_postmessage' => false,
                'deep_link_scheme' => 'myapp',
                'callback_path' => '/social/{provider}/callback',
            ],
        ];
        
        $providerConfig = [
            'google' => [
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
                'redirect' => 'https://example.com/social/google/callback',
            ],
        ];
        
        $this->platformService = new PlatformService($platformConfig);
        $this->ssoManager = new SSOManager($this->platformService, $providerConfig);
    }

    public function test_platform_validation()
    {
        $this->expectException(InvalidPlatformException::class);
        $this->expectExceptionMessage("Platform 'invalid' is not supported");
        
        $this->ssoManager->redirectUrl('google', 'invalid');
    }

    public function test_default_platform_usage()
    {
        // Should not throw exception with default platform
        $this->assertIsString($this->ssoManager->redirectUrl('google'));
    }
}
```

## Integration Tests

### 1. OAuth Flow Tests

Create `tests/Feature/OAuthFlowTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Muhammadsalman\LaravelSso\Core\SSOManager;

class OAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_oauth_redirect()
    {
        $response = $this->get('/auth/google?platform=web');
        
        $response->assertStatus(302);
        $response->assertRedirect();
        
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com', $redirectUrl);
        $this->assertStringContainsString('client_id=', $redirectUrl);
        $this->assertStringContainsString('redirect_uri=', $redirectUrl);
    }

    public function test_facebook_oauth_redirect()
    {
        $response = $this->get('/auth/facebook?platform=web');
        
        $response->assertStatus(302);
        $response->assertRedirect();
        
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('facebook.com', $redirectUrl);
        $this->assertStringContainsString('client_id=', $redirectUrl);
    }

    public function test_apple_oauth_redirect()
    {
        $response = $this->get('/auth/apple?platform=web');
        
        $response->assertStatus(302);
        $response->assertRedirect();
        
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('appleid.apple.com', $redirectUrl);
        $this->assertStringContainsString('client_id=', $redirectUrl);
    }

    public function test_platform_detection()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)'
        ])->get('/auth/google');
        
        $response->assertStatus(302);
        // Should detect iOS platform
    }

    public function test_mobile_platform_redirect()
    {
        $response = $this->get('/auth/google?platform=ios');
        
        $response->assertStatus(302);
        // Should generate deep link for iOS
    }
}
```

### 2. Deep Link API Tests

Create `tests/Feature/DeepLinkApiTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeepLinkApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_deep_link_generation()
    {
        $response = $this->get('/api/deep-links/generate?provider=google');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'provider',
            'deep_links',
            'platforms'
        ]);
        
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('google', $data['provider']);
        $this->assertArrayHasKey('web', $data['deep_links']);
        $this->assertArrayHasKey('ios', $data['deep_links']);
        $this->assertArrayHasKey('android', $data['deep_links']);
    }

    public function test_deep_link_validation()
    {
        $response = $this->post('/api/deep-links/validate', [
            'url' => 'myapp:///social/google/callback',
            'platform' => 'ios'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'url',
            'platform',
            'is_valid'
        ]);
        
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertTrue($data['is_valid']);
    }

    public function test_deep_link_data_extraction()
    {
        $response = $this->post('/api/deep-links/extract', [
            'url' => 'myapp:///social/google/callback?state=abc123&code=xyz789',
            'platform' => 'ios'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'url',
            'platform',
            'callback_path',
            'parameters'
        ]);
        
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('/social/google/callback', $data['callback_path']);
        $this->assertEquals('abc123', $data['parameters']['state']);
        $this->assertEquals('xyz789', $data['parameters']['code']);
    }
}
```

## Manual Testing

### 1. Web Platform Testing

1. **Setup Test Environment**
   ```bash
   # Set environment variables for testing
   SSO_GOOGLE_CLIENT_ID=your_test_client_id
   SSO_GOOGLE_CLIENT_SECRET=your_test_client_secret
   SSO_GOOGLE_REDIRECT=http://localhost:8000/social/google/callback
   ```

2. **Test OAuth Flow**
   - Visit `/auth/google?platform=web`
   - Verify redirect to Google OAuth
   - Complete OAuth flow
   - Verify callback handling
   - Check user creation/login

3. **Test Error Handling**
   - Test with invalid platform
   - Test with missing configuration
   - Test with invalid OAuth codes

### 2. Mobile Platform Testing

1. **iOS Testing**
   - Use iOS Simulator or device
   - Test deep link generation
   - Verify deep link validation
   - Test parameter extraction

2. **Android Testing**
   - Use Android Emulator or device
   - Test deep link generation
   - Verify deep link validation
   - Test parameter extraction

### 3. Cross-Platform Testing

1. **Platform Detection**
   - Test with different User-Agent strings
   - Verify automatic platform detection
   - Test manual platform override

2. **Deep Link Integration**
   - Test deep link generation for all platforms
   - Verify callback path consistency
   - Test parameter passing

## Performance Testing

### 1. Load Testing

```php
// Test multiple concurrent OAuth requests
public function test_concurrent_oauth_requests()
{
    $responses = [];
    
    for ($i = 0; $i < 10; $i++) {
        $responses[] = $this->get('/auth/google?platform=web');
    }
    
    foreach ($responses as $response) {
        $response->assertStatus(302);
    }
}
```

### 2. Memory Testing

```php
// Test memory usage during OAuth operations
public function test_memory_usage()
{
    $initialMemory = memory_get_usage();
    
    for ($i = 0; $i < 100; $i++) {
        $this->ssoManager->redirectUrl('google', 'web');
    }
    
    $finalMemory = memory_get_usage();
    $memoryIncrease = $finalMemory - $initialMemory;
    
    // Memory increase should be reasonable
    $this->assertLessThan(1024 * 1024, $memoryIncrease); // Less than 1MB
}
```

## Security Testing

### 1. CSRF Protection

```php
public function test_csrf_protection()
{
    $response = $this->post('/auth/google', [
        'platform' => 'web'
    ]);
    
    $response->assertStatus(419); // CSRF token mismatch
}
```

### 2. State Parameter Validation

```php
public function test_state_parameter_generation()
{
    $response1 = $this->get('/auth/google?platform=web');
    $response2 = $this->get('/auth/google?platform=web');
    
    $url1 = $response1->headers->get('Location');
    $url2 = $response2->headers->get('Location');
    
    // State parameters should be different
    $this->assertNotEquals($url1, $url2);
}
```

## Running Tests

### 1. Run All Tests

```bash
composer test
```

### 2. Run Specific Test Suite

```bash
# Run only unit tests
./vendor/bin/phpunit --testsuite="SSO Package Tests" --filter="Unit"

# Run only integration tests
./vendor/bin/phpunit --testsuite="SSO Package Tests" --filter="Feature"
```

### 3. Run with Coverage

```bash
./vendor/bin/phpunit --coverage-html coverage
```

### 4. Run Specific Test

```bash
./vendor/bin/phpunit --filter="test_google_oauth_redirect"
```

## Continuous Integration

### 1. GitHub Actions

Create `.github/workflows/test.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Execute tests
      run: composer test
    
    - name: Upload coverage
      uses: codecov/codecov-action@v1
```

### 2. Travis CI

Create `.travis.yml`:

```yaml
language: php

php:
  - 8.1
  - 8.2

before_script:
  - composer install --prefer-dist

script:
  - composer test
```

## Test Data

### 1. Factory Classes

Create test factories for OAuth data:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OAuthUserFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'provider' => $this->faker->randomElement(['google', 'facebook', 'apple']),
            'provider_id' => $this->faker->uuid,
            'avatar' => $this->faker->imageUrl(),
        ];
    }
}
```

### 2. Test Helpers

Create helper methods for testing:

```php
protected function createOAuthUser($provider = 'google')
{
    return User::factory()->create([
        'provider' => $provider,
        'provider_id' => Str::random(10),
    ]);
}

protected function mockOAuthProvider($provider, $userData)
{
    // Mock OAuth provider responses
    $this->mock(SSOManager::class, function ($mock) use ($provider, $userData) {
        $mock->shouldReceive('verifyCode')
             ->with($provider, 'test_code', 'web')
             ->andReturn($userData);
    });
}
```

## Best Practices

### 1. Test Organization
- Group related tests in test classes
- Use descriptive test method names
- Follow AAA pattern (Arrange, Act, Assert)

### 2. Test Data Management
- Use factories for test data creation
- Clean up test data after each test
- Use database transactions when possible

### 3. Mocking and Stubbing
- Mock external OAuth providers
- Stub time-dependent operations
- Use realistic test data

### 4. Error Testing
- Test both success and failure scenarios
- Verify error messages and status codes
- Test edge cases and boundary conditions

### 5. Performance Considerations
- Keep tests fast and focused
- Avoid unnecessary database operations
- Use appropriate assertions

## Troubleshooting Tests

### Common Issues

1. **Configuration Errors**
   - Ensure test environment variables are set
   - Check that test configuration is loaded
   - Verify package service providers are registered

2. **Database Issues**
   - Use `RefreshDatabase` trait for database tests
   - Ensure test database is configured
   - Check migration files

3. **Mock Issues**
   - Verify mock objects are properly configured
   - Check method signatures match
   - Ensure mocks are applied before test execution

### Debug Tips

1. **Enable Debug Mode**
   ```env
   APP_DEBUG=true
   LOG_LEVEL=debug
   ```

2. **Check Test Output**
   ```bash
   ./vendor/bin/phpunit --verbose
   ```

3. **Use Test Helpers**
   ```php
   dd($response->json()); // Dump response data
   $this->withoutExceptionHandling(); // See full exceptions
   ```
