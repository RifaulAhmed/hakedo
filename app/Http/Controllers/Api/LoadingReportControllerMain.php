<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoadingReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

class LoadingReportControllerMain extends Controller
{
    
    //----------------------------------------------------Create Loading Report--------------------------------------------//
   public function store(Request $request)
{
    try {
 
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:10',  
            'date' => 'required|date',
            'license_plate_number' => 'required|string|max:20',
            'start_finish' => 'nullable|string|max:100',
            'product_description' => 'nullable|string|max:255',
            'production_lot' => 'nullable|string|max:50',
            'qty_do' => 'nullable|integer',
            'ts_1' => 'nullable|integer',
            'ts_2' => 'nullable|integer',
            'ts_3' => 'nullable|integer',
            'ts_4' => 'nullable|integer',
            'ts_5' => 'nullable|integer',
            'ts_6' => 'nullable|integer',
            'ts_7' => 'nullable|integer',
            'ts_8' => 'nullable|integer',
            'ts_9' => 'nullable|integer',
            'ts_10' => 'nullable|integer',
            'destination' => 'required|string|max:255',  
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred',
                'error' => $validator->errors(),
            ], 422);
        }
 
        $validatedData = $validator->validated();
 
        $total = array_sum([
            $validatedData['ts_1'] ?? 0,
            $validatedData['ts_2'] ?? 0,
            $validatedData['ts_3'] ?? 0,
            $validatedData['ts_4'] ?? 0,
            $validatedData['ts_5'] ?? 0,
            $validatedData['ts_6'] ?? 0,
            $validatedData['ts_7'] ?? 0,
            $validatedData['ts_8'] ?? 0,
            $validatedData['ts_9'] ?? 0,
            $validatedData['ts_10'] ?? 0,
        ]);

        $validatedData['total'] = $total;
 
        $existingCountableForm = LoadingReport::where('date', $validatedData['date'])
            ->where('license_plate_number', $validatedData['license_plate_number'])
            ->where('destination', $validatedData['destination'])
            ->first();

        if ($existingCountableForm) {
            return response()->json([
                'status' => false,
                'message' => 'Loading Report already exists for this date, license plate number, and destination',
            ], 409);
        }
 
        $prefix = $validatedData['prefix'] ?? 'LOAD';  
        $currentYear = now()->format('Y');
        $warehouseCode = 'Warehouse';  
        $plantCode = 'HPM';             
        $shiftCode = 'VII';            
 
        $latestReport = LoadingReport::latest('id')->first();
        $nextNumber = $latestReport ? ($latestReport->id + 1) : 1;
        $refNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT) . "/$prefix/$warehouseCode/$plantCode/$shiftCode/$currentYear";
 
        $validatedData['do_number'] = $refNumber;
 
        $newLoadingReport = LoadingReport::create($validatedData);

        return response()->json([
            'status' => true,
            'message' => 'Loading Report Created Successfully',
            'data' => $newLoadingReport,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}



    
    
    
    
    
    
    //--------------------------------------------------Update Loading Report----------------------------------------------//
  public function update(Request $request, $id)
{
    try {
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:50',
            'date' => 'nullable|date',
            'license_plate_number' => 'nullable|string|max:20',
            'start_finish' => 'nullable|string|max:100',
            'product_description' => 'nullable|string|max:255',
            'production_lot' => 'nullable|string|max:50',
            'qty_do' => 'nullable|integer',
            'ts_1' => 'nullable|integer',
            'ts_2' => 'nullable|integer',
            'ts_3' => 'nullable|integer',
            'ts_4' => 'nullable|integer',
            'ts_5' => 'nullable|integer',
            'ts_6' => 'nullable|integer',
            'ts_7' => 'nullable|integer',
            'ts_8' => 'nullable|integer',
            'ts_9' => 'nullable|integer',
            'ts_10' => 'nullable|integer',
            'destination' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred',
                'error' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
 
        $loadingReport = LoadingReport::find($id);

        if (!$loadingReport) {
            return response()->json([
                'status' => false,
                'message' => 'Loading Report not found',
            ], 404);
        }
 
        $prefix = $validatedData['prefix'] ?? $loadingReport->do_number_prefix;  
        $nextNumber = str_pad($loadingReport->id, 5, '0', STR_PAD_LEFT);  
         
        $refNumber = "{$nextNumber}/{$prefix}";
 
        $updateData = [];
 
        foreach ($validatedData as $key => $value) {
            if ($value !== '' && $value !== null) {
                $updateData[$key] = $value;  
            }
        }
 
        $updateData['do_number'] = $refNumber;
 
        $updateData['total'] = array_sum([
            $validatedData['ts_1'] ?? $loadingReport->ts_1,
            $validatedData['ts_2'] ?? $loadingReport->ts_2,
            $validatedData['ts_3'] ?? $loadingReport->ts_3,
            $validatedData['ts_4'] ?? $loadingReport->ts_4,
            $validatedData['ts_5'] ?? $loadingReport->ts_5,
            $validatedData['ts_6'] ?? $loadingReport->ts_6,
            $validatedData['ts_7'] ?? $loadingReport->ts_7,
            $validatedData['ts_8'] ?? $loadingReport->ts_8,
            $validatedData['ts_9'] ?? $loadingReport->ts_9,
            $validatedData['ts_10'] ?? $loadingReport->ts_10,
        ]);
 
        $loadingReport->update($updateData);

        return response()->json([
            'status' => true,
            'message' => 'Loading Report Updated Successfully',
            'data' => $loadingReport,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}









//------------------------------------------------Get all Loading Report--------------------------------------------------------------//

  public function showAll(Request $request)
{
    try {
 
        $user = Auth::guard('sanctum')->user();
 
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
 
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
 
        $formQuery = LoadingReport::query();
 
        if ($request->has('do_number') && !empty($request->input('do_number'))) {
            $doNumber = $request->input('do_number');
            $formQuery->where('do_number', 'like', '%' . $doNumber . '%');
        }
 
        $countableForms = $formQuery->paginate($perPage, ['*'], 'page', $page);
 
        if ($countableForms->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No forms found',
                'data' => [],
            ], 404);
        }
 
        return response()->json([
            'status' => true,
            'message' => 'Loading Report Retrieved Successfully',
            'data' => [
                'current_page' => $countableForms->currentPage(),
                'data' => $countableForms->items(),
                'first_page_url' => $countableForms->url(1),
                'from' => $countableForms->firstItem(),
                'last_page' => $countableForms->lastPage(),
                'last_page_url' => $countableForms->url($countableForms->lastPage()),
                'next_page_url' => $countableForms->nextPageUrl(),
                'path' => $countableForms->path(),
                'per_page' => $countableForms->perPage(),
                'prev_page_url' => $countableForms->previousPageUrl(),
                'to' => $countableForms->lastItem(),
                'total' => $countableForms->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}





//---------------------------------------------------------Get Loading Report By Id------------------------------------------//
    public function showById($id)
{
    try {
        $countableForm = LoadingReport::find($id);

        if (!$countableForm) {
            return response()->json([
                'status' => false,
                'message' => 'Loading Report not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Loading Rport Retrieved Successfully',
            'data' => $countableForm,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}




//-------------------------------------------------Delete Loading Report----------------------------------------------//
    public function delete($id)
{
    try {
        $countableForm = LoadingReport::find($id);

        if (!$countableForm) {
            return response()->json([
                'status' => false,
                'message' => 'Loading Report not found',
            ], 404);
        }

        $countableForm->delete();

        return response()->json([
            'status' => true,
            'message' => 'Loading Report Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}


  
    
}
