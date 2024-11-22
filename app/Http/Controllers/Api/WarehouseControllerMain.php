<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use Spatie\Permission\Models\Role;
use Auth;

class WarehouseControllerMain extends Controller
{
    //------------------------------------------Create Warehouse-----------------------------------//
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:10',
            'name' => 'required|string|max:255|unique:warehouses,name',
            'mobile' => 'nullable|numeric|min:10',
            // 'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255|unique:warehouses,email',
            'zip' => 'nullable|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        $existingWarehouse = Warehouse::where('name', $validatedData['name'])
            // ->where('email', $validatedData['email'])
            ->first();

        if ($existingWarehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse already exists',
            ], 409);
        }

        $prefix = $request->input('prefix', 'WRR');
        $warehouseCode = CodeGeneratorHelper::generateCode('warehouse', $prefix);

        $imageName = 'no_image.png';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('/images/warehouses'), $imageName);
        }

        $warehouse = Warehouse::create([
            'Ref' => $warehouseCode,
            'name' => $validatedData['name'],
            'mobile' => $validatedData['mobile'] ?? null,
            'country' => $validatedData['country'] ?? null,
            'city' => $validatedData['city'] ?? null,
            'email' => $validatedData['email'] ?? null,
            'zip' => $validatedData['zip'] ?? null,
            'image' => $imageName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse Created Successfully',
            // 'warehouse_code' => $warehouseCode,
            'data' => $warehouse,
        ], 201);
    }




    //------------------------------------------Update Warehouse-----------------------------------//
    public function update(Request $request, $id)
{
    $warehouse = Warehouse::find($id);

    if (!$warehouse) {
        return response()->json([
            'success' => false,
            'message' => 'Warehouse not found'
        ], 404);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|required|string|max:255|unique:warehouses,name,' . $id,
        // 'mobile' => 'nullable|string|max:15',
        'mobile' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        'country' => 'nullable|string|max:100',
        'city' => 'nullable|string|max:100',
        'email' => 'nullable|email|max:255|unique:warehouses,email,' . $id,
        'zip' => 'nullable|string|max:10',
        'prefix' => 'nullable|string|max:10', 
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $validatedData = $validator->validated();

    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '.' . $image->extension();
        $image->move(public_path('/images/warehouses'), $imageName);
        $validatedData['image'] = $imageName;
    }

    if (isset($validatedData['prefix'])) {
        $warehouse->code = CodeGeneratorHelper::generateCode('warehouse', $validatedData['prefix']);
    }

    $currentData = $warehouse->only(array_keys($validatedData));
    if ($validatedData == $currentData) {
        return response()->json([
            'success' => false,
            'message' => 'Warehouse already updated with the same data.',
        ], 200);
    }

    $warehouse->update($validatedData);

    return response()->json([
        'success' => true,
        'message' => 'Warehouse Updated Successfully',
        'warehouse' => $warehouse->fresh(),
    ], 200);
}




    //------------------------------------------Show All Warehouses-----------------------------------//
   public function showAll(Request $request)
{
    try {
        // Check if the user is authenticated
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Pagination parameters
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Start query for Warehouse
        $warehouseQuery = Warehouse::query();

        // Search for warehouses by name, city, or country
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            
            $warehouseQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('city', 'like', '%' . $search . '%')
                      ->orWhere('mobile', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('country', 'like', '%' . $search . '%');
            });
        }
        
         $warehouseQuery->orderBy('created_at', 'desc');

        // Paginate results
        $warehouses = $warehouseQuery->paginate($perPage, ['*'], 'page', $page);

        // Check if any warehouses are found
        if ($warehouses->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No warehouses found',
                'warehouses' => [],
            ], 404);
        }

        // Return successful response with paginated data
        return response()->json([
            'success' => true,
            'message' => 'All Warehouses Retrieved Successfully',
            'warehouses' => [
                'current_page' => $warehouses->currentPage(),
                'data' => $warehouses->items(),
                'first_page_url' => $warehouses->url(1),
                'from' => $warehouses->firstItem(),
                'last_page' => $warehouses->lastPage(),
                'last_page_url' => $warehouses->url($warehouses->lastPage()),
                'next_page_url' => $warehouses->nextPageUrl(),
                'path' => $warehouses->path(),
                'per_page' => $warehouses->perPage(),
                'prev_page_url' => $warehouses->previousPageUrl(),
                'to' => $warehouses->lastItem(),
                'total' => $warehouses->total(),
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}



    //------------------------------------------Show Warehouse by ID-----------------------------------//
    public function showById($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Warehouse Retrieves Successfully",
            'warehouse' => $warehouse,
        ], 200);
    }

    //------------------------------------------Delete Warehouse-----------------------------------//
    public function delete($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found'
            ], 404);
        }

        $warehouse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully',
        ], 200);
    }
}
