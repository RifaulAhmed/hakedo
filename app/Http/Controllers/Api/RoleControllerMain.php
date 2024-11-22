<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// use App\Models\Role;
use Spatie\Permission\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class RoleControllerMain extends Controller
{
    //------------------------------------------Create Role with Permissions-----------------------------------//
    public function store(Request $request)
    {
        try{
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the Role
        $role = Role::create($validator->validated());

        // Attach Permissions to the Role
        $role->permissions()->attach($request->permissions);

        return response()->json([
            'success' => true,
            'message' => 'Role Created Successfully with Permissions',
            'role' => $role->load('permissions'),
        ], 201);
    }catch(\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}

    //------------------------------------------Update Role with Permissions-----------------------------------//
    public function update(Request $request, $id)
{
    try {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        $isRoleDataSame = ($role->name === $request->name) &&
                          ($role->description === $request->description);

        $currentPermissions = $role->permissions()->pluck('id')->toArray();
        $newPermissions = $request->permissions;

        sort($currentPermissions);
        sort($newPermissions);

        $isPermissionsSame = $currentPermissions === $newPermissions;

        if ( $isPermissionsSame) {
            return response()->json([
                'success' => false,
                'message' => 'Role is already updated with these data.',
            ], 200);
        }

        $role->update($validatedData);

        $role->permissions()->sync($newPermissions);

        return response()->json([
            'success' => true,
            'message' => 'Role Updated Successfully with Permissions',
            'role' => $role->load('permissions'),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}


    //------------------------------------------Show All Roles-----------------------------------//
   public function showAll(Request $request)
{
   
    $user = Auth::guard('sanctum')->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    $roleQuery = Role::with('permissions');

    if ($request->has('name') && !empty($request->input('name'))) {
        $name = $request->input('name');
        $roleQuery->where('name', 'like', '%' . $name . '%');
    }
     $roleQuery->orderBy('created_at', 'desc');

    $roles = $roleQuery->paginate($request->input('per_page', 10));

    if ($roles->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No roles found',
            'roles' => [],
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Roles Retrieved Successfully',
        'roles' => $roles,
    ], 200);
}






    //------------------------------------------Show Role by ID-----------------------------------//
    public function showById($id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role Retrieved Successfully',
            'role' => $role,
        ], 200);
    }

    //------------------------------------------Delete Role-----------------------------------//
    public function delete($id)
    {
        try{
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role Deleted Successfully',
        ], 200);
    }catch(\Exception $e){
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
            ], 422);
    }
}
}


