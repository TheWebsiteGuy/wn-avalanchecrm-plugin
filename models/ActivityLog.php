<?php

namespace TheWebsiteGuy\AvalancheCRM\Models;

use Winter\Storm\Database\Model;
use Backend\Models\User as BackendUser;
use Winter\User\Models\User as FrontendUser;

/**
 * ActivityLog Model
 */
class ActivityLog extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'thewebsiteguy_avalanchecrm_activity_logs';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'user_id',
        'staff_id',
        'client_id',
        'module',
        'action',
        'message',
        'object_type',
        'object_id',
        'data',
    ];

    /**
     * @var array Jsonable fields
     */
    protected $jsonable = ['data'];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => [BackendUser::class],
        'staff' => [\TheWebsiteGuy\AvalancheCRM\Models\Staff::class],
        'client' => [\TheWebsiteGuy\AvalancheCRM\Models\Client::class],
    ];

    /**
     * @var array Morph To Relations
     */
    public $morphTo = [
        'object' => []
    ];

    /**
     * Log an activity
     */
    public static function log($message, $module = null, $action = null, $object = null, $data = null)
    {
        $log = new static();
        $log->message = $message;
        $log->module = $module;
        $log->action = $action;
        $log->data = $data;

        // Determine user
        if ($user = \Backend\Facades\BackendAuth::getUser()) {
            $log->user_id = $user->id;
            $log->staff_id = \TheWebsiteGuy\AvalancheCRM\Models\Staff::where('backend_user_id', $user->id)->value('id');
        } elseif ($user = \Winter\User\Facades\Auth::getUser()) {
            $log->client_id = \TheWebsiteGuy\AvalancheCRM\Models\Client::where('user_id', $user->id)->value('id');
        }

        // Object association
        if ($object && $object instanceof Model) {
            $log->object_type = get_class($object);
            $log->object_id = $object->getKey();
        }

        $log->save();

        return $log;
    }
}
