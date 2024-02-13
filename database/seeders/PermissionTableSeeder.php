<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void{
        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user = User::find(1);
        $user->assignRole('admin');

        // Create additional users as needed
        //User::factory(5)->create(); // Create 5 more users with random data
        $permissions = [
            'create supplier',
            'edit supplier',
            'delete supplier',
            'read supplier',
        
            'create goods',
            'edit goods',
            'delete goods',
            'read goods',
        
            'create transaction',
            'edit transaction',
            'delete transaction',
            'read transaction',
        
            'create project',
            'edit project',
            'delete project',
            'read project',
        
            'create report',
            'edit report',
            'delete report',
            'read report',
        
            'create buildings',
            'edit buildings',
            'delete buildings',
            'read buildings',
        ];
        $adminRole = Role::where('name', 'admin')->first();
        foreach($permissions as $permission){
            Permission::create(['name' => $permission]);
            $adminRole->givePermissionTo($permission);
        }        
     
    }
}
