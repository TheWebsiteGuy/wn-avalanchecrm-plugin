<?php

namespace TheWebsiteGuy\NexusCRM\Models;

use Winter\Storm\Database\Model;
use Winter\User\Models\User;
use Illuminate\Support\Str;
use Winter\Storm\Exception\ValidationException;

/**
 * Client Model
 */
class Client extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'thewebsiteguy_nexuscrm_clients';

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
        'subscriptions' => [\TheWebsiteGuy\NexusCRM\Models\Subscription::class],
    ];
    public $hasOneThrough = [];
    public $hasManyThrough = [];
    public $belongsTo = [
        'user' => [\Winter\User\Models\User::class]
    ];
    public $belongsToMany = [
        'projects' => [
            \TheWebsiteGuy\NexusCRM\Models\Project::class,
            'table' => 'thewebsiteguy_nexuscrm_projects_clients',
            'key' => 'client_id',
            'otherKey' => 'project_id'
        ]
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * Hook before creating a Client to automatically create an associated User.
     */
    public function beforeCreate()
    {
        // Bypass auto-creation if the Client is being created through an existing User relation
        if ($this->user_id) {
            return;
        }

        if (!$this->email) {
            throw new ValidationException(['email' => 'An email is required to create a User account.']);
        }

        // Check if user already exists with this email
        $existingUser = User::where('email', $this->email)->first();
        if ($existingUser) {
            throw new ValidationException(['email' => 'A user with this email already exists.']);
        }

        // Generate a random secure password
        $password = Str::random(12);

        // Create the new User
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->password = $password;
        $user->password_confirmation = $password;
        $user->is_activated = true; // Auto-activate
        $user->save();

        // Add user to Client group
        $clientGroup = \Winter\User\Models\UserGroup::where('code', 'client')->first();
        if ($clientGroup) {
            $user->groups()->add($clientGroup);
        }

        // Assign the newly created user_id to this client
        $this->user_id = $user->id;
    }
}
