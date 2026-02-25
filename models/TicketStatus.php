<?php

namespace TheWebsiteGuy\AvalancheCRM\Models;

use Winter\Storm\Database\Model;

/**
 * TicketStatus Model
 */
class TicketStatus extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'thewebsiteguy_avalanchecrm_ticket_statuses';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['name', 'color', 'is_default'];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'name' => 'required|unique:thewebsiteguy_avalanchecrm_ticket_statuses',
    ];

    /**
     * @var array Relations
     */
    public $hasMany = [
        'tickets' => [\TheWebsiteGuy\AvalancheCRM\Models\Ticket::class, 'key' => 'status_id'],
    ];

    /**
     * Ensure only one status is set as default
     */
    public function afterSave()
    {
        if ($this->is_default) {
            $this->newQuery()->where('id', '<>', $this->id)->update(['is_default' => false]);
        }
    }
}
