<?php

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // Add timer state columns to tasks
        if (!Schema::hasColumn('thewebsiteguy_avalanchecrm_tasks', 'timer_running')) {
            Schema::table('thewebsiteguy_avalanchecrm_tasks', function (Blueprint $table) {
                $table->boolean('timer_running')->default(false)->after('is_invoiced');
                $table->timestamp('timer_started_at')->nullable()->after('timer_running');
            });
        }

        // Create time entries table for start/stop log
        if (!Schema::hasTable('thewebsiteguy_avalanchecrm_time_entries')) {
        Schema::create('thewebsiteguy_avalanchecrm_time_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('task_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->decimal('duration_hours', 8, 4)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('task_id');
            $table->index('user_id');
        });
        }
    }

    public function down()
    {
        Schema::dropIfExists('thewebsiteguy_avalanchecrm_time_entries');

        Schema::table('thewebsiteguy_avalanchecrm_tasks', function (Blueprint $table) {
            $table->dropColumn(['timer_running', 'timer_started_at']);
        });
    }
};
