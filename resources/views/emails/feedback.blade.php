<!doctype html>
<html lang="pt-BR">
<body style="margin:0;padding:24px;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#2a3445">
    <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:14px;padding:24px;border:1px solid #e6e9f0">
        <p style="margin:0 0 6px;color:#155eef;font-weight:bold;font-size:13px">{{ $f->typeLabel() }} · versão beta</p>
        <p style="margin:0 0 16px;font-size:15px;color:#0e1726;white-space:pre-line">{{ $f->message }}</p>
        <table style="width:100%;font-size:13px;border-collapse:collapse">
            <tr><td style="padding:4px 0;color:#667085;width:90px">Empresa</td><td>{{ $f->company?->name ?? '—' }}</td></tr>
            <tr><td style="padding:4px 0;color:#667085">Usuário</td><td>{{ $f->user?->name }} · {{ $f->user?->email }}</td></tr>
            <tr><td style="padding:4px 0;color:#667085">Página</td><td style="word-break:break-all">{{ $f->page ?: '—' }}</td></tr>
        </table>
        <p style="margin:16px 0 0;color:#98a2b3;font-size:12px">Responda este e-mail para falar direto com quem enviou.</p>
    </div>
</body>
</html>
