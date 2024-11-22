<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PreformIncomingQC;
use App\Models\Provider;
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;

class PreformIncomingQCControllerMain extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'dn_number' => 'required|string|max:50',
                'product_name' => 'required|string|max:255',
                'supplier_name' => 'required|string|max:255',
                'no_of_boxes' => 'required|integer|min:1',
                // 'parameters' => 'required|string',
                'value' => 'required|string',
                'weight' => 'required|integer|max:255',
                'thick&thin' => 'required|string',
                'mouth_inside' => 'required|integer|max:255',
                'mouth_outside' => 'required|integer|max:255',
                // 'standard' => 'required|string',
                'result_1' => 'required|numeric|min:0',
                'result_2' => 'required|numeric|min:0',
                'status' => 'required|in:ok,not ok',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors occurred.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if the supplier exists in the providers table
            $supplier = Provider::where('name', $request->supplier_name)->first();

            if (!$supplier) {
                return response()->json([
                    'status' => false,
                    'message' => 'Supplier not found in the database.',
                ], 404);
            }

            // Store the validated data in the quality inspections table
            $qualityInspection = PreformIncomingQC::create([
                'date' => Carbon::parse($request->date)->format('Y-m-d'),
                'dn_number' => $request->dn_number,
                'product_name' => $request->product_name,
                'supplier_id' => $supplier->id,
                'supplier_name' => $request->supplier_name,
                'no_of_boxes' => $request->no_of_boxes,
                'parameters' => $request->parameters,
                'standard' => $request->standard,
                'result_1' => $request->result_1,
                'result_2' => $request->result_2,
                'status' => $request->status,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Quality Inspection Form created successfully!',
                'data' => $qualityInspection,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
