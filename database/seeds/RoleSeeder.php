<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert some stuff
	    DB::table('roles')->insert(
            array(
                [
                    'id'    => 1,
                    'name'  => 'Super Admin',
                    'description' => 'Super Admin',
                    'guard_name' => 'web',
                ],
               
            )
        );
    }
}
