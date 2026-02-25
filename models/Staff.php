<?php

namespace TheWebsiteGuy\AvalancheCRM\Models;

use Winter\Storm\Database\Model;
use Winter\User\Models\User;
use Winter\User\Models\UserGroup;
use Backend\Models\User as BackendUser;
use Backend\Models\UserRole;
use Illuminate\Support\Str;
use Winter\Storm\Exception\ValidationException;

/**
 * Staff Model
 */
class Staff extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'thewebsiteguy_avalanchecrm_staff';

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
    public $rules = [
        'name' => 'required',
        'email' => 'required|email',
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => [\Winter\User\Models\User::class]
    ];

    /**
     * Hook before creating Staff to automatically create associated User.
     */
    public function beforeCreate()
    {
        if ($this->user_id) {
            return;
        }

        // Check if user already exists
        $existingUser = User::where('email', $this->email)->first();
        if ($existingUser) {
            throw new ValidationException(['email' => 'A user with this email already exists in the system.']);
        }

        // Generate password
        $password = Str::random(12);

        // Create User
        $user = new User();
        $user->name = $this->name;
        // If name contains a space, try to split it into name and surname for the User model
        $nameParts = explode(' ', $this->name, 2);
        if (count($nameParts) > 1) {
            $user->name = $nameParts[0];
            $user->surname = $nameParts[1];
        }
        $user->email = $this->email;
        $user->password = $password;
        $user->password_confirmation = $password;
        $user->is_activated = true;
        $user->save();

        // Add to Staff group
        $staffGroup = UserGroup::where('code', 'staff')->first();
        if ($staffGroup) {
            $user->groups()->add($staffGroup);
        }

        $this->user_id = $user->id;

        // Create a backend administrator account with the CRM Staff role
        $this->createBackendUser($password);
    }

    /**
     * Create a backend (admin) user for this staff member with the CRM Staff role.
     */
    protected function createBackendUser(string $password = null): void
    {
        // Skip if a backend user already exists with this email
        if (BackendUser::where('email', $this->email)->exists()) {
            // Link existing backend user
            $existing = BackendUser::where('email', $this->email)->first();
            $this->backend_user_id = $existing->id;

            // Ensure they have the CRM role
            $crmRole = UserRole::where('code', 'avalanchecrm-staff')->first();
            if ($crmRole && $existing->role_id !== $crmRole->id) {
                $existing->role_id = $crmRole->id;
                $existing->forceSave();
            }
            return;
        }

        $password = $password ?: Str::random(12);

        $nameParts = explode(' ', $this->name, 2);
        $firstName = $nameParts[0];
        $lastName  = $nameParts[1] ?? '';

        $backendUser = new BackendUser();
        $backendUser->first_name = $firstName;
        $backendUser->last_name  = $lastName;
        $backendUser->login      = $this->email;
        $backendUser->email      = $this->email;
        $backendUser->password   = $password;
        $backendUser->password_confirmation = $password;
        $backendUser->is_activated = true;

        // Assign CRM Staff role
        $crmRole = UserRole::where('code', 'avalanchecrm-staff')->first();
        if ($crmRole) {
            $backendUser->role_id = $crmRole->id;
        }

        $backendUser->forceSave();

        $this->backend_user_id = $backendUser->id;
    }
}
