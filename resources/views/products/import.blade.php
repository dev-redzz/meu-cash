@extends('layouts.app')

@section('title', 'Importar produtos')

@section('actions')
    <a href="{{ route('products.index') }}" class="btn btn-light">Voltar para produtos</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data" class="panel">
            @csrf
            <div class="panel-header"><h2 class="panel-title">Enviar planilha</h2></div>
            <div class="panel-body">
                <ol class="small ps-3 mb-4">
                    <li class="mb-1">Baixe o <a href="{{ route('products.import.template') }}">modelo de planilha</a> e abra no Excel ou Google Planilhas.</li>
                    <li class="mb-1">Preencha um produto por linha, a partir da segunda linha.</li>
                    <li>Salve como <strong>CSV</strong> (Excel: <em>Arquivo &gt; Salvar como &gt; CSV</em>; Google: <em>Arquivo &gt; Fazer download &gt; CSV</em>) e envie abaixo.</li>
                </ol>
                <label class="form-label" for="file">Planilha (.csv)</label>
                <input type="file" name="file" id="file" accept=".csv,text/csv" class="form-control @error('file') is-invalid @enderror" required>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="register_purchase" id="register_purchase" value="1">
                    <label class="form-check-label" for="register_purchase">Lançar o valor de compra como saída no caixa</label>
                </div>
            </div>
            <div class="panel-footer text-end"><button type="submit" class="btn btn-primary">Importar produtos</button></div>
        </form>

        @if (session('import_errors'))
            <div class="panel mt-3">
                <div class="panel-header"><h2 class="panel-title">Linhas com problema</h2><span class="pill pill-danger">{{ count(session('import_errors')) }}</span></div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Linha</th><th>Problema</th></tr></thead>
                        <tbody>
                            @foreach (array_slice(session('import_errors'), 0, 100, true) as $line => $problem)
                                <tr><td>{{ $line }}</td><td class="wrap">{{ $problem }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Colunas da planilha</h2></div>
            <div class="list-row"><code>nome</code><span class="small text-muted-2">obrigatório</span></div>
            <div class="list-row"><code>preco_venda</code><span class="small text-muted-2">obrigatório · ex.: 89,90</span></div>
            <div class="list-row"><code>preco_compra</code><span class="small text-muted-2">ex.: 45,00</span></div>
            <div class="list-row"><code>quantidade</code><span class="small text-muted-2">padrão 1</span></div>
            <div class="list-row"><code>categoria</code><span class="small text-muted-2">criada se não existir</span></div>
            <div class="list-row"><code>marca</code> · <code>modelo</code><span class="small text-muted-2">opcionais</span></div>
            <div class="list-row"><code>codigo_barras</code><span class="small text-muted-2">não pode repetir</span></div>
            <div class="list-row"><code>data_compra</code><span class="small text-muted-2">dd/mm/aaaa</span></div>
            <div class="list-row"><code>observacoes</code><span class="small text-muted-2">opcional</span></div>
            <div class="panel-body border-top small text-muted-2">Se qualquer linha tiver erro, nada é importado. Assim você nunca fica com metade da planilha cadastrada.</div>
        </div>
    </div>
</div>
@endsection
