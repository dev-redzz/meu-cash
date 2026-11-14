<?php

namespace App\Services\Fiscal;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente da API da Nuvem Fiscal (https://dev.nuvemfiscal.com.br/docs/api).
 * A plataforma tem uma conta; cada loja é cadastrada como "empresa" pelo CNPJ.
 */
class NuvemFiscal
{
    public function configured(): bool
    {
        return config('fiscal.client_id') && config('fiscal.client_secret');
    }

    public function sandbox(): bool
    {
        return (bool) config('fiscal.sandbox');
    }

    /* ---------- Empresa ---------- */

    public function saveCompany(array $data): array
    {
        $cnpj = $data['cpf_cnpj'];
        $found = $this->http()->get($this->url('/empresas/'.$cnpj));

        $response = $found->status() === 404
            ? $this->http()->post($this->url('/empresas'), $data)
            : $this->http()->put($this->url('/empresas/'.$cnpj), $data);

        return $this->check($response);
    }

    public function saveNfceConfig(string $cnpj, array $data): array
    {
        return $this->check($this->http()->put($this->url("/empresas/{$cnpj}/nfce"), $data));
    }

    public function uploadCertificate(string $cnpj, string $pfx, string $password): array
    {
        return $this->check($this->http()->timeout(40)->put($this->url("/empresas/{$cnpj}/certificado"), [
            'certificado' => base64_encode($pfx),
            'password' => $password,
        ]));
    }

    public function cep(string $cep): array
    {
        return $this->check($this->http()->get($this->url('/cep/'.preg_replace('/\D/', '', $cep))));
    }

    /* ---------- NFC-e ---------- */

    public function emitNfce(array $payload): array
    {
        return $this->check($this->http()->timeout(60)->post($this->url('/nfce'), $payload));
    }

    public function nfce(string $id): array
    {
        return $this->check($this->http()->get($this->url('/nfce/'.rawurlencode($id))));
    }

    public function cancelNfce(string $id, string $reason): array
    {
        return $this->check($this->http()->timeout(40)->post($this->url('/nfce/'.rawurlencode($id).'/cancelamento'), ['justificativa' => $reason]));
    }

    public function download(string $id, string $kind): string
    {
        $path = $kind === 'xml' ? '/xml' : '/pdf';
        $response = $this->http()->accept('*/*')->timeout(40)->get($this->url('/nfce/'.rawurlencode($id).$path));
        $this->check($response, false);

        return $response->body();
    }

    /* ---------- HTTP ---------- */

    private function url(string $path): string
    {
        return rtrim($this->sandbox() ? config('fiscal.sandbox_url') : config('fiscal.api_url'), '/').$path;
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->token())->acceptJson()->asJson()->timeout(25)->connectTimeout(8);
    }

    private function token(): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('Nota fiscal não configurada na plataforma (NUVEMFISCAL_CLIENT_ID).');
        }

        $key = 'nuvemfiscal.token.'.md5(config('fiscal.client_id').config('fiscal.scope'));

        if ($token = Cache::get($key)) {
            return $token;
        }

        $response = Http::asForm()->timeout(20)->post(config('fiscal.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => config('fiscal.client_id'),
            'client_secret' => config('fiscal.client_secret'),
            'scope' => config('fiscal.scope'),
        ]);

        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException('Nuvem Fiscal: não foi possível entrar (confira NUVEMFISCAL_CLIENT_ID e NUVEMFISCAL_CLIENT_SECRET).');
        }

        $ttl = max(60, (int) $response->json('expires_in', 3600) - 120);
        Cache::put($key, $response->json('access_token'), $ttl);

        return $response->json('access_token');
    }

    private function check(Response $response, bool $json = true): array
    {
        if ($response->failed()) {
            $error = $response->json('error');
            $message = is_array($error) ? ($error['message'] ?? null) : null;
            $message ??= $response->json('message') ?? 'erro '.$response->status();

            if (is_array($error) && ! empty($error['errors'])) {
                $details = collect($error['errors'])->map(fn ($e) => is_array($e) ? ($e['message'] ?? json_encode($e)) : $e)->take(3)->implode(' ');
                $message .= ' '.$details;
            }

            throw new RuntimeException('Nuvem Fiscal: '.$message, $response->status());
        }

        return $json ? ($response->json() ?? []) : [];
    }
}
