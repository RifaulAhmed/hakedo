<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailMessagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       	// Insert some stuffs
        DB::table('email_messages')->insert(
            array(
                [
                    'id'         => 1,
                    'name'       => 'sale',
                    'subject'    => 'Thank you for your purchase!',
                    'body'       => '<h1><span>Dear  {contact_name},</span></h1><p style="color:rgb(17,24,39);font-size:16px;">Thank you for your purchase! Your invoice number is {invoice_number}.</p><p style="color:rgb(17,24,39);font-size:16px;">If you have any questions or concerns, please don\'t hesitate to reach out to us. We are here to help!</p><p style="color:rgb(17,24,39);font-size:16px;">Best regards,</p><p style="color:rgb(17,24,39);font-size:16px;"><span>{business_name}</span></p>',

                ],

                [
                    'id'         => 2,
                    'name'       => 'quotation',
                    'subject'    => 'Thank you for your interest in our products !',
                    'body'       => '<p style="color:rgb(17,24,39);font-size:16px;"><span>Dear {contact_name},</span></p><p style="color:rgb(17,24,39);font-size:16px;">Thank you for your interest in our products. Your quotation number is {quotation_number}.</p><p style="color:rgb(17,24,39);font-size:16px;">Please let us know if you have any questions or concerns regarding your quotation. We are here to assist you.</p><p style="color:rgb(17,24,39);font-size:16px;">Best regards,</p><p style="color:rgb(17,24,39);font-size:16px;"><span>{business_name}</span></p>',

                ],
                [
                    'id'         => 3,
                    'name'       => 'payment_received',
                    'subject'    => 'Payment Received - Thank You',
                    'body'       => '<p style="color:rgb(17,24,39);font-size:16px;"><span>Dear {contact_name},</span></p><p style="color:rgb(17,24,39);font-size:16px;">Thank you for making your payment. We have received it and it has been processed successfully.</p><p style="color:rgb(17,24,39);font-size:16px;">If you have any further questions or concerns, please don\'t hesitate to reach out to us. We are always here to help.</p><p style="color:rgb(17,24,39);font-size:16px;">Best regards,</p><p style="color:rgb(17,24,39);font-size:16px;"><span>{business_name}</span></p>',

                ],
                [
                    'id'         => 4,
                    'name'       => 'purchase',
                    'subject'    => 'Thank You for Your Cooperation and Service',
                    'body'       => '<p style="color:rgb(17,24,39);font-size:16px;"><span>Dear {contact_name},</span></p><p style="color:rgb(17,24,39);font-size:16px;">I recently made a purchase from your company and I wanted to thank you for your cooperation and service. My invoice number is {invoice_number} .</p><p style="color:rgb(17,24,39);font-size:16px;">If you have any questions or concerns regarding my purchase, please don\'t hesitate to contact me. I am here to make sure I have a positive experience with your company.</p><p style="color:rgb(17,24,39);font-size:16px;">Best regards,</p><p style="color:rgb(17,24,39);font-size:16px;"><span>{business_name}</span></p>',

                ],
                [
                    'id'         => 5,
                    'name'       => 'payment_sent',
                    'subject'    => 'Payment Sent - Thank You for Your Service',
                    'body'       => '<p style="color:rgb(17,24,39);font-size:16px;"><span>Dear {contact_name},</span></p><p style="color:rgb(17,24,39);font-size:16px;">We have just sent the payment . We appreciate your prompt attention to this matter and the high level of service you provide.</p><p style="color:rgb(17,24,39);font-size:16px;">If you need any further information or clarification, please do not hesitate to reach out to us. We are here to help.</p><p style="color:rgb(17,24,39);font-size:16px;">Best regards,</p><p style="color:rgb(17,24,39);font-size:16px;"><span>{business_name}</span></p>',

                ],

            )
            
        );
    }
}
