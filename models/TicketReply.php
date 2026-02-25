<?php

namespace TheWebsiteGuy\AvalancheCRM\Models;

use Winter\Storm\Database\Model;

/**
 * TicketReply Model
 */
class TicketReply extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $table = 'thewebsiteguy_avalanchecrm_ticket_replies';

    protected $guarded = [];

    protected $fillable = [
        'ticket_id',
        'user_id',
        'author_type',
        'author_name',
        'content',
        'is_internal',
    ];

    public $rules = [
        'ticket_id' => 'required',
        'content'   => 'required',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public $belongsTo = [
        'ticket' => [\TheWebsiteGuy\AvalancheCRM\Models\Ticket::class],
        'user'   => [\Winter\User\Models\User::class],
    ];

    /**
     * After creating a reply, notify the client (unless it's an internal note or from the client themselves).
     */
    public function afterCreate()
    {
        // Don't notify for internal notes
        if ($this->is_internal) {
            return;
        }

        // Don't notify if the client wrote the reply
        if ($this->author_type === 'client') {
            return;
        }

        // Send notification to the ticket's client
        $ticket = $this->ticket;
        if ($ticket && $ticket->client) {
            $ticket->client->sendNotification('ticket', 'Ticket Reply');
        }
    }

    /**
     * Get the author display name.
     */
    public function getAuthorDisplayNameAttribute(): string
    {
        if ($this->author_name) {
            return $this->author_name;
        }

        if ($this->user) {
            return trim($this->user->name . ' ' . $this->user->surname);
        }

        return ucfirst($this->author_type);
    }
}
