<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class TicketComment extends Model
{
    protected $fillable = ['ticket_id','author_id','type','message'];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }

    protected static function booted(): void
    {
        static::creating(fn (TicketComment $c) => $c->author_id = $c->author_id ?: Auth::id());

        static::created(function (TicketComment $c) {
            TicketEvent::create([
                'ticket_id' => $c->ticket_id,
                'actor_id' => Auth::id(),
                'event' => 'commented',
                'meta' => ['type' => $c->type],
            ]);
        });
    }
}
