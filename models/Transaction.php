<?php

namespace TheWebsiteGuy\AvalancheCRM\Models;

use Winter\Storm\Database\Model;

/**
 * Transaction Model
 */
class Transaction extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'thewebsiteguy_avalanchecrm_transactions';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'client_id',
        'invoice_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'transaction_id',
        'description',
    ];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'client_id' => 'required',
        'amount' => 'required|numeric',
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'client' => [\TheWebsiteGuy\AvalancheCRM\Models\Client::class],
        'invoice' => [\TheWebsiteGuy\AvalancheCRM\Models\Invoice::class],
        'subscription' => [\TheWebsiteGuy\AvalancheCRM\Models\Subscription::class],
    ];
}
