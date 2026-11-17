<a class="nav__link @if (request()->routeIs('laporan.kas')) is-active @endif" href="{{ route('laporan.kas') }}">
    <span class="nav__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M3 7h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/><path d="M3 7V5a1 1 0 0 1 1-1h12"/><circle cx="17" cy="13" r="1"/></svg>
    </span>
    Laporan Kas
</a>

<a class="nav__link @if (request()->routeIs('laporan.simpanan')) is-active @endif" href="{{ route('laporan.simpanan') }}">
    <span class="nav__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M4 10c0-3.3 3.6-6 8-6s8 2.7 8 6-3.6 6-8 6-8-2.7-8-6z"/><path d="M4 10v4c0 3.3 3.6 6 8 6s8-2.7 8-6v-4"/><path d="M9 10h6"/></svg>
    </span>
    Laporan Simpanan
</a>

<a class="nav__link @if (request()->routeIs('laporan.jurnal')) is-active @endif" href="{{ route('laporan.jurnal') }}">
    <span class="nav__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16"/><path d="M8 8h8"/><path d="M8 12h5"/></svg>
    </span>
    Jurnal Umum
</a>

<a class="nav__link @if (request()->routeIs('laporan.buku-besar')) is-active @endif" href="{{ route('laporan.buku-besar') }}">
    <span class="nav__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
    </span>
    Buku Besar
</a>
