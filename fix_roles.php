<?php
$role = Spatie\Permission\Models\Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
$user = App\Models\User::find(4);
if ($user) {
    $user->assignRole($role);
    echo "Assigned sales role to user 4.\n";
}
