<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="payModalTitle">Registrar pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><span data-fill="label"></span> · <strong data-fill="amount"></strong></p>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <label class="form-label" for="pay_paid_at">Data do pagamento</label>
                        <input type="date" name="paid_at" id="pay_paid_at" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="pay_method">Forma de pagamento</label>
                        <select name="payment_method" id="pay_method" class="form-select" required>
                            @foreach ($receivingMethods as $value => $label)
                                <option value="{{ $value }}" @selected($value === 'pix')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="pay_notes">Observação</label>
                        <input type="text" name="notes" id="pay_notes" class="form-control" maxlength="1000">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Voltar</button>
                <button type="submit" class="btn btn-primary">Confirmar pagamento</button>
            </div>
        </form>
    </div>
</div>
