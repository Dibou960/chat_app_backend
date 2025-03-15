<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use Illuminate\Http\Request;
use App\Models\Conversations;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Functions\FileFunctions;

class ConversationsController extends Controller
{
    public function getUserConversations()
    {
        $userId = Auth::id();
    
        // Récupérer les IDs distincts des interlocuteurs (autre que moi)
        $friendIds = Conversations::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->selectRaw("CASE WHEN sender_id = {$userId} THEN receiver_id ELSE sender_id END as friend_id")
            ->distinct()
            ->pluck('friend_id');
    
        $conversations = collect();
    
        // Pour chaque interlocuteur, on récupère l'ensemble des messages échangés
        foreach ($friendIds as $friendId) {
            $messages = Conversations::with(['sender:id,name,url_photo', 'receiver:id,name,url_photo'])
                ->where(function ($query) use ($userId, $friendId) {
                    $query->where('sender_id', $userId)
                          ->where('receiver_id', $friendId);
                })
                ->orWhere(function ($query) use ($userId, $friendId) {
                    $query->where('sender_id', $friendId)
                          ->where('receiver_id', $userId);
                })
                ->orderBy('created_at', 'asc')
                ->get();
    
            // Compter le nombre de messages non lus envoyés par l'interlocuteur
            $unreadCount = Conversations::where('sender_id', $friendId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->count();
    
            if ($messages->isNotEmpty()) {
                // Pour récupérer les infos de l'interlocuteur, on vérifie le premier message :
                // si je suis l'expéditeur, l'interlocuteur est le receiver, sinon c'est le sender
                $firstMessage = $messages->first();
                $friendData = ($firstMessage->sender_id == $userId)
                    ? $firstMessage->receiver
                    : $firstMessage->sender;
    
                // Dernier message de la conversation
                $lastMessage = $messages->last();
    
                $conversations->push([
                    'friend_id'            => $friendId,
                    'friend_name'          => $friendData->name,
                    'friend_photo'         => $friendData->url_photo, // Assure-toi que le champ correspond bien à ta table users
                    'last_message'         => $lastMessage->message,
                    'last_message_sent_at' => $lastMessage->sent_at,
                    'unread_count'         => $unreadCount,
                    'messages'             => $messages, // Liste complète des messages de la conversation, triée par date
                ]);
            }
        }
    
        // On trie les conversations par date du dernier message (les plus récentes en premier)
        $conversations = $conversations->sortByDesc('last_message_sent_at')->values();
    
        return response()->json($conversations);
    }
    
        public function getConversationWithFriend(Request $request)
    {
        $friendId = $request->query('friends');
        $userId = Auth::id();
    
        // ✅ Mettre à jour les messages comme lus
        Conversations::where('sender_id', $friendId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    
        // ✅ Compter le nombre de messages non lus après mise à jour
        $unreadCount = Conversations::where('sender_id', $friendId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();
    
        // ✅ Récupérer les messages après mise à jour avec les noms des utilisateurs
        $messages = Conversations::with(['sender:id,name', 'receiver:id,name'])
            ->where(function ($query) use ($userId, $friendId) {
                $query->where('sender_id', $userId)->where('receiver_id', $friendId);
            })->orWhere(function ($query) use ($userId, $friendId) {
                $query->where('sender_id', $friendId)->where('receiver_id', $userId);
            })->orderBy('created_at', 'asc')->get();
    
        // ✅ Envoyer les données au WebSocket pour notifier la lecture des messages
        FileFunctions::sendToWebSocket([
            'type' => 'messages_read',
            'receiver_id' => $userId, // ✅ Celui qui a vu les messages
            'sender_id' => $friendId, // ✅ Celui qui les a envoyés
        ]);
    
        return response()->json($messages);
    }
    
    
    public function getFriendsWithUnreadMessage()
    {
        $user = Auth::user();
        $userId = $user->id;
    
        $friends = User::whereIn('id', function ($query) use ($userId) {
                $query->select('friend_id')
                    ->from('friendships')
                    ->where('user_id', $userId)
                    ->where('status', 'accepted');
            })
            ->orWhereIn('id', function ($query) use ($userId) {
                $query->select('user_id')
                    ->from('friendships')
                    ->where('friend_id', $userId)
                    ->where('status', 'accepted');
            })
            ->get();
    
        foreach ($friends as $friend) {
            $friend->unread_count = Conversations::where('sender_id', $friend->id)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->count();
        }
    
        return response()->json($friends);
    }
      
    public function sendMessages(Request $request)
    {
        $message = $request->input('message');
        $receiverId = $request->input('receiver_id');
        $userId = Auth::id();
        $result = Conversations::create([
            'sender_id' => $userId,
            'receiver_id' => $receiverId,
            'message' => $message
        ]);
    //     $unreadCount = Conversations::where('sender_id', $userId)
    // ->where('receiver_id', $receiverId)
    // ->where('is_read', false)
    // ->count();

        FileFunctions::sendToWebSocket([
            'type' => 'messages_sent',
            'messages' => $result,
            // 'unread_count' => $unreadCount,
        ]);
        return response()->json([
            "success" => true,
            "conversations" => $result,
        ]);
    }
    
}
