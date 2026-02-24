<?php

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('thewebsiteguy_nexuscrm_staff', function (Blueprint $table) {
            $table->string('department')->nullable()->after('job_title');
        });
    }

    public function down()
    {
        Schema::table('thewebsiteguy_nexuscrm_staff', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
