<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserWarehouse;
use App\Models\Warehouse;
use DataTables;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use File;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('user_view')){

            $roles = Role::where('deleted_at', '=', null)->get(['id','name']);
            $users = User::where('deleted_at', '=', null)->with('RoleUser')->orderBy('id', 'desc')->get();
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

            return view('user.user_list', compact('users','roles','warehouses'));

        }
        return abort('403', __('You are not authorized'));
    
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user_auth = auth()->user();
		if ($user_auth->can('user_add')){

            $roles = Role::where('deleted_at', '=', null)
            ->orderBy('id', 'desc')
            ->get(['id','name']);

            return response()->json($roles);

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('user_add')){

            $request->validate([
                'username'  => 'required|string|max:255',
                'email'     => 'required|string|email|max:255|unique:users',
                'password'  => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required',
                'avatar'    => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
                'status'    => 'required',
                'role_users_id'    => 'required',
            ]);

            \DB::transaction(function () use ($request) {


                if ($request->hasFile('avatar')) {


                    $image = $request->file('avatar');
                    $filename = time().'.'.$image->extension();  
                    $image->move(public_path('/images/avatar'), $filename);

                } else {
                    $filename = 'no_avatar.png';
                }

                if($request['is_all_warehouses'] == '1' || $request['is_all_warehouses'] == 'true'){
                    $is_all_warehouses = 1;
                }else{
                    $is_all_warehouses = 0;
                }

                $user = User::create([
                    'username'  => $request['username'],
                    'email'     => $request['email'],
                    'avatar'    => $filename,
                    'password'  => Hash::make($request['password']),
                    'role_users_id'   => $request['role_users_id'],
                    'status'    => $request['status'],
                    'is_all_warehouses'    => $is_all_warehouses,
                ]);

                $user->assignRole($request['role_users_id']);

                if($is_all_warehouses !== 1){
                    $user->assignedWarehouses()->sync($request['assigned_to']);
                }

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('user_edit')){

            $assigned_warehouses = UserWarehouse::where('user_id', $id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $assigned_warehouses)->pluck('id')->toArray();

            $User = User::findOrFail($id);

            return response()->json([
                'assigned_warehouses' => $warehouses,
                'User' => $User,
            ]);

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('user_edit')){

            $this->validate($request, [
                'email' => 'required|string|email|max:255|unique:users',
                'email' => Rule::unique('users')->ignore($id),
                'username'  => 'required|string|max:255',
                'avatar'    => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
                'password'  =>  'sometimes|nullable|string|confirmed|min:6,'.$id,
                'status'    => 'required',
            ], [
                'email.unique' => 'This Email already taken.',
            ]);

            \DB::transaction(function () use ($request , $id) {

                $user = User::findOrFail($id);
                $current = $user->password;

                if ($request->password != null) {
                    if ($request->password != $current) {
                        $pass = Hash::make($request->password);
                    } else {
                        $pass = $user->password;
                    }

                } else {
                    $pass = $user->password;
                }

                $currentAvatar = $user->avatar;
                if ($request->avatar != null) {
                    if ($request->avatar != $currentAvatar) {

                        $image = $request->file('avatar');
                        $filename = time().'.'.$image->extension();  
                        $image->move(public_path('/images/avatar'), $filename);
                        $path = public_path() . '/images/avatar';
                        $userPhoto = $path . '/' . $currentAvatar;
                        if (file_exists($userPhoto)) {
                            if ($user->avatar != 'no_avatar.png') {
                                @unlink($userPhoto);
                            }
                        }
                    } else {
                        $filename = $currentAvatar;
                    }
                }else{
                    $filename = $currentAvatar;
                }

                if($request['is_all_warehouses'] == '1' || $request['is_all_warehouses'] == 'true'){
                    $is_all_warehouses = 1;
                }else{
                    $is_all_warehouses = 0;
                }


                $user = User::whereId($id)->update([
                    'username'  => $request['username'],
                    'email'     => $request['email'],
                    'avatar'    => $filename,
                    'password'  => $pass,
                    'status'    => $request['status'],
                    'is_all_warehouses' => $is_all_warehouses,
                ]);

                $user_saved = User::where('deleted_at', '=', null)->findOrFail($id);

                if($is_all_warehouses !== 1 && $request['assigned_to'] != ''){
                    $user_saved->assignedWarehouses()->sync($request['assigned_to']);
                }else{
                    $user_saved->assignedWarehouses()->detach();
                }
                
            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('user_delete')){

            \DB::transaction(function () use ($id) {
    
                $user = User::where('deleted_at', '=', null)->findOrFail($id);
                $user->status = 0;
                $user->deleted_at = Carbon::now();
                $user->save();

                $user->assignedWarehouses()->detach();
    
            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }


    public function assignRole(Request $request)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('group_permission')){
            
            //remove role
            $get_user = User::find($request->user_id);
            $get_user->removeRole($get_user->role_users_id);

            User::whereId($request->user_id)->update([
                'role_users_id' => $request->role_id,
            ]);

            $user_updated = User::find($request->user_id);
            $user_updated->assignRole($request->role_id);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

 
}
