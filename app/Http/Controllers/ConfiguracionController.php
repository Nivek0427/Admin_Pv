<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ConfiguracionController extends Controller
{
    private const SESSION_KEY = 'configuracion_password_verified';

    public function index()
    {
        if (!$this->segundaValidacionActiva()) {
            return view('configuracion.verificar');
        }

        $usuarios = User::with('roles')->orderBy('name')->get();

        return view('configuracion.index', compact('usuarios'));
    }

    public function verificar(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors([
                'password' => 'La contraseña no es correcta.',
            ]);
        }

        $this->marcarSegundaValidacion();

        return redirect()->route('configuracion.index');
    }

    public function actualizarPassword(Request $request)
    {
        if (!$this->segundaValidacionActiva()) {
            return redirect()->route('configuracion.index')
                ->with('error', 'Debes validar tu contraseña para continuar.');
        }

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return back()->withErrors([
                'current_password' => 'La contraseña actual no es correcta.',
            ]);
        }

        $request->user()->update([
            'password' => Hash::make($request->new_password),
        ]);

        $this->marcarSegundaValidacion();

        return back()->with('success', 'Tu contraseña fue actualizada correctamente.');
    }

    public function actualizarPasswordUsuario(Request $request, User $user)
    {
        if (!$this->segundaValidacionActiva()) {
            return redirect()->route('configuracion.index')
                ->with('error', 'Debes validar tu contraseña para continuar.');
        }

        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', "La contraseña de {$user->username} fue actualizada correctamente.");
    }

    private function marcarSegundaValidacion(): void
    {
        session()->put(self::SESSION_KEY, [
            'user_id' => auth()->id(),
            'verified_at' => time(),
        ]);
    }

    private function segundaValidacionActiva(): bool
    {
        $verificacion = session(self::SESSION_KEY);

        return is_array($verificacion)
            && ($verificacion['user_id'] ?? null) === auth()->id()
            && ($verificacion['verified_at'] ?? 0) >= now()->subMinutes(15)->timestamp;
    }
}
