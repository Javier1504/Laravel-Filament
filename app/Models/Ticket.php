<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'code',
        'title',
        'description',
        'category_id',
        'assignee_id',
        'requester_name',
        'requester_email',
        'priority',
        'status',
        'due_at',
        'resolved_at',
        'request_type',
        'asset_tag',
        'location',
        'contact_phone',

    ];

    protected $casts = [
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    // Virtual column untuk IconColumn "is_overdue"
    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, ['resolved', 'closed'], true)) {
            return false;
        }

        return $this->due_at !== null && $this->due_at->isPast();
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            // 1) Generate code otomatis
            if (blank($ticket->code)) {
                $ticket->code = self::generateCode();
            }

            // 2) Auto due_at dari SLA category jika kosong
            if (blank($ticket->due_at) && filled($ticket->category_id)) {
                $category = Category::find($ticket->category_id);
                if ($category?->sla_hours) {
                    $ticket->due_at = now()->addHours((int) $category->sla_hours);
                }
            }

            // 3) Default status/priority jika kosong
            $ticket->status = $ticket->status ?: 'open';
            $ticket->priority = $ticket->priority ?: 'medium';
        });

        // Kalau category berubah dan due_at masih kosong → hitung lagi
        static::updating(function (Ticket $ticket) {
            if ($ticket->isDirty('category_id') && blank($ticket->due_at) && filled($ticket->category_id)) {
                $category = Category::find($ticket->category_id);
                if ($category?->sla_hours) {
                    $ticket->due_at = now()->addHours((int) $category->sla_hours);
                }
            }

            // kalau status jadi resolved → set resolved_at
            if ($ticket->isDirty('status') && $ticket->status === 'resolved' && blank($ticket->resolved_at)) {
                $ticket->resolved_at = now();
            }

            // kalau status balik open/in_progress → reset resolved_at
            if ($ticket->isDirty('status') && in_array($ticket->status, ['open', 'in_progress'], true)) {
                $ticket->resolved_at = null;
            }
        });
    }

    private static function generateCode(): string
    {
        // Format: TCK-000001, TCK-000002, ...
        // Aman untuk demo/dev. Untuk high-concurrency production bisa pakai sequence table.
        $lastId = (int) (self::max('id') ?? 0) + 1;
        return 'TCK-' . str_pad((string) $lastId, 6, '0', STR_PAD_LEFT);
    }
}
