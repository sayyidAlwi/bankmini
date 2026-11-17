<div class="ledger-summary">
    <div class="summary-box">
        <p class="summary-box__label">Saldo Awal</p>
        <p class="summary-box__value"><x-rupiah :value="$data['opening']" /></p>
    </div>
    <div class="summary-box">
        <p class="summary-box__label">Total Debit</p>
        <p class="summary-box__value"><x-rupiah :value="$data['total_debit']" /></p>
    </div>
    <div class="summary-box">
        <p class="summary-box__label">Total Kredit</p>
        <p class="summary-box__value"><x-rupiah :value="$data['total_kredit']" /></p>
    </div>
    <div class="summary-box">
        <p class="summary-box__label">Saldo Akhir</p>
        <p class="summary-box__value"><x-rupiah :value="$data['ending']" /></p>
    </div>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th class="num">Debit</th>
                <th class="num">Kredit</th>
                <th class="num">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['tanggal']->format('d/m/Y') }}</td>
                    <td>{{ $row['keterangan'] }}</td>
                    <td class="num">
                        @if ((float) $row['debit'] > 0)
                            <x-rupiah :value="$row['debit']" />
                        @endif
                    </td>
                    <td class="num">
                        @if ((float) $row['kredit'] > 0)
                            <x-rupiah :value="$row['kredit']" />
                        @endif
                    </td>
                    <td class="num"><x-rupiah :value="$row['saldo']" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-row">Tidak ada mutasi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="num"><x-rupiah :value="$data['total_debit']" /></td>
                <td class="num"><x-rupiah :value="$data['total_kredit']" /></td>
                <td class="num"><x-rupiah :value="$data['ending']" /></td>
            </tr>
        </tfoot>
    </table>
</div>
