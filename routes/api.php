<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\ConversationsController;

Route::post( '/login', [ AuthController::class, 'login' ] );
Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);
Route::middleware('auth:sanctum')->get('/users', [UserController::class, 'getAllUsers']);
Route::middleware('auth:sanctum')->get('/friend-requests', [UserController::class, 'getFriendRequests']);
Route::middleware('auth:sanctum')->get('/sent-friend-requests', [UserController::class, 'getSentFriendRequests']);
Route::middleware('auth:sanctum')->get('/received-friend-requests', [UserController::class, 'getReceivedFriendRequests']);
Route::middleware('auth:sanctum')->get('/friends', [UserController::class, 'getFriends']);
Route::middleware('auth:sanctum')->post('/respond-friend-request', [UserController::class, 'respondToFriendRequest']);
Route::middleware('auth:sanctum')->post('/cancel-friend-request', [UserController::class, 'cancelSentFriendRequest']);
Route::middleware('auth:sanctum')->post('/add-friend-request', [UserController::class, 'addFriendRequest']);
Route::middleware('auth:sanctum')->get('/stories', [StoryController::class, 'getFriendsStories']);
Route::middleware('auth:sanctum')->post('/stories/upload', [StoryController::class, 'uploadStory']);
Route::middleware('auth:sanctum')->post('stories/remove', [StoryController::class, 'removeStory']);
Route::middleware('auth:sanctum')->post('/stories/view', [StoryController::class, 'markStoryAsViewed']);
Route::middleware('auth:sanctum')->get('/stories/view', [StoryController::class, 'getMyStoryViews']);
Route::middleware('auth:sanctum')->get('/profils', [UserController::class, 'getProfils']);
Route::middleware('auth:sanctum')->post('/profils/update', [UserController::class, 'profilsUpdate']);
Route::middleware('auth:sanctum')->post('/profils/update/password', [UserController::class, 'updatePassword']);
Route::middleware('auth:sanctum')->post('/profils/update/photo', [UserController::class, 'updatePhotoAndName']);
Route::middleware('auth:sanctum')->get('/chats', [ConversationsController::class, 'getConversationWithFriend']);
Route::middleware('auth:sanctum')->get('/userchats', [ConversationsController::class, 'getUserConversations']);
Route::middleware('auth:sanctum')->post('/chats/send', [ConversationsController::class, 'sendMessages']);
Route::middleware('auth:sanctum')->get('/friends/message', [ConversationsController::class, 'getFriendsWithUnreadMessage']);
