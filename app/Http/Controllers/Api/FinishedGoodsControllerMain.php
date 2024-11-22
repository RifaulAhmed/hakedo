<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\FinishedGood;  

class FinishedGoodsControllerMain extends Controller
{
    
    
    
    //----------------------------------------------------Create Finished Goods-----------------------------------------------//
    public function store(Request $request)
    {
        try {
             
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|unique:finished_goods,code|max:100',
                'name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
 
            $neckType = FinishedGood::create($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'Finished Good Created Successfully',
                'data' => $neckType,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    
    
    
    
    
    
    //----------------------------------------------------Update Finished Goods--------------------------------------------------------//
    
    public function update(Request $request, $id)
{
    try {
         
        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|unique:finished_goods,code,' . $id . '|max:100',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
 
        $neckType = FinishedGood::findOrFail($id);
 
        $neckType->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'FinishedGood Updated Successfully',
            'data' => $neckType,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}









//-------------------------------------------------Show All Finished Goods-------------------------------------------------//

public function showAll(Request $request)
{
    try {
        $perPage = $request->input('per_page', 10);  
        $search = $request->input('search');  

        $finishedGoodQuery = FinishedGood::query();   
 
        if ($request->has('search') && !empty($search)) {
            $finishedGoodQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', '%' . $search . '%')   
                    //   ->orWhere('name', 'like', '%' . $search . '%');  
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']); 
            });
        }
        
        $finishedGoodQuery->orderBy('created_at', 'desc');
 
        $finishedGoods = $finishedGoodQuery->paginate($perPage);
 
        if ($finishedGoods->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Finished Goods found',
                'data' => [],
            ], 404);
        }
 
        return response()->json([
            'status' => true,
            'message' => 'Finished Goods Retrieved Successfully',
            'data' => $finishedGoods,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}








//-----------------------------------------------------Show Finished Goods By Id--------------------------------------------//

public function showById($id)
{
    try {
        $neckType = FinishedGood::findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'FinishedGood Retrieved Successfully',
            'data' => $neckType,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Material Type not found',
        ], 404);
    }
}







//--------------------------------------------------------Delete Finished Goods---------------------------------------------//
public function delete($id)
{
    try {
        $neckType = FinishedGood::findOrFail($id);
 
        $neckType->delete();

        return response()->json([
            'status' => true,
            'message' => 'FinishedGood Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Material Type not found',
        ], 404);
    }
}




}
