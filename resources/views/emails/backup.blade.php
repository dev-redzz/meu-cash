<!doctype html>
<html lang="pt-BR">
<body style="margin:0;padding:24px;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#2a3445">
    <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:14px;padding:24px;border:1px solid #e6e9f0">
        <h1 style="margin:0 0 8px;font-size:20px;color:#0e1726">Backup diário do Meu Cash</h1>
        <p style="margin:0 0 16px;color:#667085">{{ now()->format('d/m/Y \à\s H:i') }} · {{ number_format($size / 1024 / 1024, 2, ',', '.') }} MB</p>
        @if ($attach)
            <p style="margin:0 0 12px">A cópia completa do banco de dados está em anexo (<strong>{{ $file }}</strong>).</p>
            <p style="margin:0;color:#667085;font-size:13px">Guarde este e-mail. Para restaurar, descompacte o arquivo e importe o .sql no MySQL (por exemplo, pelo phpMyAdmin ou pelo Railway).</p>
        @else
            <p style="margin:0 0 12px">O backup foi gerado, mas ficou grande demais para ir em anexo.</p>
            <p style="margin:0">Baixe pelo <a href="{{ route('platform.index') }}" style="color:#155eef">Painel da plataforma</a>, no botão <strong>Baixar backup</strong>.</p>
        @endif
    </div>
</body>
</html>
