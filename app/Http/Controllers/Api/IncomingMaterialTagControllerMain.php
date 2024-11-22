<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomingMaterialTag;
use App\Models\Product;
use App\Models\Provider;
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;
use Auth;

class IncomingMaterialTagControllerMain extends Controller
{


    //--------------------------------------Create Material Tag-----------------------------------//
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'batch_number' => 'required|string|max:20',
                'product_id' => 'required|exists:products,id',
                'product_description' => 'required|string|max:500',
                'supplier_id' => 'required|exists:providers,id',
                'supplier_name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors occurred.',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $validatedData = $validator->validated();

            $existingMaterialTag = IncomingMaterialTag::where('batch_number', $validatedData['batch_number'])
                ->where('product_id', $validatedData['product_id'])
                ->first();

            if ($existingMaterialTag) {
                return response()->json([
                    'status' => false,
                    'message' => 'Incoming Material Tag already exists with the same data',
                ], 409);
            }
            $incomingMaterialTag = IncomingMaterialTag::create($validatedData);

            $validatedData['date'] = Carbon::parse($validatedData['date'])->format('Y-m-d');

            $product = Product::find($request->product_id);
            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Product not found in the database.',
                ], 404);
            }

            $supplier = Provider::find($request->supplier_id);
            if (!$supplier) {
                return response()->json([
                    'status' => false,
                    'message' => 'Supplier not found in the database.',
                ], 404);
            }

            $incomingMaterialTag = IncomingMaterialTag::create($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'Incoming Material Tag created successfully!',
                'data' => $incomingMaterialTag,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }



    //----------------------------------Update Material Tag-------------------------------------//
   public function update(Request $request, $id)
{
    try {
 
        $incomingMaterialTag = IncomingMaterialTag::findOrFail($id);
 
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:20',
            'product_id' => 'nullable|exists:products,id',
            'product_description' => 'nullable|string|max:500',
            'supplier_id' => 'nullable|exists:providers,id',
            'supplier_name' => 'nullable|string|max:255',
            'quantity' => 'nullable|numeric|min:0',
            'uom' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:500',
        ]);
 
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred.',
                'errors' => $validator->errors(),
            ], 422);
        }
 
        $validatedData = $validator->validated();
 
        if (isset($validatedData['date'])) {
            $validatedData['date'] = Carbon::parse($validatedData['date'])->format('Y-m-d');
        }
 
        $incomingMaterialTag->update([
            'date' => $validatedData['date'] ?? $incomingMaterialTag->date,
            'batch_number' => $validatedData['batch_number'] ?? $incomingMaterialTag->batch_number,
            'product_id' => $validatedData['product_id'] ?? $incomingMaterialTag->product_id,
            'product_description' => $validatedData['product_description'] ?? $incomingMaterialTag->product_description,
            'supplier_id' => $validatedData['supplier_id'] ?? $incomingMaterialTag->supplier_id,
            'supplier_name' => $validatedData['supplier_name'] ?? $incomingMaterialTag->supplier_name,
            'quantity' => $validatedData['quantity'] ?? $incomingMaterialTag->quantity,
            'uom' => $validatedData['uom'] ?? $incomingMaterialTag->uom,
            'remarks' => $validatedData['remarks'] ?? $incomingMaterialTag->remarks,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Incoming Material Tag updated successfully.',
            'data' => $incomingMaterialTag,
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Incoming Material Tag not found.',
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}








    //------------------------------------Show All--------------------------------------------//
   public function showAll(Request $request)
{
    try {
        
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
 
        $productId = $request->input('product_id');
        $searchTerm = $request->input('search');  
        $pageSize = $request->input('per_page', 10);   
 
        $query = IncomingMaterialTag::query();
 
        if ($productId) {
            $query->where('product_id', $productId);
            
            if (!$query->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Product ID not found.',
                ], 404);
            }
        }
 
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('operator_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('material_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('remarks', 'LIKE', "%{$searchTerm}%");
            });
        }
 
        $incomingMaterialTags = $query->paginate($pageSize);
 
        return response()->json([
            'status' => true,
            'message' => 'Incoming Material Tags retrieved successfully!',
            'data' => [
                'current_page' => $incomingMaterialTags->currentPage(),
                'items' => $incomingMaterialTags->items(),
                'first_page_url' => $incomingMaterialTags->url(1),
                'from' => $incomingMaterialTags->firstItem(),
                'last_page' => $incomingMaterialTags->lastPage(),
                'last_page_url' => $incomingMaterialTags->url($incomingMaterialTags->lastPage()),
                'next_page_url' => $incomingMaterialTags->nextPageUrl(),
                'path' => $incomingMaterialTags->path(),
                'per_page' => $incomingMaterialTags->perPage(),
                'prev_page_url' => $incomingMaterialTags->previousPageUrl(),
                'to' => $incomingMaterialTags->lastItem(),
                'total' => $incomingMaterialTags->total(),
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}









    //---------------------------------------Show by ID--------------------------------------//
    public function showById(Request $request, $id)
{
    try {
        $pageSize = $request->input('page_size', 10);

        $incomingMaterialTags = IncomingMaterialTag::where('id', $id)->paginate($pageSize);

        if ($incomingMaterialTags->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Incoming Material Tags found for this product ID.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Incoming Material Tags for product ID retrieved successfully!',
            'data' => $incomingMaterialTags->items(), 
            'pagination' => [
                'current_page' => $incomingMaterialTags->currentPage(),
                'total_items' => $incomingMaterialTags->total(),
                'per_page' => $incomingMaterialTags->perPage(),
                'total_pages' => $incomingMaterialTags->lastPage(),
                'next_page_url' => $incomingMaterialTags->nextPageUrl(),
                'prev_page_url' => $incomingMaterialTags->previousPageUrl(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}








    //-----------------------------------Delete Material Tags----------------------------------//
    public function delete($id)
{
    try {
        $incomingMaterialTag = IncomingMaterialTag::find($id);

        if (!$incomingMaterialTag) {
            return response()->json([
                'status' => false,
                'message' => 'Incoming Material Tag not found.',
            ], 404);
        }

        $incomingMaterialTag->delete();

        return response()->json([
            'status' => true,
            'message' => 'Incoming Material Tag deleted successfully!',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}


}
