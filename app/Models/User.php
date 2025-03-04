<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
    protected $fillable = ['country', 'region', 'city', 'address', 'phone', 'birthdate', 'gender','bio','url_photo','status','name'];


    /**
    * The attributes that should be hidden for serialization.
    *
    * @var array<int, string>
    */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
    * Get the attributes that should be cast.
    *
    * @return array<string, string>
    */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function friends() {
        return $this->belongsToMany( User::class, 'friendships', 'user_id', 'friend_id' )
        ->wherePivot( 'status', 'accepted' );
    }

    public function friendRequestsReceived() {
        return $this->hasMany( FriendRequest::class, 'receiver_id' );
    }

    public function friendRequestsSent() {
        return $this->hasMany( FriendRequest::class, 'sender_id' );
    }
    public function stories() {
        return $this->hasMany(Story::class);
    }
    
    public function friendsStories() {
        return Story::whereIn('user_id', function ($query) {
            $query->select('friend_id')
                  ->from('friendships')
                  ->whereColumn('user_id', 'users.id')
                  ->where('status', 'accepted')
                  ->union(
                      // Récupérer aussi les amis qui ont ajouté l'utilisateur
                      $query->select('user_id')
                            ->from('friendships')
                            ->whereColumn('friend_id', 'users.id')
                            ->where('status', 'accepted')
                  );
        })->orWhere('user_id', $this->id)->active();
    }
    
    
}