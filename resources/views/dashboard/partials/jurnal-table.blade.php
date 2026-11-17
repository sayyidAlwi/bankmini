<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Akun</th>
                <th class="num">Debit</th>
                <th class="num">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jurnal as $item)
                @foreach ($item->detail as $detail)
                    <tr>
                        @if ($loop->first)
                            <td rowspan="{{ $item->detail->count() }}">{{ $item->tanggal->format('d/m/Y') }}</td>
                            <td rowspan="{{ $item->detail->count() }}">
                                {{ $item->keterangan }}
                                <br>
                                <span class="muted">{{ $item->user->name }}</span>
                            </td>
                        @endif

                        <td>{{ $detail->akun->kode_akun }} &mdash; {{ $detail->akun->nama_akun }}</td>
                        <td class="num">
                            @if ((float) $detail->debit > 0)
                                <x-rupiah :value="$detail->debit" />
                            @endif
                        </td>
                        <td class="num">
                            @if ((float) $detail->kredit > 0)
                                <x-rupiah :value="$detail->kredit" />
                            @endif
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="empty-row">Belum ada jurnal.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
