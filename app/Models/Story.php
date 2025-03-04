<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Story extends Model {
    use HasFactory;

    protected $fillable = ['user_id', 'media_url', 'type', 'expires_at','descriptions'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query) {
        return $query->where('expires_at', '>', Carbon::now());
    }
    public function views() {
        return $this->hasMany(StoryView::class);
    }
    
}

