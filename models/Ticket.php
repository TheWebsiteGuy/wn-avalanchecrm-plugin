<?php

namespace TheWebsiteGuy\NexusCRM\Models;

use Winter\Storm\Database\Model;

/**
 * Ticket Model
 */
class Ticket extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'thewebsiteguy_nexuscrm_tickets';

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
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = ['custom_fields_data'];

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
    public $hasMany = [];
    public $hasOneThrough = [];
    public $hasManyThrough = [];
    public $belongsTo = [
        'client' => [\TheWebsiteGuy\NexusCRM\Models\Client::class],
        'project' => [\TheWebsiteGuy\NexusCRM\Models\Project::class],
        'category' => [\TheWebsiteGuy\NexusCRM\Models\TicketCategory::class],
        'status_relation' => [\TheWebsiteGuy\NexusCRM\Models\TicketStatus::class, 'key' => 'status_id'],
        'ticket_type' => [\TheWebsiteGuy\NexusCRM\Models\TicketType::class],
    ];
    public $belongsToMany = [
        'staff' => [
            \TheWebsiteGuy\NexusCRM\Models\Staff::class,
            'table' => 'thewebsiteguy_nexuscrm_tickets_staff',
            'key' => 'ticket_id',
            'otherKey' => 'staff_id'
        ]
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * Dynamically add custom fields based on ticket type
     */
    public function filterFields($fields, $context = null)
    {
        if (isset($fields->custom_fields_section)) {
            $fields->custom_fields_section->hidden = true;
        }

        $ticketTypeId = $this->ticket_type_id ?: post('Ticket[ticket_type]');

        if (!$ticketTypeId) {
            return;
        }

        $ticketType = \TheWebsiteGuy\NexusCRM\Models\TicketType::find($ticketTypeId);
        if (!$ticketType || !is_array($ticketType->custom_fields)) {
            return;
        }

        if (isset($fields->custom_fields_section)) {
            $fields->custom_fields_section->hidden = false;
        }

        foreach ($ticketType->custom_fields as $field) {
            $fieldName = 'custom_fields_data[' . $field['name'] . ']';

            $config = [
                'label' => $field['label'],
                'type' => $field['type'] ?? 'text',
                'span' => $field['span'] ?? 'full',
            ];

            if ($field['type'] === 'dropdown' && !empty($field['options'])) {
                $options = [];
                $lines = explode("\n", str_replace("\r", "", $field['options']));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!$line)
                        continue;
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $options[trim($parts[0])] = trim($parts[1]);
                    } else {
                        $options[$line] = $line;
                    }
                }
                $config['options'] = $options;
            }

            $fields->{$fieldName} = $config;
        }
    }
}
