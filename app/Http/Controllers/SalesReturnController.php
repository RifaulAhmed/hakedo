<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Mail\Sale_Return_Mail;
use App\Models\Product;
use App\Models\PaymentSaleReturns;
use App\Models\PaymentMethod;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use App\Models\Unit;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Client;
use App\Models\Setting;
use Carbon\Carbon;
use DataTables;
use Config;
use DB;
use PDF;
use ArPHP\I18N\Arabic;
use App\utils\helpers;

class SalesReturnController extends Controller
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
		if ($user_auth->can('sale_returns_view_all') || $user_auth->can('sale_returns_view_own')){


            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }
                        
            if(empty($request->warehouse_id)){
                $warehouse_id = 0;
            }else{
                $warehouse_id = $request->warehouse_id;
            }


            if ($request->ajax()) {
                $helpers = new helpers();
                // Filter fields With Params to retrieve
                $param = array(
                    0 => 'like',
                    1 => '=',
                    2 => 'like',
                    3 => '=',
                    4 => '=',
                );
                $columns = array(
                    0 => 'Ref',
                    1 => 'client_id',
                    2 => 'payment_statut',
                    3 => 'warehouse_id',
                    4 => 'sale_id',
                );

                $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
                $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
                $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
                $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

                $data = SaleReturn::where('deleted_at', '=', null)
                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })

                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where(function ($query) use ($user_auth) {
                        if (!$user_auth->can('sale_returns_view_all')) {
                            return $query->where('user_id', '=', $user_auth->id);
                        }
                    })
                    ->with('sale','facture', 'client', 'warehouse')
                    ->orderBy('id', 'desc');
                    
                //Multiple Filter
                $return_Filtred = $helpers->filter($data, $columns, $param, $request)->get();

                return Datatables::of($return_Filtred)
                ->setRowId(function($return_Filtred)
                {
                    return $return_Filtred->id;
                })

                ->addColumn('date', function($row){
                    return Carbon::parse($row->date)->format('d-m-Y H:i');
                })

                ->addColumn('sale_id', function($row){
                    return $row->sale->id;
                })

                ->addColumn('sale_ref', function($row){
                    return $row->sale->Ref;
                })
                
                ->addColumn('Ref', function($row){
                    return $row->Ref;
                })

                ->addColumn('warehouse_name', function($row){
                    return $row->warehouse->name;
                })
                ->addColumn('client_name', function($row){
                    return $row->client->username;
                })
               
                ->addColumn('GrandTotal', function($row){
                    return $this->render_price_with_symbol_placement(number_format($row->GrandTotal, 2, '.', ','));
                })
                ->addColumn('paid_amount', function($row){
                    return $this->render_price_with_symbol_placement(number_format($row->paid_amount, 2, '.', ','));
                })
                ->addColumn('due', function($row){
                    return $this->render_price_with_symbol_placement(number_format($row->GrandTotal - $row->paid_amount, 2, '.', ','));
                })

                ->addColumn('payment_status', function($row){
                    if($row->payment_statut == 'paid'){
                        $span = '<span class="badge badge-success">'.trans('translate.Paid').'</span>';
                    }else if($row->payment_statut == 'partial'){
                        $span = '<span class="badge badge-info">'.trans('translate.Partial').'</span>';
                    }else{
                        $span = '<span class="badge badge-warning">'.trans('translate.Unpaid').'</span>';
                    }
                    return $span;
                })
            
                ->addColumn('action', function($row) use ($user_auth) {

                        $btn =  '<div class="dropdown">
                                <button class="btn btn-outline-info btn-rounded dropdown-toggle" id="dropdownMenuButton" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                                .trans('translate.Action').
                                '</button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 34px, 0px);">
                                    <a class="dropdown-item" href="/sales-return/returns_sale/' .$row->id.'"> <i class="nav-icon i-Eye font-weight-bold mr-2"></i> '.trans('translate.Details_Return').'</a>';
                                
                                    //check if user has permission "sale_returns_edit"
                                    if ($user_auth->can('sale_returns_edit')){
                                        $btn .=  '<a class="dropdown-item" href="/sales-return/edit_returns_sale/' .$row->id. '/'.$row->sale_id.'" ><i class="nav-icon i-Edit font-weight-bold mr-2"></i> '.trans('translate.Edit_Return').'</a>';
                                    }

                                    //check if user has permission "payment_sell_returns_view"
                                    if ($user_auth->can('payment_sell_returns_view')){
                                        $btn .= '<a class="dropdown-item Show_Payments cursor-pointer"  id="' .$row->id. '" > <i class="nav-icon i-Money-Bag font-weight-bold mr-2"></i> ' .trans('translate.ShowPayment').'</a>';
                                    }

                                    //check if user has permission "payment_sell_returns_add"
                                    if ($user_auth->can('payment_sell_returns_add')){
                                        $btn .= '<a class="dropdown-item New_Payment cursor-pointer" payment_status="' .$row->payment_statut. '"  id="' .$row->id. '" > <i class="nav-icon i-Add font-weight-bold mr-2"></i> ' .trans('translate.AddPayment').'</a>';
                                    }

                             
                                $btn .=    '<a class="dropdown-item download_pdf cursor-pointer" Ref="' .$row->Ref. '" id="' .$row->id. '" ><i class="nav-icon i-File-TXT font-weight-bold mr-2"></i> ' .trans('translate.DownloadPdf').'</a>';

                                //check if user has permission "sale_returns_delete"
                                if ($user_auth->can('sale_returns_delete')){
                                    $btn .=    '<a class="dropdown-item delete cursor-pointer" id="' .$row->id. '" > <i class="nav-icon i-Close-Window font-weight-bold mr-2"></i> ' .trans('translate.Delete_Return').'</a>';
                                }
                                    $btn .='</div>
                            </div>';


                        return $btn;
                    })
                    ->rawColumns(['action','payment_status'])
                    ->make(true);
            }

            $clients = client::where('deleted_at', '=', null)->get(['id', 'username']);
            $sales = Sale::where('deleted_at', '=', null)->get(['id', 'Ref']);

            return view('sales_return.list_sale_return',compact('clients','sales','warehouses'));

        }
        return abort('403', __('You are not authorized'));
    }


    //------------------- create_sell_return -----------------\\

    public function create_sell_return(Request $request , $id)
    {

        $user_auth = auth()->user();
        if ($user_auth->can('sale_returns_add')){

             //get warehouses 
             if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();

            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            $SaleReturn = Sale::with('details.product.unitSale')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($array_warehouses_id) {
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
                })

                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('sales_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                })->findOrFail($id);

            $details = array();

            $Return_detail['client_id']    = $SaleReturn->client_id;
            $Return_detail['warehouse_id'] = $SaleReturn->warehouse_id;
            $Return_detail['sale_id']      = $SaleReturn->id;
            $Return_detail['sale_ref']     = $SaleReturn->Ref;
            $Return_detail['date']         = Carbon::now()->format('Y-m-d H:i');

            $Return_detail['tax_rate']               = $SaleReturn->tax_rate;
            $Return_detail['TaxNet']                 = $SaleReturn->TaxNet;
            $Return_detail['discount']               = $SaleReturn->discount;
            $Return_detail['discount_type']          = $SaleReturn->discount_type;
            $Return_detail['discount_percent_total'] = $SaleReturn->discount_percent_total;
            $Return_detail['shipping']               = $SaleReturn->shipping;

            $Return_detail['statut'] = "received";
            $Return_detail['notes'] = "";

            $detail_id = 0;
            foreach ($SaleReturn['details'] as $detail) {

                $unit = Unit::where('id', $detail->sale_unit_id)->first();

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('deleted_at', '=', null)
                        ->where('warehouse_id', $SaleReturn->warehouse_id)
                        ->first();

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->code;
                    $data['name'] = '['.$productsVariants->name . '] ' . $detail['product']['name'];

                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $SaleReturn->warehouse_id)
                        ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                        ->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];

                }

                $data['id']            = $detail->id;
                $data['detail_id']     = $detail_id += 1;
                $data['quantity']      = 0;
                $data['sale_quantity'] = $detail->quantity;
                $data['product_id']    = $detail->product_id;
                $data['unitSale']      = $unit?$unit->ShortName:'';
                $data['sale_unit_id']  = $unit?$unit->id:'';
                $data['is_imei']       = $detail['product']['is_imei'];
                $data['imei_number']   = $detail->imei_number;

                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->price * $detail->discount / 100;
                }

                $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
                $data['Unit_price'] = $detail->price;
                $data['tax_percent'] = $detail->TaxNet;
                $data['tax_method'] = $detail->tax_method;
                $data['discount'] = $detail->discount;
                $data['discount_Method'] = $detail->discount_method;

                if ($detail->tax_method == '1') {

                    $data['Net_price'] = $detail->price - $data['DiscountNet'];
                    $data['taxe'] = $tax_price;
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                } else {
                    $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                }

                $details[] = $data;
            }


            return view('sales_return.create_sale_return',
                [
                    'details' => $details,
                    'sale_return' => $Return_detail,
                ]
            );

        }
        return abort('403', __('You are not authorized'));

    }

 
    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('sale_returns_add')){

            \DB::transaction(function () use ($request) {
                $order = new SaleReturn;

                $order->date = $request->date;
                $order->Ref = $this->getNumberOrder();
                $order->client_id = $request->client_id;
                $order->sale_id = $request->sale_id;
                $order->warehouse_id = $request->warehouse_id;
                $order->tax_rate = $request->tax_rate;
                $order->TaxNet = $request->TaxNet;

                $order->discount = $request->discount;
                $order->discount_type = $request->discount_type;
                $order->discount_percent_total = $request->discount_percent_total;

                $order->shipping = $request->shipping;
                $order->GrandTotal = $request->GrandTotal;
                $order->statut = 'received';
                $order->payment_statut = 'unpaid';
                $order->notes = $request->notes;
                $order->user_id = Auth::user()->id;

                $order->save();

                $data = $request['details'];
                foreach ($data as $key => $value) {
                    $unit = Unit::where('id', $value['sale_unit_id'])->first();

                    $orderDetails[] = [
                        'sale_return_id' => $order->id,
                        'quantity' => $value['quantity'],
                        'price' => $value['Unit_price'],
                        'sale_unit_id' =>  $value['sale_unit_id']?$value['sale_unit_id']:NULL,
                        'TaxNet' => $value['tax_percent'],
                        'tax_method' => $value['tax_method'],
                        'discount' => $value['discount'],
                        'discount_method' => $value['discount_Method'],
                        'product_id' => $value['product_id'],
                        'product_variant_id' => $value['product_variant_id']?$value['product_variant_id']:NULL,
                        'total' => $value['subtotal'],
                        'imei_number' => $value['imei_number'],
                    ];

                    if ($value['product_variant_id']) {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $order->warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $product_warehouse) {
                            if ($unit->operator == '/') {
                                $product_warehouse->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte += $value['quantity'] * $unit->operator_value;
                            }

                            $product_warehouse->save();
                        }

                    } else {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $order->warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->first();

                        if ($unit && $product_warehouse) {
                            if ($unit->operator == '/') {
                                $product_warehouse->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte += $value['quantity'] * $unit->operator_value;
                            }

                            $product_warehouse->save();
                        }
                    }

                }
                SaleReturnDetails::insert($orderDetails);
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

    //---------------- Get Details Sale Return  -----------------\\

    public function show(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('sale_returns_view_all') || $user_auth->can('sale_returns_view_own')){

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }


            $Sale_Return = SaleReturn::with('sale','details.product.unitSale')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($array_warehouses_id) {
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
                })

                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('sale_returns_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                })
                ->findOrFail($id);

            $details = array();

            $return_details['id']             = $Sale_Return->id;
            $return_details['Ref']            = $Sale_Return->Ref;
            $return_details['sale_ref']       = $Sale_Return['sale']->Ref;
            $return_details['sale_id']        = $Sale_Return['sale']->id;
            $return_details['date']           = $Sale_Return->date;
            $return_details['note']           = $Sale_Return->notes;
            $return_details['statut']         = $Sale_Return->statut;

            if($Sale_Return->discount_type == 'fixed'){
                $return_details['discount']           = $this->render_price_with_symbol_placement(number_format($Sale_Return->discount, 2, '.', ','));
            }else{
                $return_details['discount']           = $this->render_price_with_symbol_placement(number_format($Sale_Return->discount_percent_total, 2, '.', ',')) .' '.'('.$Sale_Return->discount .' '.'%)';
            }

            $return_details['shipping']       = $this->render_price_with_symbol_placement(number_format($Sale_Return->shipping, 2, '.', ','));
            $return_details['tax_rate']       = $Sale_Return->tax_rate;
            $return_details['TaxNet']         = $this->render_price_with_symbol_placement(number_format($Sale_Return->TaxNet, 2, '.', ','));
            $return_details['client_name']    = $Sale_Return['client']->username;
            $return_details['client_phone']   = $Sale_Return['client']->phone;
            $return_details['client_adr']     = $Sale_Return['client']->address;
            $return_details['client_email']   = $Sale_Return['client']->email;
            $return_details['warehouse']      = $Sale_Return['warehouse']->name;
            $return_details['GrandTotal']     = $this->render_price_with_symbol_placement(number_format($Sale_Return->GrandTotal, 2, '.', ','));
            $return_details['paid_amount']    = $this->render_price_with_symbol_placement(number_format($Sale_Return->paid_amount, 2, '.', ','));
            $return_details['due']            = $this->render_price_with_symbol_placement(number_format($Sale_Return->GrandTotal - $Sale_Return->paid_amount, 2, '.', ','));
            $return_details['payment_status'] = $Sale_Return->payment_statut;

            foreach ($Sale_Return['details'] as $detail) {

                //check if detail has sale_unit_id Or Null
                if($detail->sale_unit_id){
                    $unit = Unit::where('id', $detail->sale_unit_id)->first();
                }else{
                    $product_unit_sale_id = Product::with('unitSale')
                    ->where('id', $detail->product_id)
                    ->first();
                    $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                }
                
                if ($detail->product_variant_id) {

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();

                    $data['code'] = $productsVariants->code;
                    $data['name'] = '['.$productsVariants->name . '] ' . $detail['product']['name'];
                
                } else {
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];
                }

                $data['quantity'] = $detail->quantity;
                $data['total'] = $detail->total;
                $data['price'] = $detail->price;
                $data['unit_sale'] = $unit->ShortName;

                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->price * $detail->discount / 100;
                }

                $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
                $data['Unit_price'] = $detail->price;
                $data['discount'] = $detail->discount;

                if ($detail->tax_method == '1') {
                    $data['Net_price'] = $detail->price - $data['DiscountNet'];
                    $data['taxe'] = $tax_price;
                } else {
                    $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                }

                $data['is_imei'] = $detail['product']['is_imei'];
                $data['imei_number'] = $detail->imei_number;

                if($detail->quantity > 0){
                    $details[] = $data;
                }

            }

            $company = Setting::where('deleted_at', '=', null)->first();

            return view('sales_return.details_sale_return',
            [
                'sale_Return' => $return_details,
                'details' => $details,
                'company' => $company,
            ]);

        }
        return abort('403', __('You are not authorized'));

    }



    //------------- edit_sell_return-----------\\

    public function edit_sell_return(Request $request, $id, $sale_id)
    {
    
        $user_auth = auth()->user();
        if ($user_auth->can('sale_returns_edit')){

             //get warehouses 
             if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();

            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            $SaleReturn = SaleReturn::with('sale','details.product.unitSale')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($array_warehouses_id) {
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
                })
                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('sale_returns_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                })->findOrFail($id);
                
            $details = array();

            $Return_detail['id'] = $SaleReturn->id;
            $Return_detail['client_id'] = $SaleReturn->client_id;
            $Return_detail['warehouse_id'] = $SaleReturn->warehouse_id;
            $Return_detail['sale_id'] = $SaleReturn->sale_id?$SaleReturn['sale']->id:NULL;
            $Return_detail['sale_ref'] = $SaleReturn['sale']?$SaleReturn['sale']->Ref:'---';
            $Return_detail['date'] = $SaleReturn->date;
            $Return_detail['tax_rate'] = $SaleReturn->tax_rate;
            $Return_detail['TaxNet'] = $SaleReturn->TaxNet;
            $Return_detail['discount'] = $SaleReturn->discount;
            $Return_detail['discount_type'] = $SaleReturn->discount_type;
            $Return_detail['discount_percent_total'] = $SaleReturn->discount_percent_total;
            $Return_detail['shipping'] = $SaleReturn->shipping;
            $Return_detail['notes'] = $SaleReturn->notes;
            $Return_detail['statut'] = $SaleReturn->statut;
            $Return_detail['GrandTotal'] = $SaleReturn->GrandTotal;

            $detail_id = 0;
            foreach ($SaleReturn['details'] as $detail) {
    
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
                   
                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('deleted_at', '=', null)
                        ->where('warehouse_id', $SaleReturn->warehouse_id)
                        ->first();
    
                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();
    
                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->code;
                    $data['name'] = '['.$productsVariants->name . '] ' . $detail['product']['name'];
    
                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $SaleReturn->warehouse_id)
                        ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                        ->first();
    
                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];
                    $data['name']         = $detail['product']['name'];
    
                }
    
                $data['id'] = $detail->id;
                $data['detail_id'] = $detail_id += 1;
    
                $sell_detail = SaleDetail::where('sale_id', $sale_id)
                ->where('product_id', $detail->product_id)
                ->where('product_variant_id', $detail->product_variant_id)
                ->first();
    
                $data['sale_quantity'] = $sell_detail->quantity;
    
                $data['quantity']     = $detail->quantity;
                $data['product_id']   = $detail->product_id;
                $data['unitSale']     = $unit->ShortName;
                $data['sale_unit_id'] = $unit->id;
                $data['is_imei']      = $detail['product']['is_imei'];
                $data['imei_number']  = $detail->imei_number;
    
                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->price * $detail->discount / 100;
                }
    
                $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
                $data['Unit_price'] = $detail->price;
                $data['tax_percent'] = $detail->TaxNet;
                $data['tax_method'] = $detail->tax_method;
                $data['discount'] = $detail->discount;
                $data['discount_Method'] = $detail->discount_method;
    
                if ($detail->tax_method == '1') {
    
                    $data['Net_price'] = $detail->price - $data['DiscountNet'];
                    $data['taxe'] = $tax_price;
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                } else {
                    $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                }
    
                $details[] = $data;
            }
    

            return view('sales_return.edit_sale_return',
            [
                'details'     => $details,
                'sale_return' => $Return_detail,
            ]);
        
            
        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        //
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
		if ($user_auth->can('sale_returns_edit')){

            \DB::transaction(function () use ($request, $id) {
                $current_SaleReturn = SaleReturn::findOrFail($id);
                $old_return_details = SaleReturnDetails::where('sale_return_id', $id)->get();
                $new_return_details = $request['details'];
                $length = sizeof($new_return_details);

                // Get Ids details
                $new_products_id = [];
                foreach ($new_return_details as $new_detail) {
                    $new_products_id[] = $new_detail['id'];
                }

                // Init Data with old Parametre
                $old_products_id = [];
                foreach ($old_return_details as $key => $value) {
                    $old_products_id[] = $value->id;

                    //check if detail has sale_unit_id Or Null
                    if($value['sale_unit_id']){
                        $unit = Unit::where('id', $value['sale_unit_id'])->first();
                    }else{
                        $product_unit_sale_id = Product::with('unitSale')
                        ->where('id', $value['product_id'])
                        ->first();
                        $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                    }

                    if($value['sale_unit_id']){
                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                                ->where('product_id', $value['product_id'])->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($unit && $product_warehouse) {
                                if ($unit->operator == '/') {
                                    $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                                } else {
                                    $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();

                            if ($unit && $product_warehouse) {
                                if ($unit->operator == '/') {
                                    $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                                } else {
                                    $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }

                        // Delete Detail
                        if (!in_array($old_products_id[$key], $new_products_id)) {
                            $SaleReturnDetails = SaleReturnDetails::findOrFail($value->id);
                            $SaleReturnDetails->delete();
                        }
                    }

                }

                // Update Data with New request
                foreach ($new_return_details as $key => $product_detail) {
                
                        $unit_prod = Unit::where('id', $product_detail['sale_unit_id'])->first();


                        if ($product_detail['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $product_detail['product_id'])
                                ->where('product_variant_id', $product_detail['product_variant_id'])
                                ->first();

                            if ($unit_prod && $product_warehouse) {
                                if ($unit_prod->operator == '/') {
                                    $product_warehouse->qte += $product_detail['quantity'] / $unit_prod->operator_value;
                                } else {
                                    $product_warehouse->qte += $product_detail['quantity'] * $unit_prod->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $product_detail['product_id'])
                                ->first();

                            if ($unit_prod && $product_warehouse) {
                                if ($unit_prod->operator == '/') {
                                    $product_warehouse->qte += $product_detail['quantity'] / $unit_prod->operator_value;
                                } else {
                                    $product_warehouse->qte += $product_detail['quantity'] * $unit_prod->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }

                        $orderDetails['sale_return_id'] = $id;
                        $orderDetails['sale_unit_id'] = $product_detail['sale_unit_id']?$product_detail['sale_unit_id']:NULL;
                        $orderDetails['quantity'] = $product_detail['quantity'];
                        $orderDetails['price'] = $product_detail['Unit_price'];
                        $orderDetails['TaxNet'] = $product_detail['tax_percent'];
                        $orderDetails['tax_method'] = $product_detail['tax_method'];
                        $orderDetails['discount'] = $product_detail['discount'];
                        $orderDetails['discount_method'] = $product_detail['discount_Method'];
                        $orderDetails['product_id'] = $product_detail['product_id'];
                        $orderDetails['product_variant_id'] = $product_detail['product_variant_id']?$product_detail['product_variant_id']:NULL;
                        $orderDetails['total'] = $product_detail['subtotal'];
                        $orderDetails['imei_number'] = $product_detail['imei_number'];

                        if (!in_array($product_detail['id'], $old_products_id)) {
                            SaleReturnDetails::Create($orderDetails);
                        } else {
                            SaleReturnDetails::where('id', $product_detail['id'])->update($orderDetails);
                        }

                }

                $due = $request['GrandTotal'] - $current_SaleReturn->paid_amount;
                if ($due === 0.0 || $due < 0.0) {
                    $payment_statut = 'paid';
                } else if ($due != $request['GrandTotal']) {
                    $payment_statut = 'partial';
                } else if ($due == $request['GrandTotal']) {
                    $payment_statut = 'unpaid';
                }

                $current_SaleReturn->update([
                    'date'                   => $request['date'],
                    'notes'                  => $request['notes'],
                    'statut'                 => $request['statut'],
                    'tax_rate'               => $request['tax_rate'],
                    'TaxNet'                 => $request['TaxNet'],
                    'discount'               => $request['discount'],
                    'discount_type'          => $request['discount_type'],
                    'discount_percent_total' => $request['discount_percent_total'],
                    'shipping'               => $request['shipping'],
                    'GrandTotal'             => $request['GrandTotal'],
                    'payment_statut'         => $payment_statut,
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
		if ($user_auth->can('sale_returns_delete')){

            \DB::transaction(function () use ($id) {
                $current_SaleReturn = SaleReturn::findOrFail($id);
                $old_return_details = SaleReturnDetails::where('sale_return_id', $id)->get();

                foreach ($old_return_details as $key => $value) {

                    $unit = Unit::where('id', $value['sale_unit_id'])->first();

                    if ($value['product_variant_id']) {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                            ->where('product_id', $value['product_id'])->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $product_warehouse) {
                            if ($unit->operator == '/') {
                                $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse->save();
                        }

                    } else {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->first();

                        if ($unit && $product_warehouse) {
                            if ($unit->operator == '/') {
                                $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse->save();
                        }
                    }

                }

                $current_SaleReturn->details()->delete();
                $current_SaleReturn->update([
                    'deleted_at' => Carbon::now(),
                ]);

                  // get all payments
                  $payments = PaymentSaleReturns::where('sale_return_id', $id)->get();

                  foreach ($payments as $payment) {
 
                      $account = Account::find($payment->account_id);
 
                      if ($account) {
                          $account->update([
                              'initial_balance' => $account->initial_balance + $payment->montant,
                          ]);
                      }
 
                  }

                  PaymentSaleReturns::where('sale_return_id', $id)->update([
                    'deleted_at' => Carbon::now(),
                ]);

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }


    //------------- GET Payments Sale Return-----------\\

    public function Payment_Returns(Request $request, $id)
    {

        $user_auth = auth()->user();
		if ($user_auth->can('payment_sell_returns_view')){

    
            $SaleReturn = SaleReturn::findOrFail($id);
        
            $payments = PaymentSaleReturns::with('SaleReturn')
                ->where('sale_return_id', $id)
                ->orderBy('id', 'DESC')->get();

            $due = $SaleReturn->GrandTotal - $SaleReturn->paid_amount;

            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);

            return response()->json([
                'payments' => $payments,
                 'due' => $due,
                 'payment_methods' => $payment_methods,
                 'accounts' => $accounts,
            ]);

        }
        return abort('403', __('You are not authorized'));
    }


    
    //------------- Sale Return PDF-----------\\

    public function Return_pdf(Request $request, $id)
    {

        $details = array();
        $user_auth = auth()->user();
        
        $Sale_Return = SaleReturn::with('sale','details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('sale_returns_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })
            ->findOrFail($id);

        $return_details['client_name']    = $Sale_Return['client']->username;
        $return_details['client_phone']   = $Sale_Return['client']->phone;
        $return_details['client_adr']     = $Sale_Return['client']->address;
        $return_details['client_email']   = $Sale_Return['client']->email;
        $return_details['TaxNet']         = $this->render_price_with_symbol_placement(number_format($Sale_Return->TaxNet, 2, '.', ','));
        $return_details['discount']       = $this->render_price_with_symbol_placement(number_format($Sale_Return->discount, 2, '.', ','));
        $return_details['shipping']       = $this->render_price_with_symbol_placement(number_format($Sale_Return->shipping, 2, '.', ','));
        $return_details['statut']         = $Sale_Return->statut;
        $return_details['sale_ref']       = $Sale_Return['sale']->Ref;
        $return_details['Ref']            = $Sale_Return->Ref;
        $return_details['date']           = Carbon::parse($Sale_Return->date)->format('d-m-Y H:i');
        $return_details['GrandTotal']     = $this->render_price_with_symbol_placement(number_format($Sale_Return->GrandTotal, 2, '.', ','));
        $return_details['paid_amount']    = $this->render_price_with_symbol_placement(number_format($Sale_Return->paid_amount, 2, '.', ','));
        $return_details['due']            = $this->render_price_with_symbol_placement(number_format($Sale_Return->GrandTotal - $Sale_Return->paid_amount, 2, '.', ','));
        $return_details['payment_status'] = $Sale_Return->payment_statut;

        $detail_id = 0;
        foreach ($Sale_Return['details'] as $detail) {

            $unit = Unit::where('id', $detail['sale_unit_id'])->first();

            if ($detail->product_variant_id) {
                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)
                    ->first();

                    $data['code'] = $productsVariants->code;
                    $data['name'] = '['.$productsVariants->name . '] ' . $detail['product']['name'];
            } else {
                $data['code'] = $detail['product']['code'];
                $data['name']      = $detail['product']['name'];
            }
                $data['detail_id'] = $detail_id += 1;
                $data['quantity']  = number_format($detail->quantity, 2, '.', '');
                $data['total']     = number_format($detail->total, 2, '.', ',');
                $data['unitSale']  = $unit?$unit->ShortName:'';
                $data['price']     = number_format($detail->price, 2, '.', ',');

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = number_format($detail->discount, 2, '.', '');
            } else {
                $data['DiscountNet'] = number_format($detail->price * $detail->discount / 100, 2, '.', '');
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = number_format($detail->price, 2, '.', '');
            $data['discount']   = $detail->discount;number_format($detail->discount, 2, '.', '');

            if ($detail->tax_method == '1') {
                $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['taxe']      = number_format($tax_price, 2, '.', '');
            } else {
                $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe']      = number_format($detail->price - $data['Net_price'] - $data['DiscountNet'], 2, '.', '');
            }

            $data['is_imei']     = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            if($detail->quantity > 0){
                $details[] = $data;
            }
        }

        $settings = Setting::where('deleted_at', '=', null)->first();

        $Html = view('pdf.Sales_Return_pdf', [
            'setting' => $settings,
            'return_sale' => $return_details,
            'details' => $details,
        ])->render();

        $arabic = new Arabic();
        $p = $arabic->arIdentify($Html);

        for ($i = count($p)-1; $i >= 0; $i-=2) {
            $utf8ar = $arabic->utf8Glyphs(substr($Html, $p[$i-1], $p[$i] - $p[$i-1]));
            $Html = substr_replace($Html, $utf8ar, $p[$i-1], $p[$i] - $p[$i-1]);
        }

        $pdf = PDF::loadHTML($Html);

        return $pdf->download('Sales_Return.pdf');
        //----------

    }

     //------------- Send sale on Email -----------\\

     public function SendEmail(Request $request)
     {
         $id = $request->id;
         $sale_return_data = SaleReturn::with('client')
         ->where('deleted_at', '=', null)
         ->findOrFail($id);
 
         $sale_return= [];
         $sale_return['id'] = $request->id;
         $sale_return['Ref'] = $sale_return_data->Ref;
         $sale_return['to'] = $sale_return_data['client']->email;
         $sale_return['client_name'] = $sale_return_data['client']->username;
 
         $pdf = $this->Return_pdf($request, $sale_return['id']);
         $this->Set_config_mail(); 
         $mail = Mail::to($sale_return['to'])->send(new Sale_Return_Mail($sale_return, $pdf));
         return $mail;
     }
 
       // Set config mail
       public function Set_config_mail()
       {
         $config = array(
             'driver' => env('MAIL_MAILER'),
             'host' => env('MAIL_HOST'),
             'port' => env('MAIL_PORT'),
             'from' => array('address' => env('MAIL_FROM_ADDRESS'), 'name' =>  env('MAIL_FROM_NAME')),
             'encryption' => env('MAIL_ENCRYPTION'),
             'username' => env('MAIL_USERNAME'),
             'password' => env('MAIL_PASSWORD'),
             'sendmail' => '/usr/sbin/sendmail -bs',
             'pretend' => false,
             'stream' => [
                 'ssl' => [
                     'allow_self_signed' => true,
                     'verify_peer' => false,
                     'verify_peer_name' => false,
                 ],
             ],
         );
         Config::set('mail', $config);
 
       }

    //------------ Reference Order Of Sale Return --------------\\

    public function getNumberOrder()
    {
        $last = DB::table('sale_returns')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'RT_1111';
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
