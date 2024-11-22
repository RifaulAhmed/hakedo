<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       	// Insert some stuffs
        DB::table('payment_methods')->insert(
            array([
                'id'           => 1,
                'title'        => 'Other Payment Method',
                'is_default'   => 0,

            ],
            [
                'id'           => 2,
                'title'        => 'Paypal',
                'is_default'   => 1,

            ],
            [
                'id'           => 3,
                'title'        => 'Bank transfer',
                'is_default'   => 0,

            ],
            [
                'id'           => 4,
                'title'        => 'Credit card',
                'is_default'   => 1,

            ],
            [
                'id'           => 5,
                'title'        => 'Check',
                'is_default'   => 0,

            ],
            [
                'id'           => 6,
                'title'        => 'Cash',
                'is_default'   => 0,

            ],
            )
            
        );
    }
}
