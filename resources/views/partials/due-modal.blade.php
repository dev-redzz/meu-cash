<div class="modal fade" id="dueModal" tabindex="-1" aria-labelledby="dueModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="POST" class="modal-content">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <div class="modal-head"><span class="stat-icon accent-amber"><x-icon name="calendar" width="18" height="18" /></span><h5 class="modal-title" id="dueModalTitle">Alterar vencimento</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted-2 mb-2" data-fill="label"></p>
                <label class="form-label" for="due_date_input">Novo vencimento</label>
                <input type="date" name="due_date" id="due_date_input" class="form-control" data-fill="due" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Voltar</button>
                <button type="submit" class="btn btn-primary">Salvar vencimento</button>
            </div>
        </form>
    </div>
</div>
