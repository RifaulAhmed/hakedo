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
use App\Mail\CustomEmail;
use App\Models\EmailMessage;
use App\Models\PaymentMethod;
use App\Models\Account;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Mail\PurchaseMail;
use App\Models\Product;
use App\Models\PaymentPurchase;
use App\Models\Currency;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;
use Carbon\Carbon;
use DataTables;
use Config;
use DB;
use PDF;
use ArPHP\I18N\Arabic;
use App\utils\helpers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class PurchasesController extends Controller
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
		if ($user_auth->can('purchases_view_all') || $user_auth->can('purchases_view_own')){

            $suppliers = provider::where('deleted_at', '=', null)->get(['id', 'name']);

            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }
          
            return view('purchases.list_purchases',compact('suppliers','warehouses'));

        }
        return abort('403', __('You are not authorized'));

    }

    public function get_purchases_datatable(Request $request)
    {
        
        $user_auth = auth()->user();
        if (!$user_auth->can('purchases_view_all') && !$user_auth->can('purchases_view_own')){
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
                1 => '=',
                2 => '=',
                3 => 'like',
            );

            $columns = array(
                0 => 'Ref',
                1 => 'provider_id',
                2 => 'warehouse_id',
                3 => 'payment_statut',
            );

            $columns_order = array( 
                0 => 'id', 
                1 => 'date',
                2 => 'Ref', 
            );

            $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

            $start = $request->input('start');
            $order = 'purchases.'.$columns_order[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $purchases_data = Purchase::where('deleted_at', '=', null)
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
                if (!$user_auth->can('purchases_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            });

            //Multiple Filter
            $purchase_Filtred = $helpers->filter($purchases_data, $columns, $param, $request)

             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->input('search.value')}%")
                        ->orWhere('statut', 'LIKE', "%{$request->input('search.value')}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('provider', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        });
                        
                });
            });


            $totalRows = $purchase_Filtred->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $purchases = $purchase_Filtred
            ->with('provider', 'warehouse')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $data = array();
            
            foreach ($purchases as $purchase) {
                
                $item['id']             = $purchase->id;
                $item['date']           = Carbon::parse($purchase->date)->format('d-m-Y H:i');
                $item['warehouse_name'] = $purchase->warehouse->name;
                $item['provider_name']  = $purchase->provider->name;
                $item['GrandTotal']     = $this->render_price_with_symbol_placement(number_format($purchase->GrandTotal, 2, '.', ','));
                $item['paid_amount']    = $this->render_price_with_symbol_placement(number_format($purchase->paid_amount, 2, '.', ','));
                $item['due']            = $this->render_price_with_symbol_placement(number_format($purchase->GrandTotal - $purchase->paid_amount, 2, '.', ','));
              
                //payment_status
                if($purchase->payment_statut == 'paid'){
                    $item['payment_status'] = '<span class="badge badge-outline-success">'.trans('translate.Paid').'</span>';
                }else if($purchase->payment_statut == 'partial'){
                    $item['payment_status'] = '<span class="badge badge-outline-info">'.trans('translate.Partial').'</span>';
                }else{
                    $item['payment_status'] = '<span class="badge badge-outline-warning">'.trans('translate.Unpaid').'</span>';
                }

                
                if (PurchaseReturn::where('purchase_id', $purchase->id)->where('deleted_at', '=', null)->exists()) {
                    $purchase_has_return = 'yes';
                    $item['Ref']         = $purchase->Ref .' '.'<i class="text-15 text-danger i-Back"></i>';
                }else{
                    $purchase_has_return = 'no';
                    $item['Ref']         = $purchase->Ref;
                }

                $item['action'] = '<div class="dropdown">
                                <button class="btn btn-outline-info btn-rounded dropdown-toggle" id="dropdownMenuButton" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                                .trans('translate.Action').

                                '</button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 34px, 0px);">';
                               
                                //check if user has permission "purchases_details"
                                if ($user_auth->can('purchases_details')){
                                    $item['action'] .=    '<a class="dropdown-item" href="/purchase/purchases/' .$purchase->id.'"> <i class="nav-icon i-Eye font-weight-bold mr-2"></i> ' .trans('translate.PurchaseDetail').'</a>';
                                }
                            
                                //check if user has permission "purchases_edit"
                                if ($user_auth->can('purchases_edit') &&  $purchase_has_return == 'no'){
                                    $item['action'] .=    '<a class="dropdown-item" href="/purchase/purchases/' .$purchase->id. '/edit" ><i class="nav-icon i-Edit font-weight-bold mr-2"></i> ' .trans('translate.EditPurchase').'</a>';
                                }

                                if ($user_auth->can('purchase_returns_add') &&  $purchase_has_return == 'no'){
                                    $item['action'] .= '<a class="dropdown-item" href="/purchase-return/add_returns_purchase/' .$purchase->id.'" ><i class="nav-icon i-Back font-weight-bold mr-2"></i> ' .trans('translate.Purchase_Return').'</a>';
                                }

                                //check if user has permission "payment_purchases_view"
                                if ($user_auth->can('payment_purchases_view')){
                                    $item['action'] .=    '<a class="dropdown-item Show_Payments cursor-pointer"  id="' .$purchase->id. '" > <i class="nav-icon i-Money-Bag font-weight-bold mr-2"></i> ' .trans('translate.ShowPayment').'</a>';
                                }
                                //check if user has permission "payment_purchases_add"
                                if ($user_auth->can('payment_purchases_add')){
                                    $item['action'] .=    '<a class="dropdown-item New_Payment cursor-pointer" payment_status="' .$purchase->payment_statut. '"  id="' .$purchase->id. '" > <i class="nav-icon i-Add font-weight-bold mr-2"></i> ' .trans('translate.AddPayment').'</a>';
                                }
                                $item['action'] .=  '<a class="dropdown-item download_pdf cursor-pointer" Ref="' .$purchase->Ref. '" id="' .$purchase->id. '" ><i class="nav-icon i-File-TXT font-weight-bold mr-2"></i> ' .trans('translate.DownloadPdf').'</a>
                                <a class="dropdown-item  send_email cursor-pointer" id="' .$purchase->id. '" ><i class="nav-icon i-Envelope-2 font-weight-bold mr-2"></i> ' .trans('translate.EmailPurchase').'</a>
                                <a class="dropdown-item  send_sms cursor-pointer" id="' .$purchase->id. '" ><i class="nav-icon i-Envelope-2 font-weight-bold mr-2"></i> ' .trans('translate.Send_sms').'</a>';
                                //check if user has permission "purchases_delete"
                                if ($user_auth->can('purchases_delete') &&  $purchase_has_return == 'no'){
                                    $item['action'] .= '<a class="dropdown-item delete cursor-pointer" id="' .$purchase->id. '" > <i class="nav-icon i-Close-Window font-weight-bold mr-2"></i> ' .trans('translate.DeletePurchase').'</a>';
                                }
                                $item['action'] .= '</div>
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
		if ($user_auth->can('purchases_add')){

            //get warehouses assigned to user
            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            }else{
                $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
            } 

            $suppliers = provider::where('deleted_at', '=', null)->get(['id', 'name']);

            return view('purchases.create_purchase',
                [
                    'suppliers' => $suppliers,
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




        // public function store(Request $request)
        // {
        //     $user_auth = auth()->user();
    
        // //     $data = [
        // //             'subject' => 'Test Email',
        // //             'body' => 'This is a test email body.',
        // //             'company_name' => 'Your Company'
        // //         ];
        // // Mail::to('rifaulbellway@gmail.com',)->send(new CustomEmail($data));
            
        //     if ($user_auth->can('purchases_add')) {
        //         \DB::transaction(function () use ($request) {
        //             $order = new Purchase;
        
        //             // Saving purchase details
        //             $order->date = $request->date;
        //             $order->Ref = 'PO-' . date("Ymd") . '-'. date("his");
        //             $order->provider_id = $request->supplier_id;
        //             $order->GrandTotal = $request->GrandTotal;
        //             $order->warehouse_id = $request->warehouse_id;
        //             $order->tax_rate = $request->tax_rate;
        //             $order->TaxNet = $request->TaxNet;
        //             $order->discount = $request->discount;
        //             $order->discount_type = $request->discount_type;
        //             $order->discount_percent_total = $request->discount_percent_total;
        //             $order->shipping = $request->shipping;
        //             $order->statut = 'received';
        //             $order->payment_statut = 'unpaid';
        //             $order->notes = $request->notes;
        //             $order->user_id = Auth::user()->id;
        
        //             $order->save();
        
        //             // Saving order details
        //             $data = $request['details'];
        //             $orderDetails = [];
        
        //             foreach ($data as $key => $value) {
        //                 $unit = Unit::where('id', $value['purchase_unit_id'])->first();
        //                 $orderDetails[] = [
        //                     'date' => $request->date,
        //                     'purchase_id' => $order->id,
        //                     'quantity' => $value['quantity'],
        //                     'cost' => $value['Unit_cost'],
        //                     'purchase_unit_id' =>  $value['purchase_unit_id'],
        //                     'TaxNet' => $value['tax_percent'],
        //                     'tax_method' => $value['tax_method'],
        //                     'discount' => $value['discount'],
        //                     'discount_method' => $value['discount_Method'],
        //                     'product_id' => $value['product_id'],
        //                     'product_variant_id' => $value['product_variant_id'] ? $value['product_variant_id'] : NULL,
        //                     'total' => $value['subtotal'],
        //                     'imei_number' => $value['imei_number'],
        //                 ];
        
        //                 // Updating product quantities in the warehouse
        //                 $product_warehouse = product_warehouse::where('deleted_at', '=', null)
        //                     ->where('warehouse_id', $order->warehouse_id)
        //                     ->where('product_id', $value['product_id'])
        //                     ->when($value['product_variant_id'], function($query) use ($value) {
        //                         return $query->where('product_variant_id', $value['product_variant_id']);
        //                     })
        //                     ->first();
        
        //                 if ($unit && $product_warehouse) {
        //                     if ($unit->operator == '/') {
        //                         $product_warehouse->qte += $value['quantity'] / $unit->operator_value;
        //                     } else {
        //                         $product_warehouse->qte += $value['quantity'] * $unit->operator_value;
        //                     }
        //                     $product_warehouse->save();
        //                 }
        //             }
        
        //             PurchaseDetail::insert($orderDetails);
        
                    
                    
        //             // $this->Sendemail(new Request(['id' => $order->id]));
        //             $this->Sendemail($order->id);
        
        //         }, 10);
        
        //         return response()->json(['success' => true, 'message' => 'Purchase Created !!']);
        //     }
        
        //     return abort('403', __('You are not authorized'));
        // }



        public function store(Request $request)
{
    $user_auth = auth()->user();

    if ($user_auth->can('purchases_add')) {
        \DB::transaction(function () use ($request) {
            $order = new Purchase;

            $order->date = $request->date;
            $order->Ref = 'PO-' . date("Ymd") . '-' . date("his");
            $order->provider_id = $request->supplier_id;
            $order->GrandTotal = $request->GrandTotal;
            $order->warehouse_id = $request->warehouse_id;
            $order->tax_rate = $request->tax_rate;
            $order->TaxNet = $request->TaxNet;
            $order->discount = $request->discount;
            $order->discount_type = $request->discount_type;
            $order->discount_percent_total = $request->discount_percent_total;
            $order->shipping = $request->shipping;
            $order->statut = 'received';
            $order->payment_statut = 'unpaid';
            $order->notes = $request->notes;
            $order->user_id = Auth::user()->id;

            $order->save();

            // Fetch provider's email based on the provider_id
            $provider = Provider::find($order->provider_id);

            if ($provider && !empty($provider->email)) {
                // Send an email only to the provider
                $this->sendPurchaseEmailToProvider($order, $provider->email);
            }

        }, 10);

        return response()->json(['success' => true, 'message' => 'Purchase created and email sent to provider!']);
    }

    return abort(403, __('You are not authorized'));
}


        
    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {

        $user_auth = auth()->user();
		if ($user_auth->can('purchases_details')){

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            $purchase = Purchase::with('details.product.unitPurchase')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($array_warehouses_id) {
                return $query->whereIn('warehouse_id', $array_warehouses_id);
            })
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('purchases_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })->findOrFail($id);

            $details = array();

            $purchase_data['id']                     = $purchase->id;
            $purchase_data['Ref']                    = $purchase->Ref;
            $purchase_data['date']                   = $purchase->date;
            $purchase_data['statut']                 = $purchase->statut;
            $purchase_data['note']                   = $purchase->notes;

            if($purchase->discount_type == 'fixed'){
                $purchase_data['discount']           = $this->render_price_with_symbol_placement(number_format($purchase->discount, 2, '.', ','));
            }else{
                $purchase_data['discount']           = $this->render_price_with_symbol_placement(number_format($purchase->discount_percent_total, 2, '.', ',')) .' '.'('.$purchase->discount .' '.'%)';
            }

            $purchase_data['shipping']               = $this->render_price_with_symbol_placement(number_format($purchase->shipping, 2, '.', ','));
            $purchase_data['tax_rate']               = $purchase->tax_rate;
            $purchase_data['TaxNet']                 = $this->render_price_with_symbol_placement(number_format($purchase->TaxNet, 2, '.', ','));
            $purchase_data['supplier_name']          = $purchase['provider']->name;
            $purchase_data['supplier_email']         = $purchase['provider']->email;
            $purchase_data['supplier_phone']         = $purchase['provider']->phone;
            $purchase_data['supplier_adr']           = $purchase['provider']->address;
            $purchase_data['warehouse']              = $purchase['warehouse']->name;
            $purchase_data['GrandTotal']             = $this->render_price_with_symbol_placement(number_format($purchase->GrandTotal, 2, '.', ','));
            $purchase_data['paid_amount']            = $this->render_price_with_symbol_placement(number_format($purchase->paid_amount, 2, '.', ','));
            $purchase_data['due']                    = $this->render_price_with_symbol_placement(number_format($purchase->GrandTotal - $purchase->paid_amount, 2, '.', ','));
            $purchase_data['payment_status']         = $purchase->payment_statut;

            if (PurchaseReturn::where('purchase_id', $id)->where('deleted_at', '=', null)->exists()) {
                $purchase_data['purchase_has_return'] = 'yes';
            }else{
                $purchase_data['purchase_has_return'] = 'no';
            }

            foreach ($purchase['details'] as $detail) {

                $unit = Unit::where('id', $detail->purchase_unit_id)->first();
                
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
                $data['cost'] = $detail->cost;
                $data['unit_purchase'] = $unit->ShortName;

                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->cost * $detail->discount / 100;
                }

                $tax_cost = $detail->TaxNet * (($detail->cost - $data['DiscountNet']) / 100);
                $data['Unit_cost'] = $detail->cost;
                $data['discount'] = $detail->discount;

                if ($detail->tax_method == '1') {

                    $data['Net_cost'] = $detail->cost - $data['DiscountNet'];
                    $data['taxe'] = $tax_cost;
                } else {
                    $data['Net_cost'] = ($detail->cost - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['taxe'] = $detail->cost - $data['Net_cost'] - $data['DiscountNet'];
                }

                $data['is_imei'] = $detail['product']['is_imei'];
                $data['imei_number'] = $detail->imei_number;

                $details[] = $data;
            }

            $company = Setting::where('deleted_at', '=', null)->first();
            
            return view('purchases.details_purchase',
            [
                'purchase' => $purchase_data,
                'details' => $details,
                'company' => $company,
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
        if (PurchaseReturn::where('purchase_id', $id)->where('deleted_at', '=', null)->exists()) {
            return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
        }else{

            $user_auth = auth()->user();
            if ($user_auth->can('purchases_edit')){

                //get warehouses 
                if($user_auth->is_all_warehouses){
                    $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
                    $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

                }else{
                    $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                    $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
                }

                $Purchase_data = Purchase::with('details.product.unitPurchase')
                    ->where('deleted_at', '=', null)
                    ->where(function ($query) use ($array_warehouses_id) {
                        return $query->whereIn('warehouse_id', $array_warehouses_id);
                    })
                    ->where(function ($query) use ($user_auth) {
                        if (!$user_auth->can('purchases_view_all')) {
                            return $query->where('user_id', '=', $user_auth->id);
                        }
                    })->findOrFail($id);

                $details = array();
            
                if ($Purchase_data->provider_id) {
                    if (Provider::where('id', $Purchase_data->provider_id)->where('deleted_at', '=', null)->first()) {
                        $purchase['supplier_id'] = $Purchase_data->provider_id;
                    } else {
                        $purchase['supplier_id'] = '';
                    }
                } else {
                    $purchase['supplier_id'] = '';
                }

                if ($Purchase_data->warehouse_id) {
                    if (Warehouse::where('id', $Purchase_data->warehouse_id)->where('deleted_at', '=', null)->first()) {
                        $purchase['warehouse_id'] = $Purchase_data->warehouse_id;
                    } else {
                        $purchase['warehouse_id'] = '';
                    }
                } else {
                    $purchase['warehouse_id'] = '';
                }

                $purchase['id'] = $Purchase_data->id;
                $purchase['date'] = $Purchase_data->date;
                $purchase['tax_rate'] = $Purchase_data->tax_rate;
                $purchase['TaxNet'] = $Purchase_data->TaxNet;
                $purchase['discount'] = $Purchase_data->discount;
                $purchase['discount_type'] = $Purchase_data->discount_type;
                $purchase['discount_percent_total'] = $Purchase_data->discount_percent_total;

                $purchase['shipping'] = $Purchase_data->shipping;
                $purchase['statut'] = $Purchase_data->statut;
                $purchase['notes'] = $Purchase_data->notes;
                $purchase['GrandTotal'] = $Purchase_data->GrandTotal;

                $detail_id = 0;
                foreach ($Purchase_data['details'] as $detail) {

                    $unit = Unit::where('id', $detail->purchase_unit_id)->first();

                    if ($detail->product_variant_id) {
                        $item_product = product_warehouse::where('product_id', $detail->product_id)
                            ->where('deleted_at', '=', null)
                            ->where('product_variant_id', $detail->product_variant_id)
                            ->where('warehouse_id', $Purchase_data->warehouse_id)
                            ->first();

                        $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                            ->where('id', $detail->product_variant_id)->first();

                        $item_product ? $data['del'] = 0 : $data['del'] = 1;

                        $data['code'] = $productsVariants->code;
                        $data['name'] = '['.$productsVariants->name . '] ' . $detail['product']['name'];

                        $data['product_variant_id'] = $detail->product_variant_id;                

                        if ($unit && $unit->operator == '/') {
                            $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                        } else if ($unit && $unit->operator == '*') {
                            $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                        } else {
                            $data['stock'] = 0;
                        }

                    } else {
                        $item_product = product_warehouse::where('product_id', $detail->product_id)
                            ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                            ->where('warehouse_id', $Purchase_data->warehouse_id)->first();

                        $item_product ? $data['del'] = 0 : $data['del'] = 1;
                        $data['product_variant_id'] = null;
                        $data['code'] = $detail['product']['code'];
                        $data['name'] = $detail['product']['name'];
                    

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
                    $data['unitPurchase'] = $unit->ShortName;
                    $data['purchase_unit_id'] = $unit->id;
                    
                    $data['is_imei'] = $detail['product']['is_imei'];
                    $data['imei_number'] = $detail->imei_number;

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
                    ->where('warehouse_id', $Purchase_data->warehouse_id)
                    ->where('deleted_at', '=', null)
                    ->where('manage_stock', true)
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

            
                $suppliers = Provider::where('deleted_at', '=', null)->get(['id', 'name']);

                return view('purchases.edit_purchase',
                    [
                        'details' => $details,
                        'purchase' => $purchase,
                        'suppliers' => $suppliers,
                        'warehouses' => $warehouses,
                        'products' => $products_array,
                    ]
                );

            }
            return abort('403', __('You are not authorized'));

        }

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if (PurchaseReturn::where('purchase_id', $id)->where('deleted_at', '=', null)->exists()) {
            return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
        }else{

            $user_auth = auth()->user();
            if ($user_auth->can('purchases_edit')){

                \DB::transaction(function () use ($request, $id) {
                    $current_Purchase = Purchase::findOrFail($id);
                    $old_purchase_details = PurchaseDetail::where('purchase_id', $id)->get();
                    $new_purchase_details = $request['details'];
                    $length = sizeof($new_purchase_details);

                    // Get Ids for new Details
                    $new_products_id = [];
                    foreach ($new_purchase_details as $new_detail) {
                        $new_products_id[] = $new_detail['id'];
                    }

                    // Init Data with old Parametre
                    $old_products_id = [];
                    foreach ($old_purchase_details as $key => $value) {
                        $old_products_id[] = $value->id;
                    
                        $unit = Unit::where('id', $value['purchase_unit_id'])->first();
                    

                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Purchase->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
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
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Purchase->warehouse_id)
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
                            $PurchaseDetail = PurchaseDetail::findOrFail($value->id);
                            $PurchaseDetail->delete();
                        }
                        

                    }

                    // Update Data with New request
                    foreach ($new_purchase_details as $key => $prod_detail) {
                        
                        $unit_prod = Unit::where('id', $prod_detail['purchase_unit_id'])->first();


                        if ($prod_detail['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $prod_detail['product_id'])
                                ->where('product_variant_id', $prod_detail['product_variant_id'])
                                ->first();

                            if ($unit_prod && $product_warehouse) {
                                if ($unit_prod->operator == '/') {
                                    $product_warehouse->qte += $prod_detail['quantity'] / $unit_prod->operator_value;
                                } else {
                                    $product_warehouse->qte += $prod_detail['quantity'] * $unit_prod->operator_value;
                                }

                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $prod_detail['product_id'])
                                ->first();

                            if ($unit_prod && $product_warehouse) {
                                if ($unit_prod->operator == '/') {
                                    $product_warehouse->qte += $prod_detail['quantity'] / $unit_prod->operator_value;
                                } else {
                                    $product_warehouse->qte += $prod_detail['quantity'] * $unit_prod->operator_value;
                                }

                                $product_warehouse->save();
                            }
                        }


                        $orderDetails['purchase_id'] = $id;
                        $orderDetails['date'] = $request['date'];
                        $orderDetails['cost'] = $prod_detail['Unit_cost'];
                        $orderDetails['purchase_unit_id'] = $prod_detail['purchase_unit_id'];
                        $orderDetails['TaxNet'] = $prod_detail['tax_percent'];
                        $orderDetails['tax_method'] = $prod_detail['tax_method'];
                        $orderDetails['discount'] = $prod_detail['discount'];
                        $orderDetails['discount_method'] = $prod_detail['discount_Method'];
                        $orderDetails['quantity'] = $prod_detail['quantity'];
                        $orderDetails['product_id'] = $prod_detail['product_id'];
                        $orderDetails['product_variant_id'] = $prod_detail['product_variant_id']?$prod_detail['product_variant_id']:NULL;
                        $orderDetails['total'] = $prod_detail['subtotal'];
                        $orderDetails['imei_number'] = $prod_detail['imei_number'];

                        if (!in_array($prod_detail['id'], $old_products_id)) {
                            PurchaseDetail::Create($orderDetails);
                        } else {
                            PurchaseDetail::where('id', $prod_detail['id'])->update($orderDetails);
                        }
                    }

                    $due = $request['GrandTotal'] - $current_Purchase->paid_amount;
                    if ($due === 0.0 || $due < 0.0) {
                        $payment_statut = 'paid';
                    } else if ($due != $request['GrandTotal']) {
                        $payment_statut = 'partial';
                    } else if ($due == $request['GrandTotal']) {
                        $payment_statut = 'unpaid';
                    }

                    $current_Purchase->update([
                        'date' => $request['date'],
                        'provider_id' => $request['supplier_id'],
                        'warehouse_id' => $request['warehouse_id'],
                        'notes' => $request['notes'],
                        'tax_rate' => $request['tax_rate'],
                        'TaxNet' => $request['TaxNet'],
                        'discount' => $request['discount'],
                        'discount_type' => $request['discount_type'],
                        'discount_percent_total' => $request['discount_percent_total'],
                        'shipping' => $request['shipping'],
                        'statut' => $request['statut'],
                        'GrandTotal' => $request['GrandTotal'],
                        'payment_statut' => $payment_statut,
                    ]);

                }, 10);

                return response()->json(['success' => true, 'message' => 'Purchase Updated !!']);

            }
            return abort('403', __('You are not authorized'));
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if (PurchaseReturn::where('purchase_id', $id)->where('deleted_at', '=', null)->exists()) {
            return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
        }else{

            $user_auth = auth()->user();
            if ($user_auth->can('purchases_delete')){

                \DB::transaction(function () use ($id) {
                    $current_Purchase = Purchase::findOrFail($id);
                    $old_purchase_details = PurchaseDetail::where('purchase_id', $id)->get();

                    foreach ($old_purchase_details as $key => $value) {
                    
                        $unit = Unit::where('id', $value['purchase_unit_id'])->first();


                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Purchase->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
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
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Purchase->warehouse_id)
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

                    $current_Purchase->details()->delete();
                    $current_Purchase->update([
                        'deleted_at' => Carbon::now(),
                    ]);

                    // get all payments
                    $payments = PaymentPurchase::where('purchase_id', $id)->get();

                    foreach ($payments as $payment) {

                        $account = Account::find($payment->account_id);

                        if ($account) {
                            $account->update([
                                'initial_balance' => $account->initial_balance + $payment->montant,
                            ]);
                        }

                    }

                    PaymentPurchase::where('purchase_id', $id)->update([
                        'deleted_at' => Carbon::now(),
                    ]);


                }, 10);
                return response()->json(['success' => true, 'message' => 'Purchase Deleted !!']);

            }
            return abort('403', __('You are not authorized'));
        }
    }

     //--------------- Get Payments of Purchase ----------------\\

     public function Get_Payments(Request $request, $id)
     {
        $user_auth = auth()->user();
		if ($user_auth->can('payment_purchases_view')){

            $purchase = Purchase::findOrFail($id);
            $payments = PaymentPurchase::with('purchase')
                ->where('purchase_id', $id)
                ->orderBy('id', 'DESC')->get();
    
            $due = $purchase->GrandTotal - $purchase->paid_amount;

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

    //--------------- Reference Number of Purchase ----------------\\

    public function getNumberOrder()
    {

        $last = DB::table('purchases')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'PR_1111';
        }
        return $code;

    }


    //-------------- purchase PDF -----------\\

    public function Purchase_pdf(Request $request, $id)
    {
        $details = array();
        $user_auth = auth()->user();

        $Purchase_data = Purchase::with('details.product.unitPurchase')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('purchases_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })->findOrFail($id);

        $purchase['supplier_name']  = $Purchase_data['provider']->name;
        $purchase['supplier_phone'] = $Purchase_data['provider']->phone;
        $purchase['supplier_adr']   = $Purchase_data['provider']->address;
        $purchase['supplier_email'] = $Purchase_data['provider']->email;
        $purchase['tax_rate']       = number_format($Purchase_data->tax_rate, 2, '.', '');
        $purchase['TaxNet']         = $this->render_price_with_symbol_placement(number_format($Purchase_data->TaxNet, 2, '.', ','));

        if($Purchase_data->discount_type == 'fixed'){
            $purchase['discount']           = $this->render_price_with_symbol_placement(number_format($Purchase_data->discount, 2, '.', ','));
        }else{
            $purchase['discount']           = $this->render_price_with_symbol_placement(number_format($Purchase_data->discount_percent_total, 2, '.', ',')) .' '.'('.$Purchase_data->discount .' '.'%)';
        }

        $purchase['shipping']               = $this->render_price_with_symbol_placement(number_format($Purchase_data->shipping, 2, '.', ','));
        $purchase['date']                   = Carbon::parse($Purchase_data->date)->format('d-m-Y H:i');
        $purchase['GrandTotal']             = $this->render_price_with_symbol_placement(number_format($Purchase_data->GrandTotal, 2, '.', ','));
        $purchase['paid_amount']            = $this->render_price_with_symbol_placement(number_format($Purchase_data->paid_amount, 2, '.', ','));
        $purchase['due']                    = $this->render_price_with_symbol_placement(number_format($Purchase_data->GrandTotal- $Purchase_data->paid_amount, 2, '.', ','));
        $purchase['payment_status']         = $Purchase_data->payment_statut;
        $purchase['Ref']                    = $Purchase_data->Ref;

        $detail_id = 0;
        foreach ($Purchase_data['details'] as $detail) {

            $unit = Unit::where('id', $detail->purchase_unit_id)->first();

            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                $data['code'] = $productsVariants->code;
                $data['name'] = '['.$productsVariants->name . '] ' . $detail['product']['name'];

            } else {
                $data['code'] = $detail['product']['code'];
                $data['name'] = $detail['product']['name'];
            }

                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = number_format($detail->quantity, 2, '.', '');
                $data['total'] = number_format($detail->total, 2, '.', ',');
                $data['unit_purchase'] = $unit->ShortName;
                $data['cost'] = number_format($detail->cost, 2, '.', ',');

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = number_format($detail->discount, 2, '.', '');
            } else {
                $data['DiscountNet'] = number_format($detail->cost * $detail->discount / 100, 2, '.', '');
            }

            $tax_cost = $detail->TaxNet * (($detail->cost - $data['DiscountNet']) / 100);
            $data['Unit_cost'] = number_format($detail->cost, 2, '.', '');
            $data['discount'] = number_format($detail->discount, 2, '.', '');

            if ($detail->tax_method == '1') {

                $data['Net_cost'] = $detail->cost - $data['DiscountNet'];
                $data['taxe'] = number_format($tax_cost, 2, '.', '');
            } else {
                $data['Net_cost'] = ($detail->cost - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe'] = number_format($detail->cost - $data['Net_cost'] - $data['DiscountNet'], 2, '.', '');
            }

            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            $details[] = $data;
        }

        $settings = Setting::where('deleted_at', '=', null)->first();

        $Html = view('pdf.purchase_pdf', [
            'setting' => $settings,
            'purchase' => $purchase,
            'details' => $details,
        ])->render();

        $arabic = new Arabic();
        $p = $arabic->arIdentify($Html);

        for ($i = count($p)-1; $i >= 0; $i-=2) {
            $utf8ar = $arabic->utf8Glyphs(substr($Html, $p[$i-1], $p[$i] - $p[$i-1]));
            $Html = substr_replace($Html, $utf8ar, $p[$i-1], $p[$i] - $p[$i-1]);
        }

        $pdf = PDF::loadHTML($Html);

        return $pdf->download('Purchase.pdf');
        //------------------

    }


    //------------- Send Email -----------\\
//     public function Sendemail($data)
// {
//     // dd($data);
//     try {
//         // Log the configuration setup
//         Log::info('Configuring mail settings');
//         $this->Set_config_mail();
    
//         // Clear configuration cache
//         Artisan::call('config:cache');
//         Artisan::call('config:clear');
    
//         // Retrieve the purchase and related data
//         $purchase = Purchase::with('provider')->where('deleted_at', '=', null)->findOrFail($data['id']);
//         // dd($purchase);
    
//         $helpers = new helpers();
//         $currency = $helpers->Get_Currency();
    
//         $settings = Setting::where('deleted_at', '=', null)->first();
//         $emailMessage = EmailMessage::where('name', 'purchase')->first();
    
//         if ($emailMessage) {
//             $message_body = $emailMessage->body;
//             $message_subject = $emailMessage->subject;
//         } else {
//             $message_body = '';
//             $message_subject = '';
//         }
    
//         // Tags
//         $random_number = Str::random(10);
//         $invoice_url = url('/purchase_url/' . $purchase->id . '?' . $random_number);
//         $invoice_number = $purchase->Ref;
    
//         $total_amount = $this->render_price_with_symbol_placement(number_format($purchase->GrandTotal, 2, '.', ','));
//         $paid_amount  = $this->render_price_with_symbol_placement(number_format($purchase->paid_amount, 2, '.', ','));
//         $due_amount   = $this->render_price_with_symbol_placement(number_format($purchase->GrandTotal - $purchase->paid_amount, 2, '.', ','));
    
//         $contact_name = $purchase['provider']->name;
//         $business_name = $settings->CompanyName;
    
//         // Receiver email 
//         $receiver_email = $purchase['provider']->email;
//         // dd($receiver_email);
    
//         // Replace the text with tags
//         $message_body = str_replace('{contact_name}', $contact_name, $message_body);
//         $message_body = str_replace('{business_name}', $business_name, $message_body);
//         $message_body = str_replace('{invoice_url}', $invoice_url, $message_body);
//         $message_body = str_replace('{invoice_number}', $invoice_number, $message_body);
//         $message_body = str_replace('{total_amount}', $total_amount, $message_body);
//         $message_body = str_replace('{paid_amount}', $paid_amount, $message_body);
//         $message_body = str_replace('{due_amount}', $due_amount, $message_body);
    
//         $email = [
//             'subject' => $message_subject,
//             'body' => $message_body,
//             'company_name' => $business_name,
//         ];
//         // dd($email);
    
//         // Send the email
//         Mail::to($receiver_email)->send(new CustomEmail($email));

//         return response()->json(['message' => 'Email sent successfully'], 200);
//     } catch (\Exception $e) {
//         Log::error('Error sending email: ' . $e->getMessage());
//         return response()->json(['message' => 'Failed to send email', 'error' => $e->getMessage()], 500);
//     }
// }

public function sendPurchaseEmailToProvider($order, $providerEmail)
{
    try {
        // Retrieve the email template from the database
        $emailTemplate = EmailMessage::where('name', 'purchase')->first();

        if ($emailTemplate) {
            $message_body = $emailTemplate->body;
            $message_subject = $emailTemplate->subject;
        } else {
            $message_body = 'Default email body if template is not found.';
            $message_subject = 'Default email subject if template is not found.';
        }

        // Replace the placeholders in the email template with actual values
        $helpers = new helpers();
        $currency = $helpers->Get_Currency();
        $invoice_url = url('/purchase_url/' . $order->id . '?' . Str::random(10));
        $total_amount = $this->render_price_with_symbol_placement(number_format($order->GrandTotal, 2, '.', ','));
        $paid_amount  = $this->render_price_with_symbol_placement(number_format($order->paid_amount, 2, '.', ','));
        $due_amount   = $this->render_price_with_symbol_placement(number_format($order->GrandTotal - $order->paid_amount, 2, '.', ','));

        $message_body = str_replace('{contact_name}', $order->provider->name, $message_body);
        $message_body = str_replace('{business_name}', Setting::first()->CompanyName, $message_body);
        $message_body = str_replace('{invoice_url}', $invoice_url, $message_body);
        $message_body = str_replace('{invoice_number}', $order->Ref, $message_body);
        $message_body = str_replace('{total_amount}', $total_amount, $message_body);
        $message_body = str_replace('{paid_amount}', $paid_amount, $message_body);
        $message_body = str_replace('{due_amount}', $due_amount, $message_body);

        // Send the email
        Mail::to($providerEmail)->send(new CustomEmail([
            'subject' => $message_subject,
            'body' => $message_body,
            'company_name' => Setting::first()->CompanyName,
        ]));

    } catch (\Exception $e) {
        \Log::error('Failed to send email to provider: ' . $e->getMessage());
    }
}



    // Set config mail
    public function Set_config_mail()
{
    Log::info('Setting mail configuration');

    $config = [
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => env('MAIL_HOST'),
                'port' => env('MAIL_PORT'),
                'encryption' => env('MAIL_ENCRYPTION'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'timeout' => null,
                'auth_mode' => null,
            ],
        ],
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS'),
            'name' => env('MAIL_FROM_NAME'),
        ],
        'encryption' => env('MAIL_ENCRYPTION'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'sendmail' => '/usr/sbin/sendmail -bs',
        'pretend' => false,
    ];
    config($config);
}



    //------------------- get_Products_by_purchase -----------------\\

    public function get_Products_by_purchase(Request $request , $id)
    {

        $Purchase_data = Purchase::with('details.product.unitPurchase')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $details = array();
    
        $Return_detail['supplier_id'] = $Purchase_data->provider_id;
        $Return_detail['warehouse_id'] = $Purchase_data->warehouse_id;
        $Return_detail['purchase_id'] = $Purchase_data->id;
        $Return_detail['tax_rate'] = 0;
        $Return_detail['TaxNet'] = 0;
        $Return_detail['discount'] = 0;
        $Return_detail['shipping'] = 0;
        $Return_detail['statut'] = "completed";
        $Return_detail['notes'] = "";

        $detail_id = 0;
        foreach ($Purchase_data['details'] as $detail) {

            $unit = Unit::where('id', $detail->purchase_unit_id)->first();

            if ($detail->product_variant_id) {
                $item_product = product_warehouse::where('product_id', $detail->product_id)
                    ->where('deleted_at', '=', null)
                    ->where('product_variant_id', $detail->product_variant_id)
                    ->where('warehouse_id', $Purchase_data->warehouse_id)
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

            } else {
                $item_product = product_warehouse::where('product_id', $detail->product_id)
                    ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                    ->where('warehouse_id', $Purchase_data->warehouse_id)->first();

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
            $data['name'] = $detail['product']['name'];
            $data['detail_id'] = $detail_id += 1;
            $data['quantity'] = $detail->quantity;
            $data['purchase_quantity'] = $detail->quantity;
            $data['product_id'] = $detail->product_id;
            $data['unitPurchase'] = $unit->ShortName;
            $data['purchase_unit_id'] = $unit->id;
            
            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

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

        return response()->json([
            'details' => $details,
            'purchase_return' => $Return_detail,
        ]);

    }


    //-------------------Sms Notifications -----------------\\

    public function Send_SMS(Request $request)
    {

        //purchase
        $purchase = Purchase::with('provider')->where('deleted_at', '=', null)->findOrFail($request->id);

        $helpers = new helpers();
        $currency = $helpers->Get_Currency();

        //settings
        $settings = Setting::where('deleted_at', '=', null)->first();
    
        //the custom msg of purchase
        $smsMessage  = SMSMessage::where('name', 'purchase')->first();

        if($smsMessage){
            $message_text = $smsMessage->text;
        }else{
            $message_text = '';
        }

        //Tags
        $random_number = Str::random(10);
        $invoice_url = url('/purchase_url/' . $request->id.'?'.$random_number);
        $invoice_number = $purchase->Ref;

        $total_amount = $this->render_price_with_symbol_placement(number_format($purchase->GrandTotal, 2, '.', ','));
        $paid_amount  = $this->render_price_with_symbol_placement(number_format($purchase->paid_amount, 2, '.', ','));
        $due_amount   = $this->render_price_with_symbol_placement(number_format($purchase->GrandTotal - $purchase->paid_amount, 2, '.', ','));

        $contact_name = $purchase['provider']->name;
        $business_name = $settings->CompanyName;

        //receiver Number
        $receiverNumber = $purchase['provider']->phone;

        //replace the text with tags
        $message_text = str_replace('{contact_name}', $contact_name, $message_text);
        $message_text = str_replace('{business_name}', $business_name, $message_text);
        $message_text = str_replace('{invoice_url}', $invoice_url, $message_text);
        $message_text = str_replace('{invoice_number}', $invoice_number, $message_text);

        $message_text = str_replace('{total_amount}', $total_amount, $message_text);
        $message_text = str_replace('{paid_amount}', $paid_amount, $message_text);
        $message_text = str_replace('{due_amount}', $due_amount, $message_text);

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


    // render_price_with_symbol_placement

    public function render_price_with_symbol_placement($amount) {

        if ($this->symbol_placement == 'before') {
            return $this->currency . ' ' . $amount;
        } else {
            return $amount . ' ' . $this->currency;
        }
    }

  
}