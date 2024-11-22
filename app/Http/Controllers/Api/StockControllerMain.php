<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CodeGeneratorHelper;
use App\Http\Controllers\Controller;
use App\Models\AdjustmentDetail;
use Illuminate\Http\Request;
use App\Models\Adjustment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductVariant;
use Auth;

class StockControllerMain extends Controller
{
    //------------------------------------------Create Stock-----------------------------------//
   public function store(Request $request)
{
    try {
 
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'products' => 'required|array', 
            'products.*.id' => 'required|exists:products,id', 
            'products.*.items' => 'required|numeric|min:0', 
            'date' => 'required|date',  
            'prefix' => 'nullable|string|max:10', 
        ]);
 
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); 
        }
 
        $validatedData = $validator->validated();
        $productsToEncode = [];   
 
        foreach ($validatedData['products'] as $productData) {
            $productId = $productData['id'];
            $items = $productData['items'];  
 
            $productsToEncode[] = [
                'id' => $productId,
                'items' => $items
            ];
        }
 
        $prefix = $validatedData['prefix'] ?? 'STK';
        $stockCode = CodeGeneratorHelper::generateCode('adjustment', $prefix);
 
        $encodedData = json_encode($productsToEncode);
 
        $stock = Adjustment::create([
            'Ref' => $stockCode,
            'warehouse_id' => $validatedData['warehouse_id'],
            'items' => $encodedData, 
            'date' => $validatedData['date'],
        ]);
 
        return response()->json([
            'success' => true,
            'message' => 'Stock Process Completed',
            'created_stock' => $stock,   
            'encoded_products' => $encodedData,  
        ], 201);

    } catch (\Exception $e) {
    
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}









    //------------------------------------------Update Stock-----------------------------------//
   public function update(Request $request)
{
    try {
  
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:adjustments,product_id', 
            'products.*.items' => 'nullable|numeric|min:0', 
            'products.*.date' => 'nullable|date', 
        ]);

        if ($validator->fails()) {
           
            \Log::error('Validation errors:', $validator->errors()->toArray());

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
        $response = [];

        foreach ($validatedData['products'] as $productData) {
          
            $stock = Adjustment::where('product_id', $productData['id'])->first();

            if (!$stock) {
                $response[] = [
                    'id' => $productData['id'],
                    'success' => false,
                    'message' => 'Stock not found for product ID: ' . $productData['id'],
                ];
                continue; 
            }

            $updateData = array_filter([
                'items' => $productData['items'] ?? $stock->items,
                'date' => $productData['date'] ?? $stock->date,
            ]);

            if (empty($updateData)) {
                $response[] = [
                    'id' => $productData['id'],
                    'success' => false,
                    'message' => 'No new data to update for this stock.',
                ];
                continue; 
            }

            $stock->update($updateData);

            $adjustmentDetail = AdjustmentDetail::where('adjustment_id', $stock->id)
                ->where('product_id', $stock->product_id)
                ->first();

            if ($adjustmentDetail) {
                $adjustmentDetail->update([
                    'quantity' => $updateData['items'],
                ]);
            } else {
                $response[] = [
                    'id' => $productData['id'],
                    'success' => false,
                    'message' => 'Adjustment detail not found for this stock.',
                ];
                continue; 
            }

            $response[] = [
                'id' => $productData['id'],
                'success' => true,
                'message' => 'Stock and Adjustment Detail Updated Successfully',
                'stock' => $stock->fresh(),
            ];
        }

        return response()->json($response, 200); 
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}





    //------------------------------------------Show All Stocks-----------------------------------//
   public function showAll(Request $request)
{
    $user = Auth::guard('sanctum')->user();
    
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    $perPage = $request->input('per_page', 10);
    $warehouseName = $request->input('warehouse_name');  
 
    $query = Adjustment::with(['warehouse', 'product'])
        ->join('warehouses', 'adjustments.warehouse_id', '=', 'warehouses.id')
        ->select('adjustments.*');
 
    if ($warehouseName) {
        $query->where('warehouses.name', 'LIKE', '%' . $warehouseName . '%');
    }
 
    $stocks = $query->paginate($perPage);
 
    if ($stocks->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No stocks found for the specified warehouse name.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Stocks Retrieved Successfully',
        'stocks' => [
            'current_page' => $stocks->currentPage(),
            'data' => $stocks->items(),
            'first_page_url' => $stocks->url(1),
            'from' => $stocks->firstItem(),
            'last_page' => $stocks->lastPage(),
            'last_page_url' => $stocks->url($stocks->lastPage()),
            'next_page_url' => $stocks->nextPageUrl(),
            'path' => $stocks->path(),
            'per_page' => $stocks->perPage(),
            'prev_page_url' => $stocks->previousPageUrl(),
            'to' => $stocks->lastItem(),
            'total' => $stocks->total(),
        ]
    ], 200);
}



    //------------------------------------------Show Stock by ID-----------------------------------//
  public function showById($id)
{
    try {
 
        $stock = Adjustment::with(['warehouse'])->find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock not found'
            ], 404);
        }
 
        $decodedProducts = json_decode($stock->items, true);
 
        $productDetails = [];
 
        foreach ($decodedProducts as $productData) {
            
            $product = Product::find($productData['id']);  

            if ($product) {
                
                $productDetails[] = [
                    'id' => $product->id,
                    'type' => $product->type,
                    'Ref' => $product->Ref,
                    'product_description' => $product->product_description,
                    'Type_barcode' => $product->Type_barcode,
                    'name' => $product->name,
                    'neck_type' => $product->neck_type,
                    'volume' => $product->volume,
                    'material' => $product->material,
                    'weight' => $product->weight,
                    'colour' => $product->colour,
                    'cycle_time' => $product->cycle_time,
                    'no_of_bottles' => $product->no_of_bottles,
                    'no_of_box' => $product->no_of_box,
                    'reference' => $product->reference,
                    'waste' => $product->waste,
                    'down_time' => $product->down_time,
                    'product_id_control' => $product->product_id_control,
                    'cost' => $product->cost,
                    'price' => $product->price,
                    'category_id' => $product->category_id,
                    'prefix' => $product->prefix,
                    'brand_id' => $product->brand_id,
                    'unit_id' => $product->unit_id,
                    'unit_sale_id' => $product->unit_sale_id,
                    'unit_purchase_id' => $product->unit_purchase_id,
                    'TaxNet' => $product->TaxNet,
                    'tax_method' => $product->tax_method,
                    'image' => $product->image,
                    'note' => $product->note,
                    'stock_alert' => $product->stock_alert,
                    'qty_min' => $product->qty_min,
                    'is_promo' => $product->is_promo,
                    'promo_price' => $product->promo_price,
                    'promo_start_date' => $product->promo_start_date,
                    'promo_end_date' => $product->promo_end_date,
                    'is_variant' => $product->is_variant,
                    'is_imei' => $product->is_imei,
                    'not_selling' => $product->not_selling,
                    'warehouse_id' => $product->warehouse_id,
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'deleted_at' => $product->deleted_at,
                    'items' => $productData['items']  
                ];
            }
        }
 
        return response()->json([
            'success' => true,
            'message' => 'Stock Retrieved Successfully',
            'stock' => [
                'Ref' => $stock->Ref,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse->name,   
                'date' => $stock->date,
                'products' => $productDetails,  
            ]
        ], 200);

    } catch (\Exception $e) {
        
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 422);
    }
}



    //------------------------------------------Delete Stock-----------------------------------//
    public function delete($id)
    {
        $stock = Adjustment::find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock not found'
            ], 404);
        }

        $stock->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stock Deleted Successfully',
        ], 200);
    }

    //------------------------------------------Handle Stock Increase/Decrease-----------------------------------//
    public function adjustStock($productId, $warehouseId, $quantity, $operation)
    {
        $stock = Adjustment::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$stock) {
     
            $stock = Adjustment::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => 0,
            ]);
        }
 
        if ($operation === 'increase') {
            $stock->quantity += $quantity;
        } elseif ($operation === 'decrease') {
            $stock->quantity -= $quantity;
            if ($stock->quantity < 0) {
                $stock->quantity = 0;  
            }
        }

        $stock->save();
    }

    //------------------------------------------Update Stock on Purchase-----------------------------------//
    public function updateStockOnPurchase(Purchase $purchase)
    {
        foreach ($purchase->purchaseDetails as $detail) {
            $this->adjustStock($detail->product_id, $purchase->warehouse_id, $detail->quantity, 'increase');
        }
    }

    //------------------------------------------Update Stock on Sale-----------------------------------//
    public function updateStockOnSale(Sale $sale)
    {
        foreach ($sale->saleDetails as $detail) {
            $this->adjustStock($detail->product_id, $sale->warehouse_id, $detail->quantity, 'decrease');
        }
    }

    //------------------------------------------Update Stock on Purchase Return-----------------------------------//
    public function updateStockOnPurchaseReturn(PurchaseReturn $purchaseReturn)
    {
        $this->adjustStock($purchaseReturn->product_id, $purchaseReturn->warehouse_id, $purchaseReturn->quantity, 'decrease');
    }

    //------------------------------------------Update Stock on Sale Return-----------------------------------//
    public function updateStockOnSaleReturn(SaleReturn $saleReturn)
    {
        $this->adjustStock($saleReturn->product_id, $saleReturn->warehouse_id, $saleReturn->quantity, 'increase');
    }
}
