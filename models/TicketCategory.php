<?php

namespace TheWebsiteGuy\AvalancheCRM\Models;

use Winter\Storm\Database\Model;

/**
 * TicketCategory Model
 */
class TicketCategory extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \TheWebsiteGuy\AvalancheCRM\Traits\LogsActivity;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'thewebsiteguy_avalanchecrm_ticket_categories';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['name', 'color'];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'name' => 'required|unique:thewebsiteguy_avalanchecrm_ticket_categories',
    ];

    /**
     * @var array Relations
     */
    public $hasMany = [
        'tickets' => [\TheWebsiteGuy\AvalancheCRM\Models\Ticket::class],
    ];
}
