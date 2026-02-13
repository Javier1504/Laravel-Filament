<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEvent extends Model
{
    protected $fillable = ['ticket_id','actor_id','event','meta'];
    protected $casts = ['meta' => 'array'];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
