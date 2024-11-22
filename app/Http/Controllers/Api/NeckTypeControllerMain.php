<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\NeckType; // Ensure this is properly imported

class NeckTypeControllerMain extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|unique:neck_types,code|max:100',
                'name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Create a new NeckType
            $neckType = NeckType::create($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'Neck Type Created Successfully',
                'data' => $neckType,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    
    
    public function update(Request $request, $id)
{
    try {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|unique:neck_types,code,' . $id . '|max:100',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the NeckType by ID
        $neckType = NeckType::findOrFail($id);

        // Update NeckType
        $neckType->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Neck Type Updated Successfully',
            'data' => $neckType,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}




//------------------------------------------Show All Neck Types---------------------------------------------//

    public function showAll(Request $request)
{
    try {
        $perPage = $request->input('per_page', 10);   
        $search = $request->input('search');  

        $neckTypeQuery = NeckType::query();   
 
        if ($request->has('search') && !empty($search)) {
            $neckTypeQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', '%' . $search . '%')   
                    //   ->orWhere('name', 'like', '%' . $search . '%');  
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']); 
            });
        }
        
        $neckTypeQuery->orderBy('created_at', 'desc');
 
        $neckTypes = $neckTypeQuery->paginate($perPage);
 
        if ($neckTypes->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Neck Types found',
                'data' => [],
            ], 404);
        }
 
        return response()->json([
            'status' => true,
            'message' => 'Neck Types Retrieved Successfully',
            'data' => $neckTypes,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}





//-----------------------------------------------Show Neck Types By Id------------------------------------------//

public function showById($id)
{
    try {
        $neckType = NeckType::findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Neck Type Retrieved Successfully',
            'data' => $neckType,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Neck Type not found',
        ], 404);
    }
}


public function delete($id)
{
    try {
        $neckType = NeckType::findOrFail($id);

        // Delete NeckType
        $neckType->delete();

        return response()->json([
            'status' => true,
            'message' => 'Neck Type Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Neck Type not found',
        ], 404);
    }
}




}
