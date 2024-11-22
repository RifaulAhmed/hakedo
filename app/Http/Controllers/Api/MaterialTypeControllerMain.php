<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\MaterialType;  

class MaterialTypeControllerMain extends Controller
{
    
    //---------------------------------------------------Create Material Type---------------------------------------------//
    public function store(Request $request)
    {
        try {
            
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|unique:material_types,code|max:100',
                'name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
 
            $materialType = MaterialType::create($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'Material Type Created Successfully',
                'data' => $materialType,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    
    
    
    
    
    //----------------------------------------------------------Update Material Type---------------------------------------------//
    public function update(Request $request, $id)
{
    try {
        
        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|unique:material_types,code,' . $id . '|max:100',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
 
        $materialType = MaterialType::findOrFail($id);
 
        $materialType->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Material Type Updated Successfully',
            'data' => $materialType,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}





//-----------------------------------------------Show All Material Types---------------------------------------------//
public function showAll(Request $request)
{
    try {
        $perPage = $request->input('per_page', 10);  
        $search = $request->input('search');   

        $materialTypeQuery = MaterialType::query();   
 
        if ($request->has('search') && !empty($search)) {
            $materialTypeQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', '%' . $search . '%')  
                    //   ->orWhere('name', 'like', '%' . $search . '%');   
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']); 
            });
        }
        
        $materialTypeQuery->orderBy('created_at', 'desc');
 
        $materialTypes = $materialTypeQuery->paginate($perPage);
 
        if ($materialTypes->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Material Types found',
                'data' => [],
            ], 404);
        }
 
        return response()->json([
            'status' => true,
            'message' => 'Material Types Retrieved Successfully',
            'data' => $materialTypes,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}






//---------------------------------------------------Show Material Type By Id------------------------------------------------//
public function showById($id)
{
    try {
        $materialType = MaterialType::findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Material Type Retrieved Successfully',
            'data' => $materialType,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Material Type not found',
        ], 404);
    }
}






//-------------------------------------------------------Delete Material Type-------------------------------------------------------//
public function delete($id)
{
    try {
        $materialType = MaterialType::findOrFail($id);

        // Delete NeckType
        $materialType->delete();

        return response()->json([
            'status' => true,
            'message' => 'Material Type Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Material Type not found',
        ], 404);
    }
}




}
