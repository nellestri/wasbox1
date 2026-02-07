<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - WashBox</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-color: #4F46E5;
            --secondary-color: #6366F1;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 3rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .login-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .login-left h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .login-left p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .login-right {
            flex: 1;
            padding: 3rem;
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .login-header h3 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #6B7280;
        }

        .form-control {
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #D1D5DB;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-login {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 0.5rem;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6B7280;
        }

        .alert {
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin-top: 2rem;
        }

        .features-list li {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            opacity: 0.9;
        }

        .features-list i {
            font-size: 1.5rem;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-left {
                padding: 2rem;
            }

            .login-right {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <div class="login-logo">
                <i class="bi bi-water"></i>
            </div>
            <h2>WashBox</h2>
            <p>Laundry Management System</p>

            <ul class="features-list">
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Multi-Branch Management</span>
                </li>
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Real-time Order Tracking</span>
                </li>
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Smart Promotions</span>
                </li>
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Analytics Dashboard</span>
                </li>
            </ul>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <h3>Welcome Back</h3>
                <p>Sign in to your admin account</p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('admin.login') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="admin@washbox.com"
                           required
                           autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div style="position: relative;">
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               id="password"
                               name="password"
                               placeholder="••••••••"
                               required>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>

                <div class="text-center mt-3">
                    <a href="{{ route('admin.forgot-password') }}" class="text-decoration-none" style="color: var(--primary-color);">
                        Forgot your password?
                    </a>
                </div>
            </form>

            <hr class="my-4">

            <div class="text-center text-muted">
                <small>
                    <i class="bi bi-shield-lock"></i> Secure Login • Admin Access Only
                </small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
