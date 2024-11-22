<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SystemSettingsControllerMain extends Controller
{

    //---------------------------------Create System Setting--------------------------------//
    public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'default_email' => 'nullable|string|email:rfc,dns|max:255',
            'change_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2104',
            'company_name' => 'nullable|string|max:100',
            'company_phone' => 'nullable|string|max:20',
            'developed_by' => 'nullable|string|max:100',
            'app_name' => 'nullable|string|max:50',
            // 'default_warehouse_id' => 'nullable|string|exists:warehouses,id',
            'company_address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error occurred',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        $imageName = 'no_image.png';
        if ($request->hasFile('change_logo')) {
            $image = $request->file('change_logo'); // Ensuring the input name matches the one being validated
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('/images/products'), $imageName);
        }

        $imageUrl = asset('images/products/' . $imageName);

        $systemSetting = SystemSetting::create([
            'default_email' => $validatedData['default_email'],
            'change_logo' => $imageUrl,
            'company_name' => $validatedData['company_name'],
            'company_phone' => $validatedData['company_phone'],
            'developed_by' => $validatedData['developed_by'],
            'app_name' => $validatedData['app_name'],
            // 'default_warehouse_id' => $validatedData['default_warehouse_id'] ?? null, 
            'company_address' => $validatedData['company_address'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'System Settings Created Successfully',
            'data' => $systemSetting,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred: ' . $e->getMessage(),
        ], 500);
    }
}




    //------------------------------Update System Setting-----------------------------//

   public function update(Request $request, $id)
{
    try {
        // Find the system setting by ID
        $systemSetting = SystemSetting::find($id);

        if (!$systemSetting) {
            return response()->json([
                'status' => false,
                'message' => 'System setting not found',
            ], 404);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'default_email' => 'nullable|string|email:rfc,dns|max:255',
            'change_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120', // Image validation
            'company_name' => 'nullable|string|max:100',
            'company_phone' => 'nullable|string|max:20',
            'developed_by' => 'nullable|string|max:100',
            'app_name' => 'nullable|string|max:50',
            'default_warehouse_id' => 'nullable|string|exists:warehouses,id',
            'company_address' => 'nullable|string|max:255',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error occurred',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get the validated data
        $validatedData = $validator->validated();

        // Initialize an array to store the data to update
        $dataToUpdate = [];

        // Loop through the validated data, only update fields that are not null or empty
        foreach ($validatedData as $key => $value) {
            if (!is_null($value) && $value !== '') {
                $dataToUpdate[$key] = $value;
            }
        }

        // Handle image upload (change_logo)
        if ($request->hasFile('change_logo')) {
            $image = $request->file('change_logo');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('/images/system-settings'), $imageName);
            $dataToUpdate['change_logo'] = asset('images/system-settings/' . $imageName);
        }

        // Check if there is any data to update
        if (!empty($dataToUpdate)) {
            // Update the system settings with only the non-empty data
            $systemSetting->update($dataToUpdate);

            return response()->json([
                'status' => true,
                'message' => 'System Settings Updated Successfully',
                'data' => $systemSetting->fresh(), // Get updated data
            ], 200);
        }

        // If no data was updated, return the current data without an error
        return response()->json([
            'status' => true,
            'message' => 'No changes were made, returning current data',
            'data' => $systemSetting, // Return existing data
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred: ' . $e->getMessage(),
        ], 500);
    }
}






    //-------------------------------Show All System Setting-----------------------------//

    public function showAll()
    {
        try {
            $systemSettings = SystemSetting::all();

            if ($systemSettings->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No system settings found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'System settings retrieved successfully',
                'data' => $systemSettings,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }




    //-------------------------------Shwo by ID System Setting---------------------------//
    public function showById($id)
    {
        try {
            $systemSetting = SystemSetting::find($id);

            if (!$systemSetting) {
                return response()->json([
                    'status' => false,
                    'message' => 'System setting not found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'System setting retrieved successfully',
                'data' => $systemSetting,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }




    //---------------------------------Delete System Setting--------------------------------//

    public function delete($id)
    {
        try {
            $systemSetting = SystemSetting::find($id);

            if (!$systemSetting) {
                return response()->json([
                    'status' => false,
                    'message' => 'System setting not found',
                ], 404);
            }

            $systemSetting->delete();

            return response()->json([
                'status' => true,
                'message' => 'System setting deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
