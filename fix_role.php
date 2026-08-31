<?php
$user = \App\Models\User::where('email', 'engabdelrahmanusama@gmail.com')->first();
$role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'brand_manager', 'guard_name' => 'web']);
$user->assignRole($role);
echo 'Success';
