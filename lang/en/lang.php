<?php

return [
    'plugin' => [
        'name' => 'Nexus CRM',
        'description' => 'No description provided yet...',
    ],
    'permissions' => [
        'some_permission' => 'Some permission',
    ],
    'models' => [
        'general' => [
            'id' => 'ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],
        'client' => [
            'label' => 'Client',
            'label_plural' => 'Clients',
        ],
        'project' => [
            'label' => 'Project',
            'label_plural' => 'Projects',
        ],
        'ticket' => [
            'label' => 'Ticket',
            'label_plural' => 'Tickets',
        ],
        'invoice' => [
            'label' => 'Invoice',
            'label_plural' => 'Invoices',
        ],
        'subscription' => [
            'label' => 'Subscription',
            'label_plural' => 'Subscriptions',
        ],
        'staff' => [
            'label' => 'Staff member',
            'label_plural' => 'Staff',
        ],
    ],
    'navigation' => [
        'staff' => 'Staff',
    ],
];
