<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Create an application account. The route is restricted to administrators.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'role' => ['required', 'in:administrateur,gestionnaire,comptable,consultation'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $user = User::create([
            'name' => $validator->validated()['name'],
            'email' => strtolower($validator->validated()['email']),
            'password' => $validator->validated()['password'],
            'role' => $validator->validated()['role'],
            'actif' => true,
        ]);

        event(new Registered($user));

        return $this->created(['user' => $user], 'Compte créé avec succès');
    }

    /**
     * Authenticate a user and issue a Sanctum personal access token.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation échouée', 422, $validator->errors());
        }

        $credentials = $validator->validated();
        $user = User::query()
            ->where('email', strtolower($credentials['email']))
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error('Identifiants incorrects', 401);
        }

        if (! $user->actif) {
            return $this->error('Compte désactivé. Contactez un administrateur.', 403);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api-client')->plainTextToken;

        return $this->ok([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Connexion réussie');
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->ok(null, 'Déconnexion réussie');
    }

    public function me(Request $request)
    {
        return $this->ok($request->user());
    }
}
