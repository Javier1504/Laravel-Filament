<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Ticket extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ON_GOING = 'on_going';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const COMMENT_NOTE = 'note';
    public const COMMENT_PROGRESS = 'progress';
    public const COMMENT_REVIEW = 'review';
    public const COMMENT_REPLY = 'reply';

    protected $fillable = [
        'code',
        'title',
        'description',
        'request_type',
        'asset_tag',
        'location',
        'category_id',
        'priority',
        'status',
        'requester_id',
        'assignee_id',
        'due_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (! $ticket->code) {
                $next = (static::max('id') ?? 0) + 1;
                $ticket->code = 'TCK-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            }

            if (! $ticket->status) {
                $ticket->status = self::STATUS_NEW;
            }
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_ON_GOING => 'On Going',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public static function requestTypeOptions(): array
    {
        return [
            'incident' => 'Incident',
            'service_request' => 'Service Request',
            'access_request' => 'Access Request',
            'maintenance' => 'Maintenance',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->latest('created_at');
    }

    public function getIsOverdueAttribute(): bool
    {
        if (! $this->due_at) {
            return false;
        }

        if (in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true)) {
            return false;
        }

        return $this->due_at->isPast();
    }

    public function visibleCommentsFor(User $viewer)
    {
        $query = $this->comments()->with('user');

        if (! $viewer->hasRole('admin')) {
            if ($viewer->hasRole('user')) {
                $query->where('is_internal', false);
            }
        }

        return $query;
    }

    public function addComment(User $actor, string $body, bool $isInternal = false, string $commentType = self::COMMENT_NOTE): TicketComment
    {
        $comment = $this->comments()->create([
            'user_id' => $actor->id,
            'body' => trim($body),
            'is_internal' => $isInternal,
            'comment_type' => $commentType,
        ]);

        $this->forceFill(['updated_at' => now()])->saveQuietly();

        $this->recordEvent(
            $actor,
            $actor->hasRole('technician') ? 'technician_note_added' : 'comment_added'
        );

        return $comment;
    }

    public function assignTechnician(?User $assignee, User $actor): void
    {
        DB::transaction(function () use ($assignee, $actor) {
            $fromStatus = $this->status;

            $this->assignee_id = $assignee?->id;
            $this->status = $assignee ? ($this->status === self::STATUS_NEW ? self::STATUS_ASSIGNED : $this->status) : self::STATUS_NEW;
            $this->updated_at = now();
            $this->save();

            $this->recordEvent($actor, 'assigned', $fromStatus, $this->status, [
                'from_assignee_id' => $this->getOriginal('assignee_id'),
                'to_assignee_id' => $assignee?->id,
            ]);
        });
    }

    public function moveToOngoing(User $actor): void
    {
        $this->changeStatus(self::STATUS_ON_GOING, $actor);
    }

    public function submitProgress(User $actor, string $summary, ?string $detail = null): void
    {
        if ((int) $this->assignee_id !== (int) $actor->id) {
            throw new InvalidArgumentException('Hanya technician yang diassign yang boleh submit progress.');
        }

        $parts = [];
        if (filled($summary)) {
            $parts[] = 'Ringkasan: ' . trim($summary);
        }
        if (filled($detail)) {
            $parts[] = 'Detail: ' . trim($detail);
        }
        if ($parts === []) {
            throw new InvalidArgumentException('Progress report tidak boleh kosong.');
        }

        DB::transaction(function () use ($actor, $parts) {
            $this->comments()->create([
                'user_id' => $actor->id,
                'body' => implode("\n\n", $parts),
                'is_internal' => true,
                'comment_type' => self::COMMENT_PROGRESS,
            ]);

            $fromStatus = $this->status;
            $this->status = self::STATUS_PENDING_REVIEW;
            $this->updated_at = now();
            $this->save();

            $this->recordEvent($actor, 'progress_submitted', $fromStatus, $this->status);
        });
    }

    public function approveByAdmin(User $actor, ?string $note = null): void
    {
        if ($this->status !== self::STATUS_PENDING_REVIEW) {
            throw new InvalidArgumentException('Ticket hanya bisa di-approve saat pending review.');
        }

        DB::transaction(function () use ($actor, $note) {
            $fromStatus = $this->status;
            $this->status = self::STATUS_RESOLVED;
            $this->resolved_at = now();
            $this->closed_at = null;
            $this->updated_at = now();
            $this->save();

            $this->recordEvent($actor, 'status_changed', $fromStatus, $this->status);

            $this->comments()->create([
                'user_id' => $actor->id,
                'body' => filled($note) ? trim($note) : 'Admin menyetujui hasil pengerjaan technician dan tiket dinyatakan selesai.',
                'is_internal' => true,
                'comment_type' => self::COMMENT_REVIEW,
            ]);
        });
    }

    public function rejectToOngoing(User $actor, ?string $note = null): void
    {
        if ($this->status !== self::STATUS_PENDING_REVIEW) {
            throw new InvalidArgumentException('Ticket hanya bisa di-reject saat pending review.');
        }

        DB::transaction(function () use ($actor, $note) {
            $fromStatus = $this->status;
            $this->status = self::STATUS_ON_GOING;
            $this->resolved_at = null;
            $this->closed_at = null;
            $this->updated_at = now();
            $this->save();

            $this->recordEvent($actor, 'status_changed', $fromStatus, $this->status);

            $this->comments()->create([
                'user_id' => $actor->id,
                'body' => filled($note) ? trim($note) : 'Admin menolak penyelesaian sementara. Lanjutkan pengerjaan.',
                'is_internal' => true,
                'comment_type' => self::COMMENT_REVIEW,
            ]);
        });
    }

    public function changeStatus(string $status, User $actor): void
    {
        if (! array_key_exists($status, self::statusOptions())) {
            throw new InvalidArgumentException('Status ticket tidak valid.');
        }

        if ($this->status === $status) {
            return;
        }

        $fromStatus = $this->status;
        $this->status = $status;
        $this->updated_at = now();

        if ($status === self::STATUS_RESOLVED) {
            $this->resolved_at = now();
            $this->closed_at = null;
        } elseif ($status === self::STATUS_CLOSED) {
            $this->resolved_at ??= now();
            $this->closed_at = now();
        } else {
            $this->resolved_at = null;
            $this->closed_at = null;
        }

        $this->save();
        $this->recordEvent($actor, 'status_changed', $fromStatus, $status);
    }

    public function recordEvent(?User $actor, string $eventType, ?string $fromStatus = null, ?string $toStatus = null, ?array $meta = null): void
    {
        $this->events()->create([
            'actor_id' => $actor?->id,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
