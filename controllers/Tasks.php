<?php

namespace TheWebsiteGuy\NexusCRM\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;

/**
 * Tasks Backend Controller
 */
class Tasks extends Controller
{
    use \TheWebsiteGuy\NexusCRM\Traits\HasTaskModal;

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
        'thewebsiteguy.nexuscrm.tasks.manage_all',
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('TheWebsiteGuy.NexusCRM', 'nexuscrm', 'projects');
    }

    /**
     * Kanban View Action
     */
    public function kanban()
    {
        $this->pageTitle = 'Project Kanban Board';

        // Load tasks grouped by status
        $this->vars['tasks'] = \TheWebsiteGuy\NexusCRM\Models\Task::orderBy('sort_order')->get()->groupBy('status');
    }
}
