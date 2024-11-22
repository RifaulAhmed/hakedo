<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SaleReturn; 
use App\Models\Sale; 
use App\Models\SaleDetail; 
use App\Models\Adjustment; 
use App\Models\ProductVariant; 
use App\Models\SaleReturnDetails; 
use App\Models\AdjustmentDetail; 
use App\Models\Product; 
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use DB;
use Carbon\Carbon;
use Auth;




class SalesReturnControllerMain extends Controller
{
    //------------------------------------------Create Sales Return-----------------------------------//
    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|exists:sales,id',
            'client_id' => 'nullable|exists:clients,id',
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

        $sale = Sale::find($validatedData['sale_id']);
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        $saleDetail = SaleDetail::where('sale_id', $sale->id)
            ->where('product_id', $validatedData['product_id'])
            ->first();

        if (!$saleDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in the sale'
            ], 404);
        }

        $returnQuantity = $validatedData['quantity'] ?? $saleDetail->quantity;

        if ($returnQuantity > $saleDetail->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Return quantity exceeds the sold quantity'
            ], 400);
        }

        $returnDate = Carbon::parse($validatedData['return_date'])->format('Y-m-d H:i:s');
        
        $prefix = $validatedData['prefix'] ?? 'SRET';
        $returnCode = CodeGeneratorHelper::generateCode('sales_return', $prefix);

        $transactionId = CodeGeneratorHelper::generateTransactionId();

        $salesReturn = SaleReturn::create([
            'Ref' => $returnCode,
            'sale_id' => $validatedData['sale_id'],
            'client_id' => $validatedData['client_id'] ?? null,
            'warehouse_id' => $validatedData['warehouse_id'],
            'return_date' => $returnDate,
            'quantity' => $returnQuantity,
            'reason' => $validatedData['reason'] ?? null,
            'transaction_id' => $transactionId,
        ]);

        // Save the details into sale_return_details table
        $productVariant = ProductVariant::where('product_id', $validatedData['product_id'])->first();

        SaleReturnDetails::create([
            'sale_return_id' => $salesReturn->id,
            'product_id' => $validatedData['product_id'],
            'product_variant_id' => $productVariant ? $productVariant->id : null,
            'quantity' => $returnQuantity,
            'price' => $saleDetail->price, // Assuming price is in SaleDetail
            'transaction_id' => $transactionId,
        ]);

        // Adjust stock
        $stockAdjustment = Adjustment::where('warehouse_id', $validatedData['warehouse_id'])
            ->where('product_id', $validatedData['product_id'])
            ->first();

        if ($stockAdjustment) {
            $newQuantity = $stockAdjustment->items + $returnQuantity; // Adding back the returned quantity

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
            'message' => 'Sales Return Created and Stock Updated Successfully',
            'sales_return' => $salesReturn,
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







    //------------------------------------------Update Sales Return-----------------------------------//
    public function update(Request $request, $id)
    {
        try {
            $salesReturn = SaleReturn::find($id);

            if (!$salesReturn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales Return not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'sale_id' => 'sometimes|required|exists:sales,id',
                'client_id' => 'sometimes|required|exists:clients,id',
                'warehouse_id' => 'sometimes|required|exists:warehouses,id',
                'GrandTotal' => 'sometimes|required|numeric|min:0',
                'return_date' => 'sometimes|required|date',
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
                $salesReturn->code = CodeGeneratorHelper::generateCode('sales_return', $validatedData['prefix']);
            }

            $currentData = $salesReturn->only(array_keys($validatedData));
            if ($validatedData == $currentData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales Return already updated with the same data.',
                ], 200);
            }

            $salesReturn->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Sales Return Updated Successfully',
                'sales_return' => $salesReturn->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    //------------------------------------------Show All Sales Returns-----------------------------------//
    public function showAll(Request $request)
    {
        
        $user = Auth::guard('sanctum')->user();
        
        if(!$user){
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
                ], 401);
        }
        
        $perPage = $request->input('per_page', 10);
        $salesReturns = SaleReturn::paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Sales Returns Retrieved Successfully',
            'sales_returns' => [
                'current_page' => $salesReturns->currentPage(),
                'data' => $salesReturns->items(),
                'first_page_url' => $salesReturns->url(1),
                'from' => $salesReturns->firstItem(),
                'last_page' => $salesReturns->lastPage(),
                'last_page_url' => $salesReturns->url($salesReturns->lastPage()),
                'next_page_url' => $salesReturns->nextPageUrl(),
                'path' => $salesReturns->path(),
                'per_page' => $salesReturns->perPage(),
                'prev_page_url' => $salesReturns->previousPageUrl(),
                'to' => $salesReturns->lastItem(),
                'total' => $salesReturns->total(),
            ]
        ], 200);
    }

    //------------------------------------------Show Sales Return by ID-----------------------------------//
    public function showById($id)
    {
        $salesReturn = SaleReturn::find($id);

        if (!$salesReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Sales Return not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sales Return Retrieved Successfully',
            'sales_return' => $salesReturn,
        ], 200);
    }

    //------------------------------------------Delete Sales Return-----------------------------------//
    public function delete($id)
    {
        $salesReturn = SaleReturn::find($id);

        if (!$salesReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Sales Return not found'
            ], 404);
        }

        $salesReturn->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sales Return Deleted Successfully',
        ], 200);
    }
}
