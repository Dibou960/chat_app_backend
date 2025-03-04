<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Friendship;
use App\Models\FriendRequest;
use Illuminate\Support\Facades\Hash;
use App\Functions\FileFunctions;

class UserController extends Controller {
    public function getAllUsers() {
        $userId = Auth::id();
    
        // Récupérer les ID des amis confirmés
        $friendIds = Friendship::where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('friend_id', $userId);
            })
            ->where('status', 'accepted')
            ->pluck('user_id', 'friend_id')
            ->flatten()
            ->unique()
            ->toArray();
    
        // Récupérer les ID des utilisateurs à qui j'ai envoyé une demande d'ami
        $pendingRequests = FriendRequest::where('sender_id', $userId)
            ->pluck('receiver_id')
            ->toArray();
    
        // Récupérer les ID des utilisateurs qui m'ont envoyé une demande d'ami
        $receivedRequests = FriendRequest::where('receiver_id', $userId)
            ->pluck('sender_id')
            ->toArray();
    
        // Ajouter l'utilisateur lui-même et toutes les exclusions
        $excludedIds = array_merge($friendIds, $pendingRequests, $receivedRequests, [$userId]);
    
        // Récupérer les utilisateurs qui ne sont ni amis ni impliqués dans une demande d'ami
        $users = User::whereNotIn('id', $excludedIds)->get();
    
        return response()->json($users);
    }
    

// Récupérer les demandes d'amis envoyées par l'utilisateur connecté

public function getFriendRequests() {
    $requests = FriendRequest::where( 'sender_id', Auth::id() )
    ->with( 'receiver:id,name,email,url_photo' )
            ->get();

        return response()->json($requests);
    }

    public function getFriends() {
        $user = Auth::user();
    
        $friends = User::whereIn('id', function ($query) use ($user) {
                $query->select('friend_id')
                    ->from('friendships')
                    ->where('user_id', $user->id)
                    ->where('status', 'accepted');
            })
            ->orWhereIn('id', function ($query) use ($user) {
                $query->select('user_id')
                    ->from('friendships')
                    ->where('friend_id', $user->id)
                    ->where('status', 'accepted');
            })
            ->get();
    
        return response()->json($friends);
    }
 // Récupérer les demandes d'amis envoyées par l'utilisateur connecté (uniquement celles en attente)
public function getSentFriendRequests() {
    $userId = Auth::id();

    $sentRequests = FriendRequest::where('sender_id', $userId)
        ->where('status', 'pending') // Exclure les demandes acceptées ou rejetées
        ->with(['receiver' => function ($query) {
            $query->select('id', 'name', 'email', 'url_photo'); // Correction ici
        }])
        ->get();

    return response()->json($sentRequests);
}

// Récupérer les demandes d'amis reçues par l'utilisateur connecté (uniquement celles en attente)
public function getReceivedFriendRequests() {
    $userId = Auth::id();

    $receivedRequests = FriendRequest::where('receiver_id', $userId)
        ->where('status', 'pending') // Exclure les demandes acceptées ou rejetées
        ->with(['sender' => function ($query) {
            $query->select('id', 'name', 'email', 'url_photo'); // Correction ici
        }])
        ->get();

    return response()->json($receivedRequests);
}

public function respondToFriendRequest(Request $request) {
    $userId = Auth::id();
    $idFriend = $request->input('id_friend');
    $status = $request->input('status');

    // Trouver la demande où l'utilisateur connecté est le destinataire et $idFriend est l'expéditeur
    $friendRequest = FriendRequest::where('sender_id', $idFriend)
        ->where('receiver_id', $userId)
        ->where('status', 'pending')
        ->first();

    if (!$friendRequest) {
        return response()->json(['message' => 'Demande introuvable ou déjà traitée.'], 404);
    }

    if ($status === 'accepted') {
        // Accepter la demande : Ajouter dans la table friendships
        Friendship::create([
            'user_id' => $friendRequest->sender_id,
            'friend_id' => $userId,
            'status' => 'accepted',
        ]);

        $friendRequest->update(['status' => 'accepted']);
        return response()->json(['message' => 'Demande acceptée avec succès.']);
    } elseif ($status === 'rejected') {
        // Rejeter la demande
        $friendRequest->update(['status' => 'rejected']);
        return response()->json(['message' => 'Demande rejetée.']);
    }

    return response()->json(['message' => 'Statut invalide.'], 400);
}

    
    // Nouvelle méthode pour annuler une demande d'ami envoyée
    public function cancelSentFriendRequest(Request $request) {
        $userId = Auth::id();
        $idFriend = $request->input('id_friend');
    
        $friendRequest = FriendRequest::where('sender_id', $userId)
            ->where('receiver_id', $idFriend)
            ->where('status', 'pending')
            ->first();
    
        if (!$friendRequest) {
            return response()->json(['message' => 'Demande introuvable ou déjà traitée.'], 404);
        }
    
        // Mettre à jour le statut en "rejected"
        $friendRequest->update(['status' => 'rejected']);
    
        return response()->json(['message' => 'Demande annulée avec succès.']);
    }

    public function addFriendRequest(Request $request) {
        $receiverId = $request->input("receiver_id");
        $senderId = Auth::id();
    
        if ($senderId == $receiverId) {
            return response()->json(['message' => 'Vous ne pouvez pas vous envoyer une demande d\'ami.'], 400);
        }
    
        $existingRequest = FriendRequest::where(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $senderId)->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $receiverId)->where('receiver_id', $senderId);
        })->first();
    
        if ($existingRequest) {
            return response()->json(['message' => 'Une demande d\'ami existe déjà.'], 400);
        }
    
        FriendRequest::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'status' => 'pending',
        ]);
    
        return response()->json(['message' => 'Demande d\'ami envoyée avec succès.'], 201);
    }    

    public function getProfils()
    {
        return response()->json(Auth::user());
    }
 
    public function profilsUpdate(Request $request)
{
    $user = auth()->user();


    // Validation
    $validatedData = $request->validate([
        'field' => 'required|string|in:country,region,city,address,phone,birthdate,gender,bio',
        'value' => 'required|string',
    ]);

    // Mise à jour du champ
    $user->update([
        $validatedData['field'] => $validatedData['value'],
    ]);

    return response()->json([
        'message' => 'Profil mis à jour avec succès.',
    ]);
}

public function updatePassword(Request $request)
{
    $user = auth()->user();

    // Récupération des champs
    $oldPassword = $request->input('oldPassword');
    $newPassword = $request->input('newPassword');

    // Vérification des champs requis
    if (!isset($oldPassword) || empty($oldPassword)) {
        return response()->json(['message' => 'L\'ancien mot de passe est requis.'], 400);
    }

    if (!isset($newPassword) || empty($newPassword)) {
        return response()->json(['message' => 'Le nouveau mot de passe est requis.'], 400);
    }

    if (strlen($newPassword) < 8) {
        return response()->json(['message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'], 400);
    }

    // Vérification de l'ancien mot de passe
    if (!Hash::check($oldPassword, $user->password)) {
        return response()->json(['message' => 'Ancien mot de passe incorrect.'], 400);
    }

    // Mise à jour du mot de passe
    $user->update([
        'password' => Hash::make($newPassword),
    ]);

    return response()->json([
        'message' => 'Mot de passe mis à jour avec succès.',
    ]);
}

public function updatePhotoAndName(Request $request)
{
    $pathToStore = 'media/profile_users';
    $user = auth()->user();

    $name = $request->input("name");
    $mediaUrl = $request->input("media_url");

    // Vérification des entrées vides
    if (!$name && !$mediaUrl) {
        return response()->json(['error' => 'Aucune donnée valide reçue.'], 400);
    }

    $updateData = [];

    // Gestion de la mise à jour du nom
    if (!empty($name) && $name !== $user->name) {
        $updateData['name'] = $name;
    }

    // Gestion de la mise à jour de la photo
    if (!empty($mediaUrl)) {
        $imagePath = FileFunctions::handleSingleFile($mediaUrl, $pathToStore, 512);
        
        if (!$imagePath) {
            return response()->json(['error' => 'Le fichier n\'est pas valide ou l\'upload a échoué.'], 400);
        }

        // Suppression de l'ancienne photo si une nouvelle est uploadée
        if ($user->url_photo) {
            FileFunctions::deleteFile($user->url_photo);
        }

        $updateData['url_photo'] = $imagePath;
    }

    // Appliquer la mise à jour si nécessaire
    if (!empty($updateData)) {
        $user->update($updateData);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'name' => $user->name,
            'url_photo' => $user->url_photo
        ], 200);
    }

    return response()->json(['error' => 'Aucune modification détectée.'], 400);
}
    
}
