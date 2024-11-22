<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Adjustment;
use App\Models\AdjustmentDetail;
use App\Models\Product;
use App\Models\SaleDetail;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use DB;
use Carbon\Carbon;
use Auth;


class SalesControllerMain extends Controller
{
    //-------------------------------------------Create Sales---------------------------------------//
    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $validator = Validator::make($request->all(), [
            'client_id' => 'nullable|exists:clients,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date_format:Y-m-d',
            'GrandTotal' => 'required|numeric|min:0',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'prefix' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        $product = Product::find($validatedData['product_id']);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $productVariant = ProductVariant::where('product_id', $validatedData['product_id'])->first();
        if (!$productVariant) {
            return response()->json([
                'success' => false,
                'message' => 'Product variant not found for this product'
            ], 404);
        }

        $stock = Adjustment::where('warehouse_id', $validatedData['warehouse_id'])
            ->where('product_id', $validatedData['product_id'])
            ->first();

        if ($stock && $stock->items < $validatedData['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock available'
            ], 409);
        }

        $salePrefix = $validatedData['prefix'] ?? 'SAL';
        $saleCode = CodeGeneratorHelper::generateCode('sale', $salePrefix);

        $transactionId = CodeGeneratorHelper::generateTransactionId();

        $sale = Sale::create([
            'Ref' => $saleCode,
            'client_id' => $validatedData['client_id'] ?? null,
            'warehouse_id' => $validatedData['warehouse_id'],
            'date' => Carbon::parse($validatedData['date'])->format('Y-m-d'),
            'GrandTotal' => $validatedData['GrandTotal'],
            'is_pos' => 0, 
            'statut' => 1, 
            'transaction_id' => $transactionId,
        ]);

        if ($stock) {
            $stock->items -= $validatedData['quantity'];
            $stock->save();
        } else {
            $stock = Adjustment::create([
                'Ref' => CodeGeneratorHelper::generateCode('adjustment', 'STK'),
                'warehouse_id' => $validatedData['warehouse_id'],
                'product_id' => $validatedData['product_id'],
                'items' => -$validatedData['quantity'],
            ]);
        }

        AdjustmentDetail::create([
            'adjustment_id' => $stock->id,
            'product_id' => $validatedData['product_id'],
            'quantity' => -$validatedData['quantity'],
        ]);

        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $validatedData['product_id'],
            'product_variant_id' => $productVariant->id, 
            'quantity' => $validatedData['quantity'],
            'price' => $product->price, 
            'date' => Carbon::parse($validatedData['date'])->format('Y-m-d'), 
            'transaction_id' => $transactionId,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Sale Created and Stock Updated Successfully',
            'sale_id' => $sale->Ref,
            'sale' => $sale,
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}









    //--------------------------------------------Update Sales----------------------------------------//
    public function update(Request $request, $id)
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'sometimes|required|exists:clients,id',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'date' => 'sometimes|required|date',
            'GrandTotal' => 'sometimes|required|numeric|min:0',
            // 'TaxNet' => 'nullable|numeric|min:0',
            // 'notes' => 'nullable|string|max:1000',
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
            $sale->Ref = CodeGeneratorHelper::generateCode('sale', $validatedData['prefix']);
        }

        $currentData = $sale->only(array_keys($validatedData));
        if ($validatedData == $currentData) {
            return response()->json([
                'success' => false,
                'message' => 'Sales already updated with the same data.',
            ], 200);
        }

        $sale->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Sale Updated Successfully',
            'sale' => $sale->fresh(),
        ], 200);
    }




    //-----------------------------------------Show All Sales----------------------------------------//
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
        $sales = Sale::paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'All Sales Retrieves Succussfully',
            'sales' => $sales,
        ], 200);
    }



    //-------------------------------------Show Sales by Id-----------------------------------------//
    public function showByID($id)
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sales Retrieve Successfully',
            'sale' => $sale,
        ], 200);
    }



//------------------------------------------Delete Sale---------------------------------------//
    public function delete($id)
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        $sale->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sale Deleted Successfully',
        ], 200);
    }
}

