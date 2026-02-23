<?php

namespace TheWebsiteGuy\NexusCRM\Models;

use Winter\Storm\Database\Model;

/**
 * Project Model
 */
class Project extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'thewebsiteguy_nexuscrm_projects';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'hourly_rate' => 'float',
        'fixed_price' => 'float',
    ];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = [];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [
        'tickets' => [\TheWebsiteGuy\NexusCRM\Models\Ticket::class],
        'invoices' => [\TheWebsiteGuy\NexusCRM\Models\Invoice::class],
        'tasks' => [\TheWebsiteGuy\NexusCRM\Models\Task::class],
    ];
    public $hasOneThrough = [];
    public $hasManyThrough = [];
    public $belongsTo = [];
    public $belongsToMany = [
        'staff' => [
            \TheWebsiteGuy\NexusCRM\Models\Staff::class,
            'table' => 'thewebsiteguy_nexuscrm_projects_staff',
            'key' => 'project_id',
            'otherKey' => 'staff_id'
        ],
        'clients' => [
            \TheWebsiteGuy\NexusCRM\Models\Client::class,
            'table' => 'thewebsiteguy_nexuscrm_projects_clients',
            'key' => 'project_id',
            'otherKey' => 'client_id'
        ]
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * Dropdown options for billing type.
     */
    public function getBillingTypeOptions(): array
    {
        return [
            'non_billable' => 'Non-Billable',
            'hourly'       => 'Hourly',
            'fixed'        => 'Fixed Price',
        ];
    }

    /**
     * Get billable tasks that have not yet been invoiced.
     */
    public function getUninvoicedBillableTasks()
    {
        return $this->tasks()
            ->where('is_billable', true)
            ->where('is_invoiced', false)
            ->where('hours', '>', 0)
            ->get();
    }

    /**
     * Calculate total uninvoiced amount from billable tasks.
     */
    public function getUninvoicedTotal(): float
    {
        $total = 0;

        foreach ($this->getUninvoicedBillableTasks() as $task) {
            $rate = $task->hourly_rate ?: ($this->hourly_rate ?: 0);
            $hours = $task->hours ?? 0;
            $total += $rate * $hours;
        }

        return round($total, 2);
    }
}
