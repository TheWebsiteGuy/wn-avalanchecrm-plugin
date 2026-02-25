<?php

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasColumn('thewebsiteguy_avalanchecrm_staff', 'department')) {
            return;
        }

        Schema::table('thewebsiteguy_avalanchecrm_staff', function (Blueprint $table) {
            $table->string('department')->nullable()->after('job_title');
        });
    }

    public function down()
    {
        Schema::table('thewebsiteguy_avalanchecrm_staff', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
