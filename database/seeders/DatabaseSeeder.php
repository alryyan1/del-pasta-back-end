<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::create(['name'=>'admin', 'username'=>'admin','password'=>bcrypt('1234')]);
        // Customer::create(['name'=>'default','phone'=>'','address'=>'','area'=>'','state'=>'']);
        // $role = Role::create(['name' => 'admin']);
        // $staff = Role::create(['name' => 'staff']);

        // $add_user_permission = Permission::create(['name' => 'add user']);
        // $discount_permission = Permission::create(['name' => 'discount']);
        // $cacnel_permission = Permission::create(['name' => 'cancel']);

        // $role->givePermissionTo([$add_user_permission,$discount_permission,$cacnel_permission]);
        // $user = User::find(1);
        // $user->assignRole('admin');


        // // \App\Models\User::factory(10)->create();

        // // \App\Models\User::factory()->create([
        // //     'name' => 'Test User',
        // //     'email' => 'test@example.com',
        // // ]);
        // $this->call(CategoriesTableSeeder::class);
        // $this->call(MealsTableSeeder::class);
        // $this->call(SettingsTableSeeder::class);
        // $this->call(ServicesTableSeeder::class);
        // $this->call(ChildMealsTableSeeder::class);


        $this->call([
            CategorySeeder::class,
            BuffetPackageSeeder::class,
            BuffetPersonOptionSeeder::class,
            BuffetStepSeeder::class,
            BuffetJuiceRuleSeeder::class,
        ]);
    }
}
