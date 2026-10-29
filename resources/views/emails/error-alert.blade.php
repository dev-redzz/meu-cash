<!doctype html>
<html lang="pt-BR">
<body style="margin:0;padding:24px;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#2a3445">
    <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:14px;padding:24px;border:1px solid #e6e9f0">
        <h1 style="margin:0 0 6px;font-size:19px;color:#b42318">Aconteceu um erro no Meu Cash</h1>
        <p style="margin:0 0 16px;color:#667085;font-size:13px">{{ $r->last_seen_at->format('d/m/Y H:i:s') }} · {{ $r->count === 1 ? 'primeira vez' : $r->count.' vezes até agora' }}</p>
        <p style="margin:0 0 12px;font-size:15px;color:#0e1726"><strong>{{ $r->message }}</strong></p>
        <table style="width:100%;font-size:13px;border-collapse:collapse">
            <tr><td style="padding:4px 0;color:#667085;width:90px">Tipo</td><td>{{ $r->shortClass() }}</td></tr>
            <tr><td style="padding:4px 0;color:#667085">Onde</td><td>{{ $r->shortFile() }}:{{ $r->line }}</td></tr>
            <tr><td style="padding:4px 0;color:#667085">Página</td><td style="word-break:break-all">{{ $r->method }} {{ $r->url }}</td></tr>
            <tr><td style="padding:4px 0;color:#667085">Empresa</td><td>{{ $r->company?->name ?? '—' }}</td></tr>
            <tr><td style="padding:4px 0;color:#667085">Usuário</td><td>{{ $r->user?->email ?? '—' }}</td></tr>
        </table>
        <p style="margin:18px 0 0"><a href="{{ route('platform.errors') }}" style="display:inline-block;background:#155eef;color:#fff;text-decoration:none;padding:10px 16px;border-radius:10px;font-weight:bold">Ver no painel</a></p>
        <p style="margin:14px 0 0;color:#98a2b3;font-size:12px">Você recebe no máximo um aviso por hora para o mesmo erro.</p>
    </div>
</body>
</html>
