<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Story;
use App\Models\User;
use App\Models\Friendship;
use App\Models\StoryView;
use App\Functions\FileFunctions;
use WebSocket\Client;

class StoryController extends Controller {

  public function getFriendsStories()
{
    $userId = Auth::id();

    // 🔹 1️⃣ Récupérer les IDs des amis acceptés
    $friendIds = Friendship::where('user_id', $userId)
        ->where('status', 'accepted')
        ->pluck('friend_id')
        ->toArray();

    $friendIds = array_merge($friendIds, Friendship::where('friend_id', $userId)
        ->where('status', 'accepted')
        ->pluck('user_id')
        ->toArray());

    // 🔹 2️⃣ Récupérer les stories de l'utilisateur connecté
    $userStories = Story::where('user_id', $userId)
        ->with('user')
        ->latest()
        ->get();

    // 🔹 3️⃣ Vérifier combien d'amis ont vu chaque story
    $userStoryViews = StoryView::whereIn('story_id', $userStories->pluck('id'))
        ->selectRaw('story_id, COUNT(user_id) as view_count')
        ->groupBy('story_id')
        ->pluck('view_count', 'story_id')
        ->toArray();

    // 🔹 4️⃣ Formater les stories du user
    $formattedUserStories = $userStories->map(function ($story) use ($userStoryViews) {
        return [
            'id' => $story->id,
            'media_url' => $story->media_url,
            'type' => $story->type,
            'user_id' => $story->user_id,
            'descriptions' => $story->descriptions,
            'created_at' => $story->created_at,
            'expires_at' => $story->expires_at,
            'view_count' => $userStoryViews[$story->id] ?? 0, // Nombre de vues
        ];
    });

    // 🔹 5️⃣ Récupérer les stories des amis
    $friendsStories = Story::whereIn('user_id', $friendIds)
        ->with('user')
        ->latest()
        ->get();

    // 🔹 6️⃣ Vérifier si l'utilisateur a vu ces stories
    $viewedStoryIds = StoryView::where('user_id', $userId)
        ->pluck('story_id')
        ->toArray();

    // 🔹 7️⃣ Regrouper les stories par utilisateur
    $groupedFriendsStories = [];
    foreach ($friendsStories as $story) {
        $storyUserId = $story->user->id;

        if (!isset($groupedFriendsStories[$storyUserId])) {
            $groupedFriendsStories[$storyUserId] = [
                'user' => [
                    'id' => $story->user->id,
                    'name' => $story->user->name,
                    'url_photo' => $story->user->url_photo,
                ],
                'stories' => [],
            ];
        }

        $groupedFriendsStories[$storyUserId]['stories'][] = [
            'id' => $story->id,
            'media_url' => $story->media_url,
            'type' => $story->type,
            'user_id' => $story->user_id,
            'descriptions' => $story->descriptions,
            'created_at' => $story->created_at,
            'expires_at' => $story->expires_at,
            'is_viewed' => in_array($story->id, $viewedStoryIds),
        ];
    }

  // 🔹 8️⃣ Trier les stories des amis (non lues en premier et triées par date)
foreach ($groupedFriendsStories as &$friend) {
    usort($friend['stories'], function ($a, $b) {
        // Trier d'abord par is_viewed (false en premier), puis par created_at (ancien -> récent)
        return $a['is_viewed'] <=> $b['is_viewed'] ?: strtotime($a['created_at']) <=> strtotime($b['created_at']);
    });
}


    return response()->json([
        'userStories' => [
            'user' => [
                'id' => Auth::user()->id,
                'name' => Auth::user()->name,
                'url_photo' => Auth::user()->url_photo,
            ],
            'stories' => $formattedUserStories,
        ],
        'friendsStories' => array_values($groupedFriendsStories),
    ]);
}

    public function uploadStory(Request $request)
    {
        $userId = Auth::id();

        $fields = $request->validate([
            'media_url' => 'required|string',
            'type' => 'required|in:image,video',
            'descriptions' => 'nullable|string',
            'expires_at' => 'nullable|date',
        ]);
        if (!$request->filled('expires_at')) {
            $fields['expires_at'] = now()->addHours(24);
        }

        // 🔥 Utilisation du type directement au lieu de la détection automatique
        $fields['media_url'] = FileFunctions::handleSingleFile( $request->media_url, 'media/stories_users/' . $fields['type'], 1024);

        if (!$fields['media_url']) {
            return response()->json(['error' => 'Le fichier n\'est pas valide'], 400);
        }

        $fields['user_id'] = $userId;
        $story = Story::create($fields);
      
        // 🔥 Récupérer le profil de l'utilisateur
        $user = User::select('id', 'name', 'url_photo')->where('id', $userId)->first();
    
        // 🔥 Récupérer les amis de l'utilisateur
        $friendIds = Friendship::where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('friend_id', $userId);
            })
            ->pluck(DB::raw("CASE WHEN user_id = $userId THEN friend_id ELSE user_id END"))
            ->toArray();
    
        // 🔥 Envoie la nouvelle story au serveur WebSocket avec les amis et le profil de l'utilisateur
        FileFunctions::sendToWebSocket([
            'type' => 'new_story',
            'story' => [
                'id' => $story->id,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'url_photo' => $user->url_photo,
                ],
                'media_url' => $story->media_url,
                'type' => $story->type,
                'user_id' => $story->user_id,
                'descriptions' => $story->descriptions,
                'created_at' => $story->created_at->toISOString(),
                'friends' => $friendIds,
            ]
        ]);
         FileFunctions::deleteFileAndRecordAfterDelay(Story::class, $story->id, 'media_url', 1, 'days');

        return response()->json([
            'message' => 'Story ajoutée avec succès',
            'story' => [
                'id' => $story->id,
                'user' => $user, 
                'media_url' => $story->media_url,
                'user_id' => $story->user_id,
                'type' => $story->type,
                'descriptions' => $story->descriptions,
                'created_at' => $story->created_at,
                'friends' => $friendIds,
            ]
        ]);
    }


    public function removeStory(Request $request) {
        $storyId = $request->input("story_id");
        $userId = Auth::id();
    
        $storyToDelete = Story::where("id", $storyId)->where("user_id", $userId)->first();
    
        if (!$storyToDelete) {
            return response()->json([
                "success" => false,
                "message" => "Story introuvable ou ne vous appartient pas."
            ], 404);
        }
    
        FileFunctions::deleteFile($storyToDelete->media_url);
    
        // Stocker les données avant suppression
        $storyData = [
            "id" => $storyToDelete->id,
            "user_id" => $storyToDelete->user_id
        ];
    
        // Supprimer la story de la base de données
        $storyToDelete->delete();
    
        // 🔥 Diffuser via WebSocket
        FileFunctions::sendToWebSocket([
            "type" => "delete_story",
            "story" => $storyData
        ]);
    
        return response()->json([
            "success" => true,
            "message" => "Story supprimée avec succès."
        ]);
    }
    
    public function markStoryAsViewed(Request $request) {
        $userId = Auth::id();
        $storyId = $request->input('story_id');
        // 🔥 Vérifier si la story existe
        $storyExists = Story::where('id', $storyId)->exists();

        if (!$storyExists) {
            return response()->json([
                'message' => 'Cette story n\'existe plus. Il est expiré !'
            ], 404);
        }
    
        $alreadyViewed = StoryView::where('story_id', $storyId)
                                  ->where('user_id', $userId)
                                  ->exists();
    
        if (!$alreadyViewed) {
            StoryView::create([
                'story_id' => $storyId,
                'user_id' => $userId,
            ]);
        }
    
        $viewedStories = StoryView::where('user_id', $userId)
                                  ->pluck('story_id')
                                  ->toArray();
    
        // Envoyer l'information à WebSocket
        FileFunctions::sendToWebSocket([
            'type' => 'story_viewed',
            'user_id' => $userId,
            'story_id' => $storyId
        ]);
    
        return response()->json([
            'messages' => 'Story marked as viewed',
            'viewedStories' => $viewedStories 
        ]);
    }
    
    
    public function getMyStoryViews(Request $request) {
        $userId = Auth::id(); 
        $storyId = $request->input('story_id');
    
        $isOwner = Story::where('id', $storyId)->where('user_id', $userId)->exists();
    
        if (!$isOwner) {
            return response()->json(['message' => 'Cette story n\'existe plus. Il est expiré !'], 403);
        }
    
        $views = StoryView::where('story_id', $storyId)
                          ->with('user')
                          ->get();
    
        return response()->json($views);
    }
    
}