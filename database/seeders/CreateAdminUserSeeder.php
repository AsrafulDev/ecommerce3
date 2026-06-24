<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::firstOrCreate(
            ['email' => 'asraful@curlware.com'],
            [
                'name' => 'MD ASRAFUL ISLAM',
                'password' => bcrypt('123456'),
                'status' => 1,
            ]
        );
      
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
       
        $permissions = Permission::pluck('id','id')->all();
     
        $role->syncPermissions($permissions);
       
        if (!$user->hasRole($role->id)) {
            $user->assignRole([$role->id]);
        }
    }
}
