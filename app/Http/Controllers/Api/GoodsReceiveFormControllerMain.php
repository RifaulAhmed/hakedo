<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\GoodsReceiveForm;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class GoodsReceiveFormControllerMain extends Controller
{
    
    //---------------------------------------------------Create Goods Receive Form--------------------------------------------------//
//   public function store(Request $request)
// {
//     try {
//         // Validate the input data
//         $validator = Validator::make($request->all(), [
//             'prefix' => 'nullable|string|max:100',  
//             'machine_no' => 'required|string|max:50',
//             'date' => 'nullable|date',
//             'shift' => 'nullable|string|max:255',
//             'operator_name' => 'nullable|string|max:255',
//             'materials' => 'nullable|array',   
//             'materials.*.mo_no' => 'nullable|string|max:20',
//             'materials.*.product_id' => 'nullable|string|max:255',
//             'materials.*.product_description' => 'nullable|string|max:500',
//             'materials.*.total' => 'nullable|numeric|min:0',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Validation failed',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $validatedData = $validator->validated();

//         // Check if a Goods Receive Form already exists for each product
//         foreach ($validatedData['materials'] as $material) {
//             $existingGrf = GoodsReceiveForm::where('product_id', $material['product_id'])
//                 ->where('machine_no', $validatedData['machine_no'])
//                 ->first();

//             if ($existingGrf) {
//                 return response()->json([
//                     'status' => false,
//                     'message' => 'Goods Receive Form already exists for Product ID ' . $material['product_id'],
//                 ], 422);
//             }
//         }

//         // Generate the reference number
//         $prefix = $validatedData['prefix'] ?? 'GRF';
//         $currentYear = now()->format('Y');
//         $plantCode = 'HPM';
//         $shiftCode = 'VII';
//         $latestGrf = GoodsReceiveForm::latest('id')->first();
//         $nextNumber = $latestGrf ? ($latestGrf->id + 1) : 1;
//         $refNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT) . "/$prefix/$plantCode/$shiftCode/$currentYear";

//         // Encode the materials array to JSON for storage in the product_id column
//         $encodedMaterials = json_encode($validatedData['materials']);

//         // Create the Goods Receive Form record
//         $goodsReceiveForm = GoodsReceiveForm::create([
//             'no_form' => $refNumber,
//             'machine_no' => $validatedData['machine_no'],
//             'date' => $validatedData['date'],
//             'shift' => $validatedData['shift'],
//             'operator_name' => $validatedData['operator_name'],
//             'product_id' => $encodedMaterials,  
//         ]);

//         return response()->json([
//             'status' => true,
//             'message' => 'Goods Receive Form Created Successfully',
//             'data' => $goodsReceiveForm,
//         ], 201);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }



   public function store(Request $request)
{
    try {
       
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:100',  
            'machine_no' => 'required|string|max:50',
            'date' => 'nullable|date',
            'shift' => 'nullable|string|max:255',
            'operator_name' => 'nullable|string|max:255',
            'materials' => 'nullable|array',   
            'materials.*.mo_no' => 'nullable|string|max:20',
            'materials.*.product_id' => 'required|integer|exists:products,id',
            'materials.*.product_description' => 'nullable|string|max:500',
            'materials.*.total' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
 
        $defaultPrefix = '00001/GRF/HPM/XI/' . now()->format('Y');
        $prefix = $validatedData['prefix'] ?? $defaultPrefix;

        $prefixParts = explode('/', $prefix);

        if (count($prefixParts) < 1 || !is_numeric($prefixParts[0])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid prefix format. The prefix number must be numeric.',
            ], 422);
        }

        $prefixNumber = (int) $prefixParts[0];

        $lastGrf = GoodsReceiveForm::latest('id')->first();
        $nextNumber = $prefixNumber;

        if ($lastGrf) {
            preg_match('/^(\d{5})/', $lastGrf->no_form, $matches);
            $lastNumber = (int) ($matches[1] ?? 0);
            $nextNumber = max($lastNumber + 1, $prefixNumber);
        }

        $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        $refNumber = "$formattedNumber/" . implode('/', array_slice($prefixParts, 1));

        // Process each material to include product code and name
        $processedMaterials = array_map(function($material) {
            $product = Product::find($material['product_id']);
            return array_merge($material, [
                'Ref' => $product ? $product->Ref : null,
                'product_name' => $product ? $product->name : null,
                'product_description' => $product ? $product->product_description : null,
            ]);
        }, $validatedData['materials']);
 
        $encodedMaterials = json_encode($processedMaterials);
 
        $goodsReceiveForm = GoodsReceiveForm::create([
            'no_form' => $refNumber,
            'machine_no' => $validatedData['machine_no'],
            'date' => $validatedData['date'],
            'shift' => $validatedData['shift'],
            'operator_name' => $validatedData['operator_name'],
            'product_id' => $encodedMaterials,  
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Goods Receive Form created successfully',
            'data' => [
                'id' => $goodsReceiveForm->id,
                'no_form' => $goodsReceiveForm->no_form,
                'date' => $goodsReceiveForm->date,
                'machine_no' => $goodsReceiveForm->machine_no,
                'shift' => $goodsReceiveForm->shift,
                'operator_name' => $goodsReceiveForm->operator_name,
                'materials' => $processedMaterials,
            ],
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}









//---------------------------------------------Update Goods Receive Form--------------------------------------//
  public function update(Request $request, $id)
{
    try {
        // Validate input
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:100',
            'machine_no' => 'nullable|string|max:50',
            'date' => 'nullable|date',
            'shift' => 'nullable|string|max:255',
            'operator_name' => 'nullable|string|max:255',
            'materials' => 'nullable|array', // For multiple products like in the create function
            'materials.*.mo_no' => 'nullable|string|max:20',
            'materials.*.product_id' => 'nullable|string|max:255',
            'materials.*.product_description' => 'nullable|string|max:500',
            'materials.*.total' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        // Find the existing Goods Receive Form by ID
        $goodsReceiveForm = GoodsReceiveForm::find($id);
        if (!$goodsReceiveForm) {
            return response()->json([
                'status' => false,
                'message' => "Goods Receive Form not found for ID: $id",
            ], 404);
        }

        // Check for reference number generation, similar to the create function logic
        $prefix = $validatedData['prefix'] ?? 'GRF';
        $currentYear = now()->format('Y');
        $plantCode = 'HPM';
        $shiftCode = 'XI';

        // Generate the correct new reference number format if needed
        if ($goodsReceiveForm->no_form === null || !str_contains($goodsReceiveForm->no_form, "/$plantCode/$shiftCode/$currentYear")) {
            $newPrefix = "$prefix/$plantCode/$shiftCode/$currentYear";
            $goodsReceiveForm->no_form = $newPrefix;
        }

        // Update the other fields while keeping previous values if not provided
        $goodsReceiveForm->machine_no = $validatedData['machine_no'] ?? $goodsReceiveForm->machine_no;
        $goodsReceiveForm->date = $validatedData['date'] ?? $goodsReceiveForm->date;
        $goodsReceiveForm->shift = $validatedData['shift'] ?? $goodsReceiveForm->shift;
        $goodsReceiveForm->operator_name = $validatedData['operator_name'] ?? $goodsReceiveForm->operator_name;

        // Process each material to include product code and name
        $processedMaterials = array_map(function($material) {
            $product = Product::find($material['product_id']);
            return array_merge($material, [
                'Ref' => $product ? $product->Ref : null,
                'product_name' => $product ? $product->name : null,
                'product_description' => $product ? $product->product_description : null,
            ]);
        }, $validatedData['materials']);

        // Encode the materials array and store it in the product_id column
        if (isset($validatedData['materials'])) {
            $encodedMaterials = json_encode($processedMaterials);
            $goodsReceiveForm->product_id = $encodedMaterials;
        }

        // Save the updated data
        $goodsReceiveForm->save();

        return response()->json([
            'status' => true,
            'message' => 'Goods Receive Form Updated Successfully',
            'data' => [
                'id' => $goodsReceiveForm->id,
                'no_form' => $goodsReceiveForm->no_form,
                'date' => $goodsReceiveForm->date,
                'machine_no' => $goodsReceiveForm->machine_no,
                'shift' => $goodsReceiveForm->shift,
                'operator_name' => $goodsReceiveForm->operator_name,
                'materials' => $processedMaterials,
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}






//-------------------------------------------------Show All Goods Receive Form-------------------------------------------------//

public function showAll(Request $request)
{
    try {
        // Authenticate the user
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get pagination and search parameters
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Create a query for GoodsReceiveForm
        $formQuery = GoodsReceiveForm::query();

        // Search functionality across multiple fields
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');

            $formQuery->where(function ($query) use ($search) {
                $query->where('operator_name', 'like', '%' . $search . '%')
                      ->orWhere('date', 'like', '%' . $search . '%')
                      ->orWhere('shift', 'like', '%' . $search . '%')
                      // Add other fields if necessary
                      ->orWhere('no_form', 'like', '%' . $search . '%');
                    //   ->orWhere('quantity', 'like', '%' . $search . '%');
            });
        }
        
        $formQuery->orderby('created_at', 'desc');

        // Paginate the results
        $countableForms = $formQuery->paginate($perPage, ['*'], 'page', $page);

        // Check if no forms were found
        if ($countableForms->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Goods Receive Forms found',
                'data' => [],
            ], 404);
        }

        // Return the paginated results
        return response()->json([
            'status' => true,
            'message' => 'Goods Receive Forms Retrieved Successfully',
            'data' => [
                'current_page' => $countableForms->currentPage(),
                'data' => $countableForms->items(),
                'first_page_url' => $countableForms->url(1),
                'from' => $countableForms->firstItem(),
                'last_page' => $countableForms->lastPage(),
                'last_page_url' => $countableForms->url($countableForms->lastPage()),
                'next_page_url' => $countableForms->nextPageUrl(),
                'path' => $countableForms->path(),
                'per_page' => $countableForms->perPage(),
                'prev_page_url' => $countableForms->previousPageUrl(),
                'to' => $countableForms->lastItem(),
                'total' => $countableForms->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}






//----------------------------------------------------Show Goods Receive By ID-------------------------------------------------//
   public function showById($id)
{
    try {
        // Find the GoodsReceiveForm by ID
        $goodsReceive = GoodsReceiveForm::find($id);

        if (!$goodsReceive) {
            return response()->json([
                'status' => false,
                'message' => 'Goods Receive Form not found',
            ], 404);
        }

        // Decode the JSON-encoded fields
        if ($goodsReceive->product_id) {
            $goodsReceive->product_id = json_decode($goodsReceive->product_id, true);
        }

        return response()->json([
            'status' => true,
            'message' => 'Goods Receive Form Retrieved Successfully',
            'data' => $goodsReceive,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}






//----------------------------------------------Delete Goods Receive Form----------------------------------------------//
  public function delete($id)
{
    try {
        $goodsReceive = GoodsReceiveForm::find($id);

        if (!$goodsReceive) {
            return response()->json([
                'status' => false,
                'message' => 'Goods Receive Form not found',
            ], 404);
        }

        $goodsReceive->delete();

        return response()->json([
            'status' => true,
            'message' => 'Goods Receive Form Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}










































}
