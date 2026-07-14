<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'role', 'content',
        'tool_calls', 'tool_call_id', 'tool_name', 'tool_result',
    ];

    protected $casts = [
        'tool_calls'  => 'array',
        'tool_result' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }
}