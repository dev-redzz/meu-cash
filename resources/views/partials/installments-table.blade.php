@php $showOrigin = $showOrigin ?? false; $showCustomer = $showCustomer ?? false; @endphp
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Parcela</th>
                @if ($showCustomer)<th>Cliente</th>@endif
                @if ($showOrigin)<th>Origem</th>@endif
                <th>Vencimento</th>
                <th>Status</th>
                <th>Pago em</th>
                <th class="num">Valor</th>
                <th class="text-end no-print">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($installments as $installment)
                <tr>
                    <td>{{ $installment->label() }}</td>
                    @if ($showCustomer)
                        <td>
                            @if ($installment->customer)
                                <a href="{{ route('customers.show', $installment->customer) }}">{{ $installment->customer->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                    @endif
                    @if ($showOrigin)
                        <td><a href="{{ $installment->originUrl() }}">{{ $installment->originLabel() }}</a></td>
                    @endif
                    <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                    <td><x-status :value="$installment->status" /></td>
                    <td>
                        {{ date_br($installment->paid_at) }}
                        @if ($installment->payment_method)<span class="small text-muted-2">· {{ $installment->payment_method->label() }}</span>@endif
                    </td>
                    <td class="num">{{ money($installment->amount) }}</td>
                    <td class="text-end no-print">
                        <div class="d-inline-flex gap-1">
                            @if ($installment->isOpen())
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payModal"
                                    data-action="{{ route('installments.pay', $installment) }}"
                                    data-label="Parcela {{ $installment->label() }} · {{ $installment->customer?->name ?? 'Sem cliente' }}"
                                    data-amount="{{ money($installment->amount) }}">Receber</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#dueModal"
                                    data-action="{{ route('installments.due-date', $installment) }}"
                                    data-label="Parcela {{ $installment->label() }} · {{ money($installment->amount) }}"
                                    data-due="{{ $installment->due_date->toDateString() }}">Vencimento</button>
                            @endif
                            @if ($installment->customer?->whatsappNumber())
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="dropdown" aria-expanded="false" title="Enviar pelo WhatsApp">
                                        <x-icon name="whatsapp" width="16" height="16" />
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @foreach (\App\Services\WhatsAppService::TYPES as $type => $typeLabel)
                                            <li><a class="dropdown-item" target="_blank" rel="noopener" href="{{ route('installments.whatsapp', [$installment, 'tipo' => $type]) }}">{{ $typeLabel }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><x-empty message="Nenhuma parcela encontrada." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
