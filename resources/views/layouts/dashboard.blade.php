<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name', 'BankMini') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800" rel="stylesheet">

    <style>
        :root {
            --bg: #f2f5f3;
            --surface: #ffffff;
            --surface-muted: #f7faf8;
            --ink: #172a25;
            --muted: #6b7c74;
            --line: #e2eae5;
            --brand: #123d32;
            --brand-2: #1c5a43;
            --accent: #3d8b6b;
            --mint: #c9efda;
            --danger: #b3453e;
            --radius: 16px;
            --shadow: 0 18px 40px -28px rgb(18 61 50 / 35%);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Nunito', ui-sans-serif, system-ui, sans-serif;
            font-size: 15px;
            line-height: 1.55;
        }

        a {
            color: inherit;
        }

        h1, h2, h3, p {
            margin: 0;
        }

        .shell {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: sticky;
            top: 0;
            display: flex;
            flex: 0 0 264px;
            flex-direction: column;
            gap: 2rem;
            height: 100vh;
            padding: 1.5rem 1.15rem;
            overflow-y: auto;
            background:
                radial-gradient(ellipse at 110% 0%, rgb(61 139 107 / 34%), transparent 55%),
                var(--brand);
            color: #fff;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.25rem 0.4rem;
            color: inherit;
            text-decoration: none;
        }

        .brand__mark {
            display: grid;
            flex: 0 0 auto;
            width: 2.5rem;
            height: 2.5rem;
            place-items: center;
            border: 1px solid rgb(255 255 255 / 22%);
            border-radius: 0.8rem;
            background: rgb(255 255 255 / 10%);
            color: var(--mint);
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .brand__text {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
        }

        .brand__text strong {
            font-size: 1.05rem;
            letter-spacing: -0.02em;
        }

        .brand__text small {
            color: rgb(255 255 255 / 55%);
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .nav__label {
            padding: 0 0.65rem;
            margin-top: 0.7rem;
            margin-bottom: 0.35rem;
            color: rgb(255 255 255 / 40%);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .nav__label:first-child {
            margin-top: 0;
        }

        .nav__link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.65rem 0.7rem;
            border-radius: 0.7rem;
            color: rgb(255 255 255 / 72%);
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 140ms ease, color 140ms ease;
        }

        .nav__link:hover {
            background: rgb(255 255 255 / 8%);
            color: #fff;
        }

        .nav__link.is-active {
            background: rgb(201 239 218 / 14%);
            box-shadow: inset 0 0 0 1px rgb(201 239 218 / 22%);
            color: #eafff3;
        }

        .nav__icon {
            display: grid;
            flex: 0 0 auto;
            width: 1.15rem;
            height: 1.15rem;
            place-items: center;
        }

        .nav__icon svg {
            width: 100%;
            height: 100%;
            stroke: currentColor;
            stroke-width: 1.7;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .sidebar__foot {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin-top: auto;
            padding-top: 1.25rem;
            border-top: 1px solid rgb(255 255 255 / 10%);
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 0;
            padding: 0.2rem 0.35rem;
        }

        .avatar {
            display: grid;
            flex: 0 0 auto;
            width: 2.3rem;
            height: 2.3rem;
            place-items: center;
            border-radius: 50%;
            background: var(--mint);
            color: #123d32;
            font-size: 0.9rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .user-chip__meta {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.3;
        }

        .user-chip__name {
            overflow: hidden;
            font-size: 0.9rem;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-chip__role {
            color: rgb(255 255 255 / 50%);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* Main */
        .main {
            display: flex;
            flex: 1;
            flex-direction: column;
            min-width: 0;
        }

        .topbar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.5rem;
            width: 100%;
            max-width: 1400px;
            padding: 2rem 2.25rem 1.25rem;
            margin: 0 auto;
        }

        .topbar__eyebrow {
            margin-bottom: 0.2rem;
            color: var(--accent);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .topbar__title {
            font-size: clamp(1.5rem, 2.4vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.15;
        }

        .topbar__meta {
            flex: 0 0 auto;
            padding: 0.5rem 0.85rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface);
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .content {
            width: 100%;
            max-width: 1400px;
            padding: 0 2.25rem 2.75rem;
            margin: 0 auto;
        }

        .alert {
            padding: 0.85rem 1rem;
            margin-bottom: 1.25rem;
            border: 1px solid #c8e6d5;
            border-radius: 0.85rem;
            background: #effaf3;
            color: #1c5a43;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Hero */
        .hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.75rem 2rem;
            margin-bottom: 1.5rem;
            overflow: hidden;
            border-radius: calc(var(--radius) + 4px);
            background:
                radial-gradient(ellipse at 100% 120%, rgb(201 239 218 / 28%), transparent 52%),
                linear-gradient(120deg, #123d32, #1c5a43);
            box-shadow: var(--shadow);
            color: #fff;
        }

        .hero__eyebrow {
            margin-bottom: 0.45rem;
            color: var(--mint);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .hero__title {
            font-size: clamp(1.3rem, 2.2vw, 1.7rem);
            font-weight: 800;
            letter-spacing: -0.035em;
        }

        .hero__text {
            max-width: 46ch;
            margin-top: 0.5rem;
            color: rgb(255 255 255 / 66%);
            font-size: 0.9rem;
        }

        .hero__badge {
            flex: 0 0 auto;
            padding: 0.55rem 0.9rem;
            border: 1px solid rgb(201 239 218 / 30%);
            border-radius: 999px;
            background: rgb(201 239 218 / 10%);
            color: var(--mint);
            font-size: 0.75rem;
            font-weight: 800;
        }

        /* Stats */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            position: relative;
            padding: 1.25rem 1.35rem;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .stat-card::after {
            position: absolute;
            top: 0;
            right: 0;
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 0 0 0 100%;
            background: var(--tone-soft, #eef6f1);
            content: '';
        }

        .stat-card__label {
            position: relative;
            z-index: 1;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .stat-card__value {
            position: relative;
            z-index: 1;
            margin-top: 0.55rem;
            color: var(--ink);
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.045em;
            line-height: 1.1;
        }

        .stat-card__hint {
            position: relative;
            z-index: 1;
            margin-top: 0.4rem;
            color: var(--muted);
            font-size: 0.75rem;
        }

        .stat-card--green {
            --tone-soft: #e7f4ec;
        }

        .stat-card--blue {
            --tone-soft: #e8effa;
        }

        .stat-card--amber {
            --tone-soft: #fbf1df;
        }

        .stat-card--rose {
            --tone-soft: #fbeae9;
        }

        /* Panels */
        .panel-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .panel__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.35rem 0.9rem;
            border-bottom: 1px solid var(--line);
        }

        .panel__title {
            font-size: 0.98rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .panel__subtitle {
            margin-top: 0.15rem;
            color: var(--muted);
            font-size: 0.78rem;
        }

        .panel__body {
            padding: 1.35rem;
        }

        .panel__link {
            color: var(--brand-2);
            font-size: 0.8rem;
            font-weight: 800;
            text-decoration: none;
        }

        .panel__link:hover {
            text-decoration: underline;
        }

        /* Quick links */
        .quick-list {
            display: grid;
            gap: 0.6rem;
        }

        .quick-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 0.95rem;
            border: 1px solid var(--line);
            border-radius: 0.9rem;
            background: var(--surface-muted);
            text-decoration: none;
            transition: border-color 140ms ease, transform 140ms ease, background-color 140ms ease;
        }

        .quick-card:hover {
            border-color: #cddfd5;
            background: #fff;
            transform: translateY(-1px);
        }

        .quick-card__title {
            display: block;
            font-size: 0.88rem;
            font-weight: 800;
        }

        .quick-card__desc {
            display: block;
            margin-top: 0.1rem;
            color: var(--muted);
            font-size: 0.76rem;
        }

        .quick-card__arrow {
            color: var(--accent);
            font-size: 1.1rem;
            font-weight: 800;
        }

        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 12rem;
            padding: 2rem 1.5rem;
            text-align: center;
        }

        .empty-state__icon {
            display: grid;
            width: 3rem;
            height: 3rem;
            margin-bottom: 0.85rem;
            place-items: center;
            border-radius: 50%;
            background: #eef6f1;
            color: var(--accent);
            font-size: 1.1rem;
            font-weight: 800;
        }

        .empty-state__title {
            color: var(--ink);
            font-size: 0.92rem;
            font-weight: 800;
        }

        .empty-state__desc {
            max-width: 38ch;
            margin-top: 0.3rem;
            color: var(--muted);
            font-size: 0.8rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2.5rem;
            padding: 0.55rem 1rem;
            border: 0;
            border-radius: 0.7rem;
            background: var(--brand-2);
            color: #fff;
            font: inherit;
            font-size: 0.85rem;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 140ms ease;
        }

        .btn:hover {
            background: #164a37;
        }

        .btn--ghost {
            border: 1px solid rgb(255 255 255 / 16%);
            background: rgb(255 255 255 / 6%);
            color: rgb(255 255 255 / 78%);
        }

        .btn--ghost:hover {
            background: rgb(255 255 255 / 12%);
            color: #fff;
        }

        .btn--block {
            width: 100%;
        }

        .btn:focus-visible,
        .nav__link:focus-visible,
        .quick-card:focus-visible,
        .panel__link:focus-visible {
            outline: 3px solid #7ab89a;
            outline-offset: 2px;
        }

        /* Utility */
        .stack {
            display: grid;
            gap: 1rem;
        }

        .muted {
            color: var(--muted);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 0.28rem 0.65rem;
            border-radius: 999px;
            background: #eef6f1;
            color: var(--brand-2);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        /* Tables */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        .data-table th {
            padding: 0.7rem 0.75rem;
            border-bottom: 1px solid var(--line);
            color: var(--muted);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eef3f0;
            vertical-align: middle;
        }

        .data-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .data-table tbody tr:hover {
            background: #fafcfb;
        }

        .data-table tfoot td {
            border-top: 1px solid var(--line);
            border-bottom: 0;
            font-weight: 800;
        }

        .num {
            font-variant-numeric: tabular-nums;
            text-align: right;
            white-space: nowrap;
        }

        .empty-row {
            padding: 1.5rem !important;
            color: var(--muted);
            text-align: center;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.22rem 0.55rem;
            border-radius: 999px;
            background: #eef6f1;
            color: var(--brand-2);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge--danger {
            background: #fbeae9;
            color: #a23c36;
        }

        .badge--muted {
            background: #f0f3f1;
            color: #66776f;
        }

        .badge--amber {
            background: #fbf1df;
            color: #8a6116;
        }

        .badge--blue {
            background: #e8effa;
            color: #33578c;
        }

        /* Notices */
        .notice {
            padding: 0.75rem 0.9rem;
            border: 1px solid var(--line);
            border-radius: 0.7rem;
            background: var(--surface-muted);
            color: var(--ink);
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .notice + .notice,
        .notice + form,
        form + .notice {
            margin-top: 0.9rem;
        }

        .notice--success {
            border-color: #cbe6d9;
            background: #eef8f2;
            color: #1d5a43;
        }

        .notice--danger {
            border-color: #f0cdca;
            background: #fdf0ef;
            color: #9b3b35;
        }

        .notice--info {
            border-color: #cfddf0;
            background: #f1f6fc;
            color: #2f537f;
        }

        .step-list {
            margin: 0;
            padding-left: 1.2rem;
            color: var(--ink);
            font-size: 0.85rem;
            line-height: 1.7;
        }

        .step-list li + li {
            margin-top: 0.35rem;
        }

        .error-list {
            margin: 0;
            padding-left: 1rem;
            color: var(--danger);
            font-size: 0.76rem;
            line-height: 1.5;
        }

        /* Forms */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.6rem;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            min-width: 0;
        }

        .label {
            color: #344840;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .input,
        .select,
        textarea.input {
            width: 100%;
            min-height: 2.5rem;
            padding: 0.5rem 0.7rem;
            border: 1px solid var(--line);
            border-radius: 0.6rem;
            outline: none;
            background: #fff;
            color: var(--ink);
            font: inherit;
            font-size: 0.85rem;
            transition: border-color 140ms ease, box-shadow 140ms ease;
        }

        .input:focus,
        .select:focus,
        textarea.input:focus {
            border-color: #34795f;
            box-shadow: 0 0 0 3px rgb(52 121 95 / 12%);
        }

        .input.is-invalid,
        .select.is-invalid {
            border-color: #c7524b;
        }

        textarea.input {
            min-height: 5.5rem;
            resize: vertical;
        }

        .error-text {
            color: var(--danger);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .field--full {
            grid-column: 1 / -1;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.6rem;
            margin-top: 1.35rem;
        }

        .btn--secondary {
            border: 1px solid #d8e8df;
            background: #eef6f1;
            color: var(--brand-2);
        }

        .btn--secondary:hover {
            background: #e2f1e9;
        }

        .btn--small {
            min-height: 2rem;
            padding: 0.35rem 0.7rem;
            border-radius: 0.55rem;
            font-size: 0.75rem;
        }

        .link {
            color: var(--brand-2);
            font-weight: 800;
            text-decoration: none;
        }

        .link:hover {
            text-decoration: underline;
        }

        /* Pagination */
        .pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.35rem 1.25rem;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .pagination__links {
            display: flex;
            gap: 0.4rem;
        }

        .pagination a {
            padding: 0.4rem 0.7rem;
            border: 1px solid var(--line);
            border-radius: 0.55rem;
            background: #fff;
            color: var(--brand-2);
            font-size: 0.78rem;
            font-weight: 800;
            text-decoration: none;
        }

        .pagination a:hover {
            background: #eef6f1;
        }

        .pagination__page {
            padding: 0.4rem 0.7rem;
            border-radius: 0.55rem;
            background: #f7faf8;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 800;
        }

        /* Detail lists */
        .detail-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 0.9rem 1.25rem;
        }

        .detail-list dt {
            color: var(--muted);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .detail-list dd {
            margin: 0.2rem 0 0;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .section-gap {
            margin-top: 1rem;
        }

        .ledger-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .summary-box {
            padding: 0.85rem 1rem;
            border: 1px solid var(--line);
            border-radius: 0.8rem;
            background: var(--surface-muted);
        }

        .summary-box__label {
            color: var(--muted);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .summary-box__value {
            margin-top: 0.25rem;
            font-size: 1.05rem;
            font-weight: 800;
        }

        @media (max-width: 1080px) {
            .panel-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 860px) {
            .shell {
                flex-direction: column;
            }

            .sidebar {
                position: static;
                height: auto;
                gap: 1rem;
                padding: 1rem 1.15rem;
            }

            .nav {
                flex-direction: row;
                gap: 0.4rem;
                padding-bottom: 0.2rem;
                overflow-x: auto;
            }

            .nav__label {
                display: none;
            }

            .nav__link {
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .sidebar__foot {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                padding-top: 0.9rem;
            }

            .sidebar__foot .btn {
                width: auto;
            }

            .topbar {
                padding: 1.5rem 1.25rem 1rem;
            }

            .content {
                padding: 0 1.25rem 2rem;
            }

            .hero {
                align-items: flex-start;
                flex-direction: column;
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 520px) {
            .topbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 0.75rem;
            }

            .stat-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    @php
        $user = auth()->user();
        $dashboardRoute = $user?->dashboardRouteName();
        $roleLabel = $user?->roleLabel() ?? 'Pengguna';
    @endphp

    <div class="shell">
        <aside class="sidebar">
            <a class="brand" href="{{ $dashboardRoute ? route($dashboardRoute) : url('/') }}">
                <span class="brand__mark" aria-hidden="true">BM</span>
                <span class="brand__text">
                    <strong>BankMini</strong>
                    <small>Bank Mini SMKN 25</small>
                </span>
            </a>

            <nav class="nav" aria-label="Navigasi utama">
                @if ($user?->hasRole(\App\Models\User::ROLE_ADMIN))
                    <a class="nav__link @if (request()->routeIs('admin.dashboard')) is-active @endif" href="{{ route('admin.dashboard') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        </span>
                        Dashboard
                    </a>

                    <p class="nav__label">Operasional</p>
                    <a class="nav__link @if (request()->routeIs('nasabah.*')) is-active @endif" href="{{ route('nasabah.index') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 8.5a2.8 2.8 0 1 1 0 5.6"/><path d="M17 20a4.8 4.8 0 0 0-2-3.8"/></svg>
                        </span>
                        Nasabah
                    </a>
                    <a class="nav__link @if (request()->routeIs('transaksi.*')) is-active @endif" href="{{ route('transaksi.index') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M7 7v10a3 3 0 0 0 3 3h4a3 3 0 0 0 3-3V7"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M10 12h4"/></svg>
                        </span>
                        Transaksi
                    </a>
                    <a class="nav__link @if (request()->routeIs('shift.index') || request()->routeIs('shift.show')) is-active @endif" href="{{ route('shift.index') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M3 10h18"/><path d="M12 14v3l2 1"/></svg>
                        </span>
                        Laporan Shift
                    </a>

                    <p class="nav__label">Laporan</p>
                    @include('partials.nav-laporan')

                    <p class="nav__label">Sistem</p>
                    <a class="nav__link @if (request()->routeIs('admin.impor.*')) is-active @endif" href="{{ route('admin.impor.create') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                        </span>
                        Impor Data
                    </a>
                    <a class="nav__link @if (request()->routeIs('admin.users.*')) is-active @endif" href="{{ route('admin.users.index') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.2"/><path d="M5 20a7 7 0 0 1 14 0"/><path d="M19 5v4"/><path d="M17 7h4"/></svg>
                        </span>
                        Pengguna
                    </a>
                @elseif ($user?->hasRole(\App\Models\User::ROLE_SUPERVISOR))
                    <a class="nav__link @if (request()->routeIs('supervisor.dashboard')) is-active @endif" href="{{ route('supervisor.dashboard') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        </span>
                        Dashboard
                    </a>

                    <p class="nav__label">Operasional</p>
                    <a class="nav__link @if (request()->routeIs('transaksi.*')) is-active @endif" href="{{ route('transaksi.index') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M7 7v10a3 3 0 0 0 3 3h4a3 3 0 0 0 3-3V7"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M10 12h4"/></svg>
                        </span>
                        Transaksi
                    </a>

                    <p class="nav__label">Laporan</p>
                    @include('partials.nav-laporan')
                    <a class="nav__link @if (request()->routeIs('shift.index') || request()->routeIs('shift.show')) is-active @endif" href="{{ route('shift.index') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M3 10h18"/><path d="M12 14v3l2 1"/></svg>
                        </span>
                        Laporan Shift
                    </a>
                @elseif ($user?->hasRole(\App\Models\User::ROLE_TELLER))
                    <a class="nav__link @if (request()->routeIs('teller.dashboard')) is-active @endif" href="{{ route('teller.dashboard') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        </span>
                        Dashboard
                    </a>

                    <p class="nav__label">Operasional</p>
                    <a class="nav__link @if (request()->routeIs('transaksi.create')) is-active @endif" href="{{ route('transaksi.create') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        </span>
                        Transaksi Baru
                    </a>
                    <a class="nav__link @if (request()->routeIs('transaksi.index')) is-active @endif" href="{{ route('transaksi.index') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M7 7v10a3 3 0 0 0 3 3h4a3 3 0 0 0 3-3V7"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M10 12h4"/></svg>
                        </span>
                        Riwayat Transaksi
                    </a>
                    <a class="nav__link @if (request()->routeIs('nasabah.*')) is-active @endif" href="{{ route('nasabah.index') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 8.5a2.8 2.8 0 1 1 0 5.6"/><path d="M17 20a4.8 4.8 0 0 0-2-3.8"/></svg>
                        </span>
                        Data Nasabah
                    </a>
                    <a class="nav__link @if (request()->routeIs('shift.saya') || request()->routeIs('shift.mulai') || request()->routeIs('shift.selesai')) is-active @endif" href="{{ route('shift.saya') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        </span>
                        Shift Saya
                    </a>
                @elseif ($user?->hasRole(\App\Models\User::ROLE_CS))
                    <a class="nav__link @if (request()->routeIs('cs.dashboard')) is-active @endif" href="{{ route('cs.dashboard') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        </span>
                        Dashboard
                    </a>

                    <p class="nav__label">Operasional</p>
                    <a class="nav__link @if (request()->routeIs('nasabah.index') || request()->routeIs('nasabah.show') || request()->routeIs('nasabah.edit')) is-active @endif" href="{{ route('nasabah.index') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 8.5a2.8 2.8 0 1 1 0 5.6"/><path d="M17 20a4.8 4.8 0 0 0-2-3.8"/></svg>
                        </span>
                        Nasabah
                    </a>
                    <a class="nav__link @if (request()->routeIs('nasabah.create')) is-active @endif" href="{{ route('nasabah.create') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        </span>
                        Tambah Nasabah
                    </a>
                @else
                    <a class="nav__link @if (request()->routeIs('akuntansi.dashboard')) is-active @endif" href="{{ route('akuntansi.dashboard') }}">
                        <span class="nav__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        </span>
                        Dashboard
                    </a>

                    <p class="nav__label">Laporan</p>
                    @include('partials.nav-laporan')
                @endif
            </nav>

            <div class="sidebar__foot">
                <div class="user-chip">
                    <span class="avatar" aria-hidden="true">{{ mb_substr($user?->name ?? '?', 0, 1) }}</span>
                    <span class="user-chip__meta">
                        <span class="user-chip__name">{{ $user?->name ?? 'Pengguna' }}</span>
                        <span class="user-chip__role">{{ $roleLabel }}</span>
                    </span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn--ghost btn--block">Keluar</button>
                </form>
            </div>
        </aside>

        <div class="main">
            <header class="topbar">
                <div>
                    <p class="topbar__eyebrow">@yield('eyebrow', 'BankMini')</p>
                    <h1 class="topbar__title">@yield('page-title', 'Dashboard')</h1>
                </div>

                <div class="topbar__meta">
                    {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                    @if (! empty($shiftMingguIni))
                        &middot; Petugas: {{ $shiftMingguIni->nama }}
                    @endif
                </div>
            </header>

            <main class="content">
                @if (session('status'))
                    <div class="alert" role="status">{{ session('status') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
