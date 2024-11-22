<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CountableUnloadingForm;
use Illuminate\Http\Request;
use Validator;
use Auth;

class CountableUnloadingControllerMain extends Controller
{
    public function store(Request $request){

        try{
            $validator = Validator::make($request->all(),[
                'date' => 'required|date',
                'do_number' => 'nullable|string|max:50',
                'license_plate_number' => 'required|string|max:20',
                'dn_number' => 'nullable|string|max:255',
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
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validations errors occured',
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

            $exisitngCountableForm = CountableUnloadingForm::where('do_number', $validatedData['do_number'])
                ->where('license_plate_number', $validatedData['license_plate_number'])
                ->first();

                if($exisitngCountableForm){
                    return response()->json([
                        'status' => false,
                        'message' => 'Countable Unloading Form already exists with this data'
                    ], 409);
                }

            $exisitngCountableForm = CountableUnloadingForm::create($validatedData);

            return response()->json([
                'status' => true,
                'message' => 'Countable Unloading Form Created Successfully',
                'data' => $exisitngCountableForm,
            ]);

        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    
    
    
    
    
    
   public function update(Request $request, $id)
{
    try {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'do_number' => 'nullable|string|max:50',
            'license_plate_number' => 'required|string|max:20',
            'dn_number' => 'nullable|string|max:255',
            'start_finish' => 'nullable|string|max:100',
            'product_description' => 'nullable|string|max:255',
            'production_lot' => 'nullable|integer|max:50',
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred',
                'error' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        // Calculate the total of the ts fields
        $total = array_sum(array_map(function ($key) use ($validatedData) {
            return $validatedData[$key] ?? 0; // Use 0 if not set
        }, ['ts_1', 'ts_2', 'ts_3', 'ts_4', 'ts_5', 'ts_6', 'ts_7', 'ts_8', 'ts_9', 'ts_10']));

        $validatedData['total'] = $total;

        $countableForm = CountableUnloadingForm::find($id);

        if (!$countableForm) {
            return response()->json([
                'status' => false,
                'message' => 'Countable Unloading Form not found',
            ], 404);
        }

        // Update the countable form with validated data
        $countableForm->update($validatedData);

        return response()->json([
            'status' => true,
            'message' => 'Countable Unloading Form updated successfully',
            'data' => $countableForm,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}







  public function showAll(Request $request)
{
    try {
        // Authenticate the user
        $user = Auth::guard('sanctum')->user();

        // If user is not authenticated, return unauthorized response
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Default perPage value
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Create the query for CountableUnloadingForm
        $formQuery = CountableUnloadingForm::query();

        // Check if the request has 'do_number' and apply the search filter
        if ($request->has('do_number') && !empty($request->input('do_number'))) {
            $doNumber = $request->input('do_number');
            $formQuery->where('do_number', 'like', '%' . $doNumber . '%');
        }

        // Paginate the results
        $countableForms = $formQuery->paginate($perPage, ['*'], 'page', $page);

        // Check if no forms are found
        if ($countableForms->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No forms found',
                'data' => [],
            ], 404);
        }

        // Return the paginated response
        return response()->json([
            'status' => true,
            'message' => 'Countable Unloading Forms Retrieved Successfully',
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






    public function showById($id)
{
    try {
        $countableForm = CountableUnloadingForm::find($id);

        if (!$countableForm) {
            return response()->json([
                'status' => false,
                'message' => 'Countable Unloading Form not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Countable Unloading Form Retrieved Successfully',
            'data' => $countableForm,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}





    public function delete($id)
{
    try {
        $countableForm = CountableUnloadingForm::find($id);

        if (!$countableForm) {
            return response()->json([
                'status' => false,
                'message' => 'Countable Unloading Form not found',
            ], 404);
        }

        $countableForm->delete();

        return response()->json([
            'status' => true,
            'message' => 'Countable Unloading Form Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}


  
    
}
