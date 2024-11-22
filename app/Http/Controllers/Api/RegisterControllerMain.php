<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Helpers\SendCredentialsEmail; 
// use Auth;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;





class RegisterControllerMain extends Controller
{
    //------------------------------------------Create Users-----------------------------------//
//     public function register(Request $request)
// {
//     try {
//         $validator = Validator::make($request->all(), [
//             'full_name' => 'required|string|max:255',
//             'email' => 'required|string|email|max:255|unique:users,email',
//             'password' => 'required|string|min:8|confirmed',
//             'status' => 'required|in:active,inactive',
//             'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//             'role_id' => 'required|exists:roles,id',
//             'warehouse_id' => 'required|exists:warehouses,id',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $avatar = null;
//         if ($request->hasFile('image')) {
//             $avatarPath = $request->file('image')->store('avatars', 'public');
//             $avatar = asset('storage/' . $avatarPath);
//         }

//         $password = $request->password; 

//         $user = User::create([
//             'username' => $request->full_name,
//             'email' => $request->email,
//             'password' => Hash::make($password), 
//             'status' => $request->status === 'active' ? 1 : 0,
//             'avatar' => $avatar,
//             'role_users_id' => $request->role_id,
//             'is_all_warehouses' => $request->warehouse_id,
//         ]);

//         $user->assignRoleById($request->role_id);

//         UserWarehouse::create([
//             'user_id' => $user->id,
//             'warehouse_id' => $request->warehouse_id,
//         ]);

//         SendCredentialsEmail::sendCredentialsEmail($user, $password);

//         return response()->json([
//             'success' => true,
//             'message' => 'User registered successfully with role and warehouse access',
//             'user' => $user->load('assignedWarehouses', 'RoleUser'),
//         ], 201);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => $e->getMessage(),
//         ]);
//     }
// }


    public function register(Request $request)
{
    try {
      
        $baseValidationRules = [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'role_id' => 'nullable|exists:roles,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ];

       
        $extraValidationRules = [];
        if ($request->role_id == 48) { 
            $extraValidationRules = [
                'company_id' => 'required|string|max:20',
                'join_date' => 'nullable|date',
                'resign_date' => 'nullable|date',
                'nik_ktp' => 'nullable|digits_between:1,16|unique:users,nik_ktp',
                'dob' => 'nullable|date',
                'gender' => 'nullable|in:male,female',
                'address' => 'nullable|string|max:255',
                'locker_no' => 'nullable|string|max:50',
                'distribution_date' => 'nullable|date',
            ];
        }

        $validationRules = array_merge($baseValidationRules, $extraValidationRules);

        
        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

       
        $avatar = null;
        if ($request->hasFile('image')) {
    $destinationPath = public_path('storage/avatars'); 
    $image = $request->file('image');

    if (!file_exists($destinationPath)) {
        mkdir($destinationPath, 0755, true); 
    }

    $imageName = time() . '.' . $image->getClientOriginalExtension();

    $image->move($destinationPath, $imageName);

    
    $avatar = asset('storage/avatars/' . $imageName); 
}


       
        $password = $request->password;
        
         $join_date = $request->has('join_date') ? Carbon::parse($request->join_date)->format('Y-m-d') : null;
        $resign_date = $request->filled('resign_date') ? Carbon::parse($request->resign_date)->format('Y-m-d') : null;
        $dob = $request->has('dob') ? Carbon::parse($request->dob)->format('Y-m-d') : null;
        $distribution_date = $request->has('distribution_date') ? Carbon::parse($request->distribution_date)->format('Y-m-d') : null;

        
        $userData = [
            'username' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($password), 
            'status' => $request->status === 'active' ? 1 : 0,
            'avatar' => $avatar,
            'role_users_id' => $request->role_id,
            'is_all_warehouses' => $request->warehouse_id,
        ];

       
        $user = User::create($userData);

        
        $user->assignRoleById($request->role_id); 

        
        if ($request->role_id == 48) { 
            $employeeData = [
                'company_id' => $request->company_id,
                'join_date' => $join_date,
                'resign_date' => $resign_date,
                'nik_ktp' => $request->nik_ktp,
                'dob' => $dob,
                'gender' => $request->gender,
                'address' => $request->address,
                'locker_no' => $request->locker_no,
                'distribution_date' => $distribution_date,
            ];

            
            $user->update($employeeData);
        }

       
        UserWarehouse::create([
            'user_id' => $user->id,
            'warehouse_id' => $request->warehouse_id,
        ]);

        
         $data = [
            'full_name' => $user->username,
            'email' => $user->email,
            'password' => $password,
        ];

        Mail::send('emails.credentials', $data, function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Your Account Credentials');
        });
       
        return response()->json([
            'success' => true,
            'message' => 'User registered successfully with role and warehouse access',
            'user' => $user->load('assignedWarehouses', 'RoleUser'),
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}







//----------------------------------------Update User----------------------------------------//

 public function update(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);

        $baseValidationRules = [
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'nullable|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'role_id' => 'nullable|exists:roles,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ];

        $extraValidationRules = [];
        if ($request->role_id == 48) {
            $extraValidationRules = [
                'company_id' => 'nullable|string',
                'join_date' => 'nullable|date',
                'resign_date' => 'nullable|date',
                'nik_ktp' => 'nullable|digits_between:1,16',
                'dob' => 'nullable|date',
                'gender' => 'nullable|in:male,female',
                'address' => 'nullable|string|max:255',
                'locker_no' => 'nullable|string|max:50',
                'distribution_date' => 'nullable|date',
            ];
        }

        $validationRules = array_merge($baseValidationRules, $extraValidationRules);

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->hasFile('image')) {
            $destinationPath = public_path('storage/avatars'); 
            $image = $request->file('image');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true); 
            }

            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $user->avatar = asset('storage/avatars/' . $imageName); 
        }

        $user->username = $request->full_name;
        $user->email = $request->email;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->status = $request->status === 'active' ? 1 : 0;
        $user->role_users_id = $request->role_id;
        $user->is_all_warehouses = $request->warehouse_id;
        $user->save();

        $role = Role::find($request->role_id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => "There is no role with id `{$request->role_id}`.",
            ], 404);
        }

        // Fix: Update only the specific user's warehouse record
        $userWarehouse = UserWarehouse::where('user_id', $user->id)->first();

        if ($userWarehouse) {
            // Update the existing warehouse entry for the user
            $userWarehouse->update([
                'warehouse_id' => $request->warehouse_id,
            ]);
        } else {
            // Create a new warehouse entry for the user
            UserWarehouse::create([
                'user_id' => $user->id,
                'warehouse_id' => $request->warehouse_id,
            ]);
        }

        if ($request->role_id == 48) {
            $join_date = $request->has('join_date') ? Carbon::parse($request->join_date)->format('Y-m-d') : null;
            $resign_date = $request->filled('resign_date') ? Carbon::parse($request->resign_date)->format('Y-m-d') : null;
            $dob = $request->has('dob') ? Carbon::parse($request->dob)->format('Y-m-d') : null;
            $distribution_date = $request->has('distribution_date') ? Carbon::parse($request->distribution_date)->format('Y-m-d') : null;

            $employeeData = [
                'company_id' => $request->company_id,
                'join_date' => $join_date,
                'resign_date' => $resign_date,
                'nik_ktp' => $request->nik_ktp,
                'dob' => $dob,
                'gender' => $request->gender,
                'address' => $request->address,
                'locker_no' => $request->locker_no,
                'distribution_date' => $distribution_date,
            ];

            $user->update($employeeData);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'user' => $user->load('assignedWarehouses', 'RoleUser'),
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}












//----------------------------------------Get All Users---------------------------------------//
//     public function showAll(Request $request)
// {
//     try {

//         $user = Auth::guard('sanctum')->user();

//         if (!$user) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Unauthorized',
//             ], 401);
//         }
 
//         $perPage = $request->input('per_page', 10);
//         $page = $request->input('page', 1);
 
//         $userQuery = User::with(['assignedWarehouses', 'RoleUser']);
 
//         if ($request->has('username') && !empty($request->input('username'))) {
//             $username = $request->input('username');
//             $userQuery->where('username', 'like', '%' . $username . '%');
//         }
 
//         $users = $userQuery->paginate($perPage, ['*'], 'page', $page);
 
//         if ($users->isEmpty()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'No users found',
//                 'users' => [],
//             ], 404);
//         }
 
//         return response()->json([
//             'success' => true,
//             'message' => 'Users Retrieved Successfully',
//             'users' => [
//                 'current_page' => $users->currentPage(),
//                 'data' => $users->items(),
//                 'first_page_url' => $users->url(1),
//                 'from' => $users->firstItem(),
//                 'last_page' => $users->lastPage(),
//                 'last_page_url' => $users->url($users->lastPage()),
//                 'next_page_url' => $users->nextPageUrl(),
//                 'path' => $users->path(),
//                 'per_page' => $users->perPage(),
//                 'prev_page_url' => $users->previousPageUrl(),
//                 'to' => $users->lastItem(),
//                 'total' => $users->total(),
//             ],
//         ], 200);

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }






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

        $userQuery = User::with(['assignedWarehouses', 'RoleUser']);
 
        if ($request->has('search') && !empty($request->input('search'))) {
            $searchTerm = $request->input('search');
 
            $userQuery->where(function ($query) use ($searchTerm) {
                $query->where('username', 'like', '%' . $searchTerm . '%')
                      ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    //   ->orWhere('phone', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('RoleUser', function ($q) use ($searchTerm) {
                          $q->where('name', 'like', '%' . $searchTerm . '%');
                      });
            });
        }
         $userQuery->orderBy('created_at', 'desc');

        $users = $userQuery->paginate($perPage, ['*'], 'page', $page);

        if ($users->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No users found',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Users Retrieved Successfully',
            'data' => [
                'current_page' => $users->currentPage(),
                'data' => $users->items(),
                'first_page_url' => $users->url(1),
                'from' => $users->firstItem(),
                'last_page' => $users->lastPage(),
                'last_page_url' => $users->url($users->lastPage()),
                'next_page_url' => $users->nextPageUrl(),
                'path' => $users->path(),
                'per_page' => $users->perPage(),
                'prev_page_url' => $users->previousPageUrl(),
                'to' => $users->lastItem(),
                'total' => $users->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}








//------------------------------------------Get Users by Id------------------------------------//

//     public function showByToken(Request $request)
// {
//     try {
//         
//         $user = Auth::user();  

//         if (!$user) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'User not found',
//             ], 404);
//         }
 
//         $user->load('assignedWarehouses', 'RoleUser');

//         return response()->json([
//             'success' => true,
//             'message' => 'User Retrieved Successfully',
//             'user' => $user,
//         ], 200);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }


    public function getUserProfile(Request $request)
{
    try {
         
        $user = Auth::guard('sanctum')->user();
 
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
 
        $user->load(['assignedWarehouses', 'RoleUser']);

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'user' => $user,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}




public function showById($id)
    {
        try {
            $user = User::with(['assignedWarehouses', 'RoleUser'])->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User Retrieved Successfully',
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }





//---------------------------------------------Delete Users-----------------------------------//
public function delete($id)
{
    try {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // if ($user->assignedWarehouses()->count() > 0) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Cannot delete user with related data',
        //     ], 400);
        // }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}







//--------------------------------------------Login Users-----------------------------------//

    public function login(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('Personal Access Token')->plainTextToken;
        
        $user->update(['remember_token' => $token]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ], 200);
    }
    
    
//   public function login(Request $request)
// {
 
//     $validator = Validator::make($request->all(), [
//         'email' => 'required|email',
//         'password' => 'required|string', 
//     ]);

//     if ($validator->fails()) {
//         return response()->json([
//             'success' => false,
//             'errors' => $validator->errors(),
//         ], 422);
//     }
 
//     $user = User::where('email', $request->email)->first();

//     if (!$user) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Invalid credentials',
//         ], 401);
//     }

// // dd($request->password);
   
//     if ($request->password !== $user->password) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Invalid credentials',
//         ], 401);
//     }
  
 
//     $token = $user->createToken('Personal Access Token')->plainTextToken;
 
//     $user->update(['remember_token' => $token]);

//     return response()->json([
//         'success' => true,
//         'message' => 'Login successful',
//         'user' => $user,
//         'token' => $token,
//     ], 200);
// }









}
