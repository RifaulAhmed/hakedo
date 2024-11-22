<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use App\Models\MaterialType;
use App\Models\Material;
use Auth;

class MaterialControllerMain extends Controller
{
    
    //-------------------------------------------------Create Material------------------------------------------//
    public function store(Request $request)
{
    try {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'material_type' => 'required|exists:material_types,name',  
            'material_description' => 'required|string|max:500',
            'min_stock' => 'nullable|integer|min:0',
            'rop' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Errors Occurred',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
 
        $materialType = MaterialType::where('name', $validatedData['material_type'])->first();
        if (!$materialType) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Material Type',
            ], 422);
        }
 
        $prefix = '30';

        // The next two digits come from the material_type code
        $materialTypeCode = $materialType->code;  

        // Generate the new material code
        $materialCodePrefix = $prefix . $materialTypeCode;

        // Find the last material with similar code and increment the last four digits
        $lastMaterial = Material::where('code', 'LIKE', $materialCodePrefix . '%')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastMaterial) {
            $lastMaterialID = intval(substr($lastMaterial->code, -4)); 
            $nextMaterialID = str_pad($lastMaterialID + 1, 4, '0', STR_PAD_LEFT);  
            $materialCode = $materialCodePrefix . $nextMaterialID;
        } else {
            // If no last material, start from 0001
            $materialCode = $materialCodePrefix . '0001';
        }
 
        $material = Material::create([
            'code' => $materialCode,
            'material_type' => $validatedData['material_type'],
            'material_description' => $validatedData['material_description'] ?? null,
            'min_stock' => $validatedData['min_stock'] ?? null,
            'rop' => $validatedData['rop'] ?? null,
            'max_stock' => $validatedData['max_stock'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Material Created Successfully',
            'material' => $material,
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}





    //----------------------------------------------------Update Material-------------------------------------------------//
    public function update(Request $request, $id)
{
    try {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'material_type' => 'nullable|exists:material_types,name',
            'material_description' => 'nullable|string|max:500',
            'min_stock' => 'nullable|integer|min:0',
            'rop' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Errors Occurred',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the material by ID
        $material = Material::find($id);
        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found',
            ], 404);
        }

        $validatedData = $validator->validated();

        // Check if material_type is provided and retrieve the code if necessary
        if (isset($validatedData['material_type'])) {
            $materialType = MaterialType::where('name', $validatedData['material_type'])->first();
            if (!$materialType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Material Type',
                ], 422);
            }
            $material->material_type = $materialType->name; // Update material_type if provided
        }

        // Only update other fields if they are provided, otherwise keep the previous data
        $material->update([
            'material_description' => $validatedData['material_description'] ?? $material->material_description,
            'min_stock' => $validatedData['min_stock'] ?? $material->min_stock,
            'rop' => $validatedData['rop'] ?? $material->rop,
            'max_stock' => $validatedData['max_stock'] ?? $material->max_stock,
        ]);

        // Return the material with the updated values
        return response()->json([
            'success' => true,
            'message' => 'Material Updated Successfully',
            'material' => $material,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}






//----------------------------------------------------Show All------------------------------------------------//
public function showAll(Request $request)
{
    try {
        
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
 
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
 
        $materialQuery = Material::query();
 
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            
            $materialQuery->where(function ($query) use ($search) {
                $query->where('material_type', 'like', '%' . $search . '%')
                      ->orWhere('min_stock', 'like', '%' . $search . '%')
                      ->orWhere('max_stock', 'like', '%' . $search . '%')
                      ->orWhere('rop', 'like', '%' . $search . '%')
                      ->orWhere('material_description', 'like', '%' . $search . '%')
                      ->orWhere('code', 'like', '%' . $search . '%');
            });
        }
 
        $materialQuery->orderBy('created_at', 'desc');
 
        $materials = $materialQuery->paginate($perPage, ['*'], 'page', $page);
 
        if ($materials->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No Materials found',
                'data' => [],
            ], 404);
        }
 
        return response()->json([
            'success' => true,
            'message' => 'Materials Retrieved Successfully',
            'data' => [
                'current_page' => $materials->currentPage(),
                'data' => $materials->items(),
                'first_page_url' => $materials->url(1),
                'from' => $materials->firstItem(),
                'last_page' => $materials->lastPage(),
                'last_page_url' => $materials->url($materials->lastPage()),
                'next_page_url' => $materials->nextPageUrl(),
                'path' => $materials->path(),
                'per_page' => $materials->perPage(),
                'prev_page_url' => $materials->previousPageUrl(),
                'to' => $materials->lastItem(),
                'total' => $materials->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}





    //-------------------------------------------------------Show Materials By Id-------------------------------------------//
    public function showById($id)
{
    try {
        
        $material = Material::find($id);
         
        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found',
            ], 404);
        }

        // If you have a field that needs to be decoded (e.g., 'extra_info'), you can handle it similarly.
        // Example (remove if not applicable):
        // $decodedField = $material->extra_info ? json_decode($material->extra_info, true) : [];

        // Return the material details with decoded fields if needed
        return response()->json([
            'success' => true,
            'message' => 'Material Retrieved Successfully',
            'data' => [
                'id' => $material->id,
                'code' => $material->code,
                'material_type' => $material->material_type,
                'material_description' => $material->material_description,
                'min_stock' => $material->min_stock,
                'rop' => $material->rop,
                'max_stock' => $material->max_stock,
                
                'created_at' => $material->created_at,
                'updated_at' => $material->updated_at,
            ],
        ], 200);

    } catch (\Exception $e) {
         
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}








    //-----------------------------------------------Delete Materials--------------------------------------------//
    public function delete($id)
{
    try {
        
        $material = Material::find($id);
        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found',
            ], 404);
        } 
        $material->delete();

        return response()->json([
            'success' => true,
            'message' => 'Material Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}





    
    
    
}