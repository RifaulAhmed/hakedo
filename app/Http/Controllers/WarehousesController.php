<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WarehousesController extends Controller
{

    //-------------- Get All Warehouse ---------------\\

    public function index(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('warehouse')){

            if ($request->ajax()) {
                $data = Warehouse::where('deleted_at', '=', null)->orderBy('id', 'desc')->get();

                return Datatables::of($data)->addIndexColumn()

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

            return view('warehouses.warehouses');

        }
        return abort('403', __('You are not authorized'));
     
    }

    //-------------- Store New Warehouse ---------------\\

    public function store(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('warehouse')){

            request()->validate([
                'name' => 'required',
            ]);

            \DB::transaction(function () use ($request) {

                $Warehouse          = new Warehouse;
                $Warehouse->name    = $request['name'];
                $Warehouse->mobile  = $request['mobile'];
                $Warehouse->country = $request['country'];
                $Warehouse->city    = $request['city'];
                $Warehouse->zip     = $request['zip'];
                $Warehouse->email   = $request['email'];
                $Warehouse->save();
    
                $products = Product::where('deleted_at', '=', null)->get(['id','type']);
    
                if ($products) {
                    foreach ($products as $product) {
                        $product_warehouse = [];
                        $Product_Variants = ProductVariant::where('product_id', $product->id)
                            ->where('deleted_at', null)
                            ->get();
    
                        if ($Product_Variants->isNotEmpty()) {
                            foreach ($Product_Variants as $product_variant) {
    
                                $product_warehouse[] = [
                                    'product_id'         => $product->id,
                                    'warehouse_id'       => $Warehouse->id,
                                    'product_variant_id' => $product_variant->id,
                                    'manage_stock'       => $product->type == 'is_service'?0:1,
                                ];
                            }
                        } else {
                            $product_warehouse[] = [
                                'product_id'         => $product->id,
                                'warehouse_id'       => $Warehouse->id,
                                'product_variant_id' => null,
                                'manage_stock'       => $product->type == 'is_service'?0:1,
                            ];
                        }
    
                        product_warehouse::insert($product_warehouse);
                    }
                }
    
            }, 10);
            
            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

     //------------ function show -----------\\

    public function show($id){
        //
    
    }

    public function edit(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('warehouse')){

            $warehouse = Warehouse::where('deleted_at', '=', null)->findOrFail($id);
                
            return response()->json([
                'warehouse' => $warehouse,
            ]);

        }
        return abort('403', __('You are not authorized'));
    }

    //-------------- Update warehouse ---------------\\

    public function update(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('warehouse')){

            request()->validate([
                'name' => 'required',
            ]);

            Warehouse::whereId($id)->update([
                'name' => $request['name'],
                'mobile' => $request['mobile'],
                'country' => $request['country'],
                'city' => $request['city'],
                'zip' => $request['zip'],
                'email' => $request['email'],
            ]);
            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));

    }

    //-------------- Remove warehouse ---------------\\

    public function destroy(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('warehouse')){

            \DB::transaction(function () use ($id) {

                Warehouse::whereId($id)->update([
                    'deleted_at' => Carbon::now(),
                ]);

                product_warehouse::where('warehouse_id', $id)->update([
                    'deleted_at' => Carbon::now(),
                ]);

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('warehouse')){

            \DB::transaction(function () use ($request) {
                $selectedIds = $request->selectedIds;
                foreach ($selectedIds as $warehouse_id) {
                    Warehouse::whereId($warehouse_id)->update([
                        'deleted_at' => Carbon::now(),
                    ]);

                    product_warehouse::where('warehouse_id', $warehouse_id)->update([
                        'deleted_at' => Carbon::now(),
                    ]);
                }

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

}
