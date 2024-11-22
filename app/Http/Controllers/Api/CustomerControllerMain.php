<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;

class CustomerControllerMain extends Controller
{
    public function store(Request $request)
    {
        try {
            
            $customMessages = [
            'phone.regex' => 'Phone number must be a valid number and at least 10 digits long.',
            'phone.digits_between' => 'Phone number must be between 10 and 16 digits long',
            'phone.numeric' => 'Phone number must be a number.',
            
            'office_number.regex' => 'Office number must be a valid number and at least 10 digits long.',
            'office_number.digits_between' => 'Office number must be between 10 and 16 digits long.',
            'office_number.numeric' => 'Office number must be a number.',
        ]; 
            // Validate request data
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required|string',
                'billing_address' => 'nullable|string|max:192',
                'delivery_address' => 'nullable|string|max:192',
                'contact_person' => 'nullable|string|max:20',
                'email' => 'nullable|string|email|max:255|unique:users,email',
                // 'phone' => 'nullable|numeric|digits:10',
                'phone' => 'nullable|regex:/^\+?[0-9\s]{11,16}$/',
                'office_number' => 'nullable|regex:/^\+?[0-9\s]{11,16}$/',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            ], $customMessages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error occurred',
                    'data' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            // Check if the customer already exists
            $existingCustomer = Client::where('customer_name', $validatedData['customer_name'])
                // ->where('email', $validatedData['email'])
                ->first();

            if ($existingCustomer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Customer already exists',
                ], 422);
            }

            // Generate customer code starting with "10"
            $customerCode = $this->generateCustomerCode();

            // Handle image upload
            $imageName = 'no_image.png';
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->extension();
                $image->move(public_path('/images/products'), $imageName);
            }

            $imageUrl = asset('images/products/' . $imageName);

            // Create the new customer
            $customer = Client::create([
                'Ref' => $customerCode,  // Added customer code field
                'customer_name' => $validatedData['customer_name'],
                'image' => $imageUrl ?? null,
                'billing_address' => $validatedData['billing_address'] ?? null,
                'delivery_address' => $validatedData['delivery_address'] ?? null,
                'contact_person' => $validatedData['contact_person'] ?? null,
                'email' => $validatedData['email'] ?? null,
                'phone' => $validatedData['phone'] ?? null,
                'office_number' => $validatedData['office_number'] ?? null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Customer Created Successfully',
                'data' => $customer,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // Helper function to generate the customer code
    protected function generateCustomerCode()
    {
        // Get the last created customer
        $lastCustomer = Client::orderBy('id', 'desc')->first();

        // If no customer exists, start from 10001
        if (!$lastCustomer) {
            return '10001';
        }

        // Extract the numeric part of the code and increment it
        $lastCode = (int)substr($lastCustomer->Ref, 2); // Get the last three digits after "10"
        $newCode = $lastCode + 1;

        // Ensure the new code has leading zeroes and starts with "10"
        return '10' . str_pad($newCode, 3, '0', STR_PAD_LEFT);
    }





    //-------------------------------------Update Customer-------------------------------//
    public function update(Request $request, $id)
{
    try {
        
         $customMessages = [
            'phone.regex' => 'Phone number must be a valid number and at least 10 digits long.',
            'phone.digits_between' => 'Phone number must be between 10 and 16 digits long',
            'phone.numeric' => 'Phone number must be a number.',
            
            'office_number.regex' => 'Office number must be a valid number and at least 10 digits long.',
            'office_number.digits_between' => 'Office number must be between 10 and 16 digits long.',
            'office_number.numeric' => 'Office number must be a number.',
        ]; 
        
        $validator = Validator::make($request->all(), [
            'customer_name' => 'nullable|string',
            'billing_address' => 'nullable|string|max:192',
            'delivery_address' => 'nullable|string|max:192',
            'contact_person' => 'nullable|string|max:20',
            'email' => 'nullable|string|email|max:255' . $id,
            // 'phone' => 'nullable|numeric|digits:10',
            'phone' => 'nullable|regex:/^\+?[0-9\s]{11,16}$/',
            'office_number' => 'nullable|regex:/^\+?[0-9\s]{11,16}$/',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ], $customMessages);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error occurred',
                'data' => $validator->errors(),
            ], 422);
        }

        $customer = Client::find($id);

        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $validatedData = $validator->validated();

        $dataToUpdate = array_filter($validatedData, function ($value) {
            return $value !== null && $value !== '';
        });

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('/images/products'), $imageName);
            $dataToUpdate['image'] = asset('images/products/' . $imageName);
        }

        if (empty($dataToUpdate)) {
            return response()->json([
                'status' => false,
                'message' => 'No changes were made, the data is already up-to-date',
            ], 200);
        }

        $customer->update(array_merge($customer->toArray(), $dataToUpdate));

        return response()->json([
            'status' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer->fresh(),
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred: ' . $e->getMessage(),
        ], 500);
    }
}








    //-------------------------------------Show All Customer-------------------------------//
//     public function showAll(Request $request)
// {
//     try {
//         // Retrieve search parameters
//         $search = $request->input('search'); // Search parameter for customer name, Ref (customer id), or phone

//         // Perform search query based on the search term
//         $customers = Client::query()
//             ->when($search, function ($query, $search) {
//                 return $query->where('customer_name', 'like', '%' . $search . '%')
//                     ->orWhere('Ref', 'like', '%' . $search . '%')
//                     ->orWhere('phone', 'like', '%' . $search . '%')
//                     ->orWhere('email', 'like', '%' . $search . '%')
//                     ->orWhere('delivery_address', 'like', '%' . $search . '%');
//             })
//             ->get();

//         if ($customers->isEmpty()) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'No Customers found',
//             ], 404);
//         }

//         return response()->json([
//             'status' => true,
//             'message' => 'Customers retrieved successfully',
//             'data' => $customers,
//         ], 200);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => false,
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }



public function showAll(Request $request)
{
    try {
        
        $search = $request->input('search');  
        $perPage = $request->input('per_page', 10);  
        $page = $request->input('page', 1);  
 
        $customerQuery = Client::query()
            ->when($search, function ($query, $search) {
                return $query->where('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('Ref', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('delivery_address', 'like', '%' . $search . '%');
            });
            
             $customerQuery->orderBy('created_at', 'desc');
 
 
        $customers = $customerQuery->paginate($perPage, ['*'], 'page', $page);

        if ($customers->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Customers found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Customers retrieved successfully',
            'data' => [
                'current_page' => $customers->currentPage(),
                'data' => $customers->items(),
                'first_page_url' => $customers->url(1),
                'from' => $customers->firstItem(),
                'last_page' => $customers->lastPage(),
                'last_page_url' => $customers->url($customers->lastPage()),
                'next_page_url' => $customers->nextPageUrl(),
                'path' => $customers->path(),
                'per_page' => $customers->perPage(),
                'prev_page_url' => $customers->previousPageUrl(),
                'to' => $customers->lastItem(),
                'total' => $customers->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}









    //-------------------------------------Show Customer By ID-------------------------------//
    public function showById($id)
    {
        try {
            $customer = Client::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Customer not found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Customer retrieved successfully',
                'data' => $customer,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }






    //-------------------------------------Delete Customer----------------------------------//
    public function delete($id)
    {
        try {
            $customer = Client::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Customer not found',
                ], 404);
            }

            $customer->delete();

            return response()->json([
                'status' => true,
                'message' => 'Customer deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
