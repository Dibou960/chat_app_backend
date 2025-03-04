<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller {
    // Fonction de connexion

    public function login( Request $request ) {
        $request->validate( [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ] );

        $user = User::where( 'email', $request->email )->first();

        if ( !$user || !Hash::check( $request->password, $user->password ) ) {
            return response()->json( [ 'error' => 'Identifiant ou mot de passe incorrect.' ], 401 );
        }

        // Crée un token pour l'utilisateur
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $user
    ]);
}
    // Fonction de déconnexion
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    // Fonction pour récupérer l'utilisateur authentifié

        public function me( Request $request ) {
            return response()->json( $request->user() );
        }

        public function register( Request $request ) {
            $request->validate( [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ] );

            $user = User::create( [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make( $request->password ),
                'role' => $request->role,
            ] );

            return response()->json( [ 'message' => 'Utilisateur créé avec succès.', 'user' => $user ], 201 );
        }
    }
