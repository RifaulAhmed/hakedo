<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Product;
use App\Models\product_warehouse;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Purchase;
use App\Models\SaleReturn;
use App\Models\PurchaseReturn;
use App\Models\Unit;
use App\Models\Warehouse;
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
use Illuminate\Support\Facades\DB;
use PDF;
use App\utils\helpers;

class DashboardController extends Controller
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
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard_admin(Request $request)
    {

        $helpers = new helpers();
        $currency = $helpers->Get_Currency();
        
        //------------------------ dashboard statistic -------------\\

        $end_date_default = Carbon::today()->format('Y-m-d');
        $start_date_default = Carbon::today()->format('Y-m-d');
        
        $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
        $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

        $today_sales = Sale::where('deleted_at', '=', null)
        ->whereDate('date', '>=', $start_date)
        ->whereDate('date', '<=', $end_date)
        ->sum('GrandTotal');

        $today_sales = $this->render_price_with_symbol_placement(number_format($today_sales, 2, '.', ','));


        $return_sales = SaleReturn::where('deleted_at', '=', null)
        ->whereDate('date', '>=', $start_date)
        ->whereDate('date', '<=', $end_date)
        ->sum('GrandTotal');

        $return_sales = $this->render_price_with_symbol_placement(number_format($return_sales, 2, '.', ','));


        $today_purchases = Purchase::where('deleted_at', '=', null)
        ->whereDate('date', '>=', $start_date)
        ->whereDate('date', '<=', $end_date)
        ->sum('GrandTotal');

        $today_purchases = $this->render_price_with_symbol_placement(number_format($today_purchases, 2, '.', ','));

        $return_purchases = PurchaseReturn::where('deleted_at', '=', null)
        ->whereDate('date', '>=', $start_date)
        ->whereDate('date', '<=', $end_date)
        ->sum('GrandTotal');

        $return_purchases = $this->render_price_with_symbol_placement(number_format($return_purchases, 2, '.', ','));

        //-----------chart sales & purchases this week----------------\\

         // Build an array of the dates we want to show, oldest first
         $dates = collect();
         foreach (range(-6, 0) as $i) {
             $date = Carbon::now()->addDays($i)->format('Y-m-d');
             $dates->put($date, 0);
         }
 
         $date_range = \Carbon\Carbon::today()->subDays(6);
 
         // Get Sale
         $Sale = Sale::whereDate('date', '>=', $date_range)
            ->where('deleted_at', '=', null)
            ->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->select([
                DB::raw(DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as date")),
                DB::raw('SUM(GrandTotal) AS count'),
            ])
        ->pluck('count', 'date');

        // Merge;
        $dates_sales  = $dates->merge($Sale);
 
         
        $sales_chart_data = [];
        $days = [];
        foreach ($dates_sales as $key => $value) {
            $sales_chart_data[] = $value;
        }

         // Get purchases
         $purchases = Purchase::whereDate('date', '>=', $date_range)
            ->where('deleted_at', '=', null)
            ->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->select([
                DB::raw(DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as date")),
                DB::raw('SUM(GrandTotal) AS count'),
            ])
        ->pluck('count', 'date');
 
         // Merge
         $dates_purchases = $dates->merge($purchases);

         $purchases_chart_data = [];

         foreach ($dates_purchases as $key => $value) {
             $purchases_chart_data[] = $value;
             $days[] = $key;
         }

        //------------Top clients ----------\\

        $top_clients = Sale::whereDate('date', '>=', Carbon::now()->startOfMonth())
        ->whereDate('date', '<=',  Carbon::now()->endOfMonth())
        ->where('sales.deleted_at', '=', null)
    
        ->join('clients', 'sales.client_id', '=', 'clients.id')
        ->select(
            DB::raw('clients.username as name'),
            DB::raw("sum(GrandTotal) as value")
        )
        ->groupBy('clients.username')
        ->orderBy('value', 'desc')
        ->take(5)
        ->get();

        //------------Top products ----------\\

        $top_products = SaleDetail::join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereDate('sale_details.date', '>=', Carbon::now()->startOfYear())
            ->whereDate('sale_details.date', '<=',  Carbon::now()->endOfYear())

            ->select(
                DB::raw('products.name as name'),
                DB::raw('sum(quantity) as value'),
            )
            ->groupBy('products.name')
            ->orderBy('value', 'desc')
            ->take(5)
            ->get();

        // factures unpaid

            $recent_sales = Sale::where('deleted_at', '=', null)
                ->with('client')
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();

                $recent_sales_data = [];

                foreach ($recent_sales as $sale) {
                    $item['Ref']         = $sale->Ref;             
                    $item['client_name'] = $sale->client->username ?? null;
                    $item['GrandTotal']  = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal, 2, '.', ','));
                    $item['paid_amount'] = $this->render_price_with_symbol_placement(number_format($sale->paid_amount, 2, '.', ','));
                    $item['due']         = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal - $sale->paid_amount, 2, '.', ','));
                    $recent_sales_data[] = $item;
                    // dd($recent_sales_data);
                    
                    // dd($item);
                }


        return view('dashboard.dashboard_admin', [
            'today_sales' => $today_sales,
            'return_sales' => $return_sales,
            'return_purchases' => $return_purchases,
            'today_purchases' => $today_purchases,

            'sales_chart_data' => $sales_chart_data,
            'purchases_chart_data' => $purchases_chart_data,
            'days' => $days,

            'top_clients' => $top_clients,
            'top_products' => $top_products, 

            'recent_sales_data' => $recent_sales_data, 

        ]);


    }

    public function dashboard_filter(Request $request , $start_date , $end_date)
    {

        $end_date_default = Carbon::today()->format('Y-m-d');
        $start_date_default = Carbon::today()->format('Y-m-d');
        
        $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
        $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

        $today_sales = Sale::where('deleted_at', '=', null)
        ->whereDate('date', '>=', $start_date)
        ->whereDate('date', '<=', $end_date)
        ->sum('GrandTotal');

        $today_sales = $this->render_price_with_symbol_placement(number_format($today_sales, 2, '.', ','));


        $return_sales = SaleReturn::where('deleted_at', '=', null)
        ->whereDate('date', '>=', $start_date)
        ->whereDate('date', '<=', $end_date)
        ->sum('GrandTotal');

        $return_sales = $this->render_price_with_symbol_placement(number_format($return_sales, 2, '.', ','));


        $today_purchases = Purchase::where('deleted_at', '=', null)
        ->whereDate('date', '>=', $start_date)
        ->whereDate('date', '<=', $end_date)
        ->sum('GrandTotal');

        $today_purchases = $this->render_price_with_symbol_placement(number_format($today_purchases, 2, '.', ','));

        $return_purchases = PurchaseReturn::where('deleted_at', '=', null)
        ->whereDate('date', '>=', $start_date)
        ->whereDate('date', '<=', $end_date)
        ->sum('GrandTotal');

        $return_purchases = $this->render_price_with_symbol_placement(number_format($return_purchases, 2, '.', ','));

        return response()->json([
            'today_sales' => $today_sales,
            'return_sales' => $return_sales,
            'return_purchases' => $return_purchases,
            'today_purchases' => $today_purchases,

        ]);
    }


    public function dashboard_employee()
    {
        return view('dashboard.dashboard_employee');

    }

   

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
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