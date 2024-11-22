<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Carbon\Carbon;
use App\Models\Purchase;
use App\Models\PaymentPurchase;
use App\Models\PurchaseReturn;
use App\Models\PaymentPurchaseReturns;
use App\Models\PaymentMethod;
use App\Models\Account;
use DB;
use Auth;
use DataTables;
use App\utils\helpers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SuppliersController extends Controller
{

    protected $currency;
    protected $symbol_placement;

    public function __construct()
    {
        $helpers = new helpers();
        $this->currency = $helpers->Get_Currency();
        $this->symbol_placement = $helpers->get_symbol_placement();

    }


    public function index(Request $request)
    {

        $user_auth = auth()->user();
		if ($user_auth->can('suppliers_view_all') || $user_auth->can('suppliers_view_own')){

            return view('suppliers.suppliers_list');

        }
        return abort('403', __('You are not authorized'));

    }



    public function get_suppliers_datatable(Request $request)
    {

        $user_auth = auth()->user();
        if (!$user_auth->can('suppliers_view_all') && !$user_auth->can('suppliers_view_own')){
            return abort('403', __('You are not authorized'));
        }else{

            $columns_order = array( 
                0 => 'id', 
                2 => 'code', 
                3 => 'name',
            );

            $start = $request->input('start');
            $order = 'providers.'.$columns_order[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $providers_data = Provider::where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('suppliers_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })

            // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->input('search.value')}%")
                        ->orWhere('code', 'LIKE', "%{$request->input('search.value')}%")
                        ->orWhere('phone', 'like', "%{$request->input('search.value')}%");
                });
            });

            $totalRows = $providers_data->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $providers = $providers_data
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $data = array();

            foreach ($providers as $provider) {
                $item['id']    = $provider->id;
                $item['code']  = $provider->code;
                $item['name']  = $provider->name;
                $item['phone'] = $provider->phone;
                $item['city']  = $provider->city;

                //total_debt
                $total_debt = 0;

                $item['total_amount'] = DB::table('purchases')
                    ->where('deleted_at', '=', null)
                    ->where('provider_id', $provider->id)
                    ->sum('GrandTotal');

                $item['total_paid'] = DB::table('purchases')
                    ->where('deleted_at', '=', null)
                    ->where('provider_id', $provider->id)
                    ->sum('paid_amount');

                $total_debt =  $item['total_amount'] - $item['total_paid'];
                $item['total_debt'] =  $this->render_price_with_symbol_placement(number_format($total_debt, 2, '.', ','));

                //return due
                $return_due = 0;

                $item['total_amount_return'] = DB::table('purchase_returns')
                    ->where('deleted_at', '=', null)
                    ->where('provider_id', $provider->id)
                    ->sum('GrandTotal');

                $item['total_paid_return'] = DB::table('purchase_returns')
                    ->where('deleted_at', '=', null)
                    ->where('provider_id', $provider->id)
                    ->sum('paid_amount');

                $return_due = $item['total_amount_return'] - $item['total_paid_return'];
                $item['return_due'] = $this->render_price_with_symbol_placement(number_format($return_due, 2, '.', ','));
             
                $item['action'] =  '<div class="dropdown">
                            <button class="btn btn-outline-info btn-rounded dropdown-toggle" id="dropdownMenuButton" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                            .trans('translate.Action').

                            '</button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 34px, 0px);">';
                                
                                //check if user has permission "supplier_details"
                                 if ($user_auth->can('supplier_details')){
                                    $item['action'] .=  ' <a class="dropdown-item" href="/people/suppliers/' .$provider->id.'"> <i class="nav-icon  i-Eye font-weight-bold mr-2"></i> ' .trans('translate.Provider_details').'</a>';
                                }

                                //check if user has permission "suppliers_edit"
                                if ($user_auth->can('suppliers_edit')){
                                    $item['action'] .=  '<a class="dropdown-item edit cursor-pointer" id="' .$provider->id. '"><i class="nav-icon i-Edit font-weight-bold mr-2"></i> ' .trans('translate.Edit_Provider').'</a>';
                                }

                                //check if user has permission "pay_purchase_due"
                                 if ($user_auth->can('pay_purchase_due')){
                                    $item['action'] .=  '<a class="dropdown-item payment_purchase cursor-pointer"  id="' .$provider->id. '" > <i class="nav-icon i-Dollar font-weight-bold mr-2"></i> ' .trans('translate.pay_all_purchase_due_at_a_time').'</a>';
                                }

                                 //check if user has permission "pay_purchase_return_due"
                                 if ($user_auth->can('pay_purchase_return_due')){
                                    $item['action'] .=  '<a class="dropdown-item payment_return cursor-pointer"  id="' .$provider->id. '" > <i class="nav-icon i-Dollar font-weight-bold mr-2"></i> ' .trans('translate.pay_all_purchase_return_due_at_a_time').'</a>';
                                }

                                //check if user has permission "suppliers_delete"
                                if ($user_auth->can('suppliers_delete')){
                                    $item['action'] .=  '<a class="dropdown-item delete cursor-pointer" id="' .$provider->id. '" > <i class="nav-icon i-Close-Window font-weight-bold mr-2"></i> ' .trans('translate.Delete_Provider').'</a>';
                                }
                                $item['action'] .=  '</div>
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
		if ($user_auth->can('suppliers_add')){

            $request->validate([
                'name' => 'required',
            ]);

            Provider::create([
                'user_id'        => $user_auth->id,
                'name' => $request['name'],
                'code' => $this->getNumberOrder(),
                'address' => $request['address'],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'country' => $request['country'],
                'city' => $request['city'],
            ]);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show(Request $request , $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('supplier_details')){
            
            $helpers = new helpers();
            $currency = $helpers->Get_Currency();
          
            $provider = Provider::where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('suppliers_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })->findOrFail($id);

            $client_data = [];
        
            $item['full_name'] = $provider->name;
            $item['code'] = $provider->code;
            $item['phone'] = $provider->phone;
            $item['address'] = $provider->address;

            $total_debt = 0;

            $item['total_purchases'] = DB::table('purchases')
            ->where('deleted_at', '=', null)
            ->where('provider_id', $id)
            ->count();

            $total_amount = DB::table('purchases')
            ->where('deleted_at', '=', null)
            ->where('provider_id', $id)
            ->sum('GrandTotal');

            $total_paid = DB::table('purchases')
            ->where('purchases.deleted_at', '=', null)
            ->where('purchases.provider_id', $id)
            ->sum('paid_amount');

            $total_debt =  $total_amount - $total_paid;

            $item['total_amount'] = $this->render_price_with_symbol_placement(number_format($total_amount, 2, '.', ','));
            $item['total_paid']   = $this->render_price_with_symbol_placement(number_format($total_paid, 2, '.', ','));
            $item['total_debt']   = $this->render_price_with_symbol_placement(number_format($total_debt, 2, '.', ','));

            $supplier_data[] = $item;

            return view('suppliers.details_supplier', [
                'provider_id' => $id,
                'supplier_data' => $supplier_data[0],
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
        $user_auth = auth()->user();
		if ($user_auth->can('suppliers_edit')){

            $supplier = Provider::where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('suppliers_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })->findOrFail($id);
                
            return response()->json([
                'supplier' => $supplier,
            ]);

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
		if ($user_auth->can('suppliers_edit')){

            $request->validate([
                'name'     => 'required|string|max:255',
            ]);

            Provider::whereId($id)->update([
                'name' => $request['name'],
                'address' => $request['address'],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'country' => $request['country'],
                'city' => $request['city'],
            ]);

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
		if ($user_auth->can('suppliers_delete')){

            Provider::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);
            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    
    //----------- get Number Order Of Suppliers-------\\

    public function getNumberOrder()
    {

        $last = DB::table('providers')->latest('id')->first();

        if ($last) {
            $code = $last->code + 1;
        } else {
            $code = 1;
        }
        return $code;
    }

     //------------- get_provider_debt_total -------------\\

    public function get_provider_debt_total($id){

        $user_auth = auth()->user();
		if ($user_auth->can('pay_purchase_due')){

            $total_debt = 0;

            $item['total_amount'] = DB::table('purchases')
                ->where('deleted_at', '=', null)
                ->where('provider_id', $id)
                ->sum('GrandTotal');

            $item['total_paid'] = DB::table('purchases')
                ->where('deleted_at', '=', null)
                ->where('provider_id', $id)
                ->sum('paid_amount');

            $total_debt = $item['total_amount'] - $item['total_paid'];

            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);

            return response()->json([
                'total_debt' => $total_debt,
                'payment_methods' => $payment_methods,
                'accounts' => $accounts,
            ]);

        }
        return abort('403', __('You are not authorized'));
    }

    //------------- providers_pay_due -------------\\

    public function providers_pay_due(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('pay_purchase_due')){

            request()->validate([
                'provider_id'           => 'required',
                'payment_method_id'   => 'required',
            ]);

            if($request['amount'] > 0){
                $provider_purchases_due = Purchase::where('deleted_at', '=', null)
                ->where([
                    ['payment_statut', '!=', 'paid'],
                    ['provider_id', $request->provider_id]
                ])->get();

                    $paid_amount_total = $request->amount;

                    foreach($provider_purchases_due as $key => $provider_purchase){
                        if($paid_amount_total == 0)
                        break;
                        $due = $provider_purchase->GrandTotal  - $provider_purchase->paid_amount;
        
                        if($paid_amount_total >= $due){
                            $amount = $due;
                            $payment_status = 'paid';
                        }else{
                            $amount = $paid_amount_total;
                            $payment_status = 'partial';
                        }
        
                        $payment_purchase = new PaymentPurchase();
                        $payment_purchase->purchase_id = $provider_purchase->id;
                        $payment_purchase->account_id =  $request['account_id']?$request['account_id']:NULL;
                        $payment_purchase->Ref = $this->generate_random_code_payment();
                        $payment_purchase->date =  $request['date'];
                        $payment_purchase->payment_method_id = $request['payment_method_id'];
                        $payment_purchase->montant = $amount;
                        $payment_purchase->change = 0;
                        $payment_purchase->notes = $request['notes'];
                        $payment_purchase->user_id = Auth::user()->id;
                        $payment_purchase->save();

                        $account = Account::where('id', $request['account_id'])->exists();

                        if ($account) {
                            // Account exists, perform the update
                            $account = Account::find($request['account_id']);
                            $account->update([
                                'initial_balance' => $account->initial_balance - $amount,
                            ]);
                        }
        
                        $provider_purchase->paid_amount += $amount;
                        $provider_purchase->payment_statut = $payment_status;
                        $provider_purchase->save();
        
                        $paid_amount_total -= $amount;

                    }
            }
            
            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));

    }

     //------------- get_provider_debt_return_total -------------\\

    public function get_provider_debt_return_total($id){

        $user_auth = auth()->user();
		if ($user_auth->can('pay_purchase_return_due')){

            $return_due = 0;

            $item['total_amount_return'] = DB::table('purchase_returns')
                ->where('deleted_at', '=', null)
                ->where('provider_id', $id)
                ->sum('GrandTotal');

            $item['total_paid_return'] = DB::table('purchase_returns')
                ->where('deleted_at', '=', null)
                ->where('provider_id', $id)
                ->sum('paid_amount');

            $return_due = $item['total_amount_return'] - $item['total_paid_return'];

            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);


            return response()->json([
                'return_due' => $return_due,
                'payment_methods' => $payment_methods,
                'accounts' => $accounts,
            ]);

        }
        return abort('403', __('You are not authorized'));
    }


     //------------- providers_pay_return_due -------------\\

     public function providers_pay_return_due(Request $request)
     {
        $user_auth = auth()->user();
		if ($user_auth->can('pay_purchase_return_due')){

            request()->validate([
                'provider_id'           => 'required',
                'payment_method_id'   => 'required',
            ]);

            if($request['montant'] > 0){
                $supplier_purchase_return_due = PurchaseReturn::where('deleted_at', '=', null)
                ->where([
                    ['payment_statut', '!=', 'paid'],
                    ['provider_id', $request->provider_id]
                ])->get();
    
                $paid_amount_total = $request->montant;
    
                foreach($supplier_purchase_return_due as $key => $supplier_purchase_return){
                    if($paid_amount_total == 0)
                    break;
                    $due = $supplier_purchase_return->GrandTotal  - $supplier_purchase_return->paid_amount;
    
                    if($paid_amount_total >= $due){
                        $amount = $due;
                        $payment_status = 'paid';
                    }else{
                        $amount = $paid_amount_total;
                        $payment_status = 'partial';
                    }
    
                    $payment_purchase_return = new PaymentPurchaseReturns();
                    $payment_purchase_return->purchase_return_id = $supplier_purchase_return->id;
                    $payment_purchase_return->account_id =  $request['account_id']?$request['account_id']:NULL;
                    $payment_purchase_return->Ref = $this->generate_random_code_payment_return();
                    $payment_purchase_return->date =  $request['date'];
                    $payment_purchase_return->payment_method_id = $request['payment_method_id'];
                    $payment_purchase_return->montant = $amount;
                    $payment_purchase_return->change = 0;
                    $payment_purchase_return->notes = $request['notes'];
                    $payment_purchase_return->user_id = Auth::user()->id;
                    $payment_purchase_return->save();

                    $account = Account::where('id', $request['account_id'])->exists();

                    if ($account) {
                        // Account exists, perform the update
                        $account = Account::find($request['account_id']);
                        $account->update([
                            'initial_balance' => $account->initial_balance + $amount,
                        ]);
                    }
    
                    $supplier_purchase_return->paid_amount += $amount;
                    $supplier_purchase_return->payment_statut = $payment_status;
                    $supplier_purchase_return->save();
    
                    $paid_amount_total -= $amount;
                }
            }
            
            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
 
     }

    // generate_random_code_payment
    public function generate_random_code_payment()
    {
        $gen_code = 'INV/PO-' . date("Ymd") . '-'. substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);

        if (PaymentPurchase::where('Ref', $gen_code)->exists()) {
            $this->generate_random_code_payment();
        } else {
            return $gen_code;
        }
        
    }

     // generate_random_code_payment_return
     public function generate_random_code_payment_return()
     {
         $gen_code = 'INV/RP-' . date("Ymd") . '-'. substr(number_format(time() * mt_rand(), 0, '', ''), 0, 6);
 
         if (PaymentPurchaseReturns::where('Ref', $gen_code)->exists()) {
             $this->generate_random_code_payment_return();
         } else {
             return $gen_code;
         }
         
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
