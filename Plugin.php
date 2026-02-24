<?php

namespace TheWebsiteGuy\NexusCRM;

use Backend\Facades\Backend;
use Backend\Models\UserRole;
use System\Classes\PluginBase;
use Winter\User\Models\User as UserModel;
use Winter\User\Controllers\Users as UsersController;
use Event;

/**
 * NexusCRM Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name' => 'thewebsiteguy.nexuscrm::lang.plugin.name',
            'description' => 'thewebsiteguy.nexuscrm::lang.plugin.description',
            'author' => 'TheWebsiteGuy',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register(): void
    {
        // Winter CMS has no "public" directory — set dompdf's base path to the project root.
        $this->app['config']->set('dompdf.public_path', base_path());

        $this->app->register(\Barryvdh\DomPDF\ServiceProvider::class);
    }

    /**
     * Registers any markup tags implemented by this plugin.
     */
    public function registerMarkupTags(): array
    {
        return [
            'filters' => [
                'currency' => function ($value) {
                    $settings = \TheWebsiteGuy\NexusCRM\Models\Settings::instance();
                    $symbol = $settings->currency_symbol ?: '$';
                    $code = $settings->currency_code ?: 'USD';

                    return $symbol . number_format($value, 2) . ' ' . $code;
                }
            ]
        ];
    }

    /**
     * Boot method, called right before the request route.
     */
    public function boot(): void
    {
        UserModel::extend(function ($model) {
            $model->hasOne['client'] = [\TheWebsiteGuy\NexusCRM\Models\Client::class];
            $model->hasOne['staff'] = [\TheWebsiteGuy\NexusCRM\Models\Staff::class];

            $model->bindEvent('model.afterCreate', function () use ($model) {
                if (request()->input('is_staff')) {
                    $staff = new \TheWebsiteGuy\NexusCRM\Models\Staff();
                    $staff->user_id = $model->id;
                    $staff->name = trim($model->name . ' ' . $model->surname);
                    $staff->email = $model->email;
                    $staff->save();
                }

                if (request()->input('is_client')) {
                    $client = new \TheWebsiteGuy\NexusCRM\Models\Client();
                    $client->user_id = $model->id;
                    $client->name = trim($model->name . ' ' . $model->surname);
                    $client->email = $model->email;
                    $client->save();
                }
            });

            $model->bindEvent('model.afterSave', function () use ($model) {
                if ($staffData = post('staff')) {
                    $staff = $model->staff ?: new \TheWebsiteGuy\NexusCRM\Models\Staff();
                    $staff->user_id = $model->id;
                    $staff->name = trim($model->name . ' ' . $model->surname);
                    $staff->email = $model->email;
                    $staff->fill($staffData);
                    $staff->save();
                }
            });
        });

        Event::listen('backend.form.extendFieldsBefore', function ($widget) {
            if (!$widget->getController() instanceof UsersController) {
                return;
            }

            if (!$widget->model instanceof UserModel) {
                return;
            }

            // Pre-select 'Client' group if redirecting from CRM
            if ($widget->getContext() === 'create' && request()->input('is_client')) {
                $clientGroup = \Winter\User\Models\UserGroup::where('code', 'client')->first();
                if ($clientGroup) {
                    if (isset($widget->tabs['fields']['groups'])) {
                        $widget->tabs['fields']['groups']['default'] = [$clientGroup->id];
                    }
                }
            }

            // Pre-select 'Staff' group if redirecting from CRM
            if ($widget->getContext() === 'create' && request()->input('is_staff')) {
                $staffGroup = \Winter\User\Models\UserGroup::where('code', 'staff')->first();
                if ($staffGroup) {
                    if (isset($widget->tabs['fields']['groups'])) {
                        $widget->tabs['fields']['groups']['default'] = [$staffGroup->id];
                    }
                }
            }
        });

        Event::listen('backend.form.extendFields', function ($widget) {
            if (!$widget->getController() instanceof UsersController) {
                return;
            }

            if (!$widget->model instanceof UserModel) {
                return;
            }

            // Only show Staff tab if the user is in the Staff group or is_staff is requested
            $isStaff = $widget->model->groups()->where('code', 'staff')->exists() || request()->input('is_staff');

            if ($isStaff) {
                $widget->addTabFields([
                    'staff[job_title]' => [
                        'label' => 'Job Title',
                        'tab' => 'Staff',
                        'span' => 'left'
                    ],
                    'staff[department]' => [
                        'label' => 'Department',
                        'tab' => 'Staff',
                        'span' => 'right'
                    ]
                ]);
            }
        });

    }

    /**
     * Registers any frontend components implemented in this plugin.
     */
    public function registerComponents(): array
    {
        return [
            \TheWebsiteGuy\NexusCRM\Components\Subscriptions::class => 'subscriptions',
            \TheWebsiteGuy\NexusCRM\Components\ClientProjects::class => 'projects',
            \TheWebsiteGuy\NexusCRM\Components\ClientTickets::class => 'tickets',
            \TheWebsiteGuy\NexusCRM\Components\ClientInvoices::class => 'invoices',
        ];
    }

    /**
     * Registers any backend permissions used by this plugin.
     */
    public function registerPermissions(): array
    {
        return []; // Remove this line to activate

        return [
            'thewebsiteguy.nexuscrm.some_permission' => [
                'tab' => 'thewebsiteguy.nexuscrm::lang.plugin.name',
                'label' => 'thewebsiteguy.nexuscrm::lang.permissions.some_permission',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
        ];
    }

    /**
     * Registers backend navigation items for this plugin.
     */
    public function registerNavigation(): array
    {
        return [
            'nexuscrm' => [
                'label' => 'CRM',
                'url' => Backend::url('thewebsiteguy/nexuscrm/clients'),
                'icon' => 'icon-users',
                'permissions' => ['thewebsiteguy.nexuscrm.*'],
                'order' => 500,
                'sideMenu' => [
                    'clients' => [
                        'label' => 'Clients',
                        'icon' => 'icon-users',
                        'url' => Backend::url('thewebsiteguy/nexuscrm/clients'),
                        'permissions' => ['thewebsiteguy.nexuscrm.*'],
                    ],
                    'staff' => [
                        'label' => 'Staff',
                        'icon' => 'icon-user-tie',
                        'url' => Backend::url('thewebsiteguy/nexuscrm/staff'),
                        'permissions' => ['thewebsiteguy.nexuscrm.*'],
                    ],
                    'projects' => [
                        'label' => 'Projects',
                        'icon' => 'icon-briefcase',
                        'url' => Backend::url('thewebsiteguy/nexuscrm/projects'),
                        'permissions' => ['thewebsiteguy.nexuscrm.*'],
                    ],
                    'tickets' => [
                        'label' => 'Tickets',
                        'icon' => 'icon-ticket',
                        'url' => Backend::url('thewebsiteguy/nexuscrm/tickets'),
                        'permissions' => ['thewebsiteguy.nexuscrm.*'],
                    ],
                    'invoices' => [
                        'label' => 'Invoices',
                        'icon' => 'icon-file-text-o',
                        'url' => Backend::url('thewebsiteguy/nexuscrm/invoices'),
                        'permissions' => ['thewebsiteguy.nexuscrm.*'],
                    ],
                    'subscriptions' => [
                        'label' => 'Subscriptions',
                        'icon' => 'icon-refresh',
                        'url' => Backend::url('thewebsiteguy/nexuscrm/subscriptions'),
                        'permissions' => ['thewebsiteguy.nexuscrm.*'],
                    ],
                    'tasks' => [
                        'label' => 'Tasks',
                        'icon' => 'icon-check-square-o',
                        'url' => Backend::url('thewebsiteguy/nexuscrm/tasks'),
                        'permissions' => ['thewebsiteguy.nexuscrm.*'],
                    ],
                ]
            ],
        ];
    }

    /**
     * Registers backend settings for this plugin.
     */
    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label' => 'CRM Settings',
                'description' => 'Configure theme colours, currency, invoices and payment gateways.',
                'category' => 'NexusCRM',
                'icon' => 'icon-cog',
                'class' => \TheWebsiteGuy\NexusCRM\Models\Settings::class,
                'order' => 500,
                'keywords' => 'crm payments stripe paypal gocardless settings',
                'permissions' => ['thewebsiteguy.nexuscrm.manage_settings']
            ]
        ];
    }
}
