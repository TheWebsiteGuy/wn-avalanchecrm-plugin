<?php

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // 1. Rename column in projects_staff
        if (Schema::hasColumn('thewebsiteguy_nexuscrm_projects_staff', 'user_id')) {
            Schema::table('thewebsiteguy_nexuscrm_projects_staff', function (Blueprint $table) {
                $table->renameColumn('user_id', 'staff_id');
            });
        }

        // 2. Rename column in tickets_staff
        if (Schema::hasColumn('thewebsiteguy_nexuscrm_tickets_staff', 'user_id')) {
            Schema::table('thewebsiteguy_nexuscrm_tickets_staff', function (Blueprint $table) {
                $table->renameColumn('user_id', 'staff_id');
            });
        }

        // 3. Migrate data: replace User IDs with Staff IDs
        // This is tricky if data exists. Let's try to map them.
        $staffMap = DB::table('thewebsiteguy_nexuscrm_staff')->pluck('id', 'user_id');

        foreach ($staffMap as $userId => $staffId) {
            DB::table('thewebsiteguy_nexuscrm_projects_staff')
                ->where('staff_id', $userId)
                ->update(['staff_id' => $staffId]);

            DB::table('thewebsiteguy_nexuscrm_tickets_staff')
                ->where('staff_id', $userId)
                ->update(['staff_id' => $staffId]);
        }
    }

    public function down()
    {
        // Reverse is harder because we lose the User ID information if we don't store it.
        // But for this project scope, we can just rename back.
        Schema::table('thewebsiteguy_nexuscrm_projects_staff', function (Blueprint $table) {
            $table->renameColumn('staff_id', 'user_id');
        });

        Schema::table('thewebsiteguy_nexuscrm_tickets_staff', function (Blueprint $table) {
            $table->renameColumn('staff_id', 'user_id');
        });
    }
};
