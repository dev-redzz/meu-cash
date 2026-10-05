<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups)
    {
    }

    public function index(): View
    {
        return view('settings.backup', [
            'backups' => $this->backups->list(),
            'supported' => $this->backups->supported(),
            'database' => config('database.connections.'.config('database.default').'.database'),
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $name = $this->backups->create();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Backup {$name} gerado.");
    }

    public function download(string $file): BinaryFileResponse
    {
        try {
            return response()->download($this->backups->path($file));
        } catch (RuntimeException) {
            abort(404);
        }
    }

    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'backup' => ['required', 'file', 'max:102400'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Marque a confirmação para restaurar.']);

        if (strtolower($request->file('backup')->getClientOriginalExtension()) !== 'sql') {
            return back()->with('error', 'Envie um arquivo .sql.');
        }

        try {
            $this->backups->restore($request->file('backup'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Não foi possível restaurar: '.$e->getMessage());
        }

        return redirect()->route('login')->with('success', 'Backup restaurado. Entre novamente.');
    }

    public function destroy(string $file): RedirectResponse
    {
        try {
            $this->backups->delete($file);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Backup excluído.');
    }
}
