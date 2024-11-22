<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\Currency;
use App\Models\Unit;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use Carbon\Carbon;
use DataTables;
use Config;
use DB;
use PDF;
use App\utils\helpers;

class TransfersController extends Controller
{

    protected $currency;
    protected $symbol_placement;

    public function __construct()
    {
        $helpers = new helpers();
        $this->currency = $helpers->Get_Currency();
        $this->symbol_placement = $helpers->get_symbol_placement();

    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('transfer_view_all') || $user_auth->can('transfer_view_own')){

            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }
                        
            if(empty($request->from_warehouse_id)){
                $warehouse_id = 0;
            }else{
                $warehouse_id = $request->from_warehouse_id;
            }

            if ($request->ajax()) {
                $helpers = new helpers();
                // Filter fields With Params to retrieve
                $columns = array(0 => 'Ref', 1 => 'from_warehouse_id', 2 => 'to_warehouse_id');
                $param = array(0 => 'like', 1 => '=', 2 => '=');

                $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
                $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
                $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
                $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

                $data = Transfer::where('deleted_at', '=', null)

                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('from_warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('from_warehouse_id', $array_warehouses_id);
                        }
                    })

                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where(function ($query) use ($user_auth) {
                        if (!$user_auth->can('transfer_view_all')) {
                            return $query->where('user_id', '=', $user_auth->id);
                        }
                    })
                    ->with('from_warehouse', 'to_warehouse')
                    ->orderBy('id', 'desc');

                //Multiple Filter
                $transfer_Filtred = $helpers->filter($data, $columns, $param, $request)->get();

                return Datatables::of($transfer_Filtred)
                ->setRowId(function($transfer_Filtred)
                {
                    return $transfer_Filtred->id;
                })

                ->addColumn('date', function($row){
                    return Carbon::parse($row->date)->format('d-m-Y H:i');
                })

                ->addColumn('Ref', function($row){
                    return $row->Ref;
                })

                ->addColumn('from_warehouse', function($row){
                    return $row->from_warehouse->name;
                })

                ->addColumn('to_warehouse', function($row){
                    return $row->to_warehouse->name;
                })
                ->addColumn('GrandTotal', function($row){
                    return $this->render_price_with_symbol_placement(number_format($row->GrandTotal, 2, '.', ','));
                })
                ->addColumn('items', function($row){
                    return $row->items;
                })
               

                ->addColumn('action', function($row) use ($user_auth) {
                    $btn = '';
                    if ($user_auth->can('transfer_edit')){
                        $btn = '<a href="/transfer/transfers/' .$row->id. '/edit" id="' .$row->id. '"  class="edit cursor-pointer ul-link-action text-success"
                        data-toggle="tooltip" data-placement="top" title="Edit"><i class="i-Edit"></i></a>';
                        $btn .= '&nbsp;&nbsp;';
                    }
                    if ($user_auth->can('transfer_delete')){
                        $btn .= '<a id="' .$row->id. '" class="delete cursor-pointer ul-link-action text-danger mr-1"
                        data-toggle="tooltip" data-placement="top" title="Remove"><i class="i-Close-Window"></i></a>';
                        $btn .= '&nbsp;&nbsp;';
                    }
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
            }


            return view('transfers.list_transfers' , compact('warehouses'));

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $user_auth = auth()->user();
		if ($user_auth->can('transfer_add')){

            //get warehouses 
            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            }else{
                $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
            }

            return view('transfers.create_transfer',
                [
                    'warehouses' => $warehouses,
                ]
            );

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('transfer_add')){

            \DB::transaction(function () use ($request) {
                $order = new Transfer;

                $order->date = $request->transfer['date'];
                $order->Ref = $this->getNumberOrder();
                $order->from_warehouse_id = $request->transfer['from_warehouse'];
                $order->to_warehouse_id = $request->transfer['to_warehouse'];
                $order->items = sizeof($request['details']);
                $order->tax_rate = $request->transfer['tax_rate']?$request->transfer['tax_rate']:0;
                $order->TaxNet = $request->transfer['TaxNet']?$request->transfer['TaxNet']:0;

                $order->discount = $request->transfer['discount']?$request->transfer['discount']:0;
                $order->discount_type = $request->transfer['discount_type'];
                $order->discount_percent_total = $request->transfer['discount_percent_total'];

                $order->shipping = $request->transfer['shipping']?$request->transfer['shipping']:0;
                $order->statut = 'completed';
                $order->notes = $request->transfer['notes'];
                $order->GrandTotal = $request['GrandTotal'];
                $order->user_id = Auth::user()->id;
                $order->save();

                $data = $request['details'];

                foreach ($data as $key => $value) {
                
                    $unit = Unit::where('id', $value['purchase_unit_id'])->first();

                    if ($value['product_variant_id']) {

                        //--------- eliminate the quantity ''from_warehouse''--------------\\
                        $product_warehouse_from = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $request->transfer['from_warehouse'])
                            ->where('product_id', $value['product_id'])
                            ->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $product_warehouse_from) {
                            if ($unit->operator == '/') {
                                $product_warehouse_from->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse_from->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse_from->save();
                        }

                        //--------- ADD the quantity ''TO_warehouse''------------------\\
                        $product_warehouse_to = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $request->transfer['to_warehouse'])
                            ->where('product_id', $value['product_id'])
                            ->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $product_warehouse_to) {
                            if ($unit->operator == '/') {
                                $product_warehouse_to->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse_to->qte += $value['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse_to->save();
                        }

                    } else {

                        //--------- eliminate the quantity ''from_warehouse''--------------\\
                        $product_warehouse_from = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $request->transfer['from_warehouse'])
                            ->where('product_id', $value['product_id'])->first();

                        if ($unit && $product_warehouse_from) {
                            if ($unit->operator == '/') {
                                $product_warehouse_from->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse_from->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse_from->save();
                        }

                        //--------- ADD the quantity ''TO_warehouse''------------------\\
                        $product_warehouse_to = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $request->transfer['to_warehouse'])
                            ->where('product_id', $value['product_id'])->first();

                        if ($unit && $product_warehouse_to) {
                            if ($unit->operator == '/') {
                                $product_warehouse_to->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse_to->qte += $value['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse_to->save();
                        }
                    }

                    $orderDetails['transfer_id'] = $order->id;
                    $orderDetails['quantity'] = $value['quantity'];
                    $orderDetails['purchase_unit_id'] = $value['purchase_unit_id'];
                    $orderDetails['product_id'] = $value['product_id'];
                    $orderDetails['product_variant_id'] = $value['product_variant_id']?$value['product_variant_id']:NULL;
                    $orderDetails['cost'] = $value['Unit_cost'];
                    $orderDetails['TaxNet'] = $value['tax_percent'];
                    $orderDetails['tax_method'] = $value['tax_method'];
                    $orderDetails['discount'] = $value['discount'];
                    $orderDetails['discount_method'] = $value['discount_Method'];
                    $orderDetails['total'] = $value['subtotal'];

                    TransferDetail::insert($orderDetails);
                }

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('transfer_edit')){

            //get warehouses 
            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }


            $Transfer_data = Transfer::with('details.product.unit')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($array_warehouses_id) {
                return $query->whereIn('from_warehouse_id', $array_warehouses_id);
            })
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('transfer_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })
            ->findOrFail($id);

            $details = array();

            if ($Transfer_data->from_warehouse_id) {
                if (Warehouse::where('id', $Transfer_data->from_warehouse_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $transfer['from_warehouse'] = $Transfer_data->from_warehouse_id;
                } else {
                    $transfer['from_warehouse'] = '';
                }
            } else {
                $transfer['from_warehouse'] = '';
            }

            if ($Transfer_data->to_warehouse_id) {
                if (Warehouse::where('id', $Transfer_data->to_warehouse_id)->where('deleted_at', '=', null)->first()) {
                    $transfer['to_warehouse'] = $Transfer_data->to_warehouse_id;
                } else {
                    $transfer['to_warehouse'] = '';
                }
            } else {
                $transfer['to_warehouse'] = '';
            }

            $transfer['id'] = $Transfer_data->id;
            $transfer['statut'] = $Transfer_data->statut;
            $transfer['notes'] = $Transfer_data->notes;
            $transfer['date'] = $Transfer_data->date;
            $transfer['tax_rate'] = $Transfer_data->tax_rate;
            $transfer['TaxNet'] = $Transfer_data->TaxNet;
            $transfer['discount'] = $Transfer_data->discount;
            $transfer['discount_type'] = $Transfer_data->discount_type;
            $transfer['discount_percent_total'] = $Transfer_data->discount_percent_total;
            $transfer['shipping'] = $Transfer_data->shipping;
            $transfer['GrandTotal'] = $Transfer_data->GrandTotal;

            $detail_id = 0;
            foreach ($Transfer_data['details'] as $detail) {

                $unit = Unit::where('id', $detail->purchase_unit_id)->first();

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('deleted_at', '=', null)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('warehouse_id', $Transfer_data->from_warehouse_id)
                        ->first();

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                    $data['product_variant_id'] = $detail->product_variant_id;

                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }
                    $data['unitPurchase'] = $detail['product']['unitPurchase']->ShortName;

                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('deleted_at', '=', null)->where('warehouse_id', $Transfer_data->from_warehouse_id)
                        ->where('product_variant_id', '=', null)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];
                
                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }
                }


                $data['id'] = $detail->id;
                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = $detail->quantity;
                $data['product_id'] = $detail->product_id;
                $data['name'] = $detail['product']['name'];
                $data['etat'] = 'current';
                $data['qte_copy'] = $detail->quantity;
                $data['unitPurchase'] = $unit->ShortName;
                $data['purchase_unit_id'] = $unit->id;

                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->cost * $detail->discount / 100;
                }
                $tax_cost = $detail->TaxNet * (($detail->cost - $data['DiscountNet']) / 100);
                $data['Unit_cost'] = $detail->cost;
                $data['tax_percent'] = $detail->TaxNet;
                $data['tax_method'] = $detail->tax_method;
                $data['discount'] = $detail->discount;
                $data['discount_Method'] = $detail->discount_method;

                if ($detail->tax_method == '1') {
                    $data['Net_cost'] = $detail->cost - $data['DiscountNet'];
                    $data['taxe'] = $tax_cost;
                    $data['subtotal'] = ($data['Net_cost'] * $data['quantity']) + ($tax_cost * $data['quantity']);
                } else {
                    $data['Net_cost'] = ($detail->cost - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['taxe'] = $detail->cost - $data['Net_cost'] - $data['DiscountNet'];
                    $data['subtotal'] = ($data['Net_cost'] * $data['quantity']) + ($tax_cost * $data['quantity']);
                }
                $details[] = $data;
            }

            $products_array = [];
            $get_product_warehouse_data = product_warehouse::with('warehouse', 'product', 'productVariant')
                ->where('warehouse_id', $Transfer_data->from_warehouse_id)
                ->where('deleted_at', '=', null)
                ->where('qte', '>', 0)
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

                if ($product_warehouse['product']['unitPurchase']->operator == '/') {
                    $item['qte_purchase'] = round($product_warehouse->qte * $product_warehouse['product']['unitPurchase']->operator_value, 5);
                } else {
                    $item['qte_purchase'] = round($product_warehouse->qte / $product_warehouse['product']['unitPurchase']->operator_value, 5);
                }

                $item['qte'] = $product_warehouse->qte;
                $item['unitPurchase'] = $product_warehouse['product']['unitPurchase']->ShortName;

                $products_array[] = $item;
            }

         
            return view('transfers.edit_transfer',
                [
                    'details' => $details,
                    'transfer' => $transfer,
                    'warehouses' => $warehouses,
                    'products' => $products_array,
                ]
            );

        }
        return abort('403', __('You are not authorized'));

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('transfer_edit')){

            \DB::transaction(function () use ($request, $id) {
                $current_Transfer = Transfer::findOrFail($id);
                $Old_Details = TransferDetail::where('transfer_id', $id)->get();
                $data = $request['details'];
                $Trans = $request->transfer;
                $length = sizeof($data);

                // Get Ids details
                $new_products_id = [];
                foreach ($data as $new_detail) {
                    $new_products_id[] = $new_detail['id'];
                }

                // Init Data with old Parametre
                $old_products_id = [];
                foreach ($Old_Details as $key => $value) {

                    $unit = Unit::where('id', $value['purchase_unit_id'])->first();
                    $old_products_id[] = $value->id;


                    if ($value['product_variant_id']) {

                        $warehouse_from_variant = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Transfer->from_warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $warehouse_from_variant) {
                            if ($unit->operator == '/') {
                                $warehouse_from_variant->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $warehouse_from_variant->qte += $value['quantity'] * $unit->operator_value;
                            }
                            $warehouse_from_variant->save();
                        }

                        $warehouse_To_variant = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Transfer->to_warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $warehouse_To_variant) {
                            if ($unit->operator == '/') {
                                $warehouse_To_variant->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $warehouse_To_variant->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $warehouse_To_variant->save();
                        }

                    } else {
                        $warehouse_from = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Transfer->from_warehouse_id)
                            ->where('product_id', $value['product_id'])->first();

                        if ($unit && $warehouse_from) {
                            if ($unit->operator == '/') {
                                $warehouse_from->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $warehouse_from->qte += $value['quantity'] * $unit->operator_value;
                            }
                            $warehouse_from->save();
                        }

                        $warehouse_To = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Transfer->to_warehouse_id)
                            ->where('product_id', $value['product_id'])->first();

                        if ($unit && $warehouse_To) {
                            if ($unit->operator == '/') {
                                $warehouse_To->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $warehouse_To->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $warehouse_To->save();
                        }
                    }

                    // Delete Detail
                    if (!in_array($old_products_id[$key], $new_products_id)) {
                        $TransferDetail = TransferDetail::findOrFail($value->id);
                        $TransferDetail->delete();
                    }

                }

                // Update Data with New request
                foreach ($data as $key => $product_detail) {

                    $unit = Unit::where('id', $product_detail['purchase_unit_id'])->first();

                    if ($product_detail['product_variant_id']) {

                        //--------- eliminate the quantity ''from_warehouse''--------------\\
                        $product_warehouse_from = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $Trans['from_warehouse'])
                            ->where('product_id', $product_detail['product_id'])
                            ->where('product_variant_id', $product_detail['product_variant_id'])
                            ->first();

                        if ($unit && $product_warehouse_from) {
                            if ($unit->operator == '/') {
                                $product_warehouse_from->qte -= $product_detail['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse_from->qte -= $product_detail['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse_from->save();
                        }

                        //--------- ADD the quantity ''TO_warehouse''------------------\\
                        $product_warehouse_to = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $Trans['to_warehouse'])
                            ->where('product_id', $product_detail['product_id'])
                            ->where('product_variant_id', $product_detail['product_variant_id'])
                            ->first();

                        if ($unit && $product_warehouse_to) {
                            if ($unit->operator == '/') {
                                $product_warehouse_to->qte += $product_detail['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse_to->qte += $product_detail['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse_to->save();
                        }

                    } else {

                        //--------- eliminate the quantity ''from_warehouse''--------------\\
                        $product_warehouse_from = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $Trans['from_warehouse'])
                            ->where('product_id', $product_detail['product_id'])->first();

                        if ($unit && $product_warehouse_from) {
                            if ($unit->operator == '/') {
                                $product_warehouse_from->qte -= $product_detail['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse_from->qte -= $product_detail['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse_from->save();
                        }

                        //--------- ADD the quantity ''TO_warehouse''------------------\\
                        $product_warehouse_to = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $Trans['to_warehouse'])
                            ->where('product_id', $product_detail['product_id'])->first();

                        if ($unit && $product_warehouse_to) {
                            if ($unit->operator == '/') {
                                $product_warehouse_to->qte += $product_detail['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse_to->qte += $product_detail['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse_to->save();
                        }
                    }

                    $TransDetail['transfer_id'] = $id;
                    $TransDetail['quantity'] = $product_detail['quantity'];
                    $TransDetail['purchase_unit_id'] = $product_detail['purchase_unit_id'];
                    $TransDetail['product_id'] = $product_detail['product_id'];
                    $TransDetail['product_variant_id'] = $product_detail['product_variant_id']?$product_detail['product_variant_id']:NULL;
                    $TransDetail['cost'] = $product_detail['Unit_cost'];
                    $TransDetail['TaxNet'] = $product_detail['tax_percent'];
                    $TransDetail['tax_method'] = $product_detail['tax_method'];
                    $TransDetail['discount'] = $product_detail['discount'];
                    $TransDetail['discount_method'] = $product_detail['discount_Method'];
                    $TransDetail['total'] = $product_detail['subtotal'];

                    if (!in_array($product_detail['id'], $old_products_id)) {
                        TransferDetail::Create($TransDetail);
                    } else {
                        TransferDetail::where('id', $product_detail['id'])->update($TransDetail);
                    }
                }

                $current_Transfer->update([
                    'to_warehouse_id' => $Trans['to_warehouse'],
                    'from_warehouse_id' => $Trans['from_warehouse'],
                    'date' => $Trans['date'],
                    'notes' => $Trans['notes'],
                    'statut' => $Trans['statut'],
                    'items' => sizeof($request['details']),
                    'tax_rate' => $Trans['tax_rate']?$Trans['tax_rate']:0,
                    'TaxNet' => $Trans['TaxNet']?$Trans['TaxNet']:0,
                    'discount' => $Trans['discount']?$Trans['discount']:0,
                    'discount_type' => $Trans['discount_type'],
                    'discount_percent_total' => $Trans['discount_percent_total'],
                    'shipping' => $Trans['shipping']?$Trans['shipping']:0,
                    'GrandTotal' => $request['GrandTotal'],
                ]);

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('transfer_delete')){

            \DB::transaction(function () use ($id) {
                $current_Transfer = Transfer::findOrFail($id);
                $Old_Details = TransferDetail::where('transfer_id', $id)->get();

                // Init Data with old Parametre
                foreach ($Old_Details as $key => $value) {

                    $unit = Unit::where('id', $value['purchase_unit_id'])->first();
                    
                    if ($value['product_variant_id']) {

                        $warehouse_from_variant = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Transfer->from_warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $warehouse_from_variant) {
                            if ($unit->operator == '/') {
                                $warehouse_from_variant->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $warehouse_from_variant->qte += $value['quantity'] * $unit->operator_value;
                            }
                            $warehouse_from_variant->save();
                        }

                        $warehouse_To_variant = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Transfer->to_warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $warehouse_To_variant) {
                            if ($unit->operator == '/') {
                                $warehouse_To_variant->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $warehouse_To_variant->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $warehouse_To_variant->save();
                        }

                    } else {
                        $warehouse_from = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Transfer->from_warehouse_id)
                            ->where('product_id', $value['product_id'])->first();

                        if ($unit && $warehouse_from) {
                            if ($unit->operator == '/') {
                                $warehouse_from->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $warehouse_from->qte += $value['quantity'] * $unit->operator_value;
                            }
                            $warehouse_from->save();
                        }

                        $warehouse_To = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Transfer->to_warehouse_id)
                            ->where('product_id', $value['product_id'])->first();

                        if ($unit && $warehouse_To) {
                            if ($unit->operator == '/') {
                                $warehouse_To->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $warehouse_To->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $warehouse_To->save();
                        }
                    }
                    
                }

                $current_Transfer->details()->delete();
                $current_Transfer->update([
                    'deleted_at' => Carbon::now(),
                ]);

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

     //------------ Reference Number of transfers  -----------\\

     public function getNumberOrder()
     {
 
         $last = DB::table('transfers')->latest('id')->first();
 
         if ($last) {
             $item = $last->Ref;
             $nwMsg = explode("_", $item);
             $inMsg = $nwMsg[1] + 1;
             $code = $nwMsg[0] . '_' . $inMsg;
         } else {
             $code = 'TR_1111';
         }
         return $code;
 
     }


    // render_price_with_symbol_placement

    public function render_price_with_symbol_placement($amount) {

        if ($this->symbol_placement == 'before') {
            return $this->currency . ' ' . $amount;
        } else {
            return $amount . ' ' . $this->currency;
        }
    }

}
