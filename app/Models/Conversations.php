<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversations extends Model {
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
        'sent_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function sender() {
        return $this->belongsTo( User::class, 'sender_id' );
    }

    public function receiver() {
        return $this->belongsTo( User::class, 'receiver_id' );
    }
}
