<?php

namespace TheWebsiteGuy\AvalancheCRM\Updates;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Winter\Storm\Database\Updates\Migration;

/**
 * Migrate everything from the old TheWebsiteGuy.NexusCRM plugin to the renamed
 * TheWebsiteGuy.AvalancheCRM plugin:
 *
 *  1. Rename all thewebsiteguy_nexuscrm_* tables → thewebsiteguy_avalanchecrm_*
 *  2. Rename the backend role code nexuscrm-staff → avalanchecrm-staff
 *  3. Rename the settings record code
 *  4. Delete the old plugin version/history rows (all migrations are idempotent)
 */
class RenameNexuscrmTablesToAvalanchecrm extends Migration
{
    protected string $oldPlugin = 'TheWebsiteGuy.NexusCRM';
    protected string $newPlugin = 'TheWebsiteGuy.AvalancheCRM';

    /**
     * Map of old table names to new table names.
     */
    protected array $tableMap = [
        'thewebsiteguy_nexuscrm_clients'             => 'thewebsiteguy_avalanchecrm_clients',
        'thewebsiteguy_nexuscrm_projects'            => 'thewebsiteguy_avalanchecrm_projects',
        'thewebsiteguy_nexuscrm_projects_clients'    => 'thewebsiteguy_avalanchecrm_projects_clients',
        'thewebsiteguy_nexuscrm_projects_staff'      => 'thewebsiteguy_avalanchecrm_projects_staff',
        'thewebsiteguy_nexuscrm_tickets'             => 'thewebsiteguy_avalanchecrm_tickets',
        'thewebsiteguy_nexuscrm_tickets_staff'       => 'thewebsiteguy_avalanchecrm_tickets_staff',
        'thewebsiteguy_nexuscrm_ticket_categories'   => 'thewebsiteguy_avalanchecrm_ticket_categories',
        'thewebsiteguy_nexuscrm_ticket_statuses'     => 'thewebsiteguy_avalanchecrm_ticket_statuses',
        'thewebsiteguy_nexuscrm_ticket_types'        => 'thewebsiteguy_avalanchecrm_ticket_types',
        'thewebsiteguy_nexuscrm_ticket_replies'      => 'thewebsiteguy_avalanchecrm_ticket_replies',
        'thewebsiteguy_nexuscrm_tasks'               => 'thewebsiteguy_avalanchecrm_tasks',
        'thewebsiteguy_nexuscrm_time_entries'        => 'thewebsiteguy_avalanchecrm_time_entries',
        'thewebsiteguy_nexuscrm_invoices'            => 'thewebsiteguy_avalanchecrm_invoices',
        'thewebsiteguy_nexuscrm_invoice_items'       => 'thewebsiteguy_avalanchecrm_invoice_items',
        'thewebsiteguy_nexuscrm_subscriptions'       => 'thewebsiteguy_avalanchecrm_subscriptions',
        'thewebsiteguy_nexuscrm_subscription_plans'  => 'thewebsiteguy_avalanchecrm_subscription_plans',
        'thewebsiteguy_nexuscrm_transactions'        => 'thewebsiteguy_avalanchecrm_transactions',
        'thewebsiteguy_nexuscrm_campaigns'           => 'thewebsiteguy_avalanchecrm_campaigns',
        'thewebsiteguy_nexuscrm_campaign_recipients' => 'thewebsiteguy_avalanchecrm_campaign_recipients',
        'thewebsiteguy_nexuscrm_email_templates'     => 'thewebsiteguy_avalanchecrm_email_templates',
        'thewebsiteguy_nexuscrm_staff'               => 'thewebsiteguy_avalanchecrm_staff',
    ];

    public function up()
    {
        // 1. Rename plugin tables
        foreach ($this->tableMap as $oldName => $newName) {
            if (Schema::hasTable($oldName) && !Schema::hasTable($newName)) {
                Schema::rename($oldName, $newName);
            }
        }

        // 2. Rename the backend role code
        if (Schema::hasTable('backend_user_roles')) {
            DB::table('backend_user_roles')
                ->where('code', 'nexuscrm-staff')
                ->update([
                    'code'        => 'avalanchecrm-staff',
                    'permissions' => json_encode([
                        'thewebsiteguy.avalanchecrm.*'               => 1,
                        'thewebsiteguy.avalanchecrm.manage_settings' => 1,
                        'thewebsiteguy.avalanchecrm.tickets.*'       => 1,
                        'thewebsiteguy.avalanchecrm.marketing.*'     => 1,
                    ]),
                ]);
        }

        // 3. Rename the settings record
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->where('item', 'thewebsiteguy_nexuscrm_settings')
                ->update(['item' => 'thewebsiteguy_avalanchecrm_settings']);
        }

        // 4. Clean up old plugin registration (all migrations are idempotent
        //    so Winter can safely re-run them under the new plugin code)
        if (Schema::hasTable('system_plugin_versions')) {
            DB::table('system_plugin_versions')
                ->where('code', $this->oldPlugin)
                ->delete();
        }

        if (Schema::hasTable('system_plugin_history')) {
            DB::table('system_plugin_history')
                ->where('code', $this->oldPlugin)
                ->delete();
        }
    }

    public function down()
    {
        // Reverse table renames
        foreach ($this->tableMap as $oldName => $newName) {
            if (Schema::hasTable($newName) && !Schema::hasTable($oldName)) {
                Schema::rename($newName, $oldName);
            }
        }

        // Reverse role code
        if (Schema::hasTable('backend_user_roles')) {
            DB::table('backend_user_roles')
                ->where('code', 'avalanchecrm-staff')
                ->update([
                    'code'        => 'nexuscrm-staff',
                    'permissions' => json_encode([
                        'thewebsiteguy.nexuscrm.*'               => 1,
                        'thewebsiteguy.nexuscrm.manage_settings' => 1,
                        'thewebsiteguy.nexuscrm.tickets.*'       => 1,
                        'thewebsiteguy.nexuscrm.marketing.*'     => 1,
                    ]),
                ]);
        }

        // Reverse settings code
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->where('item', 'thewebsiteguy_avalanchecrm_settings')
                ->update(['item' => 'thewebsiteguy_nexuscrm_settings']);
        }
    }
}
