<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomingMaterialQClabel;
use Illuminate\Http\Request;
use Validator;

class IncomingMaterialQClabelControllerMain extends Controller
{
    public function store(Request $request){

        try{
            $validator = Validator::make($request->all(),[
                'material_id' => 'required|integer|min:1',
                'material_description' => 'required|string|max:500',
                'supplier_id' => 'required|integer|exists,providers_id',
                'supplier_name' => 'required|string|max:50',
                'delivery_note_number' => 'required|integer|min:10',
                'batch_number' => 'required|integer|min:50',
                'remarks' => 'sometimes|string|max:255',
            ]);

            if($validator->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors occured',
                    'error' => $validator->errors(),
                ],422);
            }

            $validatedData = $validator->validate();

            $existingQClabel = IncomingMaterialQClabel::where('material_id', $validatedData['material_id'])
                    ->where('supplier_id', $validatedData['supplier_id'])
                    ->where('delivery_note_number', $validatedData['delivery_note_number'])
                    ->first();

            if($existingQClabel){
                return response()->json([
                    'status' => false,
                    'message' => 'Incoming Material QC Label already exists with this data',
                ], 409);
            }

            $existingQClabel = IncomingMaterialQClabel::create($validatedData);

            return response()->josn([
                'status' => true,
                'message' => 'Incoming Material QC Label Created Successfully',
                'data' => $existingQClabel,
            ]);
        }catch(\Exception $e){
            return response()->josn([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
