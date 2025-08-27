<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }

        .welcome-text {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .platform-indicator {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }

        .platform-indicator .platform {
            font-weight: 600;
            color: #667eea;
            text-transform: uppercase;
            font-size: 14px;
        }

        .platform-indicator .description {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .oauth-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }

        .oauth-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .oauth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .oauth-btn:active {
            transform: translateY(0);
        }

        .oauth-btn.google {
            background: #4285f4;
            color: white;
        }

        .oauth-btn.facebook {
            background: #1877f2;
            color: white;
        }

        .oauth-btn.apple {
            background: #000;
            color: white;
        }

        .oauth-btn .icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .oauth-btn .icon svg {
            width: 100%;
            height: 100%;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: #999;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }

        .divider span {
            padding: 0 15px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .submit-btn {
            width: 100%;
            background: #667eea;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .mobile-specific {
            background: #e8f4fd;
            border: 1px solid #b3d9ff;
            color: #0066cc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .mobile-specific.show {
            display: block;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>{{ config('app.name', 'Laravel') }}</h1>
        </div>

        <div class="welcome-text">
            Welcome back! Please sign in to your account.
        </div>

        <!-- Platform Detection Indicator -->
        <div class="platform-indicator">
            <div class="platform">Platform: {{ ucfirst($platform) }}</div>
            <div class="description">
                @if($platform === 'web')
                    Web browser detected
                @elseif($platform === 'ios')
                    iOS device detected
                @elseif($platform === 'android')
                    Android device detected
                @endif
            </div>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="error-message">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Mobile-Specific Information -->
        <div class="mobile-specific" id="mobileInfo">
            <strong>Mobile App Integration:</strong><br>
            This login will redirect to your mobile app for authentication.
        </div>

        <!-- OAuth Buttons -->
        <div class="oauth-buttons">
            <a href="{{ route('auth.google', ['platform' => $platform]) }}" class="oauth-btn google">
                <div class="icon">
                    <svg viewBox="0 0 24 24">
                        <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                </div>
                Sign in with Google
            </a>

            <a href="{{ route('auth.facebook', ['platform' => $platform]) }}" class="oauth-btn facebook">
                <div class="icon">
                    <svg viewBox="0 0 24 24">
                        <path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                Sign in with Facebook
            </a>

            <a href="{{ route('auth.apple', ['platform' => $platform]) }}" class="oauth-btn apple">
                <div class="icon">
                    <svg viewBox="0 0 24 24">
                        <path fill="currentColor" d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                    </svg>
                </div>
                Sign in with Apple
            </div>

        <div class="divider">
            <span>or</span>
        </div>

        <!-- Traditional Login Form -->
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="submit-btn">
                Sign In
            </button>
        </form>

        <div class="forgot-password">
            <a href="{{ route('password.request') }}">Forgot your password?</a>
        </div>
    </div>

    <!-- JavaScript for Platform Detection and UI Enhancement -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const platform = '{{ $platform }}';
            
            // Show mobile-specific information for mobile platforms
            if (platform === 'ios' || platform === 'android') {
                document.getElementById('mobileInfo').classList.add('show');
            }

            // Platform detection fallback
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

            // Update OAuth URLs with detected platform
            function updateOAuthUrls() {
                const detectedPlatform = detectPlatform();
                const buttons = document.querySelectorAll('.oauth-btn');
                
                buttons.forEach(button => {
                    const currentHref = button.href;
                    const separator = currentHref.includes('?') ? '&' : '?';
                    button.href = currentHref + separator + 'platform=' + detectedPlatform;
                });
            }

            // Call platform detection on page load
            updateOAuthUrls();

            // Add loading states to OAuth buttons
            document.querySelectorAll('.oauth-btn').forEach(button => {
                button.addEventListener('click', function() {
                    this.style.opacity = '0.7';
                    this.style.pointerEvents = 'none';
                    
                    // Add loading text
                    const originalText = this.innerHTML;
                    this.innerHTML = '<div class="icon">⏳</div>Redirecting...';
                    
                    // Reset after 5 seconds (fallback)
                    setTimeout(() => {
                        this.style.opacity = '1';
                        this.style.pointerEvents = 'auto';
                        this.innerHTML = originalText;
                    }, 5000);
                });
            });

            // Form validation
            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Email validation
                if (!emailInput.value || !emailInput.value.includes('@')) {
                    emailInput.style.borderColor = '#e74c3c';
                    isValid = false;
                } else {
                    emailInput.style.borderColor = '#e0e0e0';
                }
                
                // Password validation
                if (!passwordInput.value || passwordInput.value.length < 6) {
                    passwordInput.style.borderColor = '#e74c3c';
                    isValid = false;
                } else {
                    passwordInput.style.borderColor = '#e0e0e0';
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all fields correctly.');
                }
            });

            // Real-time validation
            emailInput.addEventListener('blur', function() {
                if (this.value && !this.value.includes('@')) {
                    this.style.borderColor = '#e74c3c';
                } else {
                    this.style.borderColor = '#e0e0e0';
                }
            });

            passwordInput.addEventListener('blur', function() {
                if (this.value && this.value.length < 6) {
                    this.style.borderColor = '#e74c3c';
                } else {
                    this.style.borderColor = '#e0e0e0';
                }
            });
        });
    </script>
</body>
</html>
