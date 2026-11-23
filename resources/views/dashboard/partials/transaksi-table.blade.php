<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kode</th>
                <th>Nasabah</th>
                <th>Jenis</th>
                <th class="num">Nominal</th>
                <th>Petugas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transaksi as $item)
                <tr>
                    <td>{{ $item->tanggal->format('d/m/Y H:i') }}</td>
                    <td>{{ $item->kode_transaksi }}</td>
                    <td>
                        {{ $item->rekening?->nasabah?->nama ?? 'Nasabah tidak ditemukan' }}
                        <br>
                        <span class="muted">{{ $item->rekening?->no_rekening ?? '-' }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $item->jenis === \App\Models\Transaksi::JENIS_SETORAN ? '' : 'badge--amber' }}">
                            {{ ucfirst($item->jenis) }}
                        </span>
                    </td>
                    <td class="num"><x-rupiah :value="$item->nominal" /></td>
                    <td>{{ $item->user?->name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $item->status === \App\Models\Transaksi::STATUS_BERHASIL ? '' : 'badge--danger' }}">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-row">Belum ada transaksi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
