<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Inscription (réservé en pratique à un administrateur qui crée les comptes).
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'in:administrateur,gestionnaire,comptable,consultation',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'consultation',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->created(['user' => $user, 'token' => $token], 'Compte créé avec succès');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Identifiants incorrects', 401);
        }

        if (! $user->actif) {
            return $this->error('Compte désactivé. Contactez un administrateur.', 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->ok(['user' => $user, 'token' => $token], 'Connexion réussie');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Déconnexion réussie');
    }

    public function me(Request $request)
    {
        return $this->ok($request->user());
    }
}
