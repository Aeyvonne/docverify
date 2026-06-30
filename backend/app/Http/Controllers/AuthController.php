<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel émetteur.
     * Les admins sont créés uniquement en base, jamais via cette route.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'nom'              => ['required', 'string', 'max:100'],
            'prenom'           => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/[A-Z]/',
                'regex:/[^a-zA-Z0-9]/',
            ],
            'telephone'        => ['nullable', 'string', 'max:20'],
            'nom_institution'  => ['required', 'string', 'max:255'],
            'type_institution' => ['required', 'string', 'max:100'],
            'adresse'          => ['nullable', 'string', 'max:255'],
        ], [
            'nom_institution.required'  => 'Le nom de l\'institution est obligatoire.',
            'type_institution.required' => 'Le type d\'institution est obligatoire.',
            'password.min'   => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.regex' => 'Le mot de passe doit contenir au moins une majuscule et un caractère spécial.',
        ]);

        $user = User::create([
            'nom'              => $data['nom'],
            'prenom'           => $data['prenom'],
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'role'             => 'emetteur',
            'telephone'        => $data['telephone'] ?? null,
            'nom_institution'  => $data['nom_institution'],
            'type_institution' => $data['type_institution'],
            'adresse'          => $data['adresse'] ?? null,
            'is_active'        => true,
            'is_certified'     => false, // toujours en attente de validation admin
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Connexion — retourne un token Sanctum.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Compte désactivé. Contactez l\'administrateur.'], 403);
        }

        // Mise à jour de la dernière connexion
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    /**
     * Déconnexion — révoque le token courant.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    /**
     * Retourne l'utilisateur authentifié.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Changement de mot de passe pour utilisateur connecté.
     * PUT /api/change-password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[^a-zA-Z0-9]/'],
        ], [
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.regex'     => 'Le mot de passe doit contenir une majuscule et un caractère spécial.',
            'password.confirmed' => 'La confirmation ne correspond pas.',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    /**
     * Envoie un lien de réinitialisation de mot de passe.
     * POST /api/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Un lien de réinitialisation a été envoyé à votre adresse email.']);
        }

        return response()->json(['message' => 'Aucun compte ne correspond à cette adresse email.'], 422);
    }

    /**
     * Réinitialise le mot de passe via le token reçu par email.
     * POST /api/reset-password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[^a-zA-Z0-9]/'],
        ], [
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.regex'     => 'Le mot de passe doit contenir une majuscule et un caractère spécial.',
            'password.confirmed' => 'La confirmation ne correspond pas.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])
                     ->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.']);
        }

        return response()->json(['message' => 'Lien invalide ou expiré. Veuillez refaire une demande.'], 422);
    }
}
