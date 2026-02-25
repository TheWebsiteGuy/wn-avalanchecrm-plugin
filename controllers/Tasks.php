<?php

namespace TheWebsiteGuy\AvalancheCRM\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;

/**
 * Tasks Backend Controller
 */
class Tasks extends Controller
{
    use \TheWebsiteGuy\AvalancheCRM\Traits\HasTaskModal;

    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    /**
     * @var array Permissions required to view this page.
     */
    protected $requiredPermissions = [
        'thewebsiteguy.avalanchecrm.tasks.manage_all',
    ];

    public function listExtendQuery($query)
    {
        if ($status = request()->get('status')) {
            $query->where('status', $status);
        }
    }

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'projects');
    }

    /**
     * Kanban View Action
     */
    public function kanban()
    {
        $this->pageTitle = 'Project Kanban Board';

        // Load tasks grouped by status
        $this->vars['tasks'] = \TheWebsiteGuy\AvalancheCRM\Models\Task::orderBy('sort_order')->get()->groupBy('status');
    }
}
