<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Provider;
use App\Helpers\CodeGeneratorHelper;

class ProvidersControllerMain extends Controller
{


    //---------------------------------------Create Provider----------------------------------//
    public function store(Request $request)
    {
        try {
            
             $customMessages = [
            'billing_address_postcode.integer' => 'Billing address postcode must be a number',
            'mobile.regex' => 'Mobile number must be atleast 10 digits.',
            'mobile.digits_between' => 'Mobile number must be at least 10 digits long and can include a leading +.',
            'mobile.numeric' => 'Mobile number must be a number.',
        ];   
            $validator = Validator::make($request->all(), [
                'company_name' => 'required|string|max:60',
                'first_name' => 'nullable|string|max:20',
                'middle_name' => 'nullable|string|max:20',
                'last_name' => 'nullable|string|max:20',
                'email' => 'nullable|string|email|max:255|unique:users,email',
                'phone' => 'nullable|regex:/^\+?[0-9\s]{11,16}$/',
                'mobile' => 'nullable|regex:/^\+?[0-9\s]{11,16}$/',
                'billing_address' => 'nullable|string|max:192',
                'billing_address_no' => 'nullable|string|max:50',
                'billing_address_rt' => 'nullable|string|max:50',
                'billing_address_rw' => 'nullable|string|max:50',
                'billing_address_postcode' => 'nullable|integer',
                'billing_address_urbanward' => 'nullable|string|max:192',
                'billing_address_district' => 'nullable|string|max:192',
                'billing_address_city' => 'nullable|string|max:192',
                'billing_address_province' => 'nullable|string|max:192',
                'tax_number' => 'nullable|string',
            ], $customMessages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();
 
            $existingSupplier = Provider::where('company_name', $validatedData['company_name'])
                // ->where('billing_address_no', $validatedData['billing_address_no'])
                // ->where('tax_number', $validatedData['tax_number'])
                ->first();

            if ($existingSupplier) {
                return response()->json([
                    'status' => false,
                    'message' => 'Supplier already exists with this data',
                ], 409);
            }
 
            $supplierCode = $this->generateSupplierCode();
 
            $supplier = Provider::create([
                'Ref' => $supplierCode,
                'company_name' => $validatedData['company_name'],
                'first_name' => $validatedData['first_name'] ?? null,
                'middle_name' => $validatedData['middle_name'] ?? null,
                'last_name' => $validatedData['last_name'] ?? null,
                'email' => $validatedData['email'] ?? null,
                'phone' => $validatedData['phone'] ?? null,
                'mobile' => $validatedData['mobile'] ?? null,
                'billing_address' => $validatedData['billing_address'] ?? null,
                'billing_address_no' => $validatedData['billing_address_no'] ?? null,
                'billing_address_rt' => $validatedData['billing_address_rt'] ?? null,
                'billing_address_rw' => $validatedData['billing_address_rw'] ?? null,
                'billing_address_postcode' => $validatedData['billing_address_postcode'] ?? null,
                'billing_address_province' => $validatedData['billing_address_province'] ?? null,
                'billing_address_city' => $validatedData['billing_address_city'] ?? null,
                'billing_address_district' => $validatedData['billing_address_district'] ?? null,
                'tax_number' => $validatedData['tax_number'] ?? null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Supplier Created Successfully',
                'data' => $supplier
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
 
    protected function generateSupplierCode()
    { 
        $lastSupplier = Provider::orderBy('id', 'desc')->first();
 
        if (!$lastSupplier) {
            return '20001';
        }
 
        $lastCode = (int)substr($lastSupplier->Ref, 2); 
        $newCode = $lastCode + 1;
 
        return '20' . str_pad($newCode, 3, '0', STR_PAD_LEFT);
    }






    //----------------------------------------Update Provider--------------------------------//
    public function update(Request $request, $id)
{
    try {
        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:80',
            'first_name' => 'nullable|string|max:20',
            'middle_name' => 'nullable|string|max:20',
            'last_name' => 'nullable|string|max:20',
            // 'email' => 'nullable|string|email|max:255|unique:providers,email,' . $id,
            'email' => 'nullable|string|email|max:255' . $id,
            'phone' => 'nullable|regex:/^\+?[0-9\s]{11,16}$/',
            'mobile' => 'nullable|regex:/^\+?[0-9\s]{11,16}$/',
            'billing_address' => 'nullable|string|max:192',
            'billing_address_no' => 'nullable|string|max:50',
            'billing_address_rt' => 'nullable|string|max:50',
            'billing_address_rw' => 'nullable|string|max:50',
            'billing_address_postcode' => 'nullable|integer',
            'billing_address_urbanward' => 'nullable|string|max:192',
            'billing_address_district' => 'nullable|string|max:192',
            'billing_address_province' => 'nullable|string|max:192',
            'billing_address_city' => 'nullable|string|max:192',
            'tax_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        $supplier = Provider::findOrFail($id);

        $hasChanges = false;
        foreach ($validatedData as $key => $value) {
            if ($supplier->$key != $value) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            return response()->json([
                'status' => false,
                'message' => 'Supplier already updated with this data',
            ], 200);
        }

        $supplier->update($validatedData);

        return response()->json([
            'status' => true,
            'message' => 'Supplier updated successfully',
            'data' => $supplier,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}








    //-------------------------------------Show All Providers--------------------------------//
   public function showAll(Request $request)
{
    try {
        
        $perPage = $request->input('per_page', 10);
 
        $providerQuery = Provider::query();
 
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            
            $providerQuery->where(function ($query) use ($search) {
                $query->where('Ref', $search)
                    ->orWhere('company_name', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('billing_address_district', 'like', '%' . $search . '%');
                    // ->orWhere('tax_number', 'like', '%' . $search . '%');
            });
        }
        
         $providerQuery->orderBy('created_at', 'desc');
 
        $providers = $providerQuery->paginate($perPage);
 
        if ($providers->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Supplier found',
                'data' => [],
            ], 404);
        }
 
        return response()->json([
            'status' => true,
            'message' => 'Suppliers retrieved successfully',
            'data' => $providers,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}

    
    
    
    //------------------------------------------------Show all Suppliers without pagination for search bar-------------------------------------------//
     public function Allsuppliers(Request $request)
    {
        try {
    
            $provider = Provider::all();
            return response()->json([
                'status' => true,
                'message' => ' All Suppliers Retrieved Successfully',
                'data' => $provider
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }







    //---------------------------------Show Providers By ID----------------------------------//
    public function showById($id)
    {
        try {
    
            $supplier = Provider::findOrFail($id);
            return response()->json([
                'status' => true,
                'message' => 'Supplier Retrieve Successfully',
                'data' => $supplier
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Provider not found',
            ], 404);
        }
    }






    //------------------------------------Delete Providers-----------------------------------//
    public function delete($id)
    {
        try {
            $supplier = Provider::findOrFail($id);
            $supplier->delete();

            return response()->json([
                'status' => true,
                'message' => 'Supplier Deleted Successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Supplier not found',
            ], 404);
        }
    }








}
