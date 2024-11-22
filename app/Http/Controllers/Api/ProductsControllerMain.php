<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\MaterialType;
use App\Models\FinishedGood;
use Auth;

class ProductsControllerMain extends Controller
{

    //------------------------------------------Create Product-----------------------------------//
    
 public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'product_description' => 'nullable|string|max:500',
            'neck_type' => 'nullable|string|max:100',
            'material_type' => 'nullable|exists:material_types,name',
            'finished_goods' => 'nullable|exists:finished_goods,name',
            'volume' => 'nullable|string|min:0',
            'material' => 'nullable|string|max:255',
            'weight' => 'nullable|string|min:0',
            'colour' => 'nullable|string|max:100',
            'cycle_time' => 'nullable|string|min:0',
            'no_of_bottles' => 'nullable|string|min:0',
            'no_of_box' => 'nullable|string|min:0',
            'reference' => 'nullable|string|max:255',
            'waste' => 'nullable|string|min:0',
            'down_time' => 'nullable|string|min:0',
            'product_id_control' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'product_type' => 'required|string|in:normal_product,product_trading,product_jasa', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Errors Occurred',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        // Initialize material type and finished goods codes
        $materialTypeCode = null;
        $finishedGoodsCode = null;

        // Retrieve material type code if provided
        if (isset($validatedData['material_type'])) {
            $materialType = MaterialType::where('name', $validatedData['material_type'])->first();
            if ($materialType) {
                $materialTypeCode = $materialType->code;  
            }
        }

        // Retrieve finished goods code if provided
        if (isset($validatedData['finished_goods'])) {
            $finishedGoods = FinishedGood::where('name', $validatedData['finished_goods'])->first();
            if ($finishedGoods) {
                $finishedGoodsCode = $finishedGoods->code;  
            }
        }

        // Ensure either material_type or finished_goods has a valid code
        if (!$materialTypeCode && !$finishedGoodsCode) {
            return response()->json([
                'success' => false,
                'message' => 'Either material_type or finished_goods must have a valid code.',
            ], 422);
        }

        // Select the correct prefix based on product_type
        $prefix = null;
        switch ($validatedData['product_type']) {
            case 'normal_product':
                $prefix = '70';
                break;
            case 'product_trading':
                $prefix = '71';
                break;
            case 'product_jasa':
                $prefix = '72';
                break;
            default:
                $prefix = '70'; // Fallback in case product_type is missing or invalid
                break;
        }

        // Determine which code to use for product ID
        $typeCode = $materialTypeCode ? $materialTypeCode : $finishedGoodsCode;
        $productID = $prefix . $typeCode;

        $lastProduct = Product::where('Ref', 'LIKE', $productID . '%')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastProduct) {
            $lastProductID = intval(substr($lastProduct->Ref, -4)); // Get the last 4 digits
            $nextProductID = str_pad($lastProductID + 1, 4, '0', STR_PAD_LEFT); // Increment and pad
            $productID .= $nextProductID; // Append new ID
        } else {
            // If no last product, start from 0001
            $productID .= '0001';
        }
 
        $imageName = 'no_image.png';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('/images/products'), $imageName);
        }

        // Only store the relative path of the image
        $imageUrl = 'images/products/' . $imageName;

        $product = Product::create([
            'Ref' => $productID,
            'name' => $validatedData['name'],
            'product_description' => $validatedData['product_description'] ?? null,
            'neck_type' => $validatedData['neck_type'] ?? null,
            'material_type' => $validatedData['material_type'] ?? null,
            'product_type' => $validatedData['product_type'] ?? null,
            'finished_goods' => $validatedData['finished_goods'] ?? null,
            'volume' => $validatedData['volume'] ?? null,
            'material' => $validatedData['material'] ?? null,
            'weight' => $validatedData['weight'] ?? null,
            'colour' => $validatedData['colour'] ?? null,
            'cycle_time' => $validatedData['cycle_time'] ?? null,
            'no_of_bottles' => $validatedData['no_of_bottles'] ?? null,
            'no_of_box' => $validatedData['no_of_box'] ?? null,
            'reference' => $validatedData['reference'] ?? null,
            'waste' => $validatedData['waste'] ?? null,
            'down_time' => $validatedData['down_time'] ?? null,
            'product_id_control' => $validatedData['product_id_control'] ?? null,
            'image' => $imageUrl, // Store relative image path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product Created Successfully',
            'product' => $product,
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}















    //-----------------------------------------Update Product--------------------------------------//
   public function update(Request $request, $id)
{
    $product = Product::find($id);

    if (!$product) {
        return response()->json([
            'success' => false,
            'message' => 'Product not found',
        ], 404);
    }

    // Validation rules
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|required|string|max:255',
        'product_description' => 'nullable|string|max:500',
        'neck_type' => 'nullable|string|max:100',
        'volume' => 'nullable|string|min:0',
        'material' => 'nullable|string|max:255',
        'weight' => 'nullable|string|min:0',
        'colour' => 'nullable|string|max:100',
        'cycle_time' => 'nullable|string|min:0',
        'no_of_bottles' => 'nullable|string|min:0',
        'no_of_box' => 'nullable|string|min:0',
        'reference' => 'nullable|string|max:255',
        'waste' => 'nullable|string|min:0',
        'down_time' => 'nullable|string|min:0',
        'product_id_control' => 'nullable|string|in:ok,not ok',
        'category_id' => 'sometimes|required|exists:categories,id',
        'brand_id' => 'sometimes|required|exists:brands,id',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        'order_tax' => 'nullable|numeric|min:0',
        'cost' => 'sometimes|required|string|min:0',
        'price' => 'sometimes|required|string|min:0',
        'material_type' => 'nullable|exists:material_types,name',
        'finished_goods' => 'nullable|exists:finished_goods,name',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    $validatedData = $validator->validated();

    // Retrieve material type code if provided
    if (isset($validatedData['material_type'])) {
        $materialType = MaterialType::where('name', $validatedData['material_type'])->first();
        if ($materialType) {
            $validatedData['material_type'] = $materialType->code; // Update material_type with its code
        }
    }

    // Retrieve finished goods code if provided
    if (isset($validatedData['finished_goods'])) {
        $finishedGoods = FinishedGood::where('name', $validatedData['finished_goods'])->first();
        if ($finishedGoods) {
            $validatedData['finished_goods'] = $finishedGoods->code; // Update finished_goods with its code
        }
    }

    // Handle image upload if provided
    if ($request->hasFile('image')) {
        // Delete the old image if it exists and is not the default image
        if ($product->image && $product->image !== 'images/products/no_image.png') {
            $oldImagePath = public_path($product->image);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Save new image
        $image = $request->file('image');
        $imageName = time() . '.' . $image->extension();
        $imagePath = 'images/products/' . $imageName;
        $image->move(public_path('images/products'), $imageName);
        $validatedData['image'] = $imagePath;
    }

    // Update the product with validated data
    $product->update($validatedData);

    // Prepare the full image URL
    $imageUrl = $product->image 
        ? url($product->image) 
        : url('images/products/no_image.png');

    // Response with updated product data
    return response()->json([
        'success' => true,
        'message' => 'Product updated successfully',
        'product' => [
            'id' => $product->id,
            'name' => $product->name,
            'product_description' => $product->product_description,
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
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'image' => $imageUrl,
            'order_tax' => $product->order_tax,
            'cost' => $product->cost,
            'price' => $product->price,
            'material_type' => $product->material_type, // Include material_type
            'finished_goods' => $product->finished_goods, // Include finished_goods
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ],
    ], 200);
}










    //---------------------------------------Show all products----------------------------------------// 
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
    $search = $request->input('search');
    $warehouseId = $request->input('warehouse_id'); 

    $query = Product::query();

    if ($search) {
        $query->where(function ($query) use ($search) {
            $query->where('Ref', 'like', '%' . $search . '%')   
                  ->orWhere('name', 'like', '%' . $search . '%')  
                  ->orWhere('neck_type', 'like', '%' . $search . '%') 
                  ->orWhere('material_type', 'like', '%' . $search . '%')  
                  ->orWhere('finished_goods', 'like', '%' . $search . '%')  
                  ->orWhere('weight', 'like', '%' . $search . '%')  
                  ->orWhere('product_description', 'like', '%' . $search . '%')  
                  ->orWhere('colour', 'like', '%' . $search . '%'); 
        });
    }

    if ($warehouseId) {
        $query->where('warehouse_id', $warehouseId); 
    }
    
    $query->orderBy('created_at', 'desc');

    $products = $query->paginate($perPage);

    if ($products->total() === 0) {
        return response()->json([
            'success' => false,
            'message' => 'No Products found',
        ], 404); 
    }

    // Update the image URL for each product with the full base URL
    $baseUrl = 'https://warehouse.bellwayinfotech.in/';
    $products->getCollection()->transform(function ($product) use ($baseUrl) {
        $product->image = $product->image ? $baseUrl . $product->image : $baseUrl . 'images/products/no_image.png';
        return $product;
    });

    return response()->json([
        'success' => true,
        'message' => 'All Products Retrieved Successfully',
        'products' => [
            'current_page' => $products->currentPage(),
            'data' => $products->items(),
            'first_page_url' => $products->url(1),
            'from' => $products->firstItem(),
            'last_page' => $products->lastPage(),
            'last_page_url' => $products->url($products->lastPage()),
            'next_page_url' => $products->nextPageUrl(),
            'path' => $products->path(),
            'per_page' => $products->perPage(),
            'prev_page_url' => $products->previousPageUrl(),
            'to' => $products->lastItem(),
            'total' => $products->total(),
        ]
    ], 200);
}








//----------------------------------------------Show All Products without pagination for search---------------------------------------------------//
 public function Allproducts(Request $request)
    {
        try {
    
            $product = Product::all();
            return response()->json([
                'status' => true,
                'message' => ' All Products Retrieved Successfully',
                'data' => $product
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }




    //--------------------------------------Show product by id----------------------------------------//

    public function showbyID($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Products Retrieves Successfully',
            'product' => $product,
        ], 200);
    }




    //-----------------------------------------Delete product--------------------------------------------//

    public function delete($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully',
        ], 200);
    }


}
