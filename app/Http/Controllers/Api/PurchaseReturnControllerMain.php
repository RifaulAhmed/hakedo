<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseReturn;
use App\Models\Purchase;
use App\Models\PurchaseReturnDetails;
use App\Models\Adjustment;
use App\Models\ProductVariant;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use DB;
use Carbon\Carbon;
use Auth;

class PurchaseReturnControllerMain extends Controller
{
    //------------------------------------------Create Purchase Return-----------------------------------//
    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $validator = Validator::make($request->all(), [
            'purchase_id' => 'required|exists:purchases,id',
            'provider_id' => 'required|exists:providers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'return_date' => 'required|date',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|numeric|min:1',
            'reason' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        $purchase = Purchase::find($validatedData['purchase_id']);
        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }

        $purchaseDetail = PurchaseDetail::where('purchase_id', $purchase->id)
            ->where('product_id', $validatedData['product_id'])
            ->first();

        if (!$purchaseDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in the purchase'
            ], 404);
        }

        $returnQuantity = $validatedData['quantity'] ?? $purchaseDetail->quantity;

        if ($returnQuantity > $purchaseDetail->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Return quantity exceeds the purchased quantity'
            ], 400);
        }

        $returnDate = Carbon::parse($validatedData['return_date'])->format('Y-m-d H:i:s');
        
        $prefix = $validatedData['prefix'] ?? 'RET';
        $returnCode = CodeGeneratorHelper::generateCode('purchase_return', $prefix);

        $transactionId = CodeGeneratorHelper::generateTransactionId();

        $purchaseReturn = PurchaseReturn::create([
            'Ref' => $returnCode,
            'purchase_id' => $validatedData['purchase_id'],
            'provider_id' => $validatedData['provider_id'],
            'warehouse_id' => $validatedData['warehouse_id'],
            'return_date' => $returnDate,
            'quantity' => $returnQuantity,
            'reason' => $validatedData['reason'] ?? null,
            'transaction_id' => $transactionId,
        ]);

        // Save the details into purchase_return_details table
        $productVariant = ProductVariant::where('product_id', $validatedData['product_id'])->first();

        PurchaseReturnDetails::create([
            'purchase_return_id' => $purchaseReturn->id,
            'product_id' => $validatedData['product_id'],
            'product_variant_id' => $productVariant ? $productVariant->id : null,
            'quantity' => $returnQuantity,
            'cost' => $purchaseDetail->cost, 
            'transaction_id' => $transactionId,
        ]);

        $stockAdjustment = Adjustment::where('warehouse_id', $validatedData['warehouse_id'])
            ->where('product_id', $validatedData['product_id'])
            ->first();

        if ($stockAdjustment) {
            $newQuantity = $stockAdjustment->items - $returnQuantity;

            if ($newQuantity < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return quantity exceeds available stock',
                ], 400);
            }

            $stockAdjustment->items = $newQuantity;
            $stockAdjustment->save();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Stock record not found for this warehouse and product',
            ], 404);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Purchase Return Created and Stock Updated Successfully',
            'purchase_return' => $purchaseReturn,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Exception: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}






    //------------------------------------------Update Purchase Return-----------------------------------//
    public function update(Request $request, $id)
    {
        try{
        $purchaseReturn = PurchaseReturn::find($id);

        if (!$purchaseReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase Return not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'purchase_id' => 'sometimes|required|exists:purchases,id',
            'provider_id' => 'sometimes|required|exists:providers,id',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'return_date' => 'sometimes|required|date',
            'quantity' => 'sometimes|required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        if (isset($validatedData['prefix'])) {
            $purchaseReturn->code = CodeGeneratorHelper::generateCode('purchase_return', $validatedData['prefix']);
        }

        $currentData = $purchaseReturn->only(array_keys($validatedData));
        if ($validatedData == $currentData) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase Return already updated with the same data.',
            ], 200);
        }

        $purchaseReturn->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Purchase Return Updated Successfully',
            'purchase_return' => $purchaseReturn->fresh(),
        ], 200);
    }catch (\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}

    //------------------------------------------Show All Purchase Returns-----------------------------------//
    public function showAll(Request $request)
    {
        
        $user = Auth::guard('sanctum')->user();
        
        if(!$user){
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
                ],401);
        }
        
        $perPage = $request->input('per_page', 10);
        $purchaseReturns = PurchaseReturn::paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Purchase Returns Retrieved Successfully',
            'purchase_returns' => [
                'current_page' => $purchaseReturns->currentPage(),
                'data' => $purchaseReturns->items(),
                'first_page_url' => $purchaseReturns->url(1),
                'from' => $purchaseReturns->firstItem(),
                'last_page' => $purchaseReturns->lastPage(),
                'last_page_url' => $purchaseReturns->url($purchaseReturns->lastPage()),
                'next_page_url' => $purchaseReturns->nextPageUrl(),
                'path' => $purchaseReturns->path(),
                'per_page' => $purchaseReturns->perPage(),
                'prev_page_url' => $purchaseReturns->previousPageUrl(),
                'to' => $purchaseReturns->lastItem(),
                'total' => $purchaseReturns->total(),
            ]
        ], 200);
    }

    //------------------------------------------Show Purchase Return by ID-----------------------------------//
    public function showById($id)
    {
        $purchaseReturn = PurchaseReturn::find($id);

        if (!$purchaseReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase Return not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Purchase Return Retrieved Successfully',
            'purchase_return' => $purchaseReturn,
        ], 200);
    }

    //------------------------------------------Delete Purchase Return-----------------------------------//
    public function delete($id)
    {
        $purchaseReturn = PurchaseReturn::find($id);

        if (!$purchaseReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase Return not found'
            ], 404);
        }

        $purchaseReturn->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase Return Deleted Successfully',
        ], 200);
    }
}
