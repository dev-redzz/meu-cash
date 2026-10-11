<?php

namespace App\Support;

use App\Models\Company;

class Tenant
{
    private ?Company $company = null;

    public function set(?Company $company): void
    {
        $this->company = $company;
    }

    public function company(): ?Company
    {
        return $this->company;
    }

    public function id(): ?int
    {
        return $this->company?->id;
    }

    public function check(): bool
    {
        return $this->company !== null;
    }

    public function run(Company $company, callable $callback): mixed
    {
        $previous = $this->company;
        $this->company = $company;

        try {
            return $callback($company);
        } finally {
            $this->company = $previous;
        }
    }
}
