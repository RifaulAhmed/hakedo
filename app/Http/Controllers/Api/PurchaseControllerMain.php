<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Adjustment;
use App\Models\AdjustmentDetail;
use App\Models\ProductVariant;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use DB;
use Carbon\Carbon;
use Auth;

class PurchaseControllerMain extends Controller
{
//------------------------------------------Create Purchase-----------------------------------//
    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'provider_id' => 'required|exists:providers,id',
            'user_id' => 'nullable|exists:users,id',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0',
            'prefix' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
        $successfulPurchases = [];
        $existingPurchases = [];

        foreach ($validatedData['products'] as $productData) {
            $product = Product::find($productData['id']);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found with ID: ' . $productData['id']
                ], 404);
            }

            // $productVariant = ProductVariant::where('product_id', $productData['id'])->first();
            // if (!$productVariant) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Product variant not found for product ID: ' . $productData['id']
            //     ], 404);
            // }

            $grandTotal = $product->cost * $productData['quantity'];
            $date = Carbon::parse($validatedData['date'])->format('Y-m-d');
            $purchasePrefix = $validatedData['prefix'] ?? 'PUR';
            $purchaseCode = CodeGeneratorHelper::generateCode('purchase', $purchasePrefix);

            $purchase = Purchase::create([
                'Ref' => $purchaseCode,
                'date' => $date,
                'GrandTotal' => $grandTotal,
                'warehouse_id' => $validatedData['warehouse_id'],
                'provider_id' => $validatedData['provider_id'],
                'user_id' => $validatedData['user_id'] ?? null,
                'product_id' => $productData['id'],
            ]);

            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'product_id' => $productData['id'],
                // 'product_variant_id' => $productVariant->id,
                'quantity' => $productData['quantity'],
                'cost' => $product->cost,
                'date' => $date,
            ]);
 
            $adjustmentPrefix = 'STK';
            $adjustmentCode = CodeGeneratorHelper::generateCode('adjustment', $adjustmentPrefix);
            $stock = Adjustment::updateOrCreate(
                [
                    'warehouse_id' => $validatedData['warehouse_id'],
                    'product_id' => $productData['id'],
                ],
                [
                    'Ref' => $adjustmentCode,
                    'items' => DB::raw("COALESCE(items, 0) + {$productData['quantity']}")
                ]
            );

            $successfulPurchases[] = $purchase;
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Purchases Created and Stock Updated Successfully',
            'created_purchases' => $successfulPurchases,
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}









//----------------------------------------Update Purchase---------------------------------//
   public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {
        
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'provider_id' => 'required|exists:providers,id',
            'user_id' => 'nullable|exists:users,id',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0',
            'prefix' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
 
        $purchase = Purchase::findOrFail($id);
 
        $successfulUpdates = [];
 
        foreach ($validatedData['products'] as $productData) {
            $product = Product::find($productData['id']);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found with ID: ' . $productData['id']
                ], 404);
            }

            $grandTotal = $product->cost * $productData['quantity'];
            $date = Carbon::parse($validatedData['date'])->format('Y-m-d');
 
            $purchase->update([
                'date' => $date,
                'GrandTotal' => $grandTotal,
                'warehouse_id' => $validatedData['warehouse_id'],
                'provider_id' => $validatedData['provider_id'],
                'user_id' => $validatedData['user_id'] ?? null,
            ]);
 
            $purchaseDetail = PurchaseDetail::updateOrCreate(
                [
                    'purchase_id' => $purchase->id,
                    'product_id' => $productData['id'],
                ],
                [
                    'quantity' => $productData['quantity'],
                    'cost' => $product->cost,
                    'date' => $date,
                ]
            );
 
            $adjustmentPrefix = 'STK';
            $adjustmentCode = CodeGeneratorHelper::generateCode('adjustment', $adjustmentPrefix);
            $stock = Adjustment::updateOrCreate(
                [
                    'warehouse_id' => $validatedData['warehouse_id'],
                    'product_id' => $productData['id'],
                ],
                [
                    'Ref' => $adjustmentCode,
                    'items' => DB::raw("COALESCE(items, 0) + {$productData['quantity']}")
                ]
            );

            $successfulUpdates[] = $purchaseDetail;
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Purchase Updated and Stock Adjusted Successfully',
            'updated_purchase' => $purchase,
            'updated_details' => $successfulUpdates,
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}








//--------------------------------------Show All Purchases--------------------------------//
    public function showAll(Request $request)
{
    $user = Auth::guard('sanctum')->user();
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    $perPage = $request->input('per_page', 10);
    $warehouseName = $request->input('warehouse_name');  
 
    $query = Purchase::query()
        ->join('warehouses', 'purchases.warehouse_id', '=', 'warehouses.id')
        ->select('purchases.*'); 
 
    if ($warehouseName) {
        $query->where('warehouses.name', 'LIKE', '%' . $warehouseName . '%');
    }
 
    $purchases = $query->paginate($perPage);
 
    if ($purchases->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No purchases found for the specified warehouse name.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Purchases Retrieved Successfully',
        'purchases' => [
            'current_page' => $purchases->currentPage(),
            'data' => $purchases->items(),
            'first_page_url' => $purchases->url(1),
            'from' => $purchases->firstItem(),
            'last_page' => $purchases->lastPage(),
            'last_page_url' => $purchases->url($purchases->lastPage()),
            'next_page_url' => $purchases->nextPageUrl(),
            'path' => $purchases->path(),
            'per_page' => $purchases->perPage(),
            'prev_page_url' => $purchases->previousPageUrl(),
            'to' => $purchases->lastItem(),
            'total' => $purchases->total(),
        ]
    ], 200);
}







//----------------------------------Show Purchase by ID-------------------------------//
    public function showById($id)
    {
        $purchase = Purchase::find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Purchase Retrieves Successfully',
            'purchase' => $purchase,
        ], 200);
    }




//-------------------------------------Delete Purchase---------------------------------//
    public function delete($id)
    {
        $purchase = Purchase::find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }

        $purchase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase deleted successfully',
        ], 200);
    }



}
