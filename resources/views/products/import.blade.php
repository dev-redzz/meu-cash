@extends('layouts.app')

@section('title', 'Importar produtos')

@section('subtitle')
    <a href="{{ route('products.index') }}" class="back-link"><x-icon name="arrow-left" width="15" height="15" /> Produtos</a>
@endsection

@section('content')
<div class="form-grid has-aside">
    <div class="form-main">
        <ol class="import-steps">
            <li>
                <span class="import-step-num">1</span>
                <div>
                    <strong>Baixe o modelo</strong>
                    <p>Abra no Excel ou no Google Planilhas.</p>
                    <a href="{{ route('products.import.template') }}" class="btn btn-cta btn-cta-light btn-cta-sm"><span class="btn-cta-icon"><x-icon name="download" width="14" height="14" /></span>Modelo .csv</a>
                </div>
            </li>
            <li>
                <span class="import-step-num">2</span>
                <div>
                    <strong>Preencha</strong>
                    <p>Um produto por linha, a partir da segunda linha.</p>
                </div>
            </li>
            <li>
                <span class="import-step-num">3</span>
                <div>
                    <strong>Salve como CSV</strong>
                    <p>Excel: <em>Salvar como › CSV</em>. Google: <em>Fazer download › CSV</em>.</p>
                </div>
            </li>
        </ol>

        <form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data">
            @csrf
            <x-form-section title="Enviar planilha" icon="upload" description="Arquivo .csv de até 5 MB.">
                <label class="dropzone @error('file') is-invalid @enderror">
                    <span class="stat-icon accent-blue"><x-icon name="upload" width="18" height="18" /></span>
                    <span class="dropzone-text"><strong>Escolher planilha</strong><span data-dropzone-label>Clique aqui ou arraste o arquivo .csv</span></span>
                    <input type="file" name="file" accept=".csv,text/csv" required data-dropzone>
                </label>
                @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                    <div class="form-check form-switch mb-0 me-auto">
                        <input class="form-check-input" type="checkbox" role="switch" name="register_purchase" id="register_purchase" value="1">
                        <label class="form-check-label" for="register_purchase">Lançar o valor de compra como saída no caixa</label>
                    </div>
                    <a href="{{ route('products.index') }}" class="btn btn-light">Cancelar</a>
                    <button type="submit" class="btn btn-cta"><span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>Importar produtos</button>
                </div>
            </x-form-section>
        </form>

        @if (session('import_errors'))
            <section class="panel">
                <header class="form-section-head">
                    <span class="stat-icon accent-rose"><x-icon name="alert" width="18" height="18" /></span>
                    <div class="min-w-0">
                        <h2 class="panel-title">Linhas com problema</h2>
                        <p class="form-section-desc">Corrija e envie de novo. Nada foi importado.</p>
                    </div>
                    <span class="pill pill-danger ms-auto">{{ count(session('import_errors')) }}</span>
                </header>
                <ul class="entry-list">
                    @foreach (array_slice(session('import_errors'), 0, 100, true) as $line => $problem)
                        <li><span class="entry-date"><strong>{{ $line }}</strong>linha</span><div class="wrap">{{ $problem }}</div></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <aside class="form-aside">
        <section class="panel">
            <header class="form-section-head">
                <span class="stat-icon accent-violet"><x-icon name="list" width="18" height="18" /></span>
                <div class="min-w-0"><h2 class="panel-title">Colunas da planilha</h2></div>
            </header>
            <ul class="column-list">
                <li><code>nome</code><span class="pill pill-danger pill-plain">obrigatório</span></li>
                <li><code>preco_venda</code><span class="pill pill-danger pill-plain">obrigatório</span></li>
                <li><code>preco_compra</code><span>ex.: 45,00</span></li>
                <li><code>quantidade</code><span>padrão 1</span></li>
                <li><code>categoria</code><span>criada se não existir</span></li>
                <li><code>marca</code> <code>modelo</code><span>opcionais</span></li>
                <li><code>codigo_barras</code><span>não pode repetir</span></li>
                <li><code>data_compra</code><span>dd/mm/aaaa</span></li>
                <li><code>observacoes</code><span>opcional</span></li>
            </ul>
            <div class="panel-body border-top">
                <div class="callout mb-0"><x-icon name="info" width="18" height="18" /><div>Se qualquer linha tiver erro, nada é importado. Você nunca fica com metade da planilha cadastrada.</div></div>
            </div>
        </section>
    </aside>
</div>
@endsection
