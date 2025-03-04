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
    
        // ✅ Récupérer les messages après mise à jour
        $messages = Conversations::where(function ($query) use ($userId, $friendId) {
            $query->where('sender_id', $userId)->where('receiver_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('sender_id', $friendId)->where('receiver_id', $userId);
        })->orderBy('created_at', 'asc')->get();
    
        // ✅ Envoyer les données au WebSocket pour notifier la lecture des messages
        FileFunctions::sendToWebSocket([
            'type' => 'messages_read',
            'receiver_id' => $userId, // ✅ Celui qui a vu les messages
            'sender_id' => $friendId, // ✅ Celui qui les a envoyés
            'unread_count' => $unreadCount, // ✅ Mise à jour du compteur
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
        $unreadCount = Conversations::where('sender_id', $userId)
    ->where('receiver_id', $receiverId)
    ->where('is_read', false)
    ->count();

        FileFunctions::sendToWebSocket([
            'type' => 'messages_sent',
            'messages' => $result,
            'unread_count' => $unreadCount,
        ]);
        return response()->json([
            "success" => true,
            "conversations" => $result,
        ]);
    }
    
}
