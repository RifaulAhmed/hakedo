<?php

namespace App\Http\Controllers;

use Twilio\Rest\Client as Client_Twilio;
use GuzzleHttp\Client as Client_guzzle;
use App\Models\SMSMessage;
use Infobip\Api\SendSmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;
use Illuminate\Support\Str;
use App\Models\EmailMessage;
use App\Mail\CustomEmail;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Client;
use App\Models\Setting;
use Config;
use App\Models\Currency;
use App\Mail\QuotationMail;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use DataTables;
use DB;
use PDF;
use ArPHP\I18N\Arabic;
use App\utils\helpers;

class QuotationsController extends Controller
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
		if ($user_auth->can('quotations_view_all') || $user_auth->can('quotations_view_own')){

            $clients = client::where('deleted_at', '=', null)->get(['id', 'username']);

            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }
            return view('quotations.list_quotations',compact('clients','warehouses'));

        }
        return abort('403', __('You are not authorized'));
    }



    public function get_quotations_datatable(Request $request)
    {
        $user_auth = auth()->user();
        if (!$user_auth->can('quotations_view_all') && !$user_auth->can('quotations_view_own')){
            return abort('403', __('You are not authorized'));
        }else{

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }
                        
            if(empty($request->warehouse_id)){
                $warehouse_id = 0;
            }else{
                $warehouse_id = $request->warehouse_id;
            }

            $helpers = new helpers();
            $param = array(
                0 => 'like',
                1 => 'like',
                2 => '=',
                3 => '=',
            );
            $columns = array(
                0 => 'Ref',
                1 => 'statut',
                2 => 'client_id',
                3 => 'warehouse_id',
            );

            $columns_order = array( 
                0 => 'id', 
                1 => 'date', 
                2 => 'Ref', 
            );

            $start = $request->input('start');
            $order = 'quotations.'.$columns_order[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;


            $quotations_data = Quotation::where('deleted_at', '=', null)

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
                if (!$user_auth->can('quotations_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            });

            //Multiple Filter
            $quotations_Filtred = $helpers->filter($quotations_data, $columns, $param, $request)

            // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->input('search.value')}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('username', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        });
                });
            });

            $totalRows = $quotations_Filtred->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $quotations = $quotations_Filtred
            ->with('client', 'warehouse')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $data = array();

            foreach ($quotations as $quotation) {

                $item['id']              = $quotation->id;
                $item['date']            = Carbon::parse($quotation->date)->format('d-m-Y H:i');
                $item['Ref']             = $quotation->Ref;
                $item['warehouse_name']  = $quotation->warehouse->name;
                $item['client_name']     = $quotation->client->username;
                $item['client_email']    = $quotation->client->email;
                $item['GrandTotal']      = $this->render_price_with_symbol_placement(number_format($quotation->GrandTotal, 2, '.', ','));


                $item['action']  =  '<div class="dropdown">
                    <button class="btn btn-outline-info btn-rounded dropdown-toggle" id="dropdownMenuButton" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                    .trans('translate.Action').

                    '</button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 34px, 0px);">';
                            
                        //check if user has permission "quotation_details"
                        if ($user_auth->can('quotation_details')){
                            $item['action']  .=  '<a class="dropdown-item" href="/quotation/quotations/' .$quotation->id.'"> <i class="nav-icon i-Eye font-weight-bold mr-2"></i> ' .trans('translate.DetailQuote').'</a>';
                        }

                        //check if user has permission "quotations_edit"
                        if ($user_auth->can('quotations_edit')){
                            $item['action']  .=  '<a class="dropdown-item" href="/quotation/quotations/' .$quotation->id. '/edit" ><i class="nav-icon i-Edit font-weight-bold mr-2"></i> ' .trans('translate.EditQuote').'</a>';
                        }
                       
                        $item['action']  .=  '<a class="dropdown-item download_pdf cursor-pointer" Ref="' .$quotation->Ref. '" id="' .$quotation->id. '" ><i class="nav-icon i-File-TXT font-weight-bold mr-2"></i> ' .trans('translate.DownloadPdf').'</a>
                                        <a class="dropdown-item  send_email cursor-pointer" id="' .$quotation->id. '" ><i class="nav-icon i-Envelope-2 font-weight-bold mr-2"></i> ' .trans('translate.QuoteEmail').'</a>
                                        <a class="dropdown-item  send_sms cursor-pointer" id="' .$quotation->id. '" ><i class="nav-icon i-Envelope-2 font-weight-bold mr-2"></i> ' .trans('translate.Send_sms').'</a>';
                            
                        //check if user has permission "quotations_delete"
                        if ($user_auth->can('quotations_delete')){
                            $item['action']  .=  '<a class="dropdown-item delete cursor-pointer" id="' .$quotation->id. '" > <i class="nav-icon i-Close-Window font-weight-bold mr-2"></i> ' .trans('translate.DeleteQuote').'</a>';
                        }
                        $item['action']  .=  '</div>
                </div>';

                $data[] = $item;

            }


            $json_data = array(
                "draw"            => intval($request->input('draw')),  
                "recordsTotal"    => intval($totalRows),  
                "recordsFiltered" => intval($totalFiltered), 
                "data"            => $data   
            );
                
            echo json_encode($json_data);
        }
    }
        

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $user_auth = auth()->user();
		if ($user_auth->can('quotations_add')){

            //get warehouses assigned to user
            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            }else{
                $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
            }

            $clients = client::where('deleted_at', '=', null)
            ->get(['id', 'username']);

            return view('quotations.create_quotation',
                [
                    'clients' => $clients,
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
		if ($user_auth->can('quotations_add')){

            \DB::transaction(function () use ($request) {

                $order = new Quotation;

                $order->date = $request->date;
                $order->Ref = $this->getNumberOrder();
                $order->statut = $request->statut;
                $order->client_id = $request->client_id;
                $order->GrandTotal = $request->GrandTotal;
                $order->warehouse_id = $request->warehouse_id;
                $order->tax_rate = $request->tax_rate;
                $order->TaxNet = $request->TaxNet;
               
                $order->discount = $request->discount;
                $order->discount_type = $request->discount_type;
                $order->discount_percent_total = $request->discount_percent_total;

                $order->shipping = $request->shipping;
                $order->notes = $request->notes;
                $order->user_id = Auth::user()->id;

                $order->save();

                $data = $request['details'];

                foreach ($data as $key => $value) {
                    $unit = Unit::where('id', $value['sale_unit_id'])->first();

                    $orderDetails[] = [
                        'quotation_id' => $order->id,
                        'quantity' => $value['quantity'],
                        'sale_unit_id' => $value['sale_unit_id']?$value['sale_unit_id']:NULL,
                        'price' => $value['Unit_price'],
                        'TaxNet' => $value['tax_percent'],
                        'tax_method' => $value['tax_method'],
                        'discount' => $value['discount'],
                        'discount_method' => $value['discount_Method'],
                        'product_id' => $value['product_id'],
                        'product_variant_id' => $value['product_variant_id']?$value['product_variant_id']:NULL,
                        'total' => $value['subtotal'],
                        'imei_number' => $value['imei_number'],
                    ];
                }
                QuotationDetail::insert($orderDetails);

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
        $user_auth = auth()->user();
		if ($user_auth->can('quotation_details')){

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            $quotation_data = Quotation::with('details.product.unitSale')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($array_warehouses_id) {
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
                })
                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('quotations_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                })->findOrFail($id);

                $details = array();

                $quote['id']     = $quotation_data->id;
                $quote['Ref']    = $quotation_data->Ref;
                $quote['date']   = $quotation_data->date;
                $quote['note']   = $quotation_data->notes;
                $quote['statut'] = $quotation_data->statut;

                if($quotation_data->discount_type == 'fixed'){
                    $quote['discount']  = $this->render_price_with_symbol_placement(number_format($quotation_data->discount, 2, '.', ','));
                }else{
                    $quote['discount']  = $this->render_price_with_symbol_placement(number_format($quotation_data->discount_percent_total, 2, '.', ',')) .' '.'('.$quotation_data->discount .' '.'%)';
                }

                $quote['shipping']     = $this->render_price_with_symbol_placement(number_format($quotation_data->shipping, 2, '.', ','));
                $quote['tax_rate']     = $quotation_data->tax_rate;
                $quote['TaxNet']       = $this->render_price_with_symbol_placement(number_format($quotation_data->TaxNet, 2, '.', ','));
                $quote['client_name']  = $quotation_data['client']->username;
                $quote['client_phone'] = $quotation_data['client']->phone;
                $quote['client_adr']   = $quotation_data['client']->address;
                $quote['client_email'] = $quotation_data['client']->email;
                $quote['warehouse']    = $quotation_data['warehouse']->name;
                $quote['GrandTotal']   = $this->render_price_with_symbol_placement(number_format($quotation_data->GrandTotal, 2, '.', ','));

                foreach ($quotation_data['details'] as $detail) {

                    $unit = Unit::where('id', $detail->sale_unit_id)->first();
                
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
                    $data['unit_sale'] = $unit?$unit->ShortName:'';

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

                    $details[] = $data;
                }

                $company = Setting::where('deleted_at', '=', null)->first();

                return view('quotations.details_quotation',
                [
                    'quote' => $quote,
                    'details' => $details,
                    'company' => $company,
                ]
            );

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
        $user_auth = auth()->user();
		if ($user_auth->can('quotations_edit')){

            //get warehouses 
            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }

            $Quotation = Quotation::with('details.product.unitSale')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($array_warehouses_id) {
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
                })
                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('quotations_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                })->findOrFail($id);

            $details = array();
        
            if ($Quotation->client_id) {
                if (Client::where('id', $Quotation->client_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $quote['client_id'] = $Quotation->client_id;
                } else {
                    $quote['client_id'] = '';
                }
            } else {
                $quote['client_id'] = '';
            }

            if ($Quotation->warehouse_id) {
                if (Warehouse::where('id', $Quotation->warehouse_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $quote['warehouse_id'] = $Quotation->warehouse_id;
                } else {
                    $quote['warehouse_id'] = '';
                }
            } else {
                $quote['warehouse_id'] = '';
            }

            $quote['id'] = $Quotation->id;
            $quote['date'] = $Quotation->date;
            $quote['TaxNet'] = $Quotation->TaxNet;
            $quote['tax_rate'] = $Quotation->tax_rate;

            $quote['discount'] = $Quotation->discount;
            $quote['discount_type'] = $Quotation->discount_type;
            $quote['discount_percent_total'] = $Quotation->discount_percent_total;

            $quote['shipping'] = $Quotation->shipping;
            $quote['statut'] = $Quotation->statut;
            $quote['notes'] = $Quotation->notes;
            $quote['GrandTotal'] = $Quotation->GrandTotal;

            $detail_id = 0;
            foreach ($Quotation['details'] as $detail) {

                $unit = Unit::where('id', $detail->sale_unit_id)->first();

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('warehouse_id', $Quotation->warehouse_id)
                        ->where('deleted_at', '=', null)
                        ->first();

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->code;
                    $data['name'] = '['.$productsVariants->name . '] ' . $detail['product']['name'];

                    if ($unit && $unit->operator == '/') {
                        $stock = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $stock = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else {
                        $stock = 0;
                    }

                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('deleted_at', '=', null)
                        ->where('warehouse_id', $Quotation->warehouse_id)
                        ->where('product_variant_id', '=', null)
                        ->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];

                    if ($unit && $unit->operator == '/') {
                        $stock = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $stock = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else {
                        $stock = 0;
                    }

                }

                $data['id'] = $detail->id;
                $data['stock'] = $detail['product']['type'] !='is_service'?$stock:'---';
                $data['product_type'] = $detail['product']['type'];
                $data['detail_id'] = $detail_id += 1;
                $data['product_id'] = $detail->product_id;
                $data['quantity'] = $detail->quantity;
                $data['etat'] = 'current';
                $data['qte_copy'] = $detail->quantity;
                $data['total'] = $detail->total;
                $data['unitSale'] = $unit?$unit->ShortName:'';
                $data['sale_unit_id'] = $unit?$unit->id:'';
                $data['is_imei'] = $detail['product']['is_imei'];
                $data['imei_number'] = $detail->imei_number;

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

            $products_array = [];
            $get_product_warehouse_data = product_warehouse::with('warehouse', 'product', 'productVariant')
                ->where('warehouse_id', $Quotation->warehouse_id)
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
                $firstimage = explode(',', $product_warehouse['product']->image);
                $item['image'] = $firstimage[0];

                if ($product_warehouse['product']['unitSale']->operator == '/') {
                    $item['qte_sale'] = $product_warehouse->qte * $product_warehouse['product']['unitSale']->operator_value;
                    $price = $product_warehouse['product']->price / $product_warehouse['product']['unitSale']->operator_value;
                } else {
                    $item['qte_sale'] = $product_warehouse->qte / $product_warehouse['product']['unitSale']->operator_value;
                    $price = $product_warehouse['product']->price * $product_warehouse['product']['unitSale']->operator_value;
                }

                if ($product_warehouse['product']['unitPurchase']->operator == '/') {
                    $item['qte_purchase'] = round($product_warehouse->qte * $product_warehouse['product']['unitPurchase']->operator_value, 5);
                } else {
                    $item['qte_purchase'] = round($product_warehouse->qte / $product_warehouse['product']['unitPurchase']->operator_value, 5);
                }

                $item['qte'] = $product_warehouse->qte;
                $item['unitSale'] = $product_warehouse['product']['unitSale']->ShortName;
                $item['unitPurchase'] = $product_warehouse['product']['unitPurchase']->ShortName;

                if ($product_warehouse['product']->TaxNet !== 0.0) {
                    //Exclusive
                    if ($product_warehouse['product']->tax_method == '1') {
                        $tax_price = $price * $product_warehouse['product']->TaxNet / 100;
                        $item['Net_price'] = $price + $tax_price;
                        // Inxclusive
                    } else {
                        $item['Net_price'] = $price;
                    }
                } else {
                    $item['Net_price'] = $price;
                }

                $products_array[] = $item;
            }
            
            $clients = client::where('deleted_at', '=', null)
            ->get(['id', 'username']);

            return view('quotations.edit_quotation',
                [
                    'details' => $details,
                    'quote' => $quote,
                    'clients' => $clients,
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
		if ($user_auth->can('quotations_edit')){
        
            \DB::transaction(function () use ($request, $id) {
                $role = Auth::user()->roles()->first();
                $current_Quotation = Quotation::findOrFail($id);

                $old_quotation_details = QuotationDetail::where('quotation_id', $id)->get();
                $new_quotation_details = $request['details'];
                $length = sizeof($new_quotation_details);

                // Get Ids details
                $new_details_id = [];
                foreach ($new_quotation_details as $new_detail) {
                    $new_details_id[] = $new_detail['id'];
                }

                // Init quotation with old Parametre
                $old_detail_id = [];
                foreach ($old_quotation_details as $key => $value) {
                    $old_detail_id[] = $value->id;

                    // Delete Detail
                    if (!in_array($old_detail_id[$key], $new_details_id)) {
                        $QuotationDetail = QuotationDetail::findOrFail($value->id);
                        $QuotationDetail->delete();
                    }

                }

                // Update quotation with New request
                foreach ($new_quotation_details as $key => $product_detail) {

                    $QuoteDetail['quotation_id'] = $id;
                    $QuoteDetail['quantity'] = $product_detail['quantity'];
                    $QuoteDetail['sale_unit_id'] = $product_detail['sale_unit_id']?$product_detail['sale_unit_id']:NULL;
                    $QuoteDetail['product_id'] = $product_detail['product_id'];
                    $QuoteDetail['product_variant_id'] = $product_detail['product_variant_id']?$product_detail['product_variant_id']:NULL;
                    $QuoteDetail['price'] = $product_detail['Unit_price'];
                    $QuoteDetail['TaxNet'] = $product_detail['tax_percent'];
                    $QuoteDetail['tax_method'] = $product_detail['tax_method'];
                    $QuoteDetail['discount'] = $product_detail['discount'];
                    $QuoteDetail['discount_method'] = $product_detail['discount_Method'];
                    $QuoteDetail['total'] = $product_detail['subtotal'];
                    $QuoteDetail['imei_number'] = $product_detail['imei_number'];

                    if (!in_array($product_detail['id'], $old_detail_id)) {
                        QuotationDetail::Create($QuoteDetail);
                    } else {
                        QuotationDetail::where('id', $product_detail['id'])->update($QuoteDetail);
                    }
                }

                $current_Quotation->update([
                    'client_id' => $request['client_id'],
                    'warehouse_id' => $request['warehouse_id'],
                    'statut' => $request['statut'],
                    'notes' => $request['notes'],
                    'tax_rate' => $request['tax_rate'],
                    'TaxNet' => $request['TaxNet'],
                    'date' => $request['date'],
                    'discount' => $request['discount'],
                    'discount_type' => $request['discount_type'],
                    'discount_percent_total' => $request['discount_percent_total'],
                    'shipping' => $request['shipping'],
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
		if ($user_auth->can('quotations_delete')){

            \DB::transaction(function () use ($id) {

                $Quotation = Quotation::findOrFail($id);

                $Quotation->details()->delete();
                $Quotation->update([
                    'deleted_at' => Carbon::now(),
                ]);

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }


    //---------------- Reference Number Of Quotation  ---------------\\

    public function getNumberOrder()
    {
        $last = DB::table('quotations')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'QT_1111';
        }
        return $code;

    }

     //---------------- Quotation PDF ---------------\\

     public function Quotation_pdf(Request $request, $id)
     {
         $user_auth = auth()->user();
         $details = array();

         $Quotation = Quotation::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('quotations_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })->findOrFail($id);
 
         $quote['client_name']  = $Quotation['client']->username;
         $quote['client_phone'] = $Quotation['client']->phone;
         $quote['client_adr']   = $Quotation['client']->address;
         $quote['client_email'] = $Quotation['client']->email;
         $quote['TaxNet']       = $this->render_price_with_symbol_placement(number_format($Quotation->TaxNet, 2, '.', ','));

        if($Quotation->discount_type == 'fixed'){
            $quote['discount']  = $this->render_price_with_symbol_placement(number_format($Quotation->discount, 2, '.', ','));
        }else{
            $quote['discount']  = $this->render_price_with_symbol_placement(number_format($Quotation->discount_percent_total, 2, '.', ',')) .' '.'('.$Quotation->discount .' '.'%)';
        }

         $quote['shipping']   = $this->render_price_with_symbol_placement(number_format($Quotation->shipping, 2, '.', ','));
         $quote['statut']     = $Quotation->statut;
         $quote['Ref']        = $Quotation->Ref;
         $quote['date']       = Carbon::parse($Quotation->date)->format('d-m-Y H:i');
         $quote['GrandTotal'] = $this->render_price_with_symbol_placement(number_format($Quotation->GrandTotal, 2, '.', ','));
 
         $detail_id = 0;
         foreach ($Quotation['details'] as $detail) {
 
            $unit = Unit::where('id', $detail->sale_unit_id)->first();
             if ($detail->product_variant_id) {
 
                 $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                     ->where('id', $detail->product_variant_id)->first();
 
                $data['code'] = $productsVariants->code;
                $data['name'] = '['.$productsVariants->name . '] ' . $detail['product']['name'];

             } else {
                 $data['code'] = $detail['product']['code'];
                 $data['name']        = $detail['product']['name'];
             }
 
                 $data['detail_id']   = $detail_id += 1;
                 $data['quantity']    = number_format($detail->quantity, 2, '.', '');
                 $data['total']       = number_format($detail->total, 2, '.', ',');
                 $data['unitSale']    = $unit?$unit->ShortName:'';
                 $data['price']       = number_format($detail->price, 2, '.', ',');
 
             if ($detail->discount_method == '2') {
                 $data['DiscountNet'] = number_format($detail->discount, 2, '.', '');
             } else {
                 $data['DiscountNet'] = number_format($detail->price * $detail->discount / 100, 2, '.', '');
             }
 
             $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
             $data['Unit_price'] = number_format($detail->price, 2, '.', '');
             $data['discount']   = number_format($detail->discount, 2, '.', '');
 
             if ($detail->tax_method == '1') {
                 $data['Net_price'] = $detail->price - $data['DiscountNet'];
                 $data['taxe']      = number_format($tax_price, 2, '.', '');
             } else {
                 $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                 $data['taxe']      = number_format($detail->price - $data['Net_price'] - $data['DiscountNet'], 2, '.', '');
             }
 
             $data['is_imei']     = $detail['product']['is_imei'];
             $data['imei_number'] = $detail->imei_number;
 
             $details[] = $data;
         }
 
         $settings = Setting::where('deleted_at', '=', null)->first();

        $Html = view('pdf.quotation_pdf', [
            'setting' => $settings,
            'quote' => $quote,
            'details' => $details,
        ])->render();

        $arabic = new Arabic();
        $p = $arabic->arIdentify($Html);

        for ($i = count($p)-1; $i >= 0; $i-=2) {
            $utf8ar = $arabic->utf8Glyphs(substr($Html, $p[$i-1], $p[$i] - $p[$i-1]));
            $Html = substr_replace($Html, $utf8ar, $p[$i-1], $p[$i] - $p[$i-1]);
        }

        $pdf = PDF::loadHTML($Html);

        return $pdf->download('Quotation.pdf');
        //----------
      
     }

     
    //------------- generate_sale -----------\\

    public function generate_sale(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('sales_add')){

            $role = Auth::user()->roles()->first();
            $Quotation = Quotation::with('details.product.unitSale')
                ->where('deleted_at', '=', null)
                ->findOrFail($id);
            $details = array();

            if ($Quotation->client_id) {
                if (Client::where('id', $Quotation->client_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $sale['client_id'] = $Quotation->client_id;
                } else {
                    $sale['client_id'] = '';
                }
            } else {
                $sale['client_id'] = '';
            }

            if ($Quotation->warehouse_id) {
                if (Warehouse::where('id', $Quotation->warehouse_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $sale['warehouse_id'] = $Quotation->warehouse_id;
                } else {
                    $sale['warehouse_id'] = '';
                }
            } else {
                $sale['warehouse_id'] = '';
            }

            $sale['date'] = $Quotation->date;
            $sale['TaxNet'] = $Quotation->TaxNet;
            $sale['tax_rate'] = $Quotation->tax_rate;
            $sale['discount'] = $Quotation->discount;
            $sale['shipping'] = $Quotation->shipping;
            $sale['statut'] = 'pending';
            $sale['notes'] = $Quotation->notes;
            $sale['GrandTotal'] = $Quotation->GrandTotal;

            $detail_id = 0;
            foreach ($Quotation['details'] as $detail) {
            
                    $unit = Unit::where('id', $detail->sale_unit_id)->first();

                    if ($detail->product_variant_id) {
                        $item_product = product_warehouse::where('product_id', $detail->product_id)
                            ->where('product_variant_id', $detail->product_variant_id)
                            ->where('warehouse_id', $Quotation->warehouse_id)
                            ->where('deleted_at', '=', null)
                            ->first();
                        $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                            ->where('id', $detail->product_variant_id)->where('deleted_at', null)->first();

                        $item_product ? $data['del'] = 0 : $data['del'] = 1;
                        $data['product_variant_id'] = $detail->product_variant_id;
                        $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                    
                        if ($unit && $unit->operator == '/') {
                            $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                        } else if ($unit && $unit->operator == '*') {
                            $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                        } else {
                            $data['stock'] = 0;
                        }

                    } else {
                        $item_product = product_warehouse::where('product_id', $detail->product_id)
                            ->where('warehouse_id', $Quotation->warehouse_id)
                            ->where('product_variant_id', '=', null)
                            ->where('deleted_at', '=', null)
                            ->first();

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
                    
                    $data['id'] = $id;
                    $data['detail_id'] = $detail_id += 1;
                    $data['quantity'] = $detail->quantity;
                    $data['product_id'] = $detail->product_id;
                    $data['total'] = $detail->total;
                    $data['name'] = $detail['product']['name'];
                    $data['etat'] = 'current';
                    $data['qte_copy'] = $detail->quantity;
                    $data['unitSale'] = $unit->ShortName;
                    $data['sale_unit_id'] = $unit->id;

                    $data['is_imei'] = $detail['product']['is_imei'];
                    $data['imei_number'] = $detail->imei_number;

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

            $products_array = [];
            $get_product_warehouse_data = product_warehouse::with('warehouse', 'product', 'productVariant')
                ->where('warehouse_id', $Quotation->warehouse_id)
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
                $firstimage = explode(',', $product_warehouse['product']->image);
                $item['image'] = $firstimage[0];

                if ($product_warehouse['product']['unitSale']->operator == '/') {
                    $item['qte_sale'] = $product_warehouse->qte * $product_warehouse['product']['unitSale']->operator_value;
                    $price = $product_warehouse['product']->price / $product_warehouse['product']['unitSale']->operator_value;
                } else {
                    $item['qte_sale'] = $product_warehouse->qte / $product_warehouse['product']['unitSale']->operator_value;
                    $price = $product_warehouse['product']->price * $product_warehouse['product']['unitSale']->operator_value;
                }

                if ($product_warehouse['product']['unitPurchase']->operator == '/') {
                    $item['qte_purchase'] = round($product_warehouse->qte * $product_warehouse['product']['unitPurchase']->operator_value, 5);
                } else {
                    $item['qte_purchase'] = round($product_warehouse->qte / $product_warehouse['product']['unitPurchase']->operator_value, 5);
                }

                $item['qte'] = $product_warehouse->qte;
                $item['unitSale'] = $product_warehouse['product']['unitSale']->ShortName;
                $item['unitPurchase'] = $product_warehouse['product']['unitPurchase']->ShortName;

                if ($product_warehouse['product']->TaxNet !== 0.0) {
                    //Exclusive
                    if ($product_warehouse['product']->tax_method == '1') {
                        $tax_price = $price * $product_warehouse['product']->TaxNet / 100;
                        $item['Net_price'] = $price + $tax_price;
                        // Inxclusive
                    } else {
                        $item['Net_price'] = $price;
                    }
                } else {
                    $item['Net_price'] = $price;
                }

                $products_array[] = $item;
            }

        //get warehouses
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            $clients = client::where('deleted_at', '=', null)
            ->get(['id', 'username']);

            return view('quotations.generate_sale',
                [
                    'details' => $details,
                    'sale' => $sale,
                    'clients' => $clients,
                    'warehouses' => $warehouses,
                    'products' => $products_array,
                ]
            );

        }
        return abort('403', __('You are not authorized'));

    }



    //------------- Send Quotation on Email -----------\\

    public function SendEmail(Request $request)
    {
        //Quotation
        $quotation = Quotation::with('client')->where('deleted_at', '=', null)->findOrFail($request->id);

        $helpers = new helpers();
        $currency = $helpers->Get_Currency();

         //settings
         $settings = Setting::where('deleted_at', '=', null)->first();
     
         //the custom msg of quotation
         $emailMessage  = EmailMessage::where('name', 'quotation')->first();
 
         if($emailMessage){
             $message_body = $emailMessage->body;
             $message_subject = $emailMessage->subject;
         }else{
             $message_body = '';
             $message_subject = '';
         }
 
         //Tags
         $random_number = Str::random(10);
         $quotation_url = url('/quotation_url/' . $request->id.'?'.$random_number);
         $quotation_number = $quotation->Ref;
 
         $total_amount = $this->render_price_with_symbol_placement(number_format($quotation->GrandTotal, 2, '.', ','));
        
         $contact_name = $quotation['client']->username;
         $business_name = $settings->CompanyName;
 
         //receiver email
         $receiver_email = $quotation['client']->email;
 
         //replace the text with tags
         $message_body = str_replace('{contact_name}', $contact_name, $message_body);
         $message_body = str_replace('{business_name}', $business_name, $message_body);
         $message_body = str_replace('{quotation_url}', $quotation_url, $message_body);
         $message_body = str_replace('{quotation_number}', $quotation_number, $message_body);
         $message_body = str_replace('{total_amount}', $total_amount, $message_body);

        $email['subject'] = $message_subject;
        $email['body'] = $message_body;
        $email['company_name'] = $business_name;

        $this->Set_config_mail(); 

        $mail = Mail::to($receiver_email)->send(new CustomEmail($email));

        return $mail;
    }

    //-------------------Sms Notifications -----------------\\

    public function Send_SMS(Request $request)
    {
        //Quotation
        $quotation = Quotation::with('client')->where('deleted_at', '=', null)->findOrFail($request->id);

        $helpers = new helpers();
        $currency = $helpers->Get_Currency();

        //settings
        $settings = Setting::where('deleted_at', '=', null)->first();
        
        //the custom msg of quotation
        $smsMessage  = SMSMessage::where('name', 'quotation')->first();

        if($smsMessage){
            $message_text = $smsMessage->text;
        }else{
            $message_text = '';
        }

        //Tags
        $random_number = Str::random(10);
        $quotation_url = url('/quotation_url/' . $request->id.'?'.$random_number);
        $quotation_number = $quotation->Ref;

        $total_amount = $this->render_price_with_symbol_placement(number_format($quotation->GrandTotal, 2, '.', ','));
        
        $contact_name = $quotation['client']->username;
        $business_name = $settings->CompanyName;

        //receiver phone
        $receiverNumber = $quotation['client']->phone;

        //replace the text with tags
        $message_text = str_replace('{contact_name}', $contact_name, $message_text);
        $message_text = str_replace('{business_name}', $business_name, $message_text);
        $message_text = str_replace('{quotation_url}', $quotation_url, $message_text);
        $message_text = str_replace('{quotation_number}', $quotation_number, $message_text);
        $message_text = str_replace('{total_amount}', $total_amount, $message_text);

        //twilio
        if($settings->default_sms_gateway == "twilio"){
            try {

                $account_sid = env("TWILIO_SID");
                $auth_token = env("TWILIO_TOKEN");
                $twilio_number = env("TWILIO_FROM");

                $client = new Client_Twilio($account_sid, $auth_token);
                $client->messages->create($receiverNumber, [
                    'from' => $twilio_number, 
                    'body' => $message_text]);

            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            //nexmo
        }elseif($settings->default_sms_gateway == "nexmo"){
                try {

                    $basic  = new \Nexmo\Client\Credentials\Basic(env("NEXMO_KEY"), env("NEXMO_SECRET"));
                    $client = new \Nexmo\Client($basic);
                    $nexmo_from = env("NEXMO_FROM");

                    $message = $client->message()->send([
                        'to' => $receiverNumber,
                        'from' => $nexmo_from,
                        'text' => $message_text
                    ]);
                            
                } catch (Exception $e) {
                    return response()->json(['message' => $e->getMessage()], 500);
                }

        //---- infobip
        }elseif($settings->default_sms_gateway == "infobip"){

                $BASE_URL = env("base_url");
                $API_KEY = env("api_key");
                $SENDER = env("sender_from");

                $configuration = (new Configuration())
                    ->setHost($BASE_URL)
                    ->setApiKeyPrefix('Authorization', 'App')
                    ->setApiKey('Authorization', $API_KEY);
                
                $client = new Client_guzzle();
                
                $sendSmsApi = new SendSMSApi($client, $configuration);
                $destination = (new SmsDestination())->setTo($receiverNumber);
                $message = (new SmsTextualMessage())
                    ->setFrom($SENDER)
                    ->setText($message_text)
                    ->setDestinations([$destination]);
                    
                $request = (new SmsAdvancedTextualRequest())->setMessages([$message]);
                
                try {
                    $smsResponse = $sendSmsApi->sendSmsMessage($request);
                    echo ("Response body: " . $smsResponse);
                } catch (Throwable $apiException) {
                    echo("HTTP Code: " . $apiException->getCode() . "\n");
                }
                
        }

        return response()->json(['success' => true]);
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


     // render_price_with_symbol_placement

    public function render_price_with_symbol_placement($amount) {

        if ($this->symbol_placement == 'before') {
            return $this->currency . ' ' . $amount;
        } else {
            return $amount . ' ' . $this->currency;
        }
    }
      

}
