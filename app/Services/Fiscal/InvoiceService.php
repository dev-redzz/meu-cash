<?php

namespace App\Services\Fiscal;

use App\Enums\SaleStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * NFC-e (nota fiscal do consumidor) das vendas, emitida pela Nuvem Fiscal.
 * Suporta empresas do Simples Nacional e MEI.
 */
class InvoiceService
{
    public const UF = [
        'RO' => 11, 'AC' => 12, 'AM' => 13, 'RR' => 14, 'PA' => 15, 'AP' => 16, 'TO' => 17,
        'MA' => 21, 'PI' => 22, 'CE' => 23, 'RN' => 24, 'PB' => 25, 'PE' => 26, 'AL' => 27, 'SE' => 28, 'BA' => 29,
        'MG' => 31, 'ES' => 32, 'RJ' => 33, 'SP' => 35, 'PR' => 41, 'SC' => 42, 'RS' => 43,
        'MS' => 50, 'MT' => 51, 'GO' => 52, 'DF' => 53,
    ];

    public const CRT = [
        '1' => 'Simples Nacional',
        '4' => 'MEI (Microempreendedor Individual)',
    ];

    public const CSOSN = [
        '102' => '102 · Tributada pelo Simples, sem crédito',
        '103' => '103 · Isenta do ICMS no Simples (faixa de receita)',
        '300' => '300 · Imune',
        '400' => '400 · Não tributada',
        '500' => '500 · ICMS já cobrado por substituição tributária',
    ];

    public const ORIGIN = [
        0 => '0 · Nacional',
        1 => '1 · Estrangeira, importação direta',
        2 => '2 · Estrangeira, comprada no mercado interno',
        3 => '3 · Nacional, mais de 40% de conteúdo importado',
        4 => '4 · Nacional, processos produtivos básicos',
        5 => '5 · Nacional, até 40% de conteúdo importado',
        6 => '6 · Estrangeira, importação direta sem similar nacional',
        7 => '7 · Estrangeira, mercado interno sem similar nacional',
        8 => '8 · Nacional, mais de 70% de conteúdo importado',
    ];

    /** Formas de pagamento do Meu Cash → código da SEFAZ (tPag). */
    private const TPAG = [
        'dinheiro' => '01',
        'credito' => '03',
        'debito' => '04',
        'pix' => '17',
        'transferencia' => '18',
        'outro' => '99',
    ];

    public function __construct(
        private readonly NuvemFiscal $api,
        private readonly NotificationService $notifications,
    ) {
    }

    /* ---------- Configuração da loja ---------- */

    public function platformReady(): bool
    {
        return $this->api->configured();
    }

    public function settings(): array
    {
        $cert = Setting::get('fiscal_certificate');

        return [
            'cnpj' => Setting::get('fiscal_cnpj'),
            'ie' => Setting::get('fiscal_ie'),
            'legal_name' => Setting::get('fiscal_legal_name'),
            'trade_name' => Setting::get('fiscal_trade_name'),
            'crt' => Setting::get('fiscal_crt', '1'),
            'phone' => Setting::get('fiscal_phone'),
            'email' => Setting::get('fiscal_email'),
            'cep' => Setting::get('fiscal_cep'),
            'street' => Setting::get('fiscal_street'),
            'number' => Setting::get('fiscal_number'),
            'complement' => Setting::get('fiscal_complement'),
            'district' => Setting::get('fiscal_district'),
            'city' => Setting::get('fiscal_city'),
            'uf' => Setting::get('fiscal_uf'),
            'city_code' => Setting::get('fiscal_city_code'),
            'environment' => Setting::get('fiscal_environment', 'homologacao'),
            'series' => (int) Setting::get('fiscal_series', 1),
            'next_number' => $this->nextNumber(Setting::get('fiscal_environment', 'homologacao')),
            'csc_id' => Setting::get('fiscal_csc_id'),
            'has_csc' => (bool) Setting::get('fiscal_csc'),
            'nature' => Setting::get('fiscal_nature', 'Venda de mercadoria'),
            'csosn' => Setting::get('fiscal_csosn', '102'),
            'cfop' => Setting::get('fiscal_cfop', '5102'),
            'auto' => Setting::get('fiscal_auto', '0') === '1',
            'registered_at' => Setting::get('fiscal_registered_at'),
            'certificate' => $cert ? json_decode($cert, true) : null,
        ];
    }

    /** O que ainda falta para emitir. Vazio = pronto. */
    public function missing(?array $s = null): array
    {
        $s ??= $this->settings();
        $missing = [];

        if (! $this->platformReady()) {
            $missing[] = 'A plataforma ainda não ligou a nota fiscal (falta a conta da Nuvem Fiscal).';
        }
        if (! $s['cnpj'] || ! $s['ie'] || ! $s['legal_name']) {
            $missing[] = 'Dados da empresa (CNPJ, Inscrição Estadual e razão social).';
        }
        if (! $s['street'] || ! $s['city_code'] || ! $s['uf']) {
            $missing[] = 'Endereço completo da empresa.';
        }
        if (! $s['csc_id'] || ! $s['has_csc']) {
            $missing[] = 'Código de segurança do contribuinte (CSC) da SEFAZ.';
        }
        if (! $s['certificate']) {
            $missing[] = 'Certificado digital A1.';
        }
        if (! $s['registered_at']) {
            $missing[] = 'Salvar os dados para cadastrar a empresa na Nuvem Fiscal.';
        }

        return $missing;
    }

    public function ready(): bool
    {
        return $this->missing() === [];
    }

    public function autoEnabled(): bool
    {
        return Setting::get('fiscal_auto', '0') === '1' && $this->ready();
    }

    public function saveSettings(array $data): void
    {
        $data['cnpj'] = preg_replace('/\D/', '', $data['cnpj']);
        $data['cep'] = preg_replace('/\D/', '', $data['cep']);
        $data['ie'] = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $data['ie']));

        if (empty($data['city_code']) && $this->platformReady()) {
            try {
                $cep = $this->api->cep($data['cep']);
                $data['city_code'] = $cep['codigo_ibge'] ?? null;
                $data['city'] = $data['city'] ?: ($cep['municipio'] ?? null);
                $data['uf'] = $data['uf'] ?: ($cep['uf'] ?? null);
            } catch (\Throwable) {
                // O código do município pode ser digitado à mão.
            }
        }

        $map = [
            'cnpj' => 'fiscal_cnpj', 'ie' => 'fiscal_ie', 'legal_name' => 'fiscal_legal_name', 'trade_name' => 'fiscal_trade_name',
            'crt' => 'fiscal_crt', 'phone' => 'fiscal_phone', 'email' => 'fiscal_email', 'cep' => 'fiscal_cep', 'street' => 'fiscal_street',
            'number' => 'fiscal_number', 'complement' => 'fiscal_complement', 'district' => 'fiscal_district', 'city' => 'fiscal_city',
            'uf' => 'fiscal_uf', 'city_code' => 'fiscal_city_code', 'environment' => 'fiscal_environment', 'series' => 'fiscal_series',
            'csc_id' => 'fiscal_csc_id', 'nature' => 'fiscal_nature', 'csosn' => 'fiscal_csosn', 'cfop' => 'fiscal_cfop',
        ];
        foreach ($map as $field => $key) {
            Setting::put($key, $data[$field] ?? '');
        }

        Setting::put('fiscal_auto', ! empty($data['auto']) ? '1' : '0');

        if (! empty($data['csc'])) {
            Setting::put('fiscal_csc', Crypt::encryptString(trim($data['csc'])));
        }

        if (! empty($data['next_number'])) {
            $this->setNextNumber($data['environment'], (int) $data['next_number']);
        }

        $this->register();
    }

    /** Cadastra ou atualiza a empresa e a configuração da NFC-e na Nuvem Fiscal. */
    public function register(): void
    {
        $s = $this->settings();

        if (! $this->platformReady() || ! $s['cnpj'] || ! $s['city_code']) {
            return;
        }

        $this->api->saveCompany(array_filter([
            'cpf_cnpj' => $s['cnpj'],
            'inscricao_estadual' => $s['ie'],
            'nome_razao_social' => $s['legal_name'],
            'nome_fantasia' => $s['trade_name'] ?: null,
            'fone' => preg_replace('/\D/', '', (string) $s['phone']) ?: null,
            'email' => $s['email'] ?: tenant()->company()->users()->orderBy('id')->value('email'),
            'endereco' => array_filter([
                'logradouro' => $s['street'],
                'numero' => $s['number'] ?: 'S/N',
                'complemento' => $s['complement'] ?: null,
                'bairro' => $s['district'],
                'codigo_municipio' => $s['city_code'],
                'cidade' => $s['city'],
                'uf' => $s['uf'],
                'cep' => $s['cep'],
                'codigo_pais' => '1058',
                'pais' => 'Brasil',
            ]),
        ]));

        if ($s['csc_id'] && $s['has_csc']) {
            $this->api->saveNfceConfig($s['cnpj'], [
                'CRT' => (int) $s['crt'],
                'ambiente' => $s['environment'],
                'sefaz' => ['id_csc' => (int) $s['csc_id'], 'csc' => $this->csc()],
            ]);
        }

        Setting::put('fiscal_registered_at', now()->toIso8601String());
    }

    public function uploadCertificate(string $pfx, string $password): array
    {
        $cnpj = Setting::get('fiscal_cnpj');
        if (! $cnpj) {
            throw new RuntimeException('Salve primeiro os dados da empresa (CNPJ).');
        }

        $cert = $this->api->uploadCertificate($cnpj, $pfx, $password);
        $info = [
            'subject' => $cert['nome_razao_social'] ?? $cert['subject_name'] ?? null,
            'document' => $cert['cpf_cnpj'] ?? null,
            'valid_until' => $cert['not_valid_after'] ?? null,
            'uploaded_at' => now()->toIso8601String(),
        ];
        Setting::put('fiscal_certificate', json_encode($info));
        AuditService::log('configuracoes', 'Certificado digital A1 enviado para emissão de nota fiscal');

        return $info;
    }

    private function csc(): ?string
    {
        $value = Setting::get('fiscal_csc');

        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function nextNumber(string $environment): int
    {
        return max(1, (int) Setting::get('fiscal_next_'.$environment, 1));
    }

    private function setNextNumber(string $environment, int $number): void
    {
        Setting::put('fiscal_next_'.$environment, (string) max(1, $number));
    }

    /* ---------- Emissão ---------- */

    /** Produtos da venda sem os dados fiscais obrigatórios. */
    public function productIssues(Sale $sale): array
    {
        $issues = [];
        foreach ($sale->items as $item) {
            if (! $item->product) {
                $issues[] = "\"{$item->description}\" não é um produto cadastrado (cadastre o produto com NCM para emitir nota).";
            } elseif (! preg_match('/^\d{8}$/', (string) $item->product->ncm)) {
                $issues[] = "\"{$item->product->fullName()}\" está sem NCM.";
            }
        }

        return $issues;
    }

    public function emit(Sale $sale): Invoice
    {
        $sale->loadMissing(['items.product', 'customer']);

        if ($sale->status !== SaleStatus::Concluida) {
            throw new RuntimeException('Só dá para emitir nota de venda concluída.');
        }
        if ($missing = $this->missing()) {
            throw new RuntimeException('Nota fiscal não configurada: '.$missing[0]);
        }
        if ($issues = $this->productIssues($sale)) {
            throw new RuntimeException(implode(' ', $issues));
        }

        $invoice = $this->invoiceFor($sale);

        if ($invoice->isAuthorized() || $invoice->status === 'pendente' && $invoice->provider_id) {
            return $invoice;
        }

        try {
            $doc = $this->api->emitNfce($this->payload($sale, $invoice));
        } catch (\Throwable $e) {
            $invoice->update(['status' => 'erro', 'message' => $e->getMessage()]);

            throw $e;
        }

        return $this->apply($invoice, $doc);
    }

    /** Emissão automática depois da venda: nunca atrapalha a venda, só avisa se der errado. */
    public function emitQuietly(Sale $sale): ?Invoice
    {
        try {
            $invoice = $this->emit($sale);
        } catch (\Throwable $e) {
            $this->notifications->notify('nota_fiscal', "Nota da venda {$sale->code} não foi emitida", $e->getMessage(), route('sales.show', $sale));

            return null;
        }

        if (! in_array($invoice->status, ['autorizado', 'pendente'], true)) {
            $this->notifications->notify('nota_fiscal', "Nota da venda {$sale->code} foi {$invoice->label()}", (string) $invoice->message, route('sales.show', $sale));
        }

        return $invoice;
    }

    public function refresh(Invoice $invoice): Invoice
    {
        if (! $invoice->provider_id || ! in_array($invoice->status, ['pendente', 'erro'], true)) {
            return $invoice;
        }

        return $this->apply($invoice, $this->api->nfce($invoice->provider_id));
    }

    public function cancel(Invoice $invoice, string $reason): Invoice
    {
        if (! $invoice->isAuthorized()) {
            throw new RuntimeException('Só dá para cancelar nota autorizada.');
        }

        $event = $this->api->cancelNfce($invoice->provider_id, $reason);
        $status = $event['status'] ?? null;

        if ($status === 'registrado') {
            $invoice->update(['status' => 'cancelado', 'cancelled_at' => now(), 'message' => 'Cancelada: '.$reason]);
            AuditService::log('venda_cancelada', "{$invoice->numberLabel()} cancelada na SEFAZ", $invoice->sale);

            return $invoice;
        }

        throw new RuntimeException('A SEFAZ não aceitou o cancelamento: '.($event['motivo_status'] ?? $event['mensagem'] ?? $status ?? 'sem resposta').'.');
    }

    /** Ao cancelar a venda, tenta cancelar a nota. Devolve um aviso se não conseguir. */
    public function cancelForSale(Sale $sale): ?string
    {
        $invoice = Invoice::where('sale_id', $sale->id)->where('status', 'autorizado')->latest('id')->first();
        if (! $invoice) {
            return null;
        }

        try {
            $this->cancel($invoice, 'Venda cancelada pela loja no sistema Meu Cash');

            return null;
        } catch (\Throwable $e) {
            return "A {$invoice->numberLabel()} continua autorizada. ".$e->getMessage().' Se passou do prazo de cancelamento, faça uma nota de devolução com o seu contador.';
        }
    }

    public function download(Invoice $invoice, string $kind): string
    {
        return $this->api->download($invoice->provider_id, $kind);
    }

    /** Nota da venda: reaproveita a rejeitada (mesmo número) ou reserva o próximo número. */
    private function invoiceFor(Sale $sale): Invoice
    {
        return DB::transaction(function () use ($sale) {
            Company::whereKey(tenant_id())->lockForUpdate()->first();

            $current = Invoice::where('sale_id', $sale->id)->whereIn('status', ['autorizado', 'pendente', 'rejeitado', 'erro'])->latest('id')->first();
            $environment = Setting::get('fiscal_environment', 'homologacao');

            if ($current && ($current->environment === $environment || $current->isAuthorized())) {
                if ($current->canRetry()) {
                    $current->update(['status' => 'pendente', 'provider_id' => null, 'message' => null]);
                }

                return $current;
            }

            $number = $this->nextNumber($environment);
            while (Invoice::where('environment', $environment)->where('series', (int) Setting::get('fiscal_series', 1))->where('number', $number)->exists()) {
                $number++;
            }
            $this->setNextNumber($environment, $number + 1);

            return Invoice::create([
                'sale_id' => $sale->id,
                'model' => 65,
                'environment' => $environment,
                'series' => (int) Setting::get('fiscal_series', 1),
                'number' => $number,
                'status' => 'pendente',
                'total' => $sale->total,
            ]);
        });
    }

    private function apply(Invoice $invoice, array $doc): Invoice
    {
        $status = $doc['status'] ?? 'erro';
        $auth = $doc['autorizacao'] ?? [];
        $message = $auth['motivo_status'] ?? $auth['mensagem'] ?? null;

        if (isset($auth['codigo_status']) && $message) {
            $message = $auth['codigo_status'].' · '.$message;
        }

        $invoice->update([
            'provider_id' => $doc['id'] ?? $invoice->provider_id,
            'status' => array_key_exists($status, Invoice::LABELS) ? $status : 'erro',
            'access_key' => $doc['chave'] ?? $invoice->access_key,
            'protocol' => $auth['numero_protocolo'] ?? $invoice->protocol,
            'message' => $status === 'autorizado' ? null : $message,
            'authorized_at' => $status === 'autorizado' ? ($invoice->authorized_at ?? now()) : $invoice->authorized_at,
        ]);

        if ($status === 'autorizado') {
            AuditService::log('venda_editada', "{$invoice->numberLabel()} autorizada para a venda {$invoice->sale?->code}", $invoice->sale);
        }

        return $invoice;
    }

    /* ---------- Montagem da NFC-e (layout 4.00 da SEFAZ) ---------- */

    public function payload(Sale $sale, Invoice $invoice): array
    {
        $s = $this->settings();
        $test = $invoice->environment !== 'producao';
        $items = $sale->items->values();
        $discounts = $this->splitDiscount($items->map(fn ($i) => (float) $i->total)->all(), (float) $sale->discount);

        $det = [];
        foreach ($items as $n => $item) {
            $product = $item->product;
            $csosn = $product->csosn ?: $s['csosn'];
            $gtin = preg_match('/^(\d{8}|\d{12,14})$/', (string) $product->barcode) ? $product->barcode : 'SEM GTIN';
            $unit = strtoupper($product->unit ?: 'UN');

            $prod = array_filter([
                'cProd' => (string) $product->id,
                'cEAN' => $gtin,
                'xProd' => $test && $n === 0 ? 'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL' : mb_substr($item->description, 0, 120),
                'NCM' => $product->ncm,
                'CEST' => $product->cest ?: null,
                'CFOP' => $product->cfop ?: $s['cfop'],
                'uCom' => $unit,
                'qCom' => (float) $item->quantity,
                'vUnCom' => (float) $item->unit_price,
                'vProd' => (float) $item->total,
                'cEANTrib' => $gtin,
                'uTrib' => $unit,
                'qTrib' => (float) $item->quantity,
                'vUnTrib' => (float) $item->unit_price,
                'vDesc' => $discounts[$n] > 0 ? $discounts[$n] : null,
                'indTot' => 1,
            ], fn ($v) => $v !== null);

            $icms = $csosn === '500'
                ? ['ICMSSN500' => ['orig' => (int) $product->origin, 'CSOSN' => '500']]
                : ['ICMSSN102' => ['orig' => (int) $product->origin, 'CSOSN' => $csosn]];

            $det[] = [
                'nItem' => $n + 1,
                'prod' => $prod,
                'imposto' => [
                    'ICMS' => $icms,
                    'PIS' => ['PISOutr' => ['CST' => '49', 'vBC' => 0, 'pPIS' => 0, 'vPIS' => 0]],
                    'COFINS' => ['COFINSOutr' => ['CST' => '49', 'vBC' => 0, 'pCOFINS' => 0, 'vCOFINS' => 0]],
                ],
            ];
        }

        $vProd = round($items->sum(fn ($i) => (float) $i->total), 2);
        $vDesc = round(array_sum($discounts), 2);
        $vNF = round($vProd - $vDesc, 2);

        $infNFe = [
            'versao' => '4.00',
            'ide' => [
                'cUF' => self::UF[$s['uf']] ?? 0,
                'natOp' => mb_substr($s['nature'] ?: 'Venda de mercadoria', 0, 60),
                'mod' => 65,
                'serie' => $invoice->series,
                'nNF' => $invoice->number,
                'dhEmi' => now()->format('Y-m-d\TH:i:sP'),
                'tpNF' => 1,
                'idDest' => 1,
                'cMunFG' => (string) $s['city_code'],
                'tpImp' => 4,
                'tpEmis' => 1,
                'finNFe' => 1,
                'indFinal' => 1,
                'indPres' => 1,
                'procEmi' => 0,
                'verProc' => 'MeuCash 1.0',
            ],
            'emit' => ['CNPJ' => $s['cnpj']],
            'det' => $det,
            'total' => ['ICMSTot' => [
                'vBC' => 0, 'vICMS' => 0, 'vICMSDeson' => 0, 'vFCP' => 0, 'vBCST' => 0, 'vST' => 0, 'vFCPST' => 0, 'vFCPSTRet' => 0,
                'vProd' => $vProd, 'vFrete' => 0, 'vSeg' => 0, 'vDesc' => $vDesc, 'vII' => 0, 'vIPI' => 0, 'vIPIDevol' => 0,
                'vPIS' => 0, 'vCOFINS' => 0, 'vOutro' => 0, 'vNF' => $vNF,
            ]],
            'transp' => ['modFrete' => 9],
            'pag' => ['detPag' => $this->payments($sale, $vNF)],
            'infAdic' => ['infCpl' => "Venda {$sale->code}".($sale->user ? ' · Vendedor: '.$sale->user->name : '')],
        ];

        if ($dest = $this->recipient($sale, $test)) {
            $infNFe['dest'] = $dest;
        }

        return [
            'ambiente' => $invoice->environment,
            'referencia' => 'mc'.tenant_id().'-nfce'.$invoice->id.'-'.now()->format('YmdHis'),
            'infNFe' => $infNFe,
        ];
    }

    private function recipient(Sale $sale, bool $test): ?array
    {
        $doc = preg_replace('/\D/', '', (string) $sale->customer?->document);
        if (! in_array(strlen($doc), [11, 14], true)) {
            return null;
        }

        return [
            strlen($doc) === 11 ? 'CPF' : 'CNPJ' => $doc,
            'xNome' => $test ? 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL' : mb_substr($sale->customer->name, 0, 60),
            'indIEDest' => 9,
        ];
    }

    private function payments(Sale $sale, float $total): array
    {
        $method = $sale->payment_method->value;

        if ($method !== 'parcelado') {
            return [$this->detPag($method, $total, 0)];
        }

        $rows = [];
        $down = round((float) $sale->down_payment, 2);
        if ($down > 0) {
            $downMethod = Payment::where('sale_id', $sale->id)->whereNull('installment_id')->orderBy('id')->first()?->payment_method?->value ?? 'dinheiro';
            $rows[] = $this->detPag($downMethod, min($down, $total), 0);
        }
        if ($total - $down > 0.004) {
            $rows[] = ['indPag' => 1, 'tPag' => '05', 'vPag' => round($total - $down, 2)];
        }

        return $rows ?: [$this->detPag('outro', $total, 0)];
    }

    private function detPag(string $method, float $value, int $indPag): array
    {
        $tPag = self::TPAG[$method] ?? '99';
        $row = ['indPag' => $indPag, 'tPag' => $tPag, 'vPag' => round($value, 2)];

        if ($tPag === '99') {
            $row['xPag'] = 'Outros';
        }
        if (in_array($tPag, ['03', '04', '17'], true)) {
            $row['card'] = ['tpIntegra' => 2];
        }

        return $row;
    }

    /** Distribui o desconto da venda entre os itens, proporcional ao valor. */
    private function splitDiscount(array $totals, float $discount): array
    {
        $sum = array_sum($totals);
        if ($discount <= 0 || $sum <= 0) {
            return array_fill(0, count($totals), 0.0);
        }

        $parts = [];
        $used = 0;
        $last = count($totals) - 1;
        foreach ($totals as $i => $value) {
            $part = $i === $last ? round($discount - $used, 2) : round($discount * $value / $sum, 2);
            $parts[] = $part;
            $used += $part;
        }

        return $parts;
    }
}
