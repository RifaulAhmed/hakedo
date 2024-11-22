<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use App\Models\PlasticIncomingQC;
use Validator;

class PlasticIncomingQCControllerMain extends Controller
{
    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'date' => 'required|date',
                'dn_number' => 'required|string|max:50',
                'product_name' => 'required|string|max:255',
                'supplier_name' => 'required|string|max:255',
                'no_of_boxes' => 'required|integer|min:1',
                // 'parameters' => 'required|string',
                'value' => 'required|string',
                'weight' => 'required|integer|max:255',
                'thick&thin' => 'nullable|string',
                'mouth_inside' => 'required|integer|max:255',
                'mouth_outside' => 'required|integer|max:255',
                // 'standard' => 'required|string',
                'result_1' => 'required|numeric|min:0',
                'result_2' => 'nullable|numeric|min:0',
                'status' => 'required|in:ok,not ok',
            ]);

            if($validator->fails()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation errors occured',
                    'error' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validate();

            $existingPlasticQC = PlasticIncomingQC::where('dn_number', $validatedData['dn_number'])
                ->where('product_name', $validatedData['product_name'])
                ->first();

                if($existingPlasticQC){
                    return response()->json([
                        'status' => false,
                        'message' => 'Plastic Incoming QC already exists with the same data'
                    ],409);
                }

                $existingPlasticQC = PlasticIncomingQC::create($validatedData);

                return response()->json([
                    'status' => true,
                    'message' => 'Plastic Incoming QC Created Successfully',
                    'data' => $existingPlasticQC,
                ]);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
