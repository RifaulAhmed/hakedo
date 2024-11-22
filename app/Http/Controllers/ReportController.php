<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Expense;
use App\Models\PaymentDebt;
use App\Models\PaymentMethod;
use App\Models\Account;
use App\Models\Driver;
use App\Models\product_warehouse;
use App\Models\AdjustmentDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Purchase;
use App\Models\SaleReturn;
use App\Models\PurchaseReturn;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
use App\Models\Provider;
use App\Models\PaymentSale;
use App\Models\PaymentPurchase;
use App\Models\PaymentSaleReturns;
use App\Models\PaymentPurchaseReturns;
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

class ReportController extends Controller
{

    protected $currency;
    protected $symbol_placement;

    public function __construct()
    {
        $helpers = new helpers();
        $this->currency = $helpers->Get_Currency();
        $this->symbol_placement = $helpers->get_symbol_placement();

    }

 
    //-----report_facture_unpaid-------\\
    public function report_facture_unpaid(Request $request)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('facture_impaye')){

            $clients = client::where('deleted_at', '=', null)->get(['id', 'username']);

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
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

                $param = array(
                    0 => '=',
                    1 => '=',
                );
                $columns = array(
                    0 => 'client_id',
                    1 => 'warehouse_id',
                );
                
                
                $end_date_default = Carbon::now()->addYear(10)->format('Y-m-d');
                $start_date_default = Carbon::now()->subYear(10)->format('Y-m-d');
                
                $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
                $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

                $data = Sale::where('deleted_at', '=', null)
                ->where('payment_statut', '!=', 'paid')
                
                ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                    if ($warehouse_id !== 0) {
                        return $query->where('warehouse_id', $warehouse_id);
                    }else{
                        return $query->whereIn('warehouse_id', $array_warehouses_id);
                    }
                })

                ->with('client', 'user','warehouse')
                ->orderBy('id', 'desc');

                //Multiple Filter
                $report_Filtred = $helpers->filter($data, $columns, $param, $request)->get();

                return Datatables::of($report_Filtred)
                ->setRowId(function($report_Filtred)
                {
                    return $report_Filtred->id;
                })

                ->addColumn('date', function($row){
                    return Carbon::parse($row->date)->format('d-m-Y H:i');
                })

                ->addColumn('Ref', function($row){
                    return $row->Ref;
                })

                ->addColumn('created_by', function($row){
                    return $row->user->username;
                })

                
                ->addColumn('client_name', function($row){
                    return $row->client->username;
                })

                ->addColumn('warehouse_name', function($row){
                    return $row->warehouse->name;
                })
                
                ->addColumn('GrandTotal', function($row){
                    return number_format($row->GrandTotal, 2, '.', ',');
                })
                ->addColumn('paid_amount', function($row){
                    return number_format($row->paid_amount, 2, '.', ',');
                })
                ->addColumn('due', function($row){
                    return number_format($row['GrandTotal'] - $row['paid_amount'], 2, '.', ',');
                })

                ->addColumn('payment_status', function($row){
                    if($row->payment_statut == 'paid'){
                        $span = '<span class="badge badge-outline-success">'.trans('translate.Paid').'</span>';
                    }else if($row->payment_statut == 'partial'){
                        $span = '<span class="badge badge-outline-info">'.trans('translate.Partial').'</span>';
                    }else{
                        $span = '<span class="badge badge-outline-warning">'.trans('translate.Unpaid').'</span>';
                    }
                    return $span;
                })

            
                ->rawColumns(['payment_status'])
                ->make(true);
            }

            return view('reports.report_facture_unpaid',compact('clients','warehouses'));
        }
        return abort('403', __('You are not authorized'));
    }



    public function report_stock_page(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('report_inventaire')){

            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            }else{
                $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
            }

            return view('reports.report_stock',compact('warehouses'));
        }
        return abort('403', __('You are not authorized'));
    }


    //------------ report_stock_datatable-----------\\

    public function get_report_stock_datatable (Request $request)
    {

       $user_auth = auth()->user();
       if ($user_auth->can('report_inventaire')){

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

        
            if(empty($request->warehouse_id)){
                $warehouse_id = 0;
                $warehouse_name = 'All';
            }else{
                $warehouse_id = $request->warehouse_id;
                $warehouse = Warehouse::where('deleted_at', '=', null)->findOrFail($warehouse_id);
                $warehouse_name = $warehouse->name;
            }

            $start = $request->input('start');

            $all_products = Product::where('deleted_at', '=', null)
            ->with('category', 'unit')
            ->orderBy('id', 'desc')
            
            // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search.value'), function ($query) use ($request) {
                    return $query->where('products.name', 'LIKE', "%{$request->input('search.value')}%")
                        ->orWhere('products.code', 'LIKE', "%{$request->input('search.value')}%");
                });
            });

            $totalRows = $all_products->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $products = $all_products
            ->offset($start)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

            $data = array();

            foreach ($products as $product) {
                $item['id']               = $product->id;
                $item['code']             = $product->code;
                $item['name']             = $product->name;
                $item['warehouse_name']   = $warehouse_name;

                $item['unit_name']   = $product['unit']?$product['unit']->ShortName:'';


                $current_stock = product_warehouse::where('product_id', $product->id)
                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                ->where('deleted_at', '=', null)
                ->sum('qte');

                $item['current_stock'] = $product->type != 'is_service'?$current_stock.' '.$item['unit_name'] :'---';
                

                $remaining_quantity = 0;
                $total_cogs = 0;
                
                $purchase_details = PurchaseDetail::where('product_id', $product->id)
                    ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                    ->orderBy('purchases.date', 'asc')
                    ->get();
                
                $sold_quantity = SaleDetail::where('product_id', $product->id)->sum('quantity');
                
                $purchased_qty = PurchaseDetail::where('product_id', $product->id)->sum('quantity');

                foreach ($purchase_details as $purchase_detail) {
                    $remaining_quantity += ($purchase_detail->quantity - $sold_quantity);
                    if ($remaining_quantity > 0 && $current_stock > 0) {
                        if ($remaining_quantity > $current_stock) {
                            $total_cogs += $current_stock * $purchase_detail->cost;
                            $current_stock = 0;
                            $remaining_quantity -= $current_stock;
                        } else {
                            $total_cogs += $remaining_quantity * $purchase_detail->cost;
                            $current_stock -= $remaining_quantity;
                            $remaining_quantity = 0;
                        }
                    }
                    $sold_quantity = 0;
                }

                $item['total_current_stock'] =  $product->type !='is_service'?$this->render_price_with_symbol_placement(number_format($total_cogs, 2, '.', ',')):0;

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

    
    //------------ report_product -----------\\

    public function report_product(Request $request)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('report_products')){

        if($user_auth->is_all_warehouses){
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        }else{
            $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
        }
        
            return view('reports.report_product', compact('warehouses'));

        }
        return abort('403', __('You are not authorized'));

    }

    //------------ get_report_product_datatable-----------\\

    public function get_report_product_datatable(Request $request)
    {
        $user_auth = auth()->user();
        if (!$user_auth->can('report_products')){
            return abort('403', __('You are not authorized'));
        }else{

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }
                        
            if(empty($request->warehouse_id)){
                $warehouse_id = 0;
                $warehouse_name = 'All';
            }else{
                $warehouse_id = $request->warehouse_id;
                $warehouse = Warehouse::where('deleted_at', '=', null)->findOrFail($warehouse_id);
                $warehouse_name = $warehouse->name;
            }

            $end_date_default = Carbon::now()->addYear(10)->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear(10)->format('Y-m-d');
            
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

            $columns_order = array( 
                0 => 'code', 
                1 => 'name',
            );

            $start = $request->input('start');
            $order = 'products.'.$columns_order[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
         
            $products_Filtred = Product::with(['category' => function($query){
                $query->select('id','name');
            }])->select('id', 'category_id', 'products.name as product_name','code','cost', 'is_variant','unit_id','type')


            ->where('deleted_at', '=', null)

                // Search With Multiple Param
                ->where(function ($query) use ($request) {
                return $query->when($request->filled('search.value'), function ($query) use ($request) {
                    return $query->where('products.name', 'LIKE', "%{$request->input('search.value')}%")
                        ->orWhere('products.code', 'LIKE', "%{$request->input('search.value')}%");
                });
            });

            $totalRows = $products_Filtred->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $products = $products_Filtred
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $product_details = [];
            $total_sales = 0;
            

            foreach ($products as $product) {
    
                if($product->type == 'is_variant') {
                    $variant_id_all = ProductVariant::where('product_id', $product->id)->where('deleted_at', '=', null)->pluck('id');
                    
                    foreach ($variant_id_all as $key => $variant_id) {
                        $variant_data = ProductVariant::select('name','code')->find($variant_id);
                        
                        $nestedData['id'] = $product->id;
                        $nestedData['name'] = ' [' . $variant_data->name . '] ' . $product->product_name;
                        $nestedData['code'] = $variant_data->code;
                        $nestedData['warehouse_name'] = $warehouse_name;
                        $nestedData['category'] = $product->category->name;
                        $nestedData['type'] = 'Variable';

                        $sold_amount = SaleDetail::with('sale')->where([
                                ['product_id', $product->id],
                                ['product_variant_id', $variant_id]
                            ])

                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })

                        ->whereDate('date', '>=', $start_date)
                        ->whereDate('date', '<=', $end_date)
                        ->sum('total');

                        $nestedData['sold_amount'] = $this->render_price_with_symbol_placement(number_format($sold_amount, 2, '.', ','));
    
                        $lims_product_sale_data = SaleDetail::select('sale_unit_id', 'quantity')->with('sale')
                        ->where([
                            ['product_id', $product->id],
                            ['product_variant_id', $variant_id]
                        ])

                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })
                        ->whereDate('date', '>=', $start_date)
                        ->whereDate('date', '<=', $end_date)
                        ->get();
    
                        $sold_qty = 0;
                        if(count($lims_product_sale_data)) {
                            foreach ($lims_product_sale_data as $product_sale) {
                                $unit =  Unit::find($product_sale->sale_unit_id);
                                if($unit->operator == '*'){
                                    $sold_qty += $product_sale->quantity * $unit->operator_value;
                                }
                                elseif($unit->operator == '/'){
                                    $sold_qty += $product_sale->quantity / $unit->operator_value;
                                }
                            }
                        }
                        $nestedData['sold_qty'] = number_format($sold_qty, 2, '.', '');

                    //qty_purchased

                    $purchased_amount = PurchaseDetail::with('purchase')->where([
                            ['product_id', $product->id],
                            ['product_variant_id', $variant_id]
                        ])

                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })

                        ->whereHas('purchase', function ($q) use ($request , $start_date , $end_date) {
                            return $q->whereDate('date', '>=', $start_date)->whereDate('date', '<=', $end_date);
                            })
                    ->sum('total');

                    $nestedData['purchased_amount'] = $this->render_price_with_symbol_placement(number_format($purchased_amount, 2, '.', ','));

                    $lims_product_purchase_data = PurchaseDetail::select('purchase_unit_id', 'quantity')->with('purchase')
                    ->where([
                            ['product_id', $product->id],
                            ['product_variant_id', $variant_id]
                        ])

                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })

                        ->whereHas('purchase', function ($q) use ($request , $start_date , $end_date) {
                            return $q->whereDate('date', '>=', $start_date)->whereDate('date', '<=', $end_date);
                            })
                        ->get();

                        $purchased_qty = 0;
                        if(count($lims_product_purchase_data)) {
                            foreach ($lims_product_purchase_data as $product_purchase) {
                                $unit =  Unit::find($product_purchase->purchase_unit_id);
                                if($unit->operator == '*'){
                                    $purchased_qty += $product_purchase->quantity * $unit->operator_value;
                                }
                                elseif($unit->operator == '/'){
                                    $purchased_qty += $product_purchase->quantity / $unit->operator_value;
                                }
                            }
                        }
                        $nestedData['purchased_qty'] = number_format($purchased_qty, 2, '.', '');
    
                        $product_details[] = $nestedData;
                        
                    }
    
                }else {

                    if( $product->type == 'is_service'){
                        
                        $nestedData['id'] = $product->id;
                        $nestedData['name'] = $product->product_name;
                        $nestedData['code'] = $product->code;
                        $nestedData['warehouse_name'] = $warehouse_name;
                        $nestedData['category'] = $product->category->name;
                        $nestedData['type'] = 'Service';

                        $sold_amount = SaleDetail::with('sale')
                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })
                        ->where('product_id', $product->id)
                        ->whereDate('date', '>=', $start_date)
                        ->whereDate('date', '<=', $end_date)
                        ->sum('total');
        
                        $nestedData['sold_amount'] = $this->render_price_with_symbol_placement(number_format($sold_amount, 2, '.', ','));


                        $lims_product_sale_data = SaleDetail::select('quantity')->with('sale')
                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })
                        ->where('product_id', $product->id)
                        ->whereDate('date', '>=', $start_date)
                        ->whereDate('date', '<=', $end_date)
                        ->get();
        
                        $sold_qty = 0;
                        if(count($lims_product_sale_data)) {
                            foreach ($lims_product_sale_data as $product_sale) {
                                $sold_qty += $product_sale->quantity;
                            }
                        }
                        
                        $nestedData['sold_qty'] = $sold_qty;

                        $nestedData['purchased_amount'] = '0';
                        $nestedData['purchased_qty']    = '---';

                        $product_details[] = $nestedData;

                    }else{

                        $nestedData['id'] = $product->id;
                        $nestedData['name'] = $product->product_name;
                        $nestedData['code'] = $product->code;
                        $nestedData['warehouse_name'] = $warehouse_name;
                        $nestedData['category'] = $product->category->name;
                        $nestedData['type'] = 'Standard';

                        $sold_amount = SaleDetail::with('sale')->where('product_id', $product->id)

                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })

                        ->whereDate('date', '>=', $start_date)
                        ->whereDate('date', '<=', $end_date)
                        ->sum('total');

                        $nestedData['sold_amount'] = $this->render_price_with_symbol_placement(number_format($sold_amount, 2, '.', ','));
        
                        $lims_product_sale_data = SaleDetail::select('sale_unit_id', 'quantity')->with('sale')
                        ->where('product_id', $product->id)

                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })

                        ->whereDate('date', '>=', $start_date)
                        ->whereDate('date', '<=', $end_date)
                        ->get();



        
                        $sold_qty = 0;
                        if(count($lims_product_sale_data)) {
                            foreach ($lims_product_sale_data as $product_sale) {
                                $unit =  Unit::find($product_sale->sale_unit_id);
        
                                if($unit->operator == '*'){
                                    $sold_qty += $product_sale->quantity * $unit->operator_value;
                                }
                                elseif($unit->operator == '/'){
                                    $sold_qty += $product_sale->quantity / $unit->operator_value;
                                }
                                
                            }
                        }
                    
                        
                        $nestedData['sold_qty'] = number_format($sold_qty, 2, '.', '');

                        //purchased qty

                        $purchased_amount = PurchaseDetail::with('purchase')->where('product_id', $product->id)

                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })

                        ->whereHas('purchase', function ($q) use ($request , $start_date , $end_date) {
                            return $q->whereDate('date', '>=', $start_date)->whereDate('date', '<=', $end_date);
                        })
                        ->sum('total');

                        $nestedData['purchased_amount'] = $this->render_price_with_symbol_placement(number_format($purchased_amount, 2, '.', ','));
                       
                        $lims_product_purchase_data = PurchaseDetail::select('purchase_unit_id', 'quantity')
                        ->with('purchase')
                        ->where('product_id', $product->id)

                        ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                            if ($warehouse_id !== 0) {
                                return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->where('warehouse_id', $warehouse_id);
                                });
                            }else{
                                return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                                    $q->whereIn('warehouse_id', $array_warehouses_id);
                                });

                            }
                        })

                        ->whereHas('purchase', function ($q) use ($request , $start_date , $end_date) {
                            return $q->whereDate('date', '>=', $start_date)->whereDate('date', '<=', $end_date);
                        })
                        
                        ->get();
        
                        $purchased_qty = 0;
                        if(count($lims_product_purchase_data)) {
                            foreach ($lims_product_purchase_data as $product_purchase) {
                                $unit =  Unit::find($product_purchase->purchase_unit_id);
        
                                if($unit->operator == '*'){
                                    $purchased_qty += $product_purchase->quantity * $unit->operator_value;
                                }
                                elseif($unit->operator == '/'){
                                    $purchased_qty += $product_purchase->quantity / $unit->operator_value;
                                }
                                
                            }
                        }
                    
                        
                        $nestedData['purchased_qty'] = number_format($purchased_qty, 2, '.', '');
        
                        $product_details[] = $nestedData;
                    }
                }
            }

            $json_data = array(
                "draw"            => intval($request->input('draw')),  
                "recordsTotal"    => intval($totalRows),  
                "recordsFiltered" => intval($totalFiltered), 
                "data"            => $product_details   
            );
                
            echo json_encode($json_data);
        }

    }
       
  

    //------------ report_clients-----------\\

    public function report_clients(Request $request)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('report_clients')){

            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }

            return view('reports.report_clients', compact('warehouses'));

        }
        return abort('403', __('You are not authorized'));

    }


    //------------ report_clients-----------\\

    public function get_report_clients_datatable(Request $request)
    {

        $user_auth = auth()->user();
        if (!$user_auth->can('report_clients')){
            return abort('403', __('You are not authorized'));
        }else{

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

        
            if(empty($request->warehouse_id)){
                $warehouse_id = 0;
                $warehouse_name = 'All';
            }else{
                $warehouse_id = $request->warehouse_id;
                $warehouse = Warehouse::where('deleted_at', '=', null)->findOrFail($warehouse_id);
                $warehouse_name = $warehouse->name;
            }

            $start = $request->input('start');
            $data = array();

            $end_date_default = Carbon::now()->addYear(10)->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear(10)->format('Y-m-d');
            
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;


            $columns_order = array( 
                0 => 'id', 
                1 => 'code', 
                2 => 'username',
            );

            $start = $request->input('start');
            $order = 'clients.'.$columns_order[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $clients_data = Client::where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('client_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })
 
            // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('username', 'LIKE', "%{$request->input('search.value')}%")
                        ->orWhere('code', 'LIKE', "%{$request->input('search.value')}%");
                });
            });

            $totalRows = $clients_data->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $clients = $clients_data
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();


            foreach ($clients as $client) {

                $item['id']       = $client->id;
                $item['code']     = $client->code;
                $item['username'] = $client->username;

                $item['total_sales'] = DB::table('sales')
                    ->where('deleted_at', '=', null)
                    ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where('client_id', $client->id)
                    ->count();
        
                $total_amount = DB::table('sales')
                    ->where('deleted_at', '=', null)
                    ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where('client_id', $client->id)
                    ->sum('GrandTotal');

                $item['total_amount']  =  $this->render_price_with_symbol_placement(number_format($total_amount, 2, '.', ','));

                //---------------
        
                $total_paid = DB::table('sales')
                    ->where('sales.deleted_at', '=', null)
                    ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where('sales.client_id', $client->id)
                    ->sum('paid_amount');

                $item['total_paid'] =  $this->render_price_with_symbol_placement(number_format($total_paid, 2, '.', ','));
        
                //---------------
                $due = $total_amount - $total_paid;
                $item['due'] = $this->render_price_with_symbol_placement(number_format($due, 2, '.', ','));

                //--------------
                $total_amount_return = DB::table('sale_returns')
                    ->where('deleted_at', '=', null)
                    ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where('client_id', $client->id)
                    ->sum('GrandTotal');

                $item['total_amount_return'] = $this->render_price_with_symbol_placement(number_format($total_amount_return, 2, '.', ','));

                //--------------

                $total_paid_return = DB::table('sale_returns')
                    ->where('sale_returns.deleted_at', '=', null)
                    ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('sale_returns.warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('sale_returns.warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where('sale_returns.client_id', $client->id)
                    ->sum('paid_amount');

                $item['total_paid_return'] = $this->render_price_with_symbol_placement(number_format($total_paid_return, 2, '.', ','));

                //--------------

                $item['return_due'] = $this->render_price_with_symbol_placement(number_format($total_amount_return - $total_paid_return, 2, '.', ','));
                    
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



      //------------ report_providers-----------\\

      public function report_providers(Request $request)
      {
          $user_auth = auth()->user();
          if ($user_auth->can('report_fournisseurs')){
  
              if($user_auth->is_all_warehouses){
                  $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
                  $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
              }else{
                  $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                  $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
              }
  
              return view('reports.report_providers', compact('warehouses'));
  
          }
          return abort('403', __('You are not authorized'));
  
      }



    //------------ get_report_providers_datatable-----------\\

    public function get_report_providers_datatable(Request $request)
    {
        $user_auth = auth()->user();
        if (!$user_auth->can('report_fournisseurs')){
            return abort('403', __('You are not authorized'));
        }else{

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

        
            if(empty($request->warehouse_id)){
                $warehouse_id = 0;
                $warehouse_name = 'All';
            }else{
                $warehouse_id = $request->warehouse_id;
                $warehouse = Warehouse::where('deleted_at', '=', null)->findOrFail($warehouse_id);
                $warehouse_name = $warehouse->name;
            }

            $start = $request->input('start');
            $data = array();

            $end_date_default = Carbon::now()->addYear(10)->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear(10)->format('Y-m-d');
            
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;


            $columns_order = array( 
                0 => 'id', 
                1 => 'code', 
                2 => 'name',
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
                        ->orWhere('code', 'LIKE', "%{$request->input('search.value')}%");
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

                
            foreach ($providers as $provider) {
                $item['id'] = $provider->id;
                $item['name'] = $provider->name;
                $item['code'] = $provider->code;

                $item['total_purchase'] = DB::table('purchases')
                    ->where('deleted_at', '=', null)
                    ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where('provider_id', $provider->id)
                    ->count();

                //---------------
        
                $total_amount = DB::table('purchases')
                    ->where('deleted_at', '=', null)
                    ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where('provider_id', $provider->id)
                    ->sum('GrandTotal');

                $item['total_amount']  =  $this->render_price_with_symbol_placement(number_format($total_amount, 2, '.', ','));

                //---------------
    
                $total_paid = DB::table('purchases')
                    ->where('purchases.deleted_at', '=', null)
                    ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where('purchases.provider_id', $provider->id)
                    ->sum('paid_amount');

                $item['total_paid']  =  $this->render_price_with_symbol_placement(number_format($total_paid, 2, '.', ','));

                //-----------------
    
                $item['due']  =  $this->render_price_with_symbol_placement(number_format($total_amount - $total_paid, 2, '.', ','));
        
                //-----------------

                $total_amount_return = DB::table('purchase_returns')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                    if ($warehouse_id !== 0) {
                        return $query->where('warehouse_id', $warehouse_id);
                    }else{
                        return $query->whereIn('warehouse_id', $array_warehouses_id);
                    }
                })
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)
                ->where('provider_id', $provider->id)
                ->sum('GrandTotal');

                $item['total_amount_return']  =  $this->render_price_with_symbol_placement(number_format($total_amount_return, 2, '.', ','));

                //-----------------
    
                $total_paid_return = DB::table('purchase_returns')
                    ->where('deleted_at', '=', null)
                    ->where(function ($query) use ($request , $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where('provider_id', $provider->id)
                    ->sum('paid_amount');

                $item['total_paid_return']  =  $this->render_price_with_symbol_placement(number_format($total_paid_return, 2, '.', ','));

                //-----------------
                $item['return_due']  =  $this->render_price_with_symbol_placement(number_format($total_amount_return - $total_paid_return, 2, '.', ','));

                //-----------------
    
    
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
    


    //-----sale report_monthly_sale-------\\
    public function report_monthly_sale(Request $request)
    {

        $user_auth = auth()->user();
		if ($user_auth->can('sale_reports')){

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

               //current year
                $year = Carbon::now()->year;

                //variable to store each order count as array.
                $sales_count = [];

                //Looping through the month array to get count for each month in the provided year
                for($i = 1; $i <= 12; $i++){

                    $item['date'] = $year.'/'.$i;
                    $item['total_sales'] = Sale::where('deleted_at', '=', null)
                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                    ->whereYear('date', $year)
                    ->whereMonth('date', $i)
                    ->count();

                    $sales_count[] = $item;
                }

                return Datatables::of($sales_count)
              
                ->addColumn('date', function($row){
                    return $row['date'];
                })

                ->addColumn('total_sales', function($row){
                    return $row['total_sales'];
                })

                ->make(true);
            }

             //current year
             $year = Carbon::now()->year;

             //variable to store each order count as array.
             $count_sales_chart = [];

             //Looping through the month array to get count for each month in the provided year
             for($i = 1; $i <= 12; $i++){

                 $item['total_sales'] = Sale::where('deleted_at', '=', null)
                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                     ->whereYear('date', $year)
                     ->whereMonth('date', $i)
                     ->count();

                 $count_sales_chart[] = $item['total_sales'];
             }

            return view('reports.report_monthly_sale', compact('count_sales_chart','warehouses'));
        }
        return abort('403', __('You are not authorized'));
    }

    //-----sale report_monthly_sale-------\\
    public function filter_report_monthly_sale(Request $request, $warehouse)
    {

        $user_auth = auth()->user();
		if ($user_auth->can('sale_reports')){


            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            if(empty($warehouse)){
                $warehouse_id = 0;
            }else{
                $warehouse_id = $warehouse;
            }


             //current year
             $year = Carbon::now()->year;

             //variable to store each order count as array.
             $count_sales_chart = [];

             //Looping through the month array to get count for each month in the provided year
             for($i = 1; $i <= 12; $i++){

                 $item['total_sales'] = Sale::where('deleted_at', '=', null)
                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                     ->whereYear('date', $year)
                     ->whereMonth('date', $i)
                     ->count();

                 $count_sales_chart[] = $item['total_sales'];
             }

             return response()->json(['count_sales_chart' => $count_sales_chart]);

        }
        return abort('403', __('You are not authorized'));
    }



     //-----sale report-------\\
     public function sale_report(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('sale_reports')){

            $clients = client::where('deleted_at', '=', null)->get(['id', 'username']);

            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            }else{
                $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
            }

            return view('reports.sale_report',compact('clients','warehouses'));

        }
        return abort('403', __('You are not authorized'));
    }

    


     //----- get_report_sales_datatable -------\\
     public function get_report_sales_datatable(Request $request)
     {
        $user_auth = auth()->user();
        if (!$user_auth->can('sale_reports')){
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
                2 => 'like',
                3 => '=',
            );
            $columns = array(
                0 => 'Ref',
                1 => 'client_id',
                2 => 'payment_statut',
                3 => 'warehouse_id',
            );

            $columns_order = array( 
                0 => 'id', 
                1 => 'date', 
                2 => 'Ref', 
            );

            $start = $request->input('start');
            $order = 'sales.'.$columns_order[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
 
            $end_date_default = Carbon::now()->addYear(10)->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear(10)->format('Y-m-d');
            
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

            $sales_data = Sale::where('deleted_at', '=', null)
            // ->with('client')
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            
            ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                if ($warehouse_id !== 0) {
                    return $query->where('warehouse_id', $warehouse_id);
                }else{
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
                }
            })
            
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('sales_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            });

            //Multiple Filter
            $sales_Filtred = $helpers->filter($sales_data, $columns, $param, $request)

             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->input('search.value')}%")
                        ->orWhere('payment_statut', 'like', "%{$request->input('search.value')}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('username', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        });
                });
            });

            $totalRows = $sales_Filtred->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $sales = $sales_Filtred
            ->with('warehouse','user','client')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $data = array();

            foreach ($sales as $sale) {

                $item['id']             = $sale->id;
                $item['date']           = Carbon::parse($sale->date)->format('d-m-Y H:i');
                $item['Ref']            = $sale->Ref;
                $item['created_by']     = $sale->user->username;
                $item['warehouse_name'] = $sale->warehouse->name;
                $item['client_name']    = $sale->client->username;
                $item['GrandTotal']     = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal, 2, '.', ','));
                $item['paid_amount']    = $this->render_price_with_symbol_placement(number_format($sale->paid_amount, 2, '.', ','));
                $item['due']            = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal - $sale->paid_amount, 2, '.', ','));
              

                //payment_status
                if($sale->payment_statut == 'paid'){
                    $item['payment_status'] = '<span class="badge badge-outline-success">'.trans('translate.Paid').'</span>';
                }else if($sale->payment_statut == 'partial'){
                    $item['payment_status'] = '<span class="badge badge-outline-info">'.trans('translate.Partial').'</span>';
                }else{
                    $item['payment_status'] = '<span class="badge badge-outline-warning">'.trans('translate.Unpaid').'</span>';
                }

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
 

    //-----Purchase report-------\\
    public function purchase_report(Request $request)
    {

        $user_auth = auth()->user();
		if ($user_auth->can('purchase_reports')){

            $suppliers = provider::where('deleted_at', '=', null)->get(['id', 'name']);

            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }
          
            return view('reports.purchase_report',compact('suppliers','warehouses'));

        }
        return abort('403', __('You are not authorized'));

    }


    //-----get_report_Purchases_datatable report-------\\

    public function get_report_Purchases_datatable(Request $request)
    {
        $user_auth = auth()->user();
        if (!$user_auth->can('purchase_reports')){
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
                $item['Ref']            = $purchase->Ref;
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


    //----- report_monthly_purchase-------\\
    public function report_monthly_purchase(Request $request)
    {

        $user_auth = auth()->user();
		if ($user_auth->can('purchase_reports')){

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

               //current year
                $year = Carbon::now()->year;

                //variable to store each order count as array.
                $purchases_count = [];

                //Looping through the month array to get count for each month in the provided year
                for($i = 1; $i <= 12; $i++){

                    $item['date'] = $year.'/'.$i;
                    $item['total_purchases'] = Purchase::where('deleted_at', '=', null)
                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                        ->whereYear('date', $year)
                        ->whereMonth('date', $i)
                        ->count();

                    $purchases_count[] = $item;
                }

                return Datatables::of($purchases_count)
              
                ->addColumn('date', function($row){
                    return $row['date'];
                })

                ->addColumn('total_purchases', function($row){
                    return $row['total_purchases'];
                })

                ->make(true);
            }

             //current year
             $year = Carbon::now()->year;

             //variable to store each order count as array.
             $count_purchases_chart = [];

             //Looping through the month array to get count for each month in the provided year
             for($i = 1; $i <= 12; $i++){

                 $item['total_purchases'] = Purchase::where('deleted_at', '=', null)
                 ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                    if ($warehouse_id !== 0) {
                        return $query->where('warehouse_id', $warehouse_id);
                    }else{
                        return $query->whereIn('warehouse_id', $array_warehouses_id);
                    }
                })
                     ->whereYear('date', $year)
                     ->whereMonth('date', $i)
                     ->count();

                 $count_purchases_chart[] = $item['total_purchases'];
             }

            return view('reports.report_monthly_purchase', compact('count_purchases_chart','warehouses'));
        }
        return abort('403', __('You are not authorized'));
    }



    //----- filter_report_monthly_purchase-------\\
    public function filter_report_monthly_purchase(Request $request, $warehouse)
    {

        $user_auth = auth()->user();
        if ($user_auth->can('purchase_reports')){

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            if(empty($warehouse)){
                $warehouse_id = 0;
            }else{
                $warehouse_id = $warehouse;
            }

                //current year
                $year = Carbon::now()->year;

                //variable to store each order count as array.
                $count_purchases_chart = [];

                //Looping through the month array to get count for each month in the provided year
                for($i = 1; $i <= 12; $i++){

                    $item['total_purchases'] = Purchase::where('deleted_at', '=', null)
                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        }else{
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })
                        ->whereYear('date', $year)
                        ->whereMonth('date', $i)
                        ->count();

                    $count_purchases_chart[] = $item['total_purchases'];
                }

                return response()->json(['count_purchases_chart' => $count_purchases_chart]);

        }
        return abort('403', __('You are not authorized'));
    }


     //-----payment_sale_report-------\\
     public function payment_sale_report(Request $request)
     {
 
         $user_auth = auth()->user();
         if ($user_auth->can('payment_sale_reports')){
 
            $clients  = Client::where('deleted_at', '=', null)->get(['id', 'username']);
            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);

             if($user_auth->is_all_warehouses){
                 $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
                 $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
             }else{
                 $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                 $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
             }
           
             return view('reports.payment_sale',compact('clients','warehouses','payment_methods','accounts'));
 
         }
         return abort('403', __('You are not authorized'));
 
     }



    //-----get_payment_sale_reports_datatable-------\\

    public function get_payment_sale_reports_datatable(Request $request)
    {
        $user_auth = auth()->user();
        if (!$user_auth->can('payment_sale_reports')){
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
            $param = array(0 => 'like', 1 => '=', 2 => '=');
            $columns = array(0 => 'Ref', 1 => 'payment_method_id', 2 => 'account_id');

            $end_date_default = Carbon::now()->addYear(10)->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear(10)->format('Y-m-d');
            
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

            $start = $request->input('start');

            $data = PaymentSale::where('deleted_at', '=', null)
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->with('sale.client','sale.warehouse','payment_method','account')

            ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                if ($warehouse_id !== 0) {
                    return $query->whereHas('sale.warehouse', function ($q) use ($warehouse_id) {
                        $q->where('warehouse_id', $warehouse_id);
                    });
                }else{
                    return $query->whereHas('sale.warehouse', function ($q) use ($array_warehouses_id) {
                        $q->whereIn('warehouse_id', $array_warehouses_id);
                    });

                }
            })

            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('sales_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })

            // Multiple Filter
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('client_id'), function ($query) use ($request) {
                    return $query->whereHas('sale.client', function ($q) use ($request) {
                        $q->where('id', '=', $request->client_id);
                    });
                });
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->whereHas('sale.warehouse', function ($q) use ($request) {
                        $q->where('id', '=', $request->warehouse_id);
                    });
                });
            });

            //Multiple Filter
            $payment_Filtred = $helpers->filter($data, $columns, $param, $request);

            $totalRows = $payment_Filtred->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $payments = $payment_Filtred
            ->offset($start)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

            $data = array();

            foreach ($payments as $payment) {

                $item['id']             = $payment->id;
                $item['date']           = Carbon::parse($payment->date)->format('d-m-Y H:i');
                $item['Ref']            = $payment->Ref;
                $item['Reglement']      = $payment->payment_method->title;
                $item['account_name']   = $payment->account?$payment->account->account_name:'---';
                $item['Ref_Sale']       = $payment->sale->Ref;
                $item['client_name']    = $payment->sale->client->username;
                $item['warehouse_name'] = $payment->sale->warehouse->name;
                $item['montant']        = $this->render_price_with_symbol_placement(number_format($payment->montant, 2, '.', ','));

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



     //-----payment_purchase_report-------\\
     public function payment_purchase_report(Request $request)
     {
 
         $user_auth = auth()->user();
         if ($user_auth->can('payment_purchase_reports')){
 
            $suppliers = provider::where('deleted_at', '=', null)->get(['id', 'name']);
            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);

             if($user_auth->is_all_warehouses){
                 $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
             }else{
                 $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                 $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
             }
           
             return view('reports.payment_purchase',compact('suppliers','warehouses','payment_methods','accounts'));
 
         }
         return abort('403', __('You are not authorized'));
 
     }


    //-----get_payment_purchase_report_datatable-------\\

    public function get_payment_purchase_report_datatable(Request $request)
    {
        $user_auth = auth()->user();
        if (!$user_auth->can('payment_sale_reports')){
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
            $param = array(0 => 'like', 1 => '=', 2 => '=');
            $columns = array(0 => 'Ref', 1 => 'payment_method_id', 2 => 'account_id');

            $end_date_default = Carbon::now()->addYear(10)->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear(10)->format('Y-m-d');
            
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

            $start = $request->input('start');

            $data = PaymentPurchase::where('deleted_at', '=', null)
            
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->with('purchase.provider','purchase.warehouse','payment_method','account')

            ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                if ($warehouse_id !== 0) {
                    return $query->whereHas('purchase.warehouse', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                        $q->where('warehouse_id', $warehouse_id);
                    });
                }else{
                    return $query->whereHas('purchase.warehouse', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                        $q->whereIn('warehouse_id', $array_warehouses_id);
                    });

                }
            })

            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('purchases_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })

            // Multiple Filter
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('provider_id'), function ($query) use ($request) {
                    return $query->whereHas('purchase.provider', function ($q) use ($request) {
                        $q->where('id', '=', $request->provider_id);
                    });
                });
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->whereHas('purchase.warehouse', function ($q) use ($request) {
                        $q->where('id', '=', $request->warehouse_id);
                    });
                });
            });

             //Multiple Filter
             $payment_Filtred = $helpers->filter($data, $columns, $param, $request);

             $totalRows = $payment_Filtred->count();
             $totalFiltered = $totalRows;
 
             if($request->input('length') != -1)
             $limit = $request->input('length');
             else
             $limit = $totalRows;
 
             $payments = $payment_Filtred
             ->offset($start)
             ->limit($limit)
             ->orderBy('id', 'desc')
             ->get();
 
             $data = array();
 
             foreach ($payments as $payment) {
 
                 $item['id']             = $payment->id;
                 $item['date']           = Carbon::parse($payment->date)->format('d-m-Y H:i');
                 $item['Ref']            = $payment->Ref;
                 $item['Reglement']      = $payment->payment_method->title;
                 $item['account_name']   = $payment->account?$payment->account->account_name:'---';
                 $item['Ref_Purchase']   = $payment->purchase->Ref;
                 $item['provider_name']  = $payment->purchase->provider->name;
                 $item['warehouse_name'] = $payment->purchase->warehouse->name;
                 $item['montant']        = $this->render_price_with_symbol_placement(number_format($payment->montant, 2, '.', ','));
 
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


     //------------ reports_quantity_alerts-----------\\

     public function reports_quantity_alerts(Request $request)
     {
         $user_auth = auth()->user();
         if ($user_auth->can('reports_alert_qty')){
 
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
 
                $data = [];
 
                 $product_warehouse_data = product_warehouse::with('warehouse', 'product', 'productVariant')

                 ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                    if ($warehouse_id !== 0) {
                        return $query->where('warehouse_id', $warehouse_id);
                    }else{
                        return $query->whereIn('warehouse_id', $array_warehouses_id);
                    }
                })
                 ->join('products', 'product_warehouse.product_id', '=', 'products.id')
                 ->where('products.type','!=', 'is_service')
                 ->whereRaw('qte <= stock_alert')
                 ->where('product_warehouse.deleted_at', null)->get();
     
     
             if ($product_warehouse_data->isNotEmpty()) {
     
                 foreach ($product_warehouse_data as $product_warehouse) {
                     if ($product_warehouse->qte <= $product_warehouse['product']->stock_alert) {
                         if ($product_warehouse->product_variant_id !== null) {
                             $item['product_code'] = $product_warehouse['productVariant']->code;
                             $item['product_name'] = '['.$product_warehouse['productVariant']->name . '] ' . $product_warehouse['product']->name;

                         } else {
                             $item['product_code'] = $product_warehouse['product']->code;
                             $item['product_name'] = $product_warehouse['product']->name;
                         }
                         $item['current_stock'] = $product_warehouse->qte;
                         $item['product_id'] = $product_warehouse['product']->id;
                         $item['warehouse_name'] = $product_warehouse['warehouse']->name;
                         $item['stock_alert'] = $product_warehouse['product']->stock_alert;
                         $data[] = $item;
                     }
                 }
             }
 
                return Datatables::of($data)
                ->setRowId(function($data)
                {
                    return $data['product_id'];
                })
    
                ->addColumn('product_code', function($row){
                    return $row['product_code'];
                })
                
                ->addColumn('product_name', function($row){
                    return $row['product_name'];
                })

                ->addColumn('current_stock', function($row){
                    return $row['current_stock'];
                })

                ->addColumn('stock_alert', function($row){
                    $span = '<span class="badge badge-outline-danger">'.$row['stock_alert'].'</span>';

                    return $span;
                })
    
                ->addColumn('warehouse_name', function($row){
                    return $row['warehouse_name'];
                })
                ->rawColumns(['stock_alert'])
                ->make(true);
                }
 
 
             return view('reports.reports_quantity_alerts', compact('warehouses'));
 
         }
         return abort('403', __('You are not authorized'));
 
     }

    //-----payment_sale_return_report-------\\
    public function payment_sale_return_report(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('payment_return_sale_reports')){

            if ($request->ajax()) {
                $helpers = new helpers();
                $param = array(0 => 'like', 1 => '=', 2 => '=' , 3 => '=');
                $columns = array(0 => 'Ref', 1 => 'sale_return_id', 2 => 'payment_method_id' , 3 => 'account_id');

                $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
                $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
                $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
                $end_date = empty($request->end_date)?$end_date_default:$request->end_date;


                $data = PaymentSaleReturns::where('deleted_at', '=', null)
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)
                ->with('SaleReturn', 'SaleReturn.client','payment_method','account')
                ->orderBy('id', 'desc')

                // Multiple Filter
                ->where(function ($query) use ($request) {
                    return $query->when($request->filled('client_id'), function ($query) use ($request) {
                        return $query->whereHas('SaleReturn.client', function ($q) use ($request) {
                            $q->where('id', '=', $request->client_id);
                        });
                    });
                });


                //Multiple Filter
                $payment_Filtred = $helpers->filter($data, $columns, $param, $request)->get();

                return Datatables::of($payment_Filtred)
                ->setRowId(function($payment_Filtred)
                {
                    return $payment_Filtred->id;
                })

                ->addColumn('date', function($row){
                    return Carbon::parse($row->date)->format('d-m-Y H:i');
                })

                ->addColumn('Ref', function($row){
                    return $row->Ref;
                })

                ->addColumn('Reglement', function($row){
                    return $row->payment_method->title;
                })

                ->addColumn('account_name', function($row){
                    return  $row->account?$row->account->account_name:'---';
                })

                ->addColumn('Ref_return', function($row){
                    return $row->SaleReturn->Ref;
                })
                ->addColumn('client_name', function($row){
                    return $row->SaleReturn->client->username;
                })
                ->addColumn('montant', function($row){
                    return $this->render_price_with_symbol_placement(number_format($row->montant, 2, '.', ','));
                    
                })
                ->make(true);
            }

            $clients = Client::where('deleted_at', '=', null)->get(['id', 'username']);
            $sale_returns = SaleReturn::where('deleted_at', '=', null)->get(['Ref', 'id']);
            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);

            return view('reports.payment_sale_return',compact('clients','sale_returns','payment_methods','accounts'));

        }
        return abort('403', __('You are not authorized'));
    }

     //-----payment_purchase_return_report-------\\
     public function payment_purchase_return_report(Request $request)
     {
        $user_auth = auth()->user();
		if ($user_auth->can('payment_return_purchase_reports')){

            if ($request->ajax()) {
                $helpers = new helpers();
                $param = array(0 => 'like', 1 => '=', 2 => '=' , 3 => '=');
                $columns = array(0 => 'Ref', 1 => 'purchase_return_id', 2 => 'payment_method_id' , 3 => 'account_id');
    
                $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
                $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
                $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
                $end_date = empty($request->end_date)?$end_date_default:$request->end_date;
    
    
                $data = PaymentPurchaseReturns::where('deleted_at', '=', null)
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)
                ->with('PurchaseReturn', 'PurchaseReturn.provider','payment_method','account')
                ->orderBy('id', 'desc')
    
                // Multiple Filter
                ->where(function ($query) use ($request) {
                    return $query->when($request->filled('provider_id'), function ($query) use ($request) {
                        return $query->whereHas('PurchaseReturn.provider', function ($q) use ($request) {
                            $q->where('id', '=', $request->provider_id);
                        });
                    });
                });
    
    
                //Multiple Filter
                $payment_Filtred = $helpers->filter($data, $columns, $param, $request)->get();
    
                return Datatables::of($payment_Filtred)
                ->setRowId(function($payment_Filtred)
                {
                    return $payment_Filtred->id;
                })
    
                ->addColumn('date', function($row){
                    return Carbon::parse($row->date)->format('d-m-Y H:i');
                })
    
                ->addColumn('Ref', function($row){
                    return $row->Ref;
                })
    
                ->addColumn('Reglement', function($row){
                    return $row->payment_method->title;
                })

                ->addColumn('account_name', function($row){
                    return  $row->account?$row->account->account_name:'---';
                })
    
                ->addColumn('Ref_return', function($row){
                    return $row->PurchaseReturn->Ref;
                })
                ->addColumn('provider_name', function($row){
                    return $row->PurchaseReturn->provider->name;
                })
                ->addColumn('montant', function($row){
                    return $this->render_price_with_symbol_placement(number_format($row->montant, 2, '.', ','));
                })
                ->make(true);
            }
    
            $suppliers = Provider::where('deleted_at', '=', null)->get(['id', 'name']);
            $purchase_returns = PurchaseReturn::where('deleted_at', '=', null)->get(['Ref', 'id']);
            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);

            return view('reports.payment_purchase_return',compact('suppliers','purchase_returns','payment_methods','accounts'));

        }
        return abort('403', __('You are not authorized'));
     }



    //-----report_profit-------\\
    public function report_profit(Request $request)
    {

      
        $user_auth = auth()->user();
		if ($user_auth->can('report_profit')){

            $data = [];

            if($user_auth->is_all_warehouses){
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }
          

            $end_date_default = Carbon::today()->format('Y-m-d');
            $start_date_default = Carbon::today()->format('Y-m-d');
            
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

            //-------------Sale
            $report_total_sales = Sale::where('deleted_at', '=', null)
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->where(function ($query) use ($request, $array_warehouses_id) {
                return $query->whereIn('warehouse_id', $array_warehouses_id);
            })
            ->select(
                DB::raw('SUM(GrandTotal) AS sum'),
                DB::raw("count(*) as nmbr")
            )->first();



            $item['sales_sum'] =  $this->render_price_with_symbol_placement(number_format($report_total_sales->sum, 2, '.', ','));

            $item['sales_count'] =   $report_total_sales->nmbr;


            //--------Purchase
            $report_total_purchases =  Purchase::where('deleted_at', '=', null)
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->where(function ($query) use ($request, $array_warehouses_id) {
                return $query->whereIn('warehouse_id', $array_warehouses_id);
            })
            ->select(
                DB::raw('SUM(GrandTotal) AS sum'),
                DB::raw("count(*) as nmbr")
            )->first();

            $item['purchases_sum'] =  $this->render_price_with_symbol_placement(number_format($report_total_purchases->sum, 2, '.', ','));
            $item['purchases_count'] =  $report_total_purchases->nmbr;

             //--------SaleReturn
            $report_total_returns_sales = SaleReturn::where('deleted_at', '=', null)
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->where(function ($query) use ($request, $array_warehouses_id) {
                return $query->whereIn('warehouse_id', $array_warehouses_id);
            })
            ->select(
                DB::raw('SUM(GrandTotal) AS sum'),
                DB::raw("count(*) as nmbr")
            )->first();

            $item['returns_sales_sum']   =   $this->render_price_with_symbol_placement(number_format($report_total_returns_sales->sum, 2, '.', ','));
            $item['returns_sales_count'] =   $report_total_returns_sales->nmbr;

            //--------returns_purchases
            $report_total_returns_purchases = PurchaseReturn::where('deleted_at', '=', null)
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->where(function ($query) use ($request, $array_warehouses_id) {
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
            })
            ->select(
                DB::raw('SUM(GrandTotal) AS sum'),
                DB::raw("count(*) as nmbr")
            )->first();

            $item['returns_purchases_sum']   =   $this->render_price_with_symbol_placement(number_format($report_total_returns_purchases->sum, 2, '.', ','));
            $item['returns_purchases_count'] =   $report_total_returns_purchases->nmbr;

            //--------paiement_sales
            $report_total_paiement_sales = PaymentSale::with('sale')
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)

            ->where(function ($query) use ($request, $array_warehouses_id) {
                return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id) {
                    $q->whereIn('warehouse_id', $array_warehouses_id);
                });
            })
            ->select(
                DB::raw('SUM(montant) AS sum')
            )->first();

            $item['paiement_sales'] =  $this->render_price_with_symbol_placement(number_format($report_total_paiement_sales->sum, 2, '.', ','));


             //--------PaymentSaleReturns
             $report_total_PaymentSaleReturns = PaymentSaleReturns::with('SaleReturn')
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)

                ->where(function ($query) use ($request, $array_warehouses_id) {
                    return $query->whereHas('SaleReturn', function ($q) use ($request, $array_warehouses_id) {
                        $q->whereIn('warehouse_id', $array_warehouses_id);
                    });
                })

                ->select(
                    DB::raw('SUM(montant) AS sum')
                )->first();

            $item['PaymentSaleReturns'] =  $this->render_price_with_symbol_placement(number_format($report_total_PaymentSaleReturns->sum, 2, '.', ','));


            //--------PaymentPurchaseReturns
            $report_total_PaymentPurchaseReturns = PaymentPurchaseReturns::with('PurchaseReturn')
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
                
            ->where(function ($query) use ($request, $array_warehouses_id) {
                 return $query->whereHas('PurchaseReturn', function ($q) use ($request, $array_warehouses_id) {
                     $q->whereIn('warehouse_id', $array_warehouses_id);
                 });
             })    
             ->select(
                 DB::raw('SUM(montant) AS sum')
             )->first();

            $item['PaymentPurchaseReturns'] = $this->render_price_with_symbol_placement(number_format($report_total_PaymentPurchaseReturns->sum, 2, '.', ','));


            //--------paiement_purchases
            $report_total_paiement_purchases = PaymentPurchase::with('purchase')
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
               
            ->where(function ($query) use ($request, $array_warehouses_id) {
                 return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id) {
                     $q->whereIn('warehouse_id', $array_warehouses_id);
                 });
             })    
             ->select(
                 DB::raw('SUM(montant) AS sum')
             )->first();

            $item['paiement_purchases'] =  $this->render_price_with_symbol_placement(number_format($report_total_paiement_purchases->sum, 2, '.', ','));


            //--------expenses
            $report_total_expenses = Expense::where('deleted_at', '=', null)
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)
                ->select(
                    DB::raw('SUM(amount) AS sum')
                )->first();


            $item['expenses_sum'] =  $this->render_price_with_symbol_placement(number_format($report_total_expenses->sum, 2, '.', ','));

                    
           
            //calcule COGS and average cost
            $cogs_average_data = $this->CalculeCogsAndAverageCost($start_date, $end_date, $warehouse_id = 0, $array_warehouses_id);
            
            $cogs = $cogs_average_data['total_cogs_products'];
            $total_average_cost = $cogs_average_data['total_average_cost'];

            $item['product_cost_fifo']   = $this->render_price_with_symbol_placement(number_format($cogs, 2, '.', ','));
            $item['averagecost']         = $this->render_price_with_symbol_placement(number_format($total_average_cost, 2, '.', ','));
            $item['profit_fifo']         = $this->render_price_with_symbol_placement(number_format($report_total_sales->sum - $cogs, 2, '.', ','));
            $item['profit_average_cost'] = $this->render_price_with_symbol_placement(number_format($report_total_sales->sum - $total_average_cost, 2, '.', ','));
            $item['payment_received']    = $this->render_price_with_symbol_placement(number_format($report_total_paiement_sales->sum  + $report_total_PaymentPurchaseReturns->sum, 2, '.', ','));
            $item['payment_sent']        = $this->render_price_with_symbol_placement(number_format($report_total_paiement_purchases->sum + $report_total_PaymentSaleReturns->sum + $report_total_expenses->sum, 2, '.', ','));
            $item['paiement_net']        = $this->render_price_with_symbol_placement(number_format(($report_total_paiement_sales->sum  + $report_total_PaymentPurchaseReturns->sum)-($report_total_paiement_purchases->sum + $report_total_PaymentSaleReturns->sum + $report_total_expenses->sum), 2, '.', ','));
            $item['total_revenue']       = $this->render_price_with_symbol_placement(number_format($report_total_sales->sum -  $report_total_returns_sales->sum, 2, '.', ','));
        
            return view('reports.report_profit',['data' => $item , 'warehouses' => $warehouses]);
 
         }
         return abort('403', __('You are not authorized'));
    }


    //-----report_profit_filter-------\\
   public function report_profit_filter(Request $request  , $start_date , $end_date , $warehouse)
   {

       $user_auth = auth()->user();
       if ($user_auth->can('report_profit')){

           $data = [];

           if($user_auth->is_all_warehouses){
               $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
           }else{
               $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
           }
                       
           if(empty($warehouse)){
               $warehouse_id = 0;
           }else{
               $warehouse_id = $warehouse;
           }



           //-------------Sale
           $report_total_sales = Sale::where('deleted_at', '=', null)
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)

               ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                   if ($warehouse_id !== 0) {
                       return $query->where('warehouse_id', $warehouse_id);
                   }else{
                       return $query->whereIn('warehouse_id', $array_warehouses_id);

                   }
               })

               ->select(
                   DB::raw('SUM(GrandTotal) AS sum'),
                   DB::raw("count(*) as nmbr")
               )->first();

           $item['sales_sum'] =  $this->render_price_with_symbol_placement(number_format($report_total_sales->sum, 2, '.', ','));

           $item['sales_count'] =   $report_total_sales->nmbr;


           //--------Purchase
           $report_total_purchases =  Purchase::where('deleted_at', '=', null)
           ->whereDate('date', '>=', $start_date)
           ->whereDate('date', '<=', $end_date)

           ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
               if ($warehouse_id !== 0) {
                   return $query->where('warehouse_id', $warehouse_id);
               }else{
                   return $query->whereIn('warehouse_id', $array_warehouses_id);

               }
           })
           ->select(
               DB::raw('SUM(GrandTotal) AS sum'),
               DB::raw("count(*) as nmbr")
           )->first();

           $item['purchases_sum'] =   $this->render_price_with_symbol_placement(number_format($report_total_purchases->sum, 2, '.', ','));
           $item['purchases_count'] =  $report_total_purchases->nmbr;

            //--------SaleReturn
           $report_total_returns_sales = SaleReturn::where('deleted_at', '=', null)
           ->whereDate('date', '>=', $start_date)
           ->whereDate('date', '<=', $end_date)

           ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
               if ($warehouse_id !== 0) {
                   return $query->where('warehouse_id', $warehouse_id);
               }else{
                   return $query->whereIn('warehouse_id', $array_warehouses_id);

               }
           })

           ->select(
               DB::raw('SUM(GrandTotal) AS sum'),
               DB::raw("count(*) as nmbr")
           )->first();

           $item['returns_sales_sum'] =   $this->render_price_with_symbol_placement(number_format($report_total_returns_sales->sum, 2, '.', ','));
           $item['returns_sales_count'] =   $report_total_returns_sales->nmbr;

           //--------returns_purchases
           $report_total_returns_purchases = PurchaseReturn::where('deleted_at', '=', null)
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)

                ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                   if ($warehouse_id !== 0) {
                       return $query->where('warehouse_id', $warehouse_id);
                   }else{
                       return $query->whereIn('warehouse_id', $array_warehouses_id);

                   }
               })

               ->select(
                   DB::raw('SUM(GrandTotal) AS sum'),
                   DB::raw("count(*) as nmbr")
               )->first();

           $item['returns_purchases_sum']   =   $this->render_price_with_symbol_placement(number_format($report_total_returns_purchases->sum, 2, '.', ','));
           $item['returns_purchases_count'] =   $report_total_returns_purchases->nmbr;

           //--------paiement_sales
           $report_total_paiement_sales = PaymentSale::with('sale')
           ->whereDate('date', '>=', $start_date)
           ->whereDate('date', '<=', $end_date)

           ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
               if ($warehouse_id !== 0) {
                   return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                       $q->where('warehouse_id', $warehouse_id);
                   });
               }else{
                   return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                       $q->whereIn('warehouse_id', $array_warehouses_id);
                   });

               }
           })

           ->select(
               DB::raw('SUM(montant) AS sum')
           )->first();

           $item['paiement_sales'] =   $this->render_price_with_symbol_placement(number_format($report_total_paiement_sales->sum, 2, '.', ','));


            //--------PaymentSaleReturns
            $report_total_PaymentSaleReturns = PaymentSaleReturns::with('SaleReturn')
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)

               ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                   if ($warehouse_id !== 0) {
                       return $query->whereHas('SaleReturn', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                           $q->where('warehouse_id', $warehouse_id);
                       });
                   }else{
                       return $query->whereHas('SaleReturn', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                           $q->whereIn('warehouse_id', $array_warehouses_id);
                       });

                   }
               })

               ->select(
                   DB::raw('SUM(montant) AS sum')
               )->first();

           $item['PaymentSaleReturns'] =   $this->render_price_with_symbol_placement(number_format($report_total_PaymentSaleReturns->sum, 2, '.', ','));


           //--------PaymentPurchaseReturns
           $report_total_PaymentPurchaseReturns = PaymentPurchaseReturns::with('PurchaseReturn')
           ->whereDate('date', '>=', $start_date)
           ->whereDate('date', '<=', $end_date)

           ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
               if ($warehouse_id !== 0) {
                   return $query->whereHas('PurchaseReturn', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                       $q->where('warehouse_id', $warehouse_id);
                   });
               }else{
                   return $query->whereHas('PurchaseReturn', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                       $q->whereIn('warehouse_id', $array_warehouses_id);
                   });

               }
           })

           ->select(
               DB::raw('SUM(montant) AS sum')
           )->first();

           $item['PaymentPurchaseReturns'] =   $this->render_price_with_symbol_placement(number_format($report_total_PaymentPurchaseReturns->sum, 2, '.', ','));


           //--------paiement_purchases
           $report_total_paiement_purchases = PaymentPurchase::with('purchase')
           ->whereDate('date', '>=', $start_date)
           ->whereDate('date', '<=', $end_date)

           ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
               if ($warehouse_id !== 0) {
                   return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                       $q->where('warehouse_id', $warehouse_id);
                   });
               }else{
                   return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                       $q->whereIn('warehouse_id', $array_warehouses_id);
                   });

               }
           })

           ->select(
               DB::raw('SUM(montant) AS sum')
           )->first();

           $item['paiement_purchases'] =   $this->render_price_with_symbol_placement(number_format($report_total_paiement_purchases->sum, 2, '.', ','));


           //--------expenses
           $report_total_expenses = Expense::where('deleted_at', '=', null)
           ->whereDate('date', '>=', $start_date)
           ->whereDate('date', '<=', $end_date)
           ->select(
               DB::raw('SUM(amount) AS sum')
           )->first();

           $item['expenses_sum'] =   $this->render_price_with_symbol_placement(number_format($report_total_expenses->sum, 2, '.', ','));



            //calcule COGS and average cost
            $cogs_average_data = $this->CalculeCogsAndAverageCost($start_date, $end_date, $warehouse_id, $array_warehouses_id);
            
            $cogs = $cogs_average_data['total_cogs_products'];
            $total_average_cost = $cogs_average_data['total_average_cost'];

           $item['product_cost_fifo']   = $this->render_price_with_symbol_placement(number_format($cogs, 2, '.', ','));
           $item['averagecost']         = $this->render_price_with_symbol_placement(number_format($total_average_cost, 2, '.', ','));

           $item['profit_fifo']         = $this->render_price_with_symbol_placement(number_format($report_total_sales->sum - $cogs, 2, '.', ','));
           $item['profit_average_cost'] = $this->render_price_with_symbol_placement(number_format($report_total_sales->sum - $total_average_cost, 2, '.', ','));

           $item['payment_received']    = $this->render_price_with_symbol_placement(number_format($report_total_paiement_sales->sum  + $report_total_PaymentPurchaseReturns->sum, 2, '.', ','));
           $item['payment_sent']        = $this->render_price_with_symbol_placement(number_format($report_total_paiement_purchases->sum + $report_total_PaymentSaleReturns->sum + $report_total_expenses->sum, 2, '.', ','));
           $item['paiement_net']        = $this->render_price_with_symbol_placement(number_format(($report_total_paiement_sales->sum  + $report_total_PaymentPurchaseReturns->sum)-($report_total_paiement_purchases->sum + $report_total_PaymentSaleReturns->sum + $report_total_expenses->sum), 2, '.', ','));
           $item['total_revenue']       = $this->render_price_with_symbol_placement(number_format($report_total_sales->sum -  $report_total_returns_sales->sum, 2, '.', ','));
       
           return response()->json(['data' => $item]);

       }
       return abort('403', __('You are not authorized'));
   }


    // Calculating the cost of goods sold (COGS)
    public function CalculeCogsAndAverageCost($start_date, $end_date , $warehouse_id, $array_warehouses_id)
    {
       
        // Initialize variable to store total COGS averageCost and for all products
        $total_cogs_products = 0;
        $total_average_cost = 0;

       // Get all distinct product IDs for sales between start and end date
        $productIds = SaleDetail::with('sale')
        ->where(function ($query) use ($warehouse_id, $array_warehouses_id) {
            if ($warehouse_id !== 0) {
                return $query->whereHas('sale', function ($q) use ($array_warehouses_id, $warehouse_id) {
                    $q->where('warehouse_id', $warehouse_id);
                });
            }else{
                return $query->whereHas('sale', function ($q) use ($array_warehouses_id, $warehouse_id) {
                    $q->whereIn('warehouse_id', $array_warehouses_id);
                });

            }
        })->whereDate('date', '>=', $start_date)
        ->whereDate('date', '<=', $end_date)
        ->select('product_id')->distinct()->get();

        // Loop through each product
        foreach ($productIds as $productId) {

            $totalCogs = 0;
            $average_cost = 0;

            // Get the total cost and quantity for all adjustments of the product
            $adjustments = AdjustmentDetail::with('adjustment')
            ->where(function ($query) use ($warehouse_id, $array_warehouses_id ,$end_date) {
                if ($warehouse_id !== 0) {
                    return $query->whereHas('adjustment', function ($q) use ($array_warehouses_id, $warehouse_id,$end_date) {
                        $q->where('warehouse_id', $warehouse_id)
                        ->whereDate('date', '<=' , $end_date);
                    });
                }else{
                    return $query->whereHas('adjustment', function ($q) use ($array_warehouses_id, $warehouse_id, $end_date ) {
                        $q->whereIn('warehouse_id', $array_warehouses_id)
                        ->whereDate('date', '<=' , $end_date);
                    });
    
                }
            })            
            ->where('product_id', $productId['product_id'])->get();
    
            $adjustment_quantity = 0;
            foreach ($adjustments as $adjustment) {
                if($adjustment->type == 'add'){
                    $adjustment_quantity += $adjustment->quantity;
                }else{
                    $adjustment_quantity -= $adjustment->quantity;
                }
            }


            // Get total quantity sold before start date
            $totalQuantitySold = SaleDetail::with('sale')
            ->where(function ($query) use ($warehouse_id, $array_warehouses_id) {
                if ($warehouse_id !== 0) {
                    return $query->whereHas('sale', function ($q) use ($array_warehouses_id, $warehouse_id) {
                        $q->where('warehouse_id', $warehouse_id);
                    });
                }else{
                    return $query->whereHas('sale', function ($q) use ($array_warehouses_id, $warehouse_id) {
                        $q->whereIn('warehouse_id', $array_warehouses_id);
                    });
    
                }
            })->where('product_id', $productId['product_id'])->whereDate('date', '<', $start_date)->sum('quantity');

            // Get purchase details for current product, ordered by date in ascending date
            $purchases = PurchaseDetail::with('purchase')
            ->where(function ($query) use ($warehouse_id, $array_warehouses_id) {
                if ($warehouse_id !== 0) {
                    return $query->whereHas('purchase', function ($q) use ($array_warehouses_id, $warehouse_id) {
                        $q->where('warehouse_id', $warehouse_id);
                    });
                }else{
                    return $query->whereHas('purchase', function ($q) use ($array_warehouses_id, $warehouse_id) {
                        $q->whereIn('warehouse_id', $array_warehouses_id);
                    });
    
                }
            })->where('product_id', $productId['product_id'])
            ->orderBy('date', 'asc')
            ->get();

            if(count($purchases) > 0){
                $purchases_to_array = $purchases->toArray();
                $purchases_sum_qty = array_sum(array_column($purchases_to_array,'quantity'));
            }else{
                $purchases_sum_qty = 0;
            }
            
            // Get sale details for current product between start and end date, ordered by date in ascending order
            $sales = SaleDetail::with('sale')
            ->where(function ($query) use ($warehouse_id, $array_warehouses_id) {
                if ($warehouse_id !== 0) {
                    return $query->whereHas('sale', function ($q) use ($array_warehouses_id, $warehouse_id) {
                        $q->where('warehouse_id', $warehouse_id);
                    });
                }else{
                    return $query->whereHas('sale', function ($q) use ($array_warehouses_id, $warehouse_id) {
                        $q->whereIn('warehouse_id', $array_warehouses_id);
                    });
    
                }
            })->where('product_id', $productId['product_id'])
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->orderBy('date', 'asc')
            ->get();

            $sales_to_array = $sales->toArray();
            $sales_sum_qty = array_sum(array_column($sales_to_array,'quantity'));
            
            $total_sum_sales = $totalQuantitySold + $sales_sum_qty;


            //calcule average Cost
            $average_cost = $this->averageCost($productId['product_id'] ,$start_date, $end_date, $warehouse_id, $array_warehouses_id);

            if($total_sum_sales > $purchases_sum_qty){
                // Handle adjustments only case
                $totalCogs += $sales_sum_qty * $average_cost;
                $total_average_cost += $sales_sum_qty * $average_cost;
                
            }else{

                foreach ($sales as $sale) {
                    
                    $saleQuantity = $sale->quantity;
                    $total_average_cost += $average_cost * $sale->quantity;

                    while ($saleQuantity > 0) {
                        $purchase = $purchases->first();
                        if ($purchase->quantity > 0) { 
                            $totalQuantitySold += $saleQuantity;
                            if ($purchase->quantity >= $totalQuantitySold) {
                                $totalCogs += $saleQuantity * $purchase->cost;
                                $purchase->quantity -= $totalQuantitySold;
                                $saleQuantity = 0;
                                $totalQuantitySold = 0;
                                if($purchase->quantity == 0){
                                    $purchase->quantity = 0;
                                    $saleQuantity = 0;
                                    $totalQuantitySold = 0;
                                    $purchases->shift();
                                }
                            
                            } else {


                                if($purchase->quantity > ($totalQuantitySold - $saleQuantity)) {

                                    $rest = $purchase->quantity - ($totalQuantitySold - $saleQuantity);
                                    if($rest <= $saleQuantity){
                                        $saleQuantity -= $rest;
                                        $totalCogs+= $rest * $purchase->cost;
                                        $totalQuantitySold =  0;
                                        $purchase->quantity = 0;
                                        $purchases->shift();

                                    }else{
                                        $totalQuantitySold -=  $saleQuantity;
                                        $purchase->quantity = $purchase->quantity - $totalQuantitySold;
                                        $totalCogs+= $purchase->quantity * $purchase->cost;
                                        $saleQuantity -= $purchase->quantity;
                                        $purchase->quantity = 0;
                                        $purchases->shift();
                                    }
                            
                                }else{
                                    $totalQuantitySold -=  $saleQuantity;
                                    $totalQuantitySold -= $purchase->quantity;
                                    $purchase->quantity = 0;
                                    $purchases->shift();
                                }
                            }
                        } else {
                            $purchases->shift();
                        }

                        
                    }
                
                }
            }
            $total_cogs_products += $totalCogs;

        }

        return [
            'total_cogs_products' => $total_cogs_products,
            'total_average_cost'  => $total_average_cost
        ];


    }

    // Calculate the average cost of a product.
    public function averageCost($product_id , $start_date, $end_date , $warehouse_id, $array_warehouses_id)
    {
        // Get the cost of the product from the products table
        $product = Product::find($product_id);
        $product_cost = $product->cost;

        // Get the total cost and quantity for all purchases of the product
        $purchases = PurchaseDetail::with('purchase')
        ->where(function ($query) use ($warehouse_id, $array_warehouses_id, $start_date, $end_date) {
            if ($warehouse_id !== 0) {
                return $query->whereHas('purchase', function ($q) use ($array_warehouses_id, $warehouse_id , $start_date, $end_date) {
                    $q->where('warehouse_id', $warehouse_id);
                });
            }else{
                return $query->whereHas('purchase', function ($q) use ($array_warehouses_id, $warehouse_id, $start_date, $end_date) {
                    $q->whereIn('warehouse_id', $array_warehouses_id);
                });

            }
        })->whereDate('date', '<=' , $end_date)->where('product_id', $product_id)->get();

        $purchase_cost = 0;
        $purchase_quantity = 0;
        foreach ($purchases as $purchase) {
            $purchase_cost += $purchase->quantity * $purchase->cost;
            $purchase_quantity += $purchase->quantity;
        }

        // Get the total cost and quantity for all adjustments of the product
        $adjustments = AdjustmentDetail::with('adjustment')
        ->where(function ($query) use ($warehouse_id, $array_warehouses_id, $start_date, $end_date) {
            if ($warehouse_id !== 0) {
                return $query->whereHas('adjustment', function ($q) use ($array_warehouses_id, $warehouse_id, $start_date, $end_date) {
                    $q->where('warehouse_id', $warehouse_id)
                    ->whereDate('date', '<=' , $end_date);
                });
            }else{
                return $query->whereHas('adjustment', function ($q) use ($array_warehouses_id, $warehouse_id , $start_date, $end_date) {
                    $q->whereIn('warehouse_id', $array_warehouses_id)
                    ->whereDate('date', '<=' , $end_date);
                });

            }
        })
        ->where('product_id', $product_id)->get();

        $adjustment_cost = 0;
        $adjustment_quantity = 0;
        foreach ($adjustments as $adjustment) {
            if($adjustment->type == 'add'){
                $adjustment_cost += $adjustment->quantity * $product_cost;
                $adjustment_quantity += $adjustment->quantity;
            }else{
                $adjustment_cost -= $adjustment->quantity * $product_cost;
                $adjustment_quantity -= $adjustment->quantity;
            }
        }

        // Calculate the average cost
        $total_cost = $purchase_cost + $adjustment_cost;
        $total_quantity = $purchase_quantity + $adjustment_quantity;
        if($total_quantity === 0 || $total_quantity == 0 || $total_quantity == '0'){
           $average_cost = $product_cost;
        }else{
            $average_cost = $total_cost / $total_quantity;
        }

        return $average_cost;
    }



    public function sales_history_page(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('sale_reports')){

            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }

            $clients = client::where('deleted_at', '=', null)->get(['id', 'username']);
            $products = Product::where('deleted_at', '=', null)->get(['id', 'name']);

            return view('reports.sales_history',compact('clients','products','warehouses'));

        }
        return abort('403', __('You are not authorized'));
    }


    public function get_sales_history_datatable(Request $request)
    {
        $user_auth = auth()->user();
        if (!$user_auth->can('sale_reports')){
            return abort('403', __('You are not authorized'));
        }else{
            $helpers = new helpers();
            $param = array(
                0 => '=',
                1 => '=',
                2 => '=',
            );
            $columns = array(
                0 => 'client_id',
                1 => 'product_id',
                2 => 'warehouse_id',
            );

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

            $start = $request->input('start');

            $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

            $sale_details_data = SaleDetail::with('product','sale','sale.client','sale.warehouse')

            ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                if ($warehouse_id !== 0) {
                    return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                        $q->where('warehouse_id', $warehouse_id);
                    });
                }else{
                    return $query->whereHas('sale', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                        $q->whereIn('warehouse_id', $array_warehouses_id);
                    });

                }
            })

            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date);

            // Filter
            $sale_details_Filtred = $sale_details_data->where(function ($query) use ($request) {
                return $query->when($request->filled('client_id'), function ($query) use ($request) {
                    return $query->whereHas('sale.client', function ($q) use ($request) {
                        $q->where('client_id', '=', $request->client_id);
                    });
                });
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('product_id'), function ($query) use ($request) {
                    return $query->whereHas('product', function ($q) use ($request) {
                        $q->where('product_id', '=', $request->product_id);
                    });
                });
            })

            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->whereHas('sale.warehouse', function ($q) use ($request) {
                        $q->where('warehouse_id', '=', $request->warehouse_id);
                    });
                });
            })

              // Search With Multiple Param
              ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                            return $query->whereHas('sale.client', function ($q) use ($request) {
                                $q->where('username', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('sale', function ($q) use ($request) {
                                $q->where('Ref', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('product', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        })

                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('sale.warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        });
                });
            });

            $totalRows = $sale_details_Filtred->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $sale_details = $sale_details_Filtred
            ->offset($start)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

            $data = array();

            foreach ($sale_details as $detail) {

                //check if detail has sale_unit_id Or Null
                if($detail->sale_unit_id !== null){
                    $unit = Unit::where('id', $detail->sale_unit_id)->first();
                }else{
                    $product_unit_sale_id = Product::with('unitSale')
                    ->where('id', $detail->product_id)
                    ->first();
                    $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                }
    
    
                if($detail->product_variant_id){
                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();
    
                    $product_name = $productsVariants->name . '-' . $detail['product']['name'];
    
                }else{
                    $product_name = $detail['product']['name'];
                }
    
                $item['date']           = Carbon::parse($detail->date)->format('d-m-Y H:i');
                $item['Ref']            = $detail['sale']->Ref;
                $item['client_name']    = $detail['sale']['client']->username;
                $item['warehouse_name'] = $detail['sale']['warehouse']->name;
                $item['quantity']       = $detail->quantity .' '.$unit->ShortName;
                $item['total']          = $this->render_price_with_symbol_placement(number_format($detail->total, 2, '.', ','));
                $item['product_name']   = $product_name;
                $item['unit_sale']      = $unit->ShortName;
    
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


    public function purchases_history_page(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('purchase_reports')){

            if($user_auth->is_all_warehouses){
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            }else{
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
            }

            $providers = Provider::where('deleted_at', '=', null)->get(['id', 'name']);
            $products = Product::where('deleted_at', '=', null)->get(['id', 'name']);

            return view('reports.purchases_history',compact('providers','products','warehouses'));

        }
        return abort('403', __('You are not authorized'));
    }


    public function get_purchases_history_datatable(Request $request)
    {
        $user_auth = auth()->user();
        if (!$user_auth->can('purchase_reports')){
            return abort('403', __('You are not authorized'));
        }else{
            $helpers = new helpers();
            $param = array(
                0 => '=',
                1 => '=',
                2 => '=',
            );
            $columns = array(
                0 => 'provider_id',
                1 => 'product_id',
                2 => 'warehouse_id',
            );

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

        

            $start = $request->input('start');

            $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

            $purchase_details_data = PurchaseDetail::with('product','purchase','purchase.provider','purchase.warehouse')

            ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                if ($warehouse_id !== 0) {
                    return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                        $q->where('warehouse_id', $warehouse_id);
                    });
                }else{
                    return $query->whereHas('purchase', function ($q) use ($request, $array_warehouses_id, $warehouse_id) {
                        $q->whereIn('warehouse_id', $array_warehouses_id);
                    });

                }
            })

            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date);

            // Filter
            $purchase_details_Filtred = $purchase_details_data->where(function ($query) use ($request) {
                return $query->when($request->filled('provider_id'), function ($query) use ($request) {
                    return $query->whereHas('purchase.provider', function ($q) use ($request) {
                        $q->where('provider_id', '=', $request->provider_id);
                    });
                });
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('product_id'), function ($query) use ($request) {
                    return $query->whereHas('product', function ($q) use ($request) {
                        $q->where('product_id', '=', $request->product_id);
                    });
                });
            })

            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->whereHas('purchase.warehouse', function ($q) use ($request) {
                        $q->where('warehouse_id', '=', $request->warehouse_id);
                    });
                });
            })

              // Search With Multiple Param
              ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                            return $query->whereHas('purchase.provider', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('purchase', function ($q) use ($request) {
                                $q->where('Ref', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('product', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('purchase.warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->input('search.value')}%");
                            });
                        });
                });
            });

            $totalRows = $purchase_details_Filtred->count();
            $totalFiltered = $totalRows;

            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $purchase_details = $purchase_details_Filtred
            ->offset($start)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

            $data = array();

            foreach ($purchase_details as $detail) {

                //-------check if detail has purchase_unit_id Or Null
                if($detail->purchase_unit_id !== null){
                   $unit = Unit::where('id', $detail->purchase_unit_id)->first();
               }else{
                   $product_unit_purchase_id = Product::with('unitPurchase')
                   ->where('id', $detail->product_id)
                   ->first();
                   $unit = Unit::where('id', $product_unit_purchase_id['unitPurchase']->id)->first();
               }
      
                  if($detail->product_variant_id){
                      $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                      ->where('id', $detail->product_variant_id)->first();
      
                      $product_name = $productsVariants->name . '-' . $detail['product']['name'];
      
                  }else{
                      $product_name = $detail['product']['name'];
                  }
      
                  $item['date']           = Carbon::parse($detail->date)->format('d-m-Y H:i');
                  $item['Ref']            = $detail['purchase']->Ref;
                  $item['provider_name']  = $detail['purchase']['provider']->name;
                  $item['warehouse_name'] = $detail['purchase']['warehouse']->name;
                  $item['quantity']       = $detail->quantity .' '.$unit->ShortName;;
                  $item['total']          = $this->render_price_with_symbol_placement(number_format($detail->total, 2, '.', ','));
                  $item['product_name']   = $product_name;
                  $item['unit_purchase']  = $unit->ShortName;
   
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

    //-----report_payment_debt-------\\
    public function report_payment_debt(Request $request)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('report_payment_due')){

            if ($request->ajax()) {
                $helpers = new helpers();
                
                $end_date_default = Carbon::now()->addYear(10)->format('Y-m-d');
                $start_date_default = Carbon::now()->subYear(10)->format('Y-m-d');
                
                $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
                $end_date = empty($request->end_date)?$end_date_default:$request->end_date;


                $payment_Filtred = PaymentDebt::where('deleted_at', '=', null)
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)
                ->with('client')
                ->orderBy('id', 'desc')
                ->get();


                return Datatables::of($payment_Filtred)
                ->setRowId(function($payment_Filtred)
                {
                    return $payment_Filtred->id;
                })

                ->addColumn('date', function($row){
                    return Carbon::parse($row->date)->format('d-m-Y H:i');
                })

                ->addColumn('payment_method', function($row){
                    $span = '';
                    $status_check = '';
                    
                    if($row->payment_method == 'espece'){
                        $span = '<span>Espèces</span>';
                        
                    }elseif($row->payment_method == 'effet'){

                        if($row->CheckClient){
                            if($row->CheckClient['status'] == 'pending'){
                                $status_check = '<span class="badge badge-warning">A DÉPOSER</span>';
                            }else if($row->CheckClient['status'] == 'deposed'){
                                $status_check = '<span class="badge badge-info">DÉPOSER</span>';
                            }else if($row->CheckClient['status'] == 'paid'){
                                $status_check = '<span class="badge badge-success">ENCAISSÉ</span>';
                            }else if($row->CheckClient['status'] == 'unpaid'){
                                $status_check = '<span class="badge badge-danger">IMPAYÉ</span>';
                            }
                            
                        }else{
                            $status_check = '<span class="badge badge-danger">Deleted</span>';
                        }

                        $span = '<span>Effet</span><br>'.$status_check;

                    }elseif($row->payment_method == 'cheque'){
                        if($row->CheckClient){
                            if($row->CheckClient['status'] == 'pending'){
                                $status_check = '<span class="badge badge-warning">A DÉPOSER</span>';
                            }else if($row->CheckClient['status'] == 'deposed'){
                                $status_check = '<span class="badge badge-info">DÉPOSER</span>';
                            }else if($row->CheckClient['status'] == 'paid'){
                                $status_check = '<span class="badge badge-success">ENCAISSÉ</span>';
                            }else if($row->CheckClient['status'] == 'unpaid'){
                                $status_check = '<span class="badge badge-danger">IMPAYÉ</span>';
                            }
                            
                        }else{
                            $status_check = '<span class="badge badge-danger">Deleted</span>';
                        }

                        $span = '<span>chèque</span><br>'.$status_check;
                    }
                    
                    return $span;
                })

               

                ->addColumn('client_name', function($row){
                    return $row->client->username;
                })
                ->addColumn('total_paid', function($row){
                    return $this->render_price_with_symbol_placement(number_format($row->total_paid, 2, '.', ','));
                })
                ->rawColumns(['payment_method'])
                ->make(true);
            }

            return view('reports.report_payment_debt');

        }
        return abort('403', __('You are not authorized'));
    }



    public function product_Expiry_dates_page(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('report_product_expiry_date')){

            return view('reports.report_expiry_dates');

        }
        return abort('403', __('You are not authorized'));
    }



    public function getProductExpiryDates(Request $request)
    {

        $user_auth = auth()->user();
        if (!$user_auth->can('report_product_expiry_date')){
            return abort('403', __('You are not authorized'));
        }else{

            $start = $request->input('start', 0);
           
            $start_date_default = Carbon::now()->format('Y-m-d');
            $end_date_default = Carbon::now()->addDays(30)->format('Y-m-d');

            $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
            $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

            $purchase_items = PurchaseDetail::whereNotNull('expiry_at')
            ->whereBetween('purchase_details.expiry_at', array($start_date, $end_date))
            ->join('products', 'purchase_details.product_id', '=', 'products.id')
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                ->select('purchase_details.expiry_at','purchase_details.quantity as qty_expired', 'products.name as product_name',
                 'purchases.date as purchase_date', 'purchases.Ref as purchase_ref')

                 // Search With Multiple Param
                ->where(function ($query) use ($request) {
                    return $query->when($request->filled('search'), function ($query) use ($request) {
                        return $query->where('purchases.Ref', 'LIKE', "%{$request->input('search.value')}%")
                            ->orWhere('products.name', 'like', "%{$request->input('search.value')}%");
                    });
                });

            $totalRows = $purchase_items->count();
            $totalFiltered = $totalRows;

            
            if($request->input('length') != -1)
            $limit = $request->input('length');
            else
            $limit = $totalRows;

            $product_expiry = $purchase_items
            ->offset($start)
            ->limit($limit)
            ->orderBy('purchase_details.expiry_at', 'asc')
            ->get();


            $data = array();

            foreach ($product_expiry as $product) {
                $item['product_name'] = $product->product_name;
                $item['purchase_ref'] = $product->purchase_ref;

                if($product->expiry_at){
                    $diff = Carbon::parse(Carbon::now()->format('Y-m-d'))->diffInDays($product->expiry_at, false);
                    if($diff < 0){
                        $span = '<span style="color: red;">('.$diff.')</span>';
                        $item['expiry_at'] = $product->expiry_at.' '. $span;
                    }elseif($diff >= 0){
                        $span = '<span style="color: #47c363;">(+'.$diff.')</span>';
                        $item['expiry_at'] = $product->expiry_at.'  '. $span;
                    }else{
                        $item['expiry_at'] = $product->expiry_at;
                    }
                }else{
                    $item['expiry_at'] = 'N/B';
                }

                $item['purchase_date'] = $product->purchase_date;
                $item['qty_expired'] = $product->qty_expired;

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


    // render_price_with_symbol_placement

    public function render_price_with_symbol_placement($amount) {

        if ($this->symbol_placement == 'before') {
            return $this->currency . ' ' . $amount;
        } else {
            return $amount . ' ' . $this->currency;
        }
    }

    

}
