<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login') &mdash; {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            /* Dark tint over the photo keeps the card and footer readable */
            background:
                linear-gradient(rgba(20, 48, 79, .55), rgba(20, 48, 79, .55)),
                url('{{ asset('images/welcome-bg.jpg') }}') center / cover no-repeat fixed,
                #14304f;
            display: flex;
            align-items: center;
            padding: 2rem 1rem;
        }
        :root {
            --brand: #14304f;
            --brand-deep: #0c1f35;
            --brand-light: #1d4570;
            --gold: #d9b45a;
            --gold-soft: #f1dfa6;
        }
        body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif; }
        .auth-footer { color: rgba(255, 255, 255, .85); letter-spacing: .02em; }

        /* ---------- Card: frosted glass over the background photo ---------- */
        .auth-card {
            /* Room for the emblem that floats above the card */
            margin-top: 44px;
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 22px;
            /* Nearly clear glass - the text on it is light so it stays readable */
            background: rgba(255, 255, 255, .1);
            color: #fff;
            -webkit-backdrop-filter: blur(10px) saturate(140%);
            backdrop-filter: blur(10px) saturate(140%);
            box-shadow:
                0 30px 60px -20px rgba(6, 18, 34, .65),
                0 12px 24px -12px rgba(6, 18, 34, .4),
                inset 0 1px 0 rgba(255, 255, 255, .35);
        }
        .auth-card .card-body { padding: 1.75rem 2rem 2rem !important; color: #fff; }
        .auth-card .bg-light {
            background: rgba(255, 255, 255, .08) !important;
            border-color: rgba(255, 255, 255, .25) !important;
            border-radius: 12px !important;
        }
        .auth-card .table {
            --bs-table-bg: transparent;
            --bs-table-color: #fff;
            --bs-table-border-color: rgba(255, 255, 255, .2);
        }
        .auth-card .text-muted,
        .auth-card .form-text { color: rgba(255, 255, 255, .75) !important; }
        /* Bootstrap's red is too dark to read on the glass */
        .auth-card .text-danger,
        .auth-card .invalid-feedback { color: #ffb3b3 !important; }
        .auth-card hr { border-color: #fff; opacity: .25; }
        .auth-card a:not(.btn) { color: var(--gold-soft); font-weight: 600; }
        .auth-card a:not(.btn):hover { color: #fff; }
        .auth-card .card-body > form .btn-outline-secondary:not(.input-group .btn) {
            color: #fff;
            border-color: rgba(255, 255, 255, .55);
        }
        .auth-card .card-body > form .btn-outline-secondary:not(.input-group .btn):hover {
            color: var(--brand-deep);
            background: #fff;
        }

        /* ---------- Header ---------- */
        .auth-header {
            position: relative;
            padding: 3.5rem 1.75rem 1.6rem;
            border-radius: 21px 21px 0 0;
            color: #fff;
            background:
                radial-gradient(120% 90% at 50% 0%, rgba(217, 180, 90, .22), transparent 60%),
                linear-gradient(160deg, rgba(29, 69, 112, .5), rgba(12, 31, 53, .6));
        }
        /* Thin gold line where the header meets the form */
        .auth-header::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold) 30%, var(--gold-soft) 50%, var(--gold) 70%, transparent);
        }
        .auth-emblem {
            position: absolute;
            top: -44px; left: 50%;
            transform: translateX(-50%);
            width: 88px; height: 88px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            color: var(--gold-soft);
            background: linear-gradient(145deg, var(--brand-light), var(--brand-deep));
            border: 3px solid var(--gold);
            box-shadow:
                0 0 0 6px rgba(255, 255, 255, .18),
                0 14px 28px -8px rgba(6, 18, 34, .7);
        }
        .auth-eyebrow {
            margin: 0 0 .35rem;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .28em;
            text-transform: uppercase;
            color: var(--gold-soft);
        }
        .auth-title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -.01em;
        }
        .auth-subtitle {
            margin: .45rem 0 0;
            font-size: .875rem;
            color: rgba(255, 255, 255, .75);
        }

        /* ---------- Form controls ---------- */
        .form-label { font-weight: 600; font-size: .85rem; color: #fff; }
        .auth-card .form-control,
        .auth-card .form-select,
        .auth-card .input-group-text,
        .auth-card .input-group .btn {
            border-color: rgba(20, 48, 79, .18);
            background-color: rgba(255, 255, 255, .85);
            padding-top: .6rem;
            padding-bottom: .6rem;
        }
        /* Bootstrap's input-group rules still square off the inner corners */
        .auth-card .form-control,
        .auth-card .form-select,
        .auth-card .input-group-text,
        .auth-card .input-group .btn { border-radius: 10px; }
        .auth-card .input-group-text { color: var(--brand-light); }
        .auth-card .form-control:focus,
        .auth-card .form-select:focus {
            background-color: #fff;
            border-color: var(--gold);
            box-shadow: 0 0 0 .2rem rgba(217, 180, 90, .28);
        }
        .auth-card .form-check-input:checked { background-color: var(--brand); border-color: var(--brand); }

        .btn-brand {
            color: #fff;
            font-weight: 600;
            letter-spacing: .02em;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--brand-light), var(--brand-deep));
            box-shadow: 0 10px 20px -10px rgba(12, 31, 53, .9);
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }
        .btn-brand:hover,
        .btn-brand:focus {
            color: #fff;
            filter: brightness(1.12);
            transform: translateY(-1px);
            box-shadow: 0 14px 24px -10px rgba(12, 31, 53, .95);
        }
        .btn-brand:active { color: #fff; transform: translateY(0); }

        @media (max-width: 575.98px) {
            .auth-card .card-body { padding: 1.5rem 1.25rem 1.5rem !important; }
            .auth-title { font-size: 1.2rem; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 @yield('column-width', 'col-md-6 col-lg-5')">

                <div class="card auth-card">
                    <div class="auth-header text-center">
                        <div class="auth-emblem"><i class="bi bi-mortarboard-fill"></i></div>
                        <p class="auth-eyebrow">Welcome</p>
                        <h1 class="auth-title">Attendance Monitoring System</h1>
                        <p class="auth-subtitle">@yield('subtitle', 'Sign in to your account')</p>
                    </div>

                    <div class="card-body p-4">

                        {{-- Flash messages --}}
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @yield('content')

                    </div>
                </div>

                <p class="text-center auth-footer small mt-3 mb-0">
                    {{ config('app.name') }} &copy; {{ date('Y') }}
                </p>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
