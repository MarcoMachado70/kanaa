<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
        ], [
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.min' => 'Use uma senha com pelo menos 12 caracteres.',
            'password.confirmed' => 'As senhas precisam ser iguais.',
        ]);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('profile')->with('status', 'Bem-vindo à Kanaã! Seu caminho em comunidade começa aqui.');
    }
}
