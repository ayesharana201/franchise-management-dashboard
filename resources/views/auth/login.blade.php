<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Franchise Manager</title>
    <style>
        * { box-sizing: border-box; }

        body { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; min-height: 100vh; margin: 0; padding: 28px 18px;
            margin: 0;
            min-height: 100vh; width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 18px;
            background:
                radial-gradient(ellipse at top left, #1e3a5f 0%, transparent 45%),
                linear-gradient(135deg, #0b1220, #111c30 55%, #0b1220);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172033;
        }

        .login-wrapper { width: 100%; max-width: 420px; margin: 0 auto; position: relative; left: 0; right: 0; }

        .brand {
            text-align: center;
            margin-bottom: 26px;
            color: white;
        }

        .brand-icon {
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            border-radius: 17px;
            background: linear-gradient(145deg, #3b82f6, #1d4ed8);
            box-shadow: 0 10px 30px rgba(37, 99, 235, .3);
            font-size: 25px;
            font-weight: 800;
            color: white;
        }

        .brand h1 {
            margin: 0;
            font-size: 25px;
            font-weight: 750;
            letter-spacing: -.7px;
        }

        .brand p {
            margin: 8px 0 0;
            color: #aab8cc;
            font-size: 14px;
        }

        .login-card {
            width: 100%;
            padding: 34px;
            background: #fff;
            border: 1px solid rgba(255,255,255,.6);
            border-radius: 20px;
            box-shadow: 0 25px 70px rgba(0,0,0,.3);
        }

        .card-heading {
            margin-bottom: 26px;
        }

        .card-heading h2 {
            margin: 0 0 7px;
            font-size: 22px;
            letter-spacing: -.5px;
            color: #111827;
        }

        .card-heading p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 9px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            line-height: 1.5;
        }

        .field {
            margin-bottom: 19px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-size: 13px;
            font-weight: 650;
        }

        .field input {
            display: block;
            width: 100%;
            height: 47px;
            padding: 0 13px;
            border: 1px solid #d7deea;
            border-radius: 9px;
            background: #fff;
            color: #111827;
            font: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .field input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.13);
        }

        .field input::placeholder {
            color: #9ca3af;
        }

        .form-options {
            display: flex;
            align-items: center;
            margin: 2px 0 23px;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #4b5563;
            font-size: 13px;
            cursor: pointer;
        }

        .remember input {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: #2563eb;
        }

        .submit-btn {
            width: 100%;
            min-height: 48px;
            border: none;
            border-radius: 9px;
            background: #2563eb;
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 5px 12px rgba(37,99,235,.18);
            transition: background .2s, transform .2s;
        }

        .submit-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 20px;
            color: #6b7280;
            font-size: 12px;
        }

        .demo {
            margin: 20px 0 0;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #f9fafb;
            color: #4b5563;
            font-size: 12px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .demo strong {
            color: #374151;
        }

        .footer {
            margin-top: 23px;
            text-align: center;
            color: #93a4ba;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .login-card { padding: 27px 22px; }
            .brand h1 { font-size: 23px; }
        }
    </style>
</head>
<body>
    <main class="login-wrapper">
        <header class="brand">
            <div class="brand-icon">F</div>
            <h1>Franchise Manager</h1>
            <p>Your franchise operations, in one place.</p>
        </header>

        <section class="login-card">
            <div class="card-heading">
                <h2>Welcome back</h2>
                <p>Sign in to access your management dashboard.</p>
            </div>

            <form method="POST" action="{{ url('/login') }}">
                @csrf

                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror

                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email address"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="form-options">
                    <label class="remember">
                        <input type="checkbox" name="remember" value="1">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="submit-btn">
                    Sign in to your account
                </button>
            </form>

            <div class="security-note">
                <span aria-hidden="true">&#128274;</span>
                Secure access to your franchise dashboard
            </div>
        </section>

        <footer class="footer">
            Franchise Management System
        </footer>
    </main>
</body>
</html>
'@ | Set-Content .\resources\views\auth\login.blade.php -Encoding UTF8




