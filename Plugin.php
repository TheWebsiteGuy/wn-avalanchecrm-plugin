<?php

namespace TheWebsiteGuy\AvalancheCRM;

use Backend\Facades\Backend;
use System\Classes\PluginBase;
use Winter\Storm\Support\Facades\Schema;
use Winter\User\Models\User as UserModel;
use Winter\User\Models\UserGroup;
use Winter\User\Controllers\Users as UsersController;
use Backend\Models\User as BackendUserModel;
use Event;
use TheWebsiteGuy\AvalancheCRM\Models\Settings;

/**
 * Avalanche CRM Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name' => 'thewebsiteguy.avalanchecrm::lang.plugin.name',
            'description' => 'thewebsiteguy.avalanchecrm::lang.plugin.description',
            'author' => 'TheWebsiteGuy',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register(): void
    {
        // Winter CMS has no "public" directory â€” set dompdf's base path to the project root.
        $this->app['config']->set('dompdf.public_path', base_path());

        $this->app->register(\Barryvdh\DomPDF\ServiceProvider::class);

        // Register console commands
        $this->registerConsoleCommand('avalanchecrm.send-overdue-reminders', \TheWebsiteGuy\AvalancheCRM\Console\SendOverdueReminders::class);
        $this->registerConsoleCommand('avalanchecrm.send-renewal-reminders', \TheWebsiteGuy\AvalancheCRM\Console\SendRenewalReminders::class);
    }

    /**
     * Register scheduled tasks.
     */
    public function registerSchedule($schedule): void
    {
        // Send overdue invoice reminders daily at 9:00 AM
        $schedule->command('avalanchecrm:send-overdue-reminders')->dailyAt('09:00');

        // Send subscription renewal reminders daily at 9:00 AM
        $schedule->command('avalanchecrm:send-renewal-reminders')->dailyAt('09:00');
    }

    /**
     * Registers any markup tags implemented by this plugin.
     */
    public function registerMarkupTags(): array
    {
        return [
            'filters' => [
                'currency' => function ($value) {
                    $settings = \TheWebsiteGuy\AvalancheCRM\Models\Settings::instance();
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
        $this->ensureUserGroupsExist();

        /*
                BackendUserModel::extend(function ($model) {
                    $model->bindEvent('model.afterSave', function () use ($model) {
                        // Sync to Staff if linked via backend_user_id
                        $staff = \TheWebsiteGuy\AvalancheCRM\Models\Staff::where('backend_user_id', $model->id)->first();
                        if ($staff) {
                            $staff->name = trim($model->first_name . ' ' . $model->last_name);
                            $staff->email = $model->email;
                            $staff->save();
                        }
                    });
                });
        */

        UserModel::extend(function ($model) {
            $model->hasOne['client'] = [\TheWebsiteGuy\AvalancheCRM\Models\Client::class];
            $model->hasOne['staff'] = [\TheWebsiteGuy\AvalancheCRM\Models\Staff::class];

            $model->bindEvent('model.beforeValidate', function () use ($model) {
                // trace_log('UserModel::beforeValidate called. Attributes: ' . json_encode($model->getAttributes()));
            });

            $model->bindEvent('model.afterCreate', function () use ($model) {
                // If we are in the backend User form, the FormController will automatically
                // create the related Staff/Client models via deferred bindings. We only need 
                // to manually create them if we are NOT in the backend form.
                if (request()->has('User')) {
                    return;
                }

                // Determine name and email
                $name = trim(($model->name ?? '') . ' ' . ($model->surname ?? ''));
                $email = $model->email;

                if (request()->input('is_staff')) {
                    $staff = new \TheWebsiteGuy\AvalancheCRM\Models\Staff();
                    $staff->user_id = $model->id;
                    $staff->name = $name ?: $email; // Fallback to email if name is missing
                    $staff->email = $email;
                    $staff->save();
                }

                if (request()->input('is_client')) {
                    $client = new \TheWebsiteGuy\AvalancheCRM\Models\Client();
                    $client->user_id = $model->id;
                    $client->name = $name ?: $email;
                    $client->email = $email;
                    $client->save();
                }
            });

            $model->bindEvent('model.afterSave', function () use ($model) {
                // Always sync email and name to associated Staff if they exist
                $staff = $model->staff;
                $isStaff = $model->groups()->where('code', 'staff')->exists() || request()->input('is_staff');

                if (!$staff && $isStaff) {
                    $staff = new \TheWebsiteGuy\AvalancheCRM\Models\Staff();
                    $staff->user_id = $model->id;
                    $model->setRelation('staff', $staff);
                }

                if ($staff) {
                    if ($staffData = post('staff')) {
                        $staff->fill($staffData);
                    }
                    $staff->name = trim(($model->name ?? '') . ' ' . ($model->surname ?? '')) ?: $model->email;
                    $staff->email = $model->email;
                    $staff->save();
                }

                // Sync for Client
                $client = $model->client;
                $isClient = $model->groups()->where('code', 'client')->exists() || request()->input('is_client');

                if (!$client && $isClient) {
                    $client = new \TheWebsiteGuy\AvalancheCRM\Models\Client();
                    $client->user_id = $model->id;
                    $model->setRelation('client', $client);
                }

                if ($client) {
                    $client->name = trim(($model->name ?? '') . ' ' . ($model->surname ?? '')) ?: $model->email;
                    $client->email = $model->email;
                    $client->save();

                    // Save marketing opt-out preference for clients
                    if ($marketingData = post('client_marketing')) {
                        $client->marketing_opt_out = !empty($marketingData['marketing_opt_out']);
                        $client->save();
                    }
                }
            });
        });

        Event::listen('backend.form.extendFieldsBefore', function ($widget) {
            if ($widget->getController() instanceof UsersController && post()) {
                trace_log('Form submission data: ' . json_encode(post()));
            }

            if (!$widget->getController() instanceof UsersController) {
                return;
            }

            if (!$widget->model instanceof UserModel) {
                return;
            }

            // Pre-select 'Client' group if redirecting from CRM
            if ($widget->getContext() === 'create' && request()->input('is_client')) {
                $clientGroup = UserGroup::where('code', 'client')->first();
                if ($clientGroup) {
                    if (isset($widget->tabs['fields']['groups'])) {
                        $widget->tabs['fields']['groups']['default'] = [$clientGroup->id];
                    }
                }
            }

            // Pre-select 'Staff' group if redirecting from CRM
            if ($widget->getContext() === 'create' && request()->input('is_staff')) {
                $staffGroup = UserGroup::where('code', 'staff')->first();
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

            // Only show Client tabs if the user is in the Client group or is_client is requested
            $isClient = $widget->model->groups()->where('code', 'client')->exists() || request()->input('is_client');

            if ($isClient) {
                $settings = Settings::instance();
                $fields = [];

                if ($settings->enable_marketing) {
                    $fields['client_marketing'] = [
                        'tab' => 'Marketing',
                        'type' => 'partial',
                        'path' => '$/thewebsiteguy/avalanchecrm/views/user_tabs/_marketing.htm'
                    ];
                }

                if ($settings->enable_tickets) {
                    $fields['client_tickets'] = [
                        'tab' => 'Tickets',
                        'type' => 'partial',
                        'path' => '$/thewebsiteguy/avalanchecrm/views/user_tabs/_tickets.htm'
                    ];
                }

                if ($settings->enable_projects) {
                    $fields['client_projects'] = [
                        'tab' => 'Projects',
                        'type' => 'partial',
                        'path' => '$/thewebsiteguy/avalanchecrm/views/user_tabs/_projects.htm'
                    ];
                }

                if ($settings->enable_invoices) {
                    $fields['client_invoices'] = [
                        'tab' => 'Invoices',
                        'type' => 'partial',
                        'path' => '$/thewebsiteguy/avalanchecrm/views/user_tabs/_invoices.htm'
                    ];
                }

                if ($settings->enable_subscriptions) {
                    $fields['client_subscriptions'] = [
                        'tab' => 'Subscriptions',
                        'type' => 'partial',
                        'path' => '$/thewebsiteguy/avalanchecrm/views/user_tabs/_subscriptions.htm'
                    ];
                }

                $widget->addTabFields($fields);
            }
        });

        // Conditionally hide navigation items
        Event::listen('backend.menu.extendItems', function ($manager) {
            $settings = Settings::instance();

            if (!$settings->enable_projects) {
                $manager->removeSideMenuItem('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'projects');
            }

            if (!$settings->enable_tickets) {
                $manager->removeSideMenuItem('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'tickets');
            }

            if (!$settings->enable_invoices) {
                $manager->removeSideMenuItem('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'invoices');
            }

            if (!$settings->enable_subscriptions) {
                $manager->removeSideMenuItem('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'subscriptions');
            }

            if (!$settings->enable_marketing) {
                $manager->removeSideMenuItem('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'campaigns');
                $manager->removeSideMenuItem('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'templates');
            }
        });
    }

    /**
     * Ensure required user groups exist for the CRM.
     */
    protected function ensureUserGroupsExist(): void
    {
        if (!class_exists(UserGroup::class) || !Schema::hasTable('user_groups')) {
            return;
        }

        $groups = [
            [
                'name' => 'Client',
                'code' => 'client',
                'description' => 'CRM Clients Group',
            ],
            [
                'name' => 'Staff',
                'code' => 'staff',
                'description' => 'CRM Staff Group',
            ],
        ];

        foreach ($groups as $group) {
            if (!UserGroup::where('code', $group['code'])->exists()) {
                UserGroup::create($group);
            }
        }

        // Ensure the CRM Staff backend role exists
        $this->ensureBackendRoleExists();
    }

    /**
     * Ensure the CRM Staff backend role exists with all required permissions.
     */
    protected function ensureBackendRoleExists(): void
    {
        if (!Schema::hasTable('backend_user_roles')) {
            return;
        }

        // Check by both code and name to avoid unique-validation failures
        // after a plugin rename where the old role still exists.
        $exists = \Db::table('backend_user_roles')
            ->where('code', 'avalanchecrm-staff')
            ->orWhere('name', 'CRM Staff')
            ->exists();

        if (!$exists) {
            \Db::table('backend_user_roles')->insert([
                'name' => 'CRM Staff',
                'code' => 'avalanchecrm-staff',
                'description' => 'Backend role for CRM staff members with access to all CRM features and settings.',
                'permissions' => json_encode([
                    'thewebsiteguy.avalanchecrm.*' => 1,
                    'thewebsiteguy.avalanchecrm.manage_settings' => 1,
                    'thewebsiteguy.avalanchecrm.tickets.*' => 1,
                    'thewebsiteguy.avalanchecrm.marketing.*' => 1,
                ]),
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Registers any frontend components implemented in this plugin.
     */
    public function registerComponents(): array
    {
        return [
            \TheWebsiteGuy\AvalancheCRM\Components\Dashboard::class => 'dashboard',
            \TheWebsiteGuy\AvalancheCRM\Components\Subscriptions::class => 'subscriptions',
            \TheWebsiteGuy\AvalancheCRM\Components\Projects::class => 'projects',
            \TheWebsiteGuy\AvalancheCRM\Components\Tickets::class => 'tickets',
            \TheWebsiteGuy\AvalancheCRM\Components\Invoices::class => 'invoices',
            \TheWebsiteGuy\AvalancheCRM\Components\Account::class => 'crmAccount',
        ];
    }

    /**
     * Registers any backend permissions used by this plugin.
     */
    public function registerPermissions(): array
    {
        return [
            'thewebsiteguy.avalanchecrm.*' => [
                'tab' => 'Avalanche CRM',
                'label' => 'Manage all CRM features',
            ],
            'thewebsiteguy.avalanchecrm.manage_settings' => [
                'tab' => 'Avalanche CRM',
                'label' => 'Manage CRM Settings',
            ],
            'thewebsiteguy.avalanchecrm.tickets.*' => [
                'tab' => 'Avalanche CRM',
                'label' => 'Access Tickets section',
            ],
            'thewebsiteguy.avalanchecrm.marketing.*' => [
                'tab' => 'Avalanche CRM',
                'label' => 'Manage Email Marketing',
            ],
        ];
    }

    /**
     * Registers backend navigation items for this plugin.
     */
    public function registerNavigation(): array
    {
        return [
            'avalanchecrm' => [
                'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.crm',
                'url' => Backend::url('thewebsiteguy/avalanchecrm/dashboard'),
                'iconSvg' => '/plugins/thewebsiteguy/avalanchecrm/assets/images/mountain.svg',
                'permissions' => ['thewebsiteguy.avalanchecrm.*'],
                'order' => 500,
                'sideMenu' => [
                    'dashboard' => [
                        'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.dashboard',
                        'icon' => 'icon-dashboard',
                        'url' => Backend::url('thewebsiteguy/avalanchecrm/dashboard'),
                        'permissions' => ['thewebsiteguy.avalanchecrm.*'],
                    ],
                    'clients' => [
                        'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.clients',
                        'icon' => 'icon-users',
                        'url' => Backend::url('thewebsiteguy/avalanchecrm/clients'),
                        'permissions' => ['thewebsiteguy.avalanchecrm.*'],
                    ],
                    'staff' => [
                        'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.staff',
                        'icon' => 'icon-user-tie',
                        'url' => Backend::url('thewebsiteguy/avalanchecrm/staff'),
                        'permissions' => ['thewebsiteguy.avalanchecrm.*'],
                    ],
                    'projects' => [
                        'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.projects',
                        'icon' => 'icon-briefcase',
                        'url' => Backend::url('thewebsiteguy/avalanchecrm/projects'),
                        'permissions' => ['thewebsiteguy.avalanchecrm.*'],
                    ],
                    'tickets' => [
                        'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.tickets',
                        'icon' => 'icon-ticket',
                        'url' => Backend::url('thewebsiteguy/avalanchecrm/tickets'),
                        'permissions' => ['thewebsiteguy.avalanchecrm.*'],
                    ],
                    'invoices' => [
                        'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.invoices',
                        'icon' => 'icon-file-text-o',
                        'url' => Backend::url('thewebsiteguy/avalanchecrm/invoices'),
                        'permissions' => ['thewebsiteguy.avalanchecrm.*'],
                    ],
                    'subscriptions' => [
                        'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.subscriptions',
                        'icon' => 'icon-refresh',
                        'url' => Backend::url('thewebsiteguy/avalanchecrm/subscriptions'),
                        'permissions' => ['thewebsiteguy.avalanchecrm.*'],
                    ],
                    'campaigns' => [
                        'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.marketing',
                        'icon' => 'icon-envelope',
                        'url' => Backend::url('thewebsiteguy/avalanchecrm/campaigns'),
                        'permissions' => ['thewebsiteguy.avalanchecrm.marketing.*'],
                        'sideMenu' => [
                            'campaigns' => [
                                'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.campaigns',
                                'icon' => 'icon-bullhorn',
                                'url' => Backend::url('thewebsiteguy/avalanchecrm/campaigns'),
                                'permissions' => ['thewebsiteguy.avalanchecrm.marketing.*'],
                            ],
                        ]
                    ],
                    'templates' => [
                        'label' => 'thewebsiteguy.avalanchecrm::lang.navigation.email_templates',
                        'icon' => 'icon-file-code-o',
                        'url' => Backend::url('thewebsiteguy/avalanchecrm/emailtemplates'),
                        'permissions' => ['thewebsiteguy.avalanchecrm.marketing.*'],
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
                'label' => 'thewebsiteguy.avalanchecrm::lang.models.settings.label',
                'description' => 'thewebsiteguy.avalanchecrm::lang.models.settings.description',
                'category' => 'Avalanche CRM',
                'icon' => 'icon-cog',
                'class' => \TheWebsiteGuy\AvalancheCRM\Models\Settings::class,
                'order' => 500,
                'keywords' => 'crm payments stripe paypal gocardless settings',
                'permissions' => ['thewebsiteguy.avalanchecrm.manage_settings']
            ]
        ];
    }
}
