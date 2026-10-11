<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') &mdash; {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet">

    <style>
        :root {
            --brand: #14304f;
            --brand-deep: #0c1f35;
            --brand-light: #1d4570;
            --gold: #d9b45a;
            --gold-soft: #f1dfa6;
            --ink: #12233a;
            --line: #e6ebf3;
            --shadow-sm: 0 1px 2px rgba(16, 32, 62, .04), 0 8px 18px -10px rgba(16, 32, 62, .14);
            --shadow-md: 0 2px 4px rgba(16, 32, 62, .05), 0 20px 36px -18px rgba(16, 32, 62, .28);

            --bs-body-font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --bs-body-color: var(--ink);
            --bs-link-color-rgb: 29, 69, 112;
            --bs-link-hover-color-rgb: 12, 31, 53;
        }
        /* Soft off-white canvas, so the pure white cards lift off it */
        body { background: #f6f8fb; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            background:
                radial-gradient(140% 40% at 50% 0%, rgba(217, 180, 90, .16), transparent 70%),
                linear-gradient(180deg, var(--brand-light) 0%, var(--brand) 35%, var(--brand-deep) 100%);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1030;
            overflow-y: auto;
            box-shadow: 8px 0 30px -18px rgba(6, 18, 34, .8);
        }
        .sidebar .brand {
            padding: 1.25rem 1.25rem 1.15rem;
            color: #fff;
            position: relative;
        }
        /* Thin gold line under the brand, same as the login header */
        .sidebar .brand::after {
            content: '';
            position: absolute;
            left: 1.25rem; right: 1.25rem; bottom: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold) 30%, var(--gold-soft) 50%, var(--gold) 70%, transparent);
            opacity: .7;
        }
        .sidebar .brand-emblem {
            width: 44px; height: 44px;
            flex-shrink: 0;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            color: var(--gold-soft);
            background: linear-gradient(145deg, var(--brand-light), var(--brand-deep));
            border: 2px solid var(--gold);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, .1);
        }
        .sidebar .brand-name { font-size: .95rem; font-weight: 700; letter-spacing: -.01em; }
        .sidebar .brand-role {
            font-size: .65rem;
            font-weight: 600;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--gold-soft);
        }
        .sidebar .nav { padding: .75rem .75rem 1.5rem !important; }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, .74);
            padding: .58rem .85rem;
            margin-bottom: 2px;
            font-size: .9rem;
            border-radius: 10px;
            transition: background .15s ease, color .15s ease;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, .08);
        }
        .sidebar .nav-link.active {
            color: #fff;
            font-weight: 600;
            background: linear-gradient(90deg, rgba(217, 180, 90, .28), rgba(255, 255, 255, .08));
            box-shadow: inset 3px 0 0 var(--gold);
        }
        .sidebar .nav-link.active i { color: var(--gold-soft); }
        .sidebar .nav-link i { width: 20px; }
        .sidebar .nav-heading {
            color: rgba(241, 223, 166, .6);
            font-size: .66rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .16em;
            padding: 1.1rem .85rem .4rem;
        }

        /* ---------- Main area ---------- */
        .main-wrap { margin-left: 250px; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); transition: transform .25s; }
            .sidebar.show { transform: translateX(0); }
            .main-wrap { margin-left: 0; }
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--line);
            padding: .7rem 1.5rem;
        }
        .topbar .btn-light {
            background: #f3f5f9;
            border-color: transparent;
            border-radius: 999px;
            padding: .4rem .8rem;
        }
        .topbar .btn-light:hover { background: #e8ecf3; }

        .page-heading {
            padding-left: .9rem;
            border-left: 4px solid var(--gold);
        }
        .page-heading h1 { font-weight: 700; letter-spacing: -.02em; color: var(--brand-deep); }

        /* ---------- Cards ---------- */
        .card {
            --bs-card-bg: #fff;
            --bs-card-cap-bg: transparent;
            --bs-card-border-color: var(--line);
            --bs-card-border-radius: 16px;
            --bs-card-inner-border-radius: 15px;
            --bs-card-cap-padding-y: .9rem;
            --bs-card-cap-padding-x: 1.15rem;
            box-shadow: var(--shadow-sm);
        }
        .card-header,
        .card-footer { border-color: var(--line); }
        .card .bg-light { background: #f6f8fb !important; }
        .card-header { font-weight: 600 !important; color: var(--brand-deep); }
        .card > .table-responsive:last-child { border-radius: 0 0 15px 15px; }
        .stat-card { transition: transform .18s ease, box-shadow .18s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-card .stat-icon {
            width: 50px; height: 50px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }
        .stat-card .stat-value { font-size: 1.7rem; font-weight: 700; line-height: 1.1; letter-spacing: -.02em; }
        .stat-card .stat-label { font-size: .8rem; font-weight: 500; color: #6b7a90; }

        .notif-dropdown { width: 340px; max-height: 400px; overflow-y: auto; }
        .notif-item { border-bottom: 1px solid #f0f2f5; }
        .notif-item.unread { background: #f5f9ff; }

        /* ---------- Tables ---------- */
        .table { --bs-table-border-color: var(--line); }
        .table > :not(caption) > * > * { padding: .7rem .85rem; }
        .table thead th {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            white-space: nowrap;
        }
        .table thead.table-light {
            --bs-table-bg: #f8fafc;
            --bs-table-color: #5b6b82;
            --bs-table-border-color: var(--line);
        }
        .table tbody tr:hover > * { box-shadow: inset 0 0 0 9999px rgba(20, 48, 79, .035); }

        /* ---------- Buttons, forms, badges ---------- */
        .btn { border-radius: 10px; font-weight: 500; }
        .btn-sm { border-radius: 8px; }
        .btn-brand {
            color: #fff;
            border: none;
            background: linear-gradient(135deg, var(--brand-light), var(--brand-deep));
            box-shadow: 0 8px 16px -10px rgba(12, 31, 53, .9);
            transition: transform .15s ease, filter .15s ease;
        }
        .btn-brand:hover,
        .btn-brand:focus { color: #fff; filter: brightness(1.14); transform: translateY(-1px); }
        .btn-brand:active,
        .btn-brand:disabled { color: #fff; transform: none; }

        .form-label { font-weight: 600; color: var(--brand-deep); }
        .form-control,
        .form-select { border-radius: 10px; border-color: #d6deea; }
        .form-control-sm,
        .form-select-sm { border-radius: 8px; }
        .form-control:focus,
        .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 .2rem rgba(217, 180, 90, .28);
        }
        .form-check-input:checked { background-color: var(--brand); border-color: var(--brand); }

        .badge { font-weight: 600; padding: .42em .75em; border-radius: 999px; letter-spacing: .01em; }

        /* ---------- Alerts, menus, modals, paging ---------- */
        .alert {
            border: none;
            border-radius: 12px;
            box-shadow: inset 4px 0 0 var(--bs-alert-link-color), var(--shadow-sm);
        }
        .dropdown-menu {
            --bs-dropdown-border-color: var(--line);
            --bs-dropdown-border-radius: 14px;
            --bs-dropdown-link-active-bg: var(--brand);
            box-shadow: var(--shadow-md);
        }
        .modal-content { border: none; border-radius: 18px; box-shadow: var(--shadow-md); }
        .list-group { --bs-list-group-border-color: var(--line); }
        .pagination {
            --bs-pagination-color: var(--brand-light);
            --bs-pagination-hover-color: var(--brand-deep);
            --bs-pagination-border-color: var(--line);
            --bs-pagination-border-radius: 10px;
            --bs-pagination-active-bg: var(--brand);
            --bs-pagination-active-border-color: var(--brand);
            --bs-pagination-focus-box-shadow: 0 0 0 .2rem rgba(217, 180, 90, .28);
        }

        /* ---------- Mobile refinements ---------- */
        @media (max-width: 767.98px) {
            main.p-4 { padding: 1rem !important; }
            .stat-card .stat-value { font-size: 1.35rem; }
            .table { font-size: .85rem; }
            .card-header .btn { font-size: .78rem; }
            h1.h4 { font-size: 1.15rem; }
        }

        /* Backdrop when the sidebar is open on mobile */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .4);
            z-index: 1029;
            display: none;
        }
        .sidebar-backdrop.show { display: block; }

        /* ---------- Accessibility ---------- */
        .nav-link:focus-visible,
        .btn:focus-visible,
        .form-control:focus-visible {
            outline: 2px solid var(--gold);
            outline-offset: 2px;
        }

        /* Sticky table headers on long report tables */
        .table-responsive { max-height: none; }
        .table-sticky thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* ---------- Print ---------- */
        @media print {
            .sidebar, .topbar, .no-print, .card-footer { display: none !important; }
            .main-wrap { margin-left: 0 !important; }
            body { background: #fff; }
            .card {
                border: 1px solid #ccc !important;
                box-shadow: none !important;
                break-inside: avoid;
            }
            .alert { box-shadow: none !important; border: 1px solid #ccc; }
            .page-heading { border-left: none; padding-left: 0; }
            .card-header { background: #f5f5f5 !important; }
            .badge { border: 1px solid #999; color: #000 !important; background: #fff !important; }
            .table { font-size: 11px; }
            canvas { max-height: 200px !important; }
            a[href]:after { content: none !important; }
            @page { margin: 1.5cm; size: landscape; }
        }
        .d-print-block { display: none; }
        @media print { .d-print-block { display: block !important; } }
    </style>
    @stack('styles')
</head>
<body>

    @include('partials.sidebar')

    <div class="main-wrap">

        @include('partials.navbar')

        <main class="p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div class="page-heading">
                    <h1 class="h4 mb-0">@yield('page-title')</h1>
                    <p class="text-muted small mb-0">@yield('page-subtitle')</p>
                </div>
                <div class="no-print">@yield('page-actions')</div>
            </div>

            @include('partials.alerts')

            @yield('content')
        </main>

    </div>

    @include('partials.confirm-modal')
    @include('partials.status-lock')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <script>
        // ---------- Mobile sidebar with backdrop ----------
        (function () {
            const sidebar = document.getElementById('sidebar');
            const toggle  = document.getElementById('sidebarToggle');
            if (!sidebar || !toggle) return;

            const backdrop = document.createElement('div');
            backdrop.className = 'sidebar-backdrop';
            document.body.appendChild(backdrop);

            function closeSidebar() {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            }

            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            });

            backdrop.addEventListener('click', closeSidebar);

            // Close on Escape
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeSidebar();
            });

            // Close after tapping a link
            sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
                link.addEventListener('click', closeSidebar);
            });
        })();

        // ---------- Live clock in the navbar ----------
        function tickClock() {
            const el = document.getElementById('liveClock');
            if (!el) return;
            el.textContent = new Date().toLocaleTimeString('en-PH', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
            });
        }
        tickClock();
        setInterval(tickClock, 1000);

        // ---------- Bootstrap confirm modal ----------
        (function () {
            const modalEl = document.getElementById('confirmModal');
            if (!modalEl) return;

            const modal     = new bootstrap.Modal(modalEl);
            const bodyEl    = document.getElementById('confirmModalBody');
            const titleEl   = document.getElementById('confirmModalTitle');
            const okBtn     = document.getElementById('confirmModalOk');
            const spinnerEl = document.getElementById('confirmModalSpinner');

            let pendingForm = null;

            document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.confirmed === 'yes') return;

                    event.preventDefault();

                    pendingForm         = form;
                    bodyEl.textContent  = form.dataset.confirm;
                    titleEl.textContent = form.dataset.confirmTitle || 'Please Confirm';

                    okBtn.className = 'btn ' + (form.dataset.confirmStyle || 'btn-danger');
                    spinnerEl.classList.add('d-none');
                    okBtn.disabled = false;

                    modal.show();
                });
            });

            okBtn.addEventListener('click', function () {
                if (!pendingForm) return;

                spinnerEl.classList.remove('d-none');
                okBtn.disabled = true;

                pendingForm.dataset.confirmed = 'yes';
                pendingForm.submit();
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                pendingForm = null;
            });
        })();

        // ---------- Disable submit buttons after click ----------
        document.querySelectorAll('form:not([data-confirm])').forEach(function (form) {
            form.addEventListener('submit', function () {
                const button = form.querySelector('button[type="submit"]');
                if (!button || button.dataset.noSpinner) return;

                button.disabled = true;
                button.insertAdjacentHTML('afterbegin',
                    '<span class="spinner-border spinner-border-sm me-1"></span>');

                // Re-enable if the browser restores the page from cache
                setTimeout(() => { button.disabled = false; }, 8000);
            });
        });

        // ---------- Auto-dismiss success alerts ----------
        setTimeout(function () {
            document.querySelectorAll('.alert-success.alert-dismissible').forEach(function (alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            });
        }, 6000);
    </script>

    @stack('scripts')
</body>
</html>
