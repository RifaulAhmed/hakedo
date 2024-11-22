<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\AdjustmentDetail;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use DataTables;
use DB;
use App\utils\helpers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdjustmentsController extends Controller
{

    //-------------- Get All Adjustments ---------------\\

    public function index(Request $request)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('adjustment_view_all') || $user_auth->can('adjustment_view_own')) {


            if ($user_auth->is_all_warehouses) {
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            } else {
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }

            if (empty($request->warehouse_id)) {
                $warehouse_id = 0;
            } else {
                $warehouse_id = $request->warehouse_id;
            }

            if ($request->ajax()) {
                $helpers = new helpers();
                // Filter fields With Params to retrieve
                $columns = array(0 => 'Ref', 1 => 'warehouse_id');
                $param = array(0 => 'like', 1 => '=');

                $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
                $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
                $start_date = empty($request->start_date) ? $start_date_default : $request->start_date;
                $end_date = empty($request->end_date) ? $end_date_default : $request->end_date;

                $data = Adjustment::where('deleted_at', '=', null)
                    ->where(function ($query) use ($user_auth) {
                        if (!$user_auth->can('adjustment_view_all')) {
                            return $query->where('user_id', '=', $user_auth->id);
                        }
                    })
                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        } else {
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })

                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->with('warehouse')
                    ->orderBy('id', 'desc');

                //Multiple Filter
                $adjustment_Filtred = $helpers->filter($data, $columns, $param, $request)->get();

                return Datatables::of($adjustment_Filtred)
                    ->setRowId(function ($adjustment_Filtred) {
                        return $adjustment_Filtred->id;
                    })

                    ->addColumn('date', function ($row) {
                        return Carbon::parse($row->date)->format('d-m-Y H:i');
                    })

                    ->addColumn('Ref', function ($row) {
                        return $row->Ref;
                    })

                    ->addColumn('warehouse_name', function ($row) {
                        return $row->warehouse->name;
                    })

                    ->addColumn('items', function ($row) {
                        return $row->items;
                    })


                    ->addColumn('action', function ($row) use ($user_auth) {
                        $btn = '';
                        if ($user_auth->can('adjustment_edit')) {
                            $btn .= '<a href="/adjustments/' . $row->id . '/edit" id="' . $row->id . '"  class="edit cursor-pointer ul-link-action text-success"
                        data-toggle="tooltip" data-placement="top" title="Edit"><i class="i-Edit"></i></a>';
                            $btn .= '&nbsp;&nbsp;';
                        }
                        if ($user_auth->can('adjustment_delete')) {
                            $btn .= '<a id="' . $row->id . '" class="delete cursor-pointer ul-link-action text-danger mr-1"
                        data-toggle="tooltip" data-placement="top" title="Remove"><i class="i-Close-Window"></i></a>';
                            $btn .= '&nbsp;&nbsp;';
                        }
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return view('adjustments.adjustments', compact('warehouses'));

        }
        return abort('403', __('You are not authorized'));

    }

    //---------------- Show Form Create Adjustment ---------------\\

    public function create(Request $request)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('adjustment_add')) {

            //get warehouses assigned to user
            if ($user_auth->is_all_warehouses) {
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            } else {
                $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
            }

            return view('adjustments.create_adjustment', [
                'warehouses' => $warehouses
            ]);
        }
        return abort('403', __('You are not authorized'));
    }



    //-------------- Store New adjustment ---------------\\

    public function store(Request $request)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('adjustment_add')) {

            \DB::transaction(function () use ($request) {
                $order = new Adjustment;
                $order->date = $request->date;
                $order->Ref = $this->getNumberOrder();
                $order->warehouse_id = $request->warehouse_id;
                $order->notes = $request->notes;
                $order->items = sizeof($request['details']);
                $order->user_id = Auth::user()->id;
                $order->save();

                $data = $request['details'];
                $i = 0;
                foreach ($data as $key => $value) {
                    $orderDetails[] = [
                        'adjustment_id' => $order->id,
                        'quantity' => $value['quantity'],
                        'product_id' => $value['product_id'],
                        'product_variant_id' => $value['product_variant_id'] ? $value['product_variant_id'] : NULL,
                        'type' => $value['type'],
                    ];

                    if ($value['type'] == "add") {
                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $order->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte += $value['quantity'];
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $order->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte += $value['quantity'];
                                $product_warehouse->save();
                            }
                        }
                    } else {

                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $order->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte -= $value['quantity'];
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $order->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte -= $value['quantity'];
                                $product_warehouse->save();
                            }
                        }
                    }
                }
                AdjustmentDetail::insert($orderDetails);
            }, 10);

            return response()->json(['success' => true]);
        }
        return abort('403', __('You are not authorized'));
    }

    //------------ function show -----------\\

    public function show($id)
    {
        //

    }

    public function edit(Request $request, $id)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('adjustment_edit')) {

            //get warehouses 
            if ($user_auth->is_all_warehouses) {
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

            } else {
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }


            $details = array();

            $Adjustment_data = Adjustment::with('details.product')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($array_warehouses_id) {
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
                })

                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('adjustment_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                })->findOrFail($id);

            if ($Adjustment_data->warehouse_id) {
                if (
                    Warehouse::where('id', $Adjustment_data->warehouse_id)
                        ->where('deleted_at', '=', null)
                        ->first()
                ) {
                    $adjustment['warehouse_id'] = $Adjustment_data->warehouse_id;
                } else {
                    $adjustment['warehouse_id'] = '';
                }
            } else {
                $adjustment['warehouse_id'] = '';
            }

            $adjustment['notes'] = $Adjustment_data->notes;
            $adjustment['date'] = $Adjustment_data->date;
            $adjustment['id'] = $Adjustment_data->id;

            $detail_id = 0;
            foreach ($Adjustment_data['details'] as $detail) {

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('deleted_at', '=', null)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('warehouse_id', $Adjustment_data->warehouse_id)
                        ->first();

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();

                    $data['id'] = $detail->id;
                    $data['detail_id'] = $detail_id += 1;
                    $data['quantity'] = $detail->quantity;
                    $data['product_id'] = $detail->product_id;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];
                    $data['current'] = $item_product ? $item_product->qte : 0;
                    $data['type'] = $detail->type;
                    $data['unit'] = $detail['product']['unit']->ShortName;
                    $item_product ? $data['del'] = 0 : $data['del'] = 1;


                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('deleted_at', '=', null)
                        ->where('warehouse_id', $Adjustment_data->warehouse_id)
                        ->where('product_variant_id', '=', null)
                        ->first();

                    $data['id'] = $detail->id;
                    $data['detail_id'] = $detail_id += 1;
                    $data['quantity'] = $detail->quantity;
                    $data['product_id'] = $detail->product_id;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];
                    $data['current'] = $item_product ? $item_product->qte : 0;
                    $data['type'] = $detail->type;
                    $data['unit'] = $detail['product']['unit']->ShortName;
                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                }

                $details[] = $data;
            }

            $products_array = [];
            $get_product_warehouse_data = product_warehouse::with('warehouse', 'product', 'productVariant')
                ->where('warehouse_id', $Adjustment_data->warehouse_id)
                ->where('deleted_at', '=', null)
                ->get();

            foreach ($get_product_warehouse_data as $product_warehouse) {
                if ($product_warehouse->product_variant_id) {
                    $item['product_variant_id'] = $product_warehouse->product_variant_id;
                    $item['code'] = $product_warehouse['productVariant']->name . '-' . $product_warehouse['product']->code;
                    $item['Variant'] = $product_warehouse['productVariant']->name;
                } else {
                    $item['product_variant_id'] = null;
                    $item['Variant'] = null;
                    $item['code'] = $product_warehouse['product']->code;
                }

                $item['id'] = $product_warehouse->product_id;
                $item['name'] = $product_warehouse['product']->name;
                $item['barcode'] = $product_warehouse['product']->code;
                $item['Type_barcode'] = $product_warehouse['product']->Type_barcode;

                $item['qte'] = $product_warehouse->qte;

                $products_array[] = $item;
            }

            return view('adjustments.edit_adjustment', [
                'details' => $details,
                'adjustment' => $adjustment,
                'warehouses' => $warehouses,
                'products' => $products_array,
            ]);
        }

        return abort('403', __('You are not authorized'));
    }

    //-------------- Update Category ---------------\\

    public function update(Request $request, $id)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('adjustment_edit')) {
            \DB::transaction(function () use ($request, $id) {
                $current_adjustment = Adjustment::findOrFail($id);

                $old_adjustment_details = AdjustmentDetail::where('adjustment_id', $id)->get();
                $new_adjustment_details = $request['details'];
                $length = sizeof($new_adjustment_details);

                // Get Ids for new Details
                $new_products_id = [];
                foreach ($new_adjustment_details as $new_detail) {
                    $new_products_id[] = $new_detail['id'];
                }

                $old_products_id = [];
                // Init Data with old Parametre
                foreach ($old_adjustment_details as $key => $value) {
                    $old_products_id[] = $value->id;
                    if ($value['type'] == "add") {

                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_adjustment->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte -= $value['quantity'];
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_adjustment->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte -= $value['quantity'];
                                $product_warehouse->save();
                            }
                        }
                    } else {
                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_adjustment->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte += $value['quantity'];
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_adjustment->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte += $value['quantity'];
                                $product_warehouse->save();
                            }
                        }
                    }

                    // Delete Detail
                    if (!in_array($old_products_id[$key], $new_products_id)) {
                        $AdjustmentDetail = AdjustmentDetail::findOrFail($value->id);
                        $AdjustmentDetail->delete();
                    }

                }

                // Update Data with New request
                foreach ($new_adjustment_details as $key => $product_detail) {
                    if ($product_detail['type'] == "add") {

                        if ($product_detail['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $product_detail['product_id'])
                                ->where('product_variant_id', $product_detail['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte += $product_detail['quantity'];
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $product_detail['product_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte += $product_detail['quantity'];
                                $product_warehouse->save();
                            }
                        }
                    } else {
                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $product_detail['product_id'])
                                ->where('product_variant_id', $product_detail['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte -= $product_detail['quantity'];
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $product_detail['product_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte -= $product_detail['quantity'];
                                $product_warehouse->save();
                            }
                        }
                    }

                    $orderDetails['adjustment_id'] = $id;
                    $orderDetails['quantity'] = $product_detail['quantity'];
                    $orderDetails['product_id'] = $product_detail['product_id'];
                    $orderDetails['product_variant_id'] = $product_detail['product_variant_id'] ? $product_detail['product_variant_id'] : NULL;
                    $orderDetails['type'] = $product_detail['type'];

                    if (!in_array($product_detail['id'], $old_products_id)) {
                        AdjustmentDetail::Create($orderDetails);
                    } else {
                        AdjustmentDetail::where('id', $product_detail['id'])->update($orderDetails);
                    }

                }

                $current_adjustment->update([
                    'warehouse_id' => $request['warehouse_id'],
                    'notes' => $request['notes'],
                    'date' => $request['date'],
                    'items' => $length,
                ]);

            }, 10);

            return response()->json(['success' => true]);
        }

        return abort('403', __('You are not authorized'));

    }

    //-------------- Remove Category ---------------\\

    public function destroy(Request $request, $id)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('adjustment_delete')) {

            \DB::transaction(function () use ($id, $request) {
                $current_adjustment = Adjustment::findOrFail($id);
                $old_adjustment_details = AdjustmentDetail::where('adjustment_id', $id)->get();

                // Init Data with old Parametre
                foreach ($old_adjustment_details as $key => $value) {
                    if ($value['type'] == "add") {

                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_adjustment->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte -= $value['quantity'];
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_adjustment->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte -= $value['quantity'];
                                $product_warehouse->save();
                            }
                        }
                    } else {
                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_adjustment->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte += $value['quantity'];
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_adjustment->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();

                            if ($product_warehouse) {
                                $product_warehouse->qte += $value['quantity'];
                                $product_warehouse->save();
                            }
                        }
                    }

                }
                $current_adjustment->details()->delete();

                $current_adjustment->update([
                    'deleted_at' => Carbon::now(),
                ]);

            }, 10);

            return response()->json(['success' => true], 200);
        }
        return abort('403', __('You are not authorized'));
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('adjustment_delete')) {
            \DB::transaction(function () use ($request) {
                $selectedIds = $request->selectedIds;
                foreach ($selectedIds as $adjustment_id) {
                    // $Adjustment = Adjustment::findOrFail($adjustment_id);
                    $current_adjustment = Adjustment::findOrFail($adjustment_id);
                    $old_adjustment_details = AdjustmentDetail::where('adjustment_id', $adjustment_id)->get();

                    // Init Data with old Parametre
                    foreach ($old_adjustment_details as $key => $value) {
                        if ($value['type'] == "add") {

                            if ($value['product_variant_id']) {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_adjustment->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->where('product_variant_id', $value['product_variant_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    $product_warehouse->qte -= $value['quantity'];
                                    $product_warehouse->save();
                                }

                            } else {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_adjustment->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    $product_warehouse->qte -= $value['quantity'];
                                    $product_warehouse->save();
                                }
                            }
                        } else {
                            if ($value['product_variant_id']) {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_adjustment->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->where('product_variant_id', $value['product_variant_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    $product_warehouse->qte += $value['quantity'];
                                    $product_warehouse->save();
                                }

                            } else {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_adjustment->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    $product_warehouse->qte += $value['quantity'];
                                    $product_warehouse->save();
                                }
                            }
                        }

                    }
                    $current_adjustment->details()->delete();

                    $current_adjustment->update([
                        'deleted_at' => Carbon::now(),
                    ]);
                }
            }, 10);

            return response()->json(['success' => true], 200);
        }
        return abort('403', __('You are not authorized'));
    }

    //------------ Reference Number of Adjustement  -----------\\

    public function getNumberOrder()
    {

        $last = DB::table('adjustments')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'AD_1111';
        }
        return $code;

    }


    //---------------- Get Details Adjustment-----------------\\

    public function Adjustment_detail(Request $request, $id)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('adjustment_details')) {

            $details = array();

            $Adjustment_data = Adjustment::with('details.product.unit')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('adjustment_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                })->findOrFail($id);

            $Adjustment['Ref'] = $Adjustment_data->Ref;
            $Adjustment['date'] = $Adjustment_data->date;
            $Adjustment['note'] = $Adjustment_data->notes;
            $Adjustment['warehouse'] = $Adjustment_data['warehouse']->name;

            foreach ($Adjustment_data['details'] as $detail) {
                if ($detail->product_variant_id) {

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)
                        ->first();

                    $data['quantity'] = $detail->quantity;
                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];
                    $data['unit'] = $detail['product']['unit']->ShortName;
                    $data['type'] = $detail->type;

                } else {

                    $data['quantity'] = $detail->quantity;
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];
                    $data['type'] = $detail->type;
                    $data['unit'] = $detail['product']['unit']->ShortName;
                }

                $details[] = $data;
            }

            return response()->json([
                'details' => $details,
                'adjustment' => $Adjustment,
            ]);

        }
        return abort('403', __('You are not authorized'));
    }

}
