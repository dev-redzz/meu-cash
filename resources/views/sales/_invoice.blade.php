@php $concluded = $sale->status === \App\Enums\SaleStatus::Concluida; @endphp
@if ($invoice || $fiscalReady || auth()->user()->isAdmin())
<div class="panel mt-3 invoice-panel">
    <div class="panel-header">
        <h2 class="panel-title">Nota fiscal</h2>
        @if ($invoice)<span class="pill {{ $invoice->pillClass() }}">{{ $invoice->label() }}</span>@endif
    </div>
    <div class="panel-body">
        @if ($invoice)
            <div class="invoice-number">{{ $invoice->numberLabel() }}</div>
            @if ($invoice->isTest())<div class="small text-warning-emphasis">Homologação: nota de teste, sem valor fiscal.</div>@endif
            @if ($invoice->access_key)
                <div class="invoice-key" title="Chave de acesso">{{ trim(chunk_split($invoice->access_key, 4, ' ')) }}</div>
            @endif
            @if ($invoice->authorized_at)<div class="small text-muted-2">Autorizada em {{ $invoice->authorized_at->format('d/m/Y H:i') }}@if ($invoice->protocol) · protocolo {{ $invoice->protocol }}@endif</div>@endif
            @if ($invoice->message && $invoice->status !== 'autorizado')
                <div class="invoice-msg {{ in_array($invoice->status, ['rejeitado', 'erro', 'denegado'], true) ? 'is-error' : '' }}">{{ $invoice->message }}</div>
            @endif

            <div class="d-flex flex-wrap gap-2 mt-3">
                @if (in_array($invoice->status, ['autorizado', 'cancelado'], true))
                    <a href="{{ route('invoices.download', [$invoice, 'pdf']) }}" target="_blank" class="btn btn-sm btn-primary"><x-icon name="receipt" width="14" height="14" /> DANFE</a>
                    <a href="{{ route('invoices.download', [$invoice, 'xml']) }}" class="btn btn-sm btn-light">XML</a>
                @endif
                @if ($invoice->status === 'pendente')
                    <form method="POST" action="{{ route('invoices.refresh', $invoice) }}">@csrf<button class="btn btn-sm btn-light">Atualizar situação</button></form>
                @endif
                @if ($invoice->canRetry() && $concluded)
                    <form method="POST" action="{{ route('invoices.store', $sale) }}" data-no-lock>@csrf<button class="btn btn-sm btn-primary">Corrigi, transmitir de novo</button></form>
                @endif
            </div>

            @if ($invoice->isAuthorized() && auth()->user()->isAdmin())
                <details class="invoice-cancel mt-3">
                    <summary>Cancelar nota</summary>
                    <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" data-confirm="Cancelar a {{ $invoice->numberLabel() }} na SEFAZ? Isso não pode ser desfeito.">
                        @csrf
                        <x-textarea name="reason" label="Motivo (mínimo 15 letras)" rows="2" required minlength="15" maxlength="255" placeholder="Ex.: Cliente desistiu da compra" />
                        <button class="btn btn-sm btn-outline-danger">Cancelar na SEFAZ</button>
                        <div class="small text-muted-2 mt-1">A maioria dos estados aceita cancelar a NFC-e só nos primeiros 30 minutos.</div>
                    </form>
                </details>
            @endif
        @elseif (! $fiscalReady)
            <p class="small text-muted-2 mb-2">Emita a NFC-e desta venda direto pelo Meu Cash.</p>
            <a href="{{ route('settings.fiscal') }}" class="btn btn-sm btn-light">Configurar nota fiscal</a>
        @elseif (! $concluded)
            <p class="small text-muted-2 mb-0">Venda cancelada: não dá para emitir nota.</p>
        @else
            @if ($fiscalIssues)
                <div class="invoice-msg is-error mb-2">
                    @foreach ($fiscalIssues as $issue)<div>{{ $issue }}</div>@endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('invoices.store', $sale) }}" data-no-lock>
                @csrf
                <button class="btn btn-cta w-100 justify-content-start" @disabled($fiscalIssues)><span class="btn-cta-icon"><x-icon name="receipt" width="16" height="16" /></span>Emitir NFC-e</button>
            </form>
        @endif
    </div>
</div>
@endif
