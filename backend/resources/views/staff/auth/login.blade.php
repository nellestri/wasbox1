<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Login - WashBox Laundry Services</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --washbox-purple: #3D3B6B;
            --washbox-gradient: linear-gradient(135deg, #3D3B6B 0%, #2B2952 100%);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--washbox-gradient);
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
            max-width: 450px;
            width: 100%;
        }

        .login-header {
            background: var(--washbox-gradient);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .login-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }

        .login-body {
            padding: 2.5rem 2rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #D1D5DB;
        }

        .form-control:focus {
            border-color: var(--washbox-purple);
            box-shadow: 0 0 0 3px rgba(61, 59, 107, 0.1);
        }

        .btn-login {
            background: var(--washbox-gradient);
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(61, 59, 107, 0.4);
            color: white;
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

        .staff-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="mb-3">
                <i class="bi bi-person-badge" style="font-size: 3rem;"></i>
            </div>
            <h2>Staff Portal</h2>
            <p>WashBox Laundry Management</p>
            <div class="staff-badge">
                <i class="bi bi-shield-check"></i>
                <span>Branch Staff Access</span>
            </div>
        </div>

        <!-- Login Form -->
        <div class="login-body">
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

            <form action="{{ route('staff.login') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="staff@washbox.com"
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
            </form>

            <hr class="my-4">

            <div class="text-center">
                <p class="text-muted mb-2">
                    <i class="bi bi-info-circle"></i> For branch staff only
                </p>
                <p class="text-muted small mb-0">
                    Admin access? <a href="{{ route('admin.login') }}" style="color: var(--washbox-purple);">Login here</a>
                </p>
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
