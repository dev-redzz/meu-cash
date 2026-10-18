<?php

namespace App\Http\Controllers;

use App\Services\UndoService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class UndoController extends Controller
{
    public function __invoke(string $token, UndoService $undo): RedirectResponse
    {
        try {
            $label = $undo->restore($token);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pronto, desfeito. '.$label.' voltou.');
    }
}
