<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Product;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class UnitsController extends Controller
{

    //-------------- show All Units -----------\\

    public function index(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('unit')){

            if ($request->ajax()) {
                $data = Unit::where('deleted_at', '=', null)->orderBy('id', 'desc')->get();

                return Datatables::of($data)->addIndexColumn()

                ->addColumn('base_unit_name', function($row){

                    if ($row->base_unit) {
                        $unit_base = Unit::where('id', $row->base_unit)->where('deleted_at', null)->first();
                        $base_unit_name = $unit_base['name'];
                    } else {
                        $base_unit_name = 'ND';
                    }
        
                    return $base_unit_name;
                })
            
                ->addColumn('action', function($row){

                        $btn = '<a id="' .$row->id. '"  class="edit cursor-pointer ul-link-action text-success"
                        data-toggle="tooltip" data-placement="top" title="Edit"><i class="i-Edit"></i></a>';
                        $btn .= '&nbsp;&nbsp;';

                        $btn .= '<a id="' .$row->id. '" class="delete cursor-pointer ul-link-action text-danger"
                        data-toggle="tooltip" data-placement="top" title="Remove"><i class="i-Close-Window"></i></a>';
                        $btn .= '&nbsp;&nbsp;';

                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return view('products.units');

        }
        return abort('403', __('You are not authorized'));
       
    }

    
    public function create(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('unit')){

            $units_base = Unit::where('base_unit', null)
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->get(['id', 'name']);
                
            return response()->json([
                'units_base' => $units_base,
            ]);

        }
        return abort('403', __('You are not authorized'));

    }

    //-------------- STORE NEW UNIT -----------\\

    public function store(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('unit')){

            request()->validate([
                'name' => 'required',
                'ShortName' => 'required',
            ]);

            if ($request->base_unit == '') {
                $operator = '*';
                $operator_value = 1;
                $base_unit = NULL;
            } else {
                $operator = $request->operator;
                $operator_value = $request->operator_value;
                $base_unit = $request['base_unit'];
            }

            Unit::create([
                'name' => $request['name'],
                'ShortName' => $request['ShortName'],
                'base_unit' => $base_unit,
                'operator' => $operator,
                'operator_value' => $operator_value,
            ]);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));

    }


    public function edit(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('unit')){

            $unit = Unit::where('deleted_at', '=', null)->findOrFail($id);

            $units_base = Unit::where('id' , '!=', $id)->where('base_unit', null)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get(['id', 'name']);
                
            return response()->json([
                'unit' => $unit,
                'units_base' => $units_base,
            ]);

        }
        return abort('403', __('You are not authorized'));
    }

    //-------------- UPDATE UNIT -----------\\

    public function update(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('unit')){

            request()->validate([
                'name' => 'required',
                'ShortName' => 'required',
            ]);

            if ($request->base_unit == '' || $request->base_unit == $id) {
                $operator = '*';
                $operator_value = 1;
                $base_unit = NULL;
            } else {
                $operator = $request->operator;
                $operator_value = $request->operator_value;
                $base_unit = $request['base_unit'];
            }

            Unit::whereId($id)->update([
                'name' => $request['name'],
                'ShortName' => $request['ShortName'],
                'base_unit' => $base_unit,
                'operator' => $operator,
                'operator_value' => $operator_value,
            ]);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));

    }

    //-------------- REMOVE UNIT -----------\\

    public function destroy(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('unit')){

            $Sub_Unit_exist = Unit::where('base_unit', $id)->where('deleted_at', null)->exists();
            if (!$Sub_Unit_exist) {
                Unit::whereId($id)->update([
                    'deleted_at' => Carbon::now(),
                ]);

                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false]);
            }
            
        }
        return abort('403', __('You are not authorized'));

    }

    //-------------- Get Units SubBase ------------------\\

    public function Get_Units_SubBase(request $request)
    {
        $units = Unit::where(function ($query) use ($request) {
            return $query->when($request->filled('id'), function ($query) use ($request) {
                return $query->where('id', $request->id)
                              ->orWhere('base_unit', $request->id);
            });
        })->get();

        return response()->json($units);
    }



    //-------------- Get Sales Units ------------------\\

    public function Get_sales_units(request $request)
    {

        $product_unit_id = Product::with('unit')->where(function ($query) use ($request) {
            return $query->when($request->filled('id'), function ($query) use ($request) {
                return $query->where('id', $request->id);
            });
        })->first();

        $units = Unit::where('base_unit', $product_unit_id->unit_id)
                        ->orWhere('id', $product_unit_id->unit_id)
                        ->get();
        
        return response()->json($units);
    }

}
