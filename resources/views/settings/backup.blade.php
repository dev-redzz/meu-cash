@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Backups gerados</h2>
                <form method="POST" action="{{ route('backup.store') }}">
                    @csrf
                    <button class="btn btn-sm btn-primary" @disabled(! $supported)>Gerar backup agora</button>
                </form>
            </div>
            @unless ($supported)
                <div class="panel-body"><div class="alert alert-warning mb-0">O backup pelo sistema funciona com o MySQL do XAMPP. Verifique o DB_CONNECTION no arquivo .env.</div></div>
            @endunless
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Arquivo</th><th>Data</th><th class="num">Tamanho</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($backups as $backup)
                            <tr>
                                <td class="text-break">{{ $backup['name'] }}</td>
                                <td>{{ $backup['date'] }}</td>
                                <td class="num">{{ number_format($backup['size'] / 1024, 0, ',', '.') }} KB</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('backup.download', $backup['name']) }}" class="btn btn-sm btn-light">Baixar</a>
                                        <form method="POST" action="{{ route('backup.destroy', $backup['name']) }}" data-confirm="Excluir este backup?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty message="Nenhum backup gerado ainda." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="panel-body border-top small text-muted-2">Os arquivos ficam em <code>storage/app/backups</code>. Copie-os para um pendrive ou nuvem com frequência.</div>
        </div>
    </div>
    <div class="col-lg-5">
        <form method="POST" action="{{ route('backup.restore') }}" enctype="multipart/form-data" class="panel mb-3" data-confirm="Restaurar vai substituir TODOS os dados atuais pelos do arquivo. Continuar?">
            @csrf
            <div class="panel-header"><h2 class="panel-title">Restaurar backup</h2></div>
            <div class="panel-body">
                <input type="file" name="backup" accept=".sql" class="form-control mb-2 @error('backup') is-invalid @enderror" required>
                @error('backup')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-check mb-3">
                    <input class="form-check-input @error('confirm') is-invalid @enderror" type="checkbox" name="confirm" id="confirm" value="1">
                    <label class="form-check-label small" for="confirm">Entendo que os dados atuais serão substituídos</label>
                </div>
                <button class="btn btn-outline-danger" @disabled(! $supported)>Restaurar</button>
                <p class="small text-muted-2 mt-2 mb-0">Gere um backup antes de restaurar. Depois da restauração será preciso entrar de novo.</p>
            </div>
        </form>
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Pelo phpMyAdmin</h2></div>
            <div class="panel-body small">
                <p class="mb-2"><strong>Exportar:</strong> abra <code>http://localhost/phpmyadmin</code>, clique no banco <code>{{ $database }}</code>, aba <em>Exportar</em>, formato SQL, <em>Executar</em>.</p>
                <p class="mb-0"><strong>Importar:</strong> selecione o banco <code>{{ $database }}</code>, aba <em>Importar</em>, escolha o arquivo .sql e clique em <em>Importar</em>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
