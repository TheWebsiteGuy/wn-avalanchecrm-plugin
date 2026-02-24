<?php

use Winter\Storm\Database\Updates\Migration;
use Winter\User\Models\UserGroup;

return new class extends Migration {
    public function up()
    {
        if (!class_exists(UserGroup::class)) {
            return;
        }

        if (!UserGroup::where('code', 'clients')->exists()) {
            UserGroup::create([
                'name' => 'Clients',
                'code' => 'clients',
                'description' => 'CRM Clients Group'
            ]);
        }
    }

    public function down()
    {
        if (!class_exists(UserGroup::class)) {
            return;
        }

        $group = UserGroup::where('code', 'clients')->first();
        if ($group) {
            $group->delete();
        }
    }
};
