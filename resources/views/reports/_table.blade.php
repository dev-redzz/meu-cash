<table class="table align-middle report-table">
    <thead>
        <tr>
            @foreach ($report['columns'] as $i => $column)
                <th class="{{ ($report['align'][$i] ?? null) === 'end' ? 'num' : '' }}">{{ $column }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($report['rows'] as $row)
            <tr>
                @foreach ($row as $i => $cell)
                    <td class="{{ ($report['align'][$i] ?? null) === 'end' ? 'num' : '' }}">{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($report['columns']) }}" class="text-center text-muted-2 py-4">Nenhum registro no período.</td></tr>
        @endforelse
    </tbody>
</table>
