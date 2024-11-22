<?php

namespace App\Http\Controllers;

use App\Models\UncountableUnloadingForm;
use Illuminate\Http\Request;
use Validator;

class UncountableUnloadingFormControllermain extends Controller
{
    // Store the uncountable unloading form
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'dn_number' => 'required|string|max:20',
            'product_name' => 'required|string|max:255',
            'supplier_name' => 'required|string|max:255',
            'standard_weight' => 'required|numeric|min:0',
            'no_of_boxes' => 'required|integer|min:1',
            'total_weight' => 'required|numeric|min:0',
        ]);

        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create the uncountable unloading form
        $unloadingForm = UncountableUnloadingForm::create($validator->validated());

        // Return a success response
        return response()->json([
            'status' => true,
            'message' => 'Uncountable Unloading Form created successfully!',
            'data' => $unloadingForm,
        ], 201);
    }
}
