<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use Illuminate\Http\Request;
use Validator;

class EmailSettingsControllerMain extends Controller
{



    //--------------------------------Create Email Setting-------------------------------//
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mail_mailer' => 'required|string|max:10|in:smtp,sendmail,mailgun,ses,postmark',
                'mail_host' => 'required|string|max:255', 
                'mail_from_name' => 'required|string|max:50', 
                'mail_from_address' => 'required|string|email:rfc,dns|max:255',
                'mail_port' => 'required|integer|min:1|max:65535', 
                'mail_username' => 'required|string|email:rfc,dns|max:255', 
                'mail_password' => 'required|string|min:8|max:255',
                'mail_encryption' => 'required|string|in:tls,ssl,none|max:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error occurred',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            $mailsmtp = EmailSetting::create([
                'mail_mailer' => $validatedData['mail_mailer'],
                'mail_host' => $validatedData['mail_host'],
                'mail_from_name' => $validatedData['mail_from_name'],
                'mail_from_address' => $validatedData['mail_from_address'],
                'mail_port' => $validatedData['mail_port'],
                'mail_username' => $validatedData['mail_username'],
                'mail_password' => $validatedData['mail_password'],
                'mail_encryption' => $validatedData['mail_encryption'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Email Settings Created Successfully',
                'data' => $mailsmtp,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500); 
        }
    }





    //----------------------------------Update Email Setting------------------------------//

    public function update(Request $request, $id)
{
    try {
        $emailSetting = EmailSetting::find($id);

        if (!$emailSetting) {
            return response()->json([
                'status' => false,
                'message' => 'Email setting not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'mail_mailer' => 'nullable|string|max:10|in:smtp,sendmail,mailgun,ses,postmark',
            'mail_host' => 'nullable|string|max:255',
            'mail_from_name' => 'nullable|string|max:50',
            'mail_from_address' => 'nullable|string|email:rfc,dns|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|email:rfc,dns|max:255',
            'mail_password' => 'nullable|string|min:8|max:255',
            'mail_encryption' => 'nullable|string|in:tls,ssl,none|max:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error occurred',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        $dataToUpdate = array_filter($validatedData, function ($value) {
            return $value !== null && $value !== ''; 
        });

        $emailSetting->update(array_merge($emailSetting->toArray(), $dataToUpdate));

        return response()->json([
            'status' => true,
            'message' => 'Email Settings Updated Successfully',
            'data' => $emailSetting->fresh(), 
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred: ' . $e->getMessage(),
        ], 500);
    }
}







    //----------------------------------Show ALl Email Setting------------------------------//

    public function showAll()
    {
        try {
            $emailSettings = EmailSetting::all();

            if ($emailSettings->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No email settings found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Email settings retrieved successfully',
                'data' => $emailSettings,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }






    //----------------------------------Show By ID Email Setting------------------------------//

    public function showById($id)
    {
        try {
            $emailSetting = EmailSetting::find($id);

            if (!$emailSetting) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email setting not found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Email setting retrieved successfully',
                'data' => $emailSetting,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }






    //----------------------------------Delete Email Setting------------------------------//

    public function delete($id)
    {
        try {
            $emailSetting = EmailSetting::find($id);

            if (!$emailSetting) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email setting not found',
                ], 404);
            }

            $emailSetting->delete();

            return response()->json([
                'status' => true,
                'message' => 'Email setting deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }





}
