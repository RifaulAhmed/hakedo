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
use App\Models\PaymentMethod;
use App\Models\Account;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Product;
use App\Models\PaymentSale;
use App\Models\Currency;
use App\Models\SaleReturn;
use App\Models\Sale;
use App\Models\SaleDetail;
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
use Stripe;
use Config;
use DB;
use PDF;
use ArPHP\I18N\Arabic;
use App\utils\helpers;

class SalesController extends Controller
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
        if ($user_auth->can('sales_view_all') || $user_auth->can('sales_view_own')) {

            $clients = client::where('deleted_at', '=', null)->get(['id', 'username']);

            if ($user_auth->is_all_warehouses) {
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            } else {
                $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
            }

            return view('sales.list_sales', compact('clients', 'warehouses'));

        }
        return abort('403', __('You are not authorized'));
    }



    public function get_sales_datatable(Request $request)
    {
        $user_auth = auth()->user();
        if (!$user_auth->can('sales_view_all') && !$user_auth->can('sales_view_own')) {
            return abort('403', __('You are not authorized'));
        } else {
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
                2 => 'date',
                3 => 'Ref',
            );

            if ($user_auth->is_all_warehouses) {
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            } else {
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            if (empty($request->warehouse_id)) {
                $warehouse_id = 0;
            } else {
                $warehouse_id = $request->warehouse_id;
            }



            $start = $request->input('start');
            $order = 'sales.' . $columns_order[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
            $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
            $start_date = empty($request->start_date) ? $start_date_default : $request->start_date;
            $end_date = empty($request->end_date) ? $end_date_default : $request->end_date;


            $sales_data = Sale::where('deleted_at', '=', null)
                ->whereDate('date', '>=', $start_date)
                ->whereDate('date', '<=', $end_date)

                ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                    if ($warehouse_id !== 0) {
                        return $query->where('warehouse_id', $warehouse_id);
                    } else {
                        return $query->whereIn('warehouse_id', $array_warehouses_id);
                    }
                })

                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('sales_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                });

            // Filter
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

            if ($request->input('length') != -1)
                $limit = $request->input('length');
            else
                $limit = $totalRows;

            // $sales = $sales_Filtred
            //     ->with('client', 'warehouse', 'user')
            //     ->offset($start)
            //     ->limit($limit)
            //     ->orderBy($order, $dir)
            //     ->get();

            // $data = array();

            // foreach ($sales as $sale) {

            //     $item['id'] = $sale->id;
            //     $item['date'] = Carbon::parse($sale->date)->format('d-m-Y H:i');
            //     $item['created_by'] = $sale->user->username;
            //     $item['warehouse_name'] = $sale->warehouse->name;
            //     $item['client_name'] = $sale->client->username ;
            //     $item['client_email'] = $sale->client->email;
            //     $item['city_name'] = $sale->client->city;
            //     $item['GrandTotal'] = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal, 2, '.', ','));
            //     $item['paid_amount'] = $this->render_price_with_symbol_placement(number_format($sale->paid_amount, 2, '.', ','));
            //     $item['due'] = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal - $sale->paid_amount, 2, '.', ','));

            //     //payment_status
            //     if ($sale->payment_statut == 'paid') {
            //         $item['payment_status'] = '<span class="badge badge-outline-success">' . trans('translate.Paid') . '</span>';
            //     } else if ($sale->payment_statut == 'partial') {
            //         $item['payment_status'] = '<span class="badge badge-outline-info">' . trans('translate.Partial') . '</span>';
            //     } else {
            //         $item['payment_status'] = '<span class="badge badge-outline-warning">' . trans('translate.Unpaid') . '</span>';
            //     }


            //     if (SaleReturn::where('sale_id', $sale->id)->where('deleted_at', '=', null)->exists()) {
            //         $sale_has_return = 'yes';
            //         $item['Ref'] = $sale->Ref . ' ' . '<i class="text-15 text-danger i-Back"></i>';
            //     } else {
            //         $sale_has_return = 'no';
            //         $item['Ref'] = $sale->Ref;
            //     }

            //     $item['action'] = '<div class="dropdown">
            //                         <button class="btn btn-outline-info btn-rounded dropdown-toggle" id="dropdownMenuButton" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
            //         . trans('translate.Action') .
            //         '</button>
            //                         <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 34px, 0px);">';

            //     //check if user has permission "sales_details"
            //     if ($user_auth->can('sales_details')) {
            //         $item['action'] .= '<a class="dropdown-item" href="/sale/sales/' . $sale->id . '"> <i class="nav-icon i-Eye font-weight-bold mr-2"></i> ' . trans('translate.SaleDetail') . '</a>';
            //     }

            //     if ($user_auth->can('sales_edit') && $sale_has_return == 'no') {
            //         $item['action'] .= '<a class="dropdown-item" href="/sale/sales/' . $sale->id . '/edit" ><i class="nav-icon i-Edit font-weight-bold mr-2"></i> ' . trans('translate.EditSale') . '</a>';
            //     }

            //     if ($user_auth->can('sale_returns_add') && $sale_has_return == 'no') {
            //         $item['action'] .= '<a class="dropdown-item" href="/sales-return/add_returns_sale/' . $sale->id . '" ><i class="nav-icon i-Back font-weight-bold mr-2"></i> ' . trans('translate.Sell_Return') . '</a>';
            //     }

            //     //check if user has permission "payment_sales_view"
            //     if ($user_auth->can('payment_sales_view')) {
            //         $item['action'] .= '<a class="dropdown-item Show_Payments cursor-pointer"  id="' . $sale->id . '" > <i class="nav-icon i-Money-Bag font-weight-bold mr-2"></i> ' . trans('translate.ShowPayment') . '</a>';
            //     }

            //     //check if user has permission "payment_sales_add"
            //     if ($user_auth->can('payment_sales_add')) {
            //         $item['action'] .= '<a class="dropdown-item New_Payment cursor-pointer" payment_status="' . $sale->payment_statut . '"  id="' . $sale->id . '" > <i class="nav-icon i-Add font-weight-bold mr-2"></i> ' . trans('translate.AddPayment') . '</a>';
            //     }

            //     $item['action'] .= '<a class="dropdown-item" href="/invoice_pos/' . $sale->id . '" target=_blank> <i class="nav-icon i-File-TXT font-weight-bold mr-2"></i> ' . trans('translate.Invoice_POS') . '</a>
            //             <a class="dropdown-item download_pdf cursor-pointer" Ref="' . $sale->Ref . '" id="' . $sale->id . '" ><i class="nav-icon i-File-TXT font-weight-bold mr-2"></i> ' . trans('translate.DownloadPdf') . '</a>
            //             <a class="dropdown-item  send_email cursor-pointer" id="' . $sale->id . '" ><i class="nav-icon i-Envelope-2 font-weight-bold mr-2"></i> ' . trans('translate.EmailSale') . '</a>
            //             <a class="dropdown-item  send_sms cursor-pointer" id="' . $sale->id . '" ><i class="nav-icon i-Envelope-2 font-weight-bold mr-2"></i> ' . trans('translate.Send_sms') . '</a>';

            //     //check if user has permission "sales_delete"
            //     if ($user_auth->can('sales_delete') && $sale_has_return == 'no') {
            //         $item['action'] .= '<a class="dropdown-item delete cursor-pointer" id="' . $sale->id . '" > <i class="nav-icon i-Close-Window font-weight-bold mr-2"></i> ' . trans('translate.DeleteSale') . '</a>';
            //     }

            //     $item['action'] .= '</div>
            //     </div>';

            //     $data[] = $item;

            // }


            // $json_data = array(
            //     "draw" => intval($request->input('draw')),
            //     "recordsTotal" => intval($totalRows),
            //     "recordsFiltered" => intval($totalFiltered),
            //     "data" => $data
            // );

            // echo json_encode($json_data);

            $sales = $sales_Filtred
    ->with('client', 'warehouse', 'user') // Eager load relationships
    ->offset($start)
    ->limit($limit)
    ->orderBy($order, $dir)
    ->get();

$data = array();

foreach ($sales as $sale) {
    $item['id'] = $sale->id;
    $item['date'] = Carbon::parse($sale->date)->format('d-m-Y H:i');
    $item['created_by'] = optional($sale->user)->username; // Handle null case for username
    $item['warehouse_name'] = $sale->warehouse ? $sale->warehouse->name : 'N/A'; // Handle null case for warehouse
    $item['client_name'] = $sale->client ? $sale->client->username : 'N/A'; // Handle null case for client
    $item['client_email'] = $sale->client ? $sale->client->email : 'N/A'; // Handle null case for client
    $item['city_name'] = $sale->client ? $sale->client->city : 'N/A'; // Handle null case for client
    $item['GrandTotal'] = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal, 2, '.', ','));
    $item['paid_amount'] = $this->render_price_with_symbol_placement(number_format($sale->paid_amount, 2, '.', ','));
    $item['due'] = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal - $sale->paid_amount, 2, '.', ','));

    // Payment status
    if ($sale->payment_statut == 'paid') {
        $item['payment_status'] = '<span class="badge badge-outline-success">' . trans('translate.Paid') . '</span>';
    } else if ($sale->payment_statut == 'partial') {
        $item['payment_status'] = '<span class="badge badge-outline-info">' . trans('translate.Partial') . '</span>';
    } else {
        $item['payment_status'] = '<span class="badge badge-outline-warning">' . trans('translate.Unpaid') . '</span>';
    }

    if (SaleReturn::where('sale_id', $sale->id)->where('deleted_at', '=', null)->exists()) {
        $sale_has_return = 'yes';
        $item['Ref'] = $sale->Ref . ' ' . '<i class="text-15 text-danger i-Back"></i>';
    } else {
        $sale_has_return = 'no';
        $item['Ref'] = $sale->Ref;
    }

    $item['action'] = '<div class="dropdown">
                        <button class="btn btn-outline-info btn-rounded dropdown-toggle" id="dropdownMenuButton" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
        . trans('translate.Action') .
        '</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 34px, 0px);">';

    // Check if user has permission "sales_details"
    if ($user_auth->can('sales_details')) {
        $item['action'] .= '<a class="dropdown-item" href="/sale/sales/' . $sale->id . '"> <i class="nav-icon i-Eye font-weight-bold mr-2"></i> ' . trans('translate.SaleDetail') . '</a>';
    }

    if ($user_auth->can('sales_edit') && $sale_has_return == 'no') {
        $item['action'] .= '<a class="dropdown-item" href="/sale/sales/' . $sale->id . '/edit" ><i class="nav-icon i-Edit font-weight-bold mr-2"></i> ' . trans('translate.EditSale') . '</a>';
    }

    if ($user_auth->can('sale_returns_add') && $sale_has_return == 'no') {
        $item['action'] .= '<a class="dropdown-item" href="/sales-return/add_returns_sale/' . $sale->id . '" ><i class="nav-icon i-Back font-weight-bold mr-2"></i> ' . trans('translate.Sell_Return') . '</a>';
    }

    // Check if user has permission "payment_sales_view"
    if ($user_auth->can('payment_sales_view')) {
        $item['action'] .= '<a class="dropdown-item Show_Payments cursor-pointer"  id="' . $sale->id . '" > <i class="nav-icon i-Money-Bag font-weight-bold mr-2"></i> ' . trans('translate.ShowPayment') . '</a>';
    }

    // Check if user has permission "payment_sales_add"
    if ($user_auth->can('payment_sales_add')) {
        $item['action'] .= '<a class="dropdown-item New_Payment cursor-pointer" payment_status="' . $sale->payment_statut . '"  id="' . $sale->id . '" > <i class="nav-icon i-Add font-weight-bold mr-2"></i> ' . trans('translate.AddPayment') . '</a>';
    }

    $item['action'] .= '<a class="dropdown-item" href="/invoice_pos/' . $sale->id . '" target=_blank> <i class="nav-icon i-File-TXT font-weight-bold mr-2"></i> ' . trans('translate.Invoice_POS') . '</a>
                    <a class="dropdown-item download_pdf cursor-pointer" Ref="' . $sale->Ref . '" id="' . $sale->id . '" ><i class="nav-icon i-File-TXT font-weight-bold mr-2"></i> ' . trans('translate.DownloadPdf') . '</a>
                    <a class="dropdown-item  send_email cursor-pointer" id="' . $sale->id . '" ><i class="nav-icon i-Envelope-2 font-weight-bold mr-2"></i> ' . trans('translate.EmailSale') . '</a>
                    <a class="dropdown-item  send_sms cursor-pointer" id="' . $sale->id . '" ><i class="nav-icon i-Envelope-2 font-weight-bold mr-2"></i> ' . trans('translate.Send_sms') . '</a>';

    // Check if user has permission "sales_delete"
    if ($user_auth->can('sales_delete') && $sale_has_return == 'no') {
        $item['action'] .= '<a class="dropdown-item delete cursor-pointer" id="' . $sale->id . '" > <i class="nav-icon i-Close-Window font-weight-bold mr-2"></i> ' . trans('translate.DeleteSale') . '</a>';
    }

    $item['action'] .= '</div>
    </div>';

    $data[] = $item;
}

$json_data = array(
    "draw" => intval($request->input('draw')),
    "recordsTotal" => intval($totalRows),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
);

return response()->json($json_data);

        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $user_auth = auth()->user();
        if ($user_auth->can('sales_add')) {

            //get warehouses 
            if ($user_auth->is_all_warehouses) {
                $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            } else {
                $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
            }

            $clients = client::where('deleted_at', '=', null)
                ->get(['id', 'username']);

            return view(
                'sales.create_sale',
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
        if ($user_auth->can('sales_add')) {

            \DB::transaction(function () use ($request) {
                $order = new Sale;

                $order->is_pos = 0;
                $order->date = $request->date;
                $order->Ref = 'SO-' . date("Ymd") . '-' . date("his");
                $order->client_id = $request->client_id;
                $order->GrandTotal = $request->GrandTotal;
                $order->warehouse_id = $request->warehouse_id;
                $order->tax_rate = $request->tax_rate;
                $order->TaxNet = $request->TaxNet;

                $order->discount = $request->discount;
                $order->discount_type = $request->discount_type;
                $order->discount_percent_total = $request->discount_percent_total;

                $order->shipping = $request->shipping;
                $order->statut = 'completed';
                $order->payment_statut = 'unpaid';
                $order->notes = $request->notes;
                $order->user_id = Auth::user()->id;
                $order->save();

                $data = $request['details'];
                foreach ($data as $key => $value) {
                    $unit = Unit::where('id', $value['sale_unit_id'])
                        ->first();
                    $orderDetails[] = [
                        'date' => $request->date,
                        'sale_id' => $order->id,
                        'sale_unit_id' => $value['sale_unit_id'] ? $value['sale_unit_id'] : NULL,
                        'quantity' => $value['quantity'],
                        'price' => $value['Unit_price'],
                        'TaxNet' => $value['tax_percent'],
                        'tax_method' => $value['tax_method'],
                        'discount' => $value['discount'],
                        'discount_method' => $value['discount_Method'],
                        'product_id' => $value['product_id'],
                        'product_variant_id' => $value['product_variant_id'] ? $value['product_variant_id'] : NULL,
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
                                $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
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
                                $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse->save();
                        }
                    }
                }
                SaleDetail::insert($orderDetails);

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
        if ($user_auth->can('sales_details')) {

            if ($user_auth->is_all_warehouses) {
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            } else {
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            $sale_data = Sale::with('details.product.unitSale')
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

            $sale_details['id'] = $sale_data->id;
            $sale_details['Ref'] = $sale_data->Ref;
            $sale_details['date'] = $sale_data->date;
            $sale_details['note'] = $sale_data->notes;
            $sale_details['statut'] = $sale_data->statut;
            $sale_details['warehouse'] = $sale_data['warehouse']->name;

            if ($sale_data->discount_type == 'fixed') {
                $sale_details['discount'] = $this->render_price_with_symbol_placement(number_format($sale_data->discount, 2, '.', ','));
            } else {
                $sale_details['discount'] = $this->render_price_with_symbol_placement(number_format($sale_data->discount_percent_total, 2, '.', ',')) . ' ' . '(' . $sale_data->discount . ' ' . '%)';
            }

            $sale_details['shipping'] = $this->render_price_with_symbol_placement(number_format($sale_data->shipping, 2, '.', ','));
            $sale_details['tax_rate'] = $sale_data->tax_rate;
            $sale_details['TaxNet'] = $this->render_price_with_symbol_placement(number_format($sale_data->TaxNet, 2, '.', ','));
            $sale_details['client_name'] = $sale_data['client']->username;
            $sale_details['client_phone'] = $sale_data['client']->phone;
            $sale_details['client_adr'] = $sale_data['client']->address;
            $sale_details['client_email'] = $sale_data['client']->email;
            $sale_details['GrandTotal'] = $this->render_price_with_symbol_placement(number_format($sale_data->GrandTotal, 2, '.', ','));
            $sale_details['paid_amount'] = $this->render_price_with_symbol_placement(number_format($sale_data->paid_amount, 2, '.', ','));
            $sale_details['due'] = $this->render_price_with_symbol_placement(number_format($sale_data->GrandTotal - $sale_data->paid_amount, 2, '.', ','));
            $sale_details['payment_status'] = $sale_data->payment_statut;


            if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
                $sale_details['sale_has_return'] = 'yes';
            } else {
                $sale_details['sale_has_return'] = 'no';
            }


            foreach ($sale_data['details'] as $detail) {

                $unit = Unit::where('id', $detail->sale_unit_id)->first();
                if ($detail->product_variant_id) {

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();

                    $data['code'] = $productsVariants->code;
                    $data['name'] = '[' . $productsVariants->name . '] ' . $detail['product']['name'];

                } else {
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];
                }

                $data['quantity'] = $detail->quantity;
                $data['total'] = $detail->total;
                $data['price'] = $detail->price;
                $data['unit_sale'] = $unit ? $unit->ShortName : '';

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

            return view(
                'sales.details_sale',
                [
                    'sale' => $sale_details,
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

        if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
            return response()->json(['success' => false, 'Return exist for the Transaction' => false], 403);
        } else {

            $user_auth = auth()->user();
            if ($user_auth->can('sales_edit')) {

                //get warehouses 
                if ($user_auth->is_all_warehouses) {
                    $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
                    $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

                } else {
                    $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
                    $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $array_warehouses_id)->get(['id', 'name']);
                }

                $Sale_data = Sale::with('details.product.unitSale')
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

                if ($Sale_data->client_id) {
                    if (
                        $client_data = Client::where('id', $Sale_data->client_id)
                            ->where('deleted_at', '=', null)
                            ->first()
                    ) {
                        $sale['client_id'] = $Sale_data->client_id;

                    } else {
                        $sale['client_id'] = '';
                    }
                } else {
                    $sale['client_id'] = '';
                }

                if ($Sale_data->warehouse_id) {
                    if (
                        Warehouse::where('id', $Sale_data->warehouse_id)
                            ->where('deleted_at', '=', null)
                            ->first()
                    ) {
                        $sale['warehouse_id'] = $Sale_data->warehouse_id;
                    } else {
                        $sale['warehouse_id'] = '';
                    }
                } else {
                    $sale['warehouse_id'] = '';
                }

                $sale['id'] = $Sale_data->id;
                $sale['date'] = $Sale_data->date;
                $sale['tax_rate'] = $Sale_data->tax_rate;
                $sale['TaxNet'] = $Sale_data->TaxNet;
                $sale['discount'] = $Sale_data->discount;
                $sale['discount_type'] = $Sale_data->discount_type;
                $sale['discount_percent_total'] = $Sale_data->discount_percent_total;
                $sale['shipping'] = $Sale_data->shipping;
                $sale['statut'] = $Sale_data->statut;
                $sale['notes'] = $Sale_data->notes;
                $sale['GrandTotal'] = $Sale_data->GrandTotal;

                $detail_id = 0;
                foreach ($Sale_data['details'] as $detail) {

                    $unit = Unit::where('id', $detail->sale_unit_id)->first();

                    if ($detail->product_variant_id) {
                        $item_product = product_warehouse::where('product_id', $detail->product_id)
                            ->where('deleted_at', '=', null)
                            ->where('product_variant_id', $detail->product_variant_id)
                            ->where('warehouse_id', $Sale_data->warehouse_id)
                            ->first();

                        $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                            ->where('id', $detail->product_variant_id)->first();

                        $item_product ? $data['del'] = 0 : $data['del'] = 1;
                        $data['product_variant_id'] = $detail->product_variant_id;

                        $data['code'] = $productsVariants->code;
                        $data['name'] = '[' . $productsVariants->name . '] ' . $detail['product']['name'];

                        if ($unit && $unit->operator == '/') {
                            $stock = $item_product ? $item_product->qte * $unit->operator_value : 0;
                        } else if ($unit && $unit->operator == '*') {
                            $stock = $item_product ? $item_product->qte / $unit->operator_value : 0;
                        } else {
                            $stock = 0;
                        }

                    } else {
                        $item_product = product_warehouse::where('product_id', $detail->product_id)
                            ->where('deleted_at', '=', null)->where('warehouse_id', $Sale_data->warehouse_id)
                            ->where('product_variant_id', '=', null)->first();

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
                    $data['stock'] = $detail['product']['type'] != 'is_service' ? $stock : '---';
                    $data['product_type'] = $detail['product']['type'];
                    $data['qty_min'] = $detail['product']['type'] != 'is_service' ? $detail['product']['qty_min'] : '---';
                    $data['detail_id'] = $detail_id += 1;
                    $data['product_id'] = $detail->product_id;
                    $data['total'] = $detail->total;
                    $data['quantity'] = $detail->quantity;
                    $data['qte_copy'] = $detail->quantity;
                    $data['etat'] = 'current';
                    $data['unitSale'] = $unit ? $unit->ShortName : '';
                    $data['sale_unit_id'] = $unit ? $unit->id : '';
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
                    ->where('warehouse_id', $Sale_data->warehouse_id)
                    ->where('deleted_at', '=', null)
                    ->where('qte', '>', 0)->orWhere('manage_stock', false)
                    ->get();

                foreach ($get_product_warehouse_data as $product_warehouse) {

                    if ($product_warehouse->product_variant_id) {

                        $item['product_variant_id'] = $product_warehouse->product_variant_id;
                        $item['code'] = $product_warehouse['productVariant']->code;
                        $item['Variant'] = '[' . $product_warehouse['productVariant']->name . ']' . $product_warehouse['product']->name;
                        $item['name'] = '[' . $product_warehouse['productVariant']->name . ']' . $product_warehouse['product']->name;
                        $item['barcode'] = $product_warehouse['productVariant']->code;

                        $product_price = $product_warehouse['productVariant']->price;

                    } else {
                        $item['product_variant_id'] = null;
                        $item['Variant'] = null;
                        $item['code'] = $product_warehouse['product']->code;
                        $item['name'] = $product_warehouse['product']->name;
                        $item['barcode'] = $product_warehouse['product']->code;

                        $product_price = $product_warehouse['product']->price;
                    }

                    $item['id'] = $product_warehouse->product_id;
                    $item['product_type'] = $product_warehouse['product']->type;
                    $item['qty_min'] = $product_warehouse['product']->qty_min;
                    $item['barcode'] = $product_warehouse['product']->code;
                    $item['Type_barcode'] = $product_warehouse['product']->Type_barcode;
                    $item['image'] = $product_warehouse['product']->image;

                    if ($product_warehouse['product']['unitSale']) {

                        if ($product_warehouse['product']['unitSale']->operator == '/') {
                            $item['qte_sale'] = $product_warehouse->qte * $product_warehouse['product']['unitSale']->operator_value;
                            $price = $product_price / $product_warehouse['product']['unitSale']->operator_value;

                        } else {
                            $item['qte_sale'] = $product_warehouse->qte / $product_warehouse['product']['unitSale']->operator_value;
                            $price = $product_price * $product_warehouse['product']['unitSale']->operator_value;
                        }

                    } else {
                        $item['qte_sale'] = $product_warehouse->qte;
                        $price = $product_price;
                    }

                    if ($product_warehouse['product']['unitPurchase']) {

                        if ($product_warehouse['product']['unitPurchase']->operator == '/') {
                            $item['qte_purchase'] = round($product_warehouse->qte * $product_warehouse['product']['unitPurchase']->operator_value, 5);

                        } else {
                            $item['qte_purchase'] = round($product_warehouse->qte / $product_warehouse['product']['unitPurchase']->operator_value, 5);
                        }

                    } else {
                        $item['qte_purchase'] = $product_warehouse->qte;
                    }


                    $item['qte'] = $product_warehouse->qte;
                    $item['unitSale'] = $product_warehouse['product']['unitSale'] ? $product_warehouse['product']['unitSale']->ShortName : '';
                    $item['unitPurchase'] = $product_warehouse['product']['unitPurchase'] ? $product_warehouse['product']['unitPurchase']->ShortName : '';

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


                $clients = client::where('deleted_at', '=', null)->get(['id', 'username']);

                return view(
                    'sales.edit_sale',
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

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
            return response()->json(['success' => false, 'Return exist for the Transaction' => false], 403);
        } else {

            $user_auth = auth()->user();
            if ($user_auth->can('sales_edit')) {

                \DB::transaction(function () use ($request, $id) {

                    $current_Sale = Sale::findOrFail($id);

                    $old_sale_details = SaleDetail::where('sale_id', $id)->get();
                    $new_sale_details = $request['details'];
                    $length = sizeof($new_sale_details);

                    // Get Ids for new Details
                    $new_products_id = [];
                    foreach ($new_sale_details as $new_detail) {
                        $new_products_id[] = $new_detail['id'];
                    }

                    // Init Data with old Parametre
                    $old_products_id = [];
                    foreach ($old_sale_details as $key => $value) {
                        $old_products_id[] = $value->id;

                        $old_unit = Unit::where('id', $value['sale_unit_id'])->first();


                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse && $old_unit) {
                                if ($old_unit->operator == '/') {
                                    $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                } else {
                                    $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();
                            if ($product_warehouse && $old_unit) {
                                if ($old_unit->operator == '/') {
                                    $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                } else {
                                    $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }

                        // Delete Detail
                        if (!in_array($old_products_id[$key], $new_products_id)) {
                            $SaleDetail = SaleDetail::findOrFail($value->id);
                            $SaleDetail->delete();
                        }

                    }

                    // Update Data with New request
                    foreach ($new_sale_details as $prd => $prod_detail) {

                        $unit_prod = Unit::where('id', $prod_detail['sale_unit_id'])->first();


                        if ($prod_detail['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $prod_detail['product_id'])
                                ->where('product_variant_id', $prod_detail['product_variant_id'])
                                ->first();

                            if ($product_warehouse && $unit_prod) {
                                if ($unit_prod->operator == '/') {
                                    $product_warehouse->qte -= $prod_detail['quantity'] / $unit_prod->operator_value;
                                } else {
                                    $product_warehouse->qte -= $prod_detail['quantity'] * $unit_prod->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $prod_detail['product_id'])
                                ->first();

                            if ($product_warehouse && $unit_prod) {
                                if ($unit_prod->operator == '/') {
                                    $product_warehouse->qte -= $prod_detail['quantity'] / $unit_prod->operator_value;
                                } else {
                                    $product_warehouse->qte -= $prod_detail['quantity'] * $unit_prod->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }


                        $orderDetails['sale_id'] = $id;
                        $orderDetails['date'] = $request['date'];
                        $orderDetails['price'] = $prod_detail['Unit_price'];
                        $orderDetails['sale_unit_id'] = $prod_detail['sale_unit_id'] ? $prod_detail['sale_unit_id'] : NULL;
                        $orderDetails['TaxNet'] = $prod_detail['tax_percent'];
                        $orderDetails['tax_method'] = $prod_detail['tax_method'];
                        $orderDetails['discount'] = $prod_detail['discount'];
                        $orderDetails['discount_method'] = $prod_detail['discount_Method'];
                        $orderDetails['quantity'] = $prod_detail['quantity'];
                        $orderDetails['product_id'] = $prod_detail['product_id'];
                        $orderDetails['product_variant_id'] = $prod_detail['product_variant_id'] ? $prod_detail['product_variant_id'] : NULL;
                        $orderDetails['total'] = $prod_detail['subtotal'];
                        $orderDetails['imei_number'] = $prod_detail['imei_number'];

                        if (!in_array($prod_detail['id'], $old_products_id)) {
                            $orderDetails['sale_unit_id'] = $unit_prod ? $unit_prod->id : Null;
                            SaleDetail::Create($orderDetails);
                        } else {
                            SaleDetail::where('id', $prod_detail['id'])->update($orderDetails);
                        }
                    }

                    $due = $request['GrandTotal'] - $current_Sale->paid_amount;
                    if ($due === 0.0 || $due < 0.0) {
                        $payment_statut = 'paid';
                    } else if ($due != $request['GrandTotal']) {
                        $payment_statut = 'partial';
                    } else if ($due == $request['GrandTotal']) {
                        $payment_statut = 'unpaid';
                    }


                    $current_Sale->update([
                        'date' => $request['date'],
                        'client_id' => $request['client_id'],
                        'warehouse_id' => $request['warehouse_id'],
                        'notes' => $request['notes'],
                        'statut' => $request['statut'],
                        'tax_rate' => $request['tax_rate'],
                        'TaxNet' => $request['TaxNet'],
                        'discount' => $request['discount'],
                        'discount_type' => $request['discount_type'],
                        'discount_percent_total' => $request['discount_percent_total'],
                        'shipping' => $request['shipping'],
                        'GrandTotal' => $request['GrandTotal'],
                        'payment_statut' => $payment_statut,
                    ]);

                }, 10);

                return response()->json(['success' => true]);

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

        if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
            return response()->json(['success' => false, 'Return exist for the Transaction' => false], 403);
        } else {

            $user_auth = auth()->user();
            if ($user_auth->can('sales_delete')) {

                \DB::transaction(function () use ($id) {
                    $current_Sale = Sale::findOrFail($id);
                    $old_sale_details = SaleDetail::where('sale_id', $id)->get();

                    foreach ($old_sale_details as $key => $value) {

                        $old_unit = Unit::where('id', $value['sale_unit_id'])->first();

                        if ($value['product_variant_id']) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse && $old_unit) {
                                if ($old_unit->operator == '/') {
                                    $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                } else {
                                    $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();
                            if ($product_warehouse && $old_unit) {
                                if ($old_unit->operator == '/') {
                                    $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                } else {
                                    $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }

                    }

                    $current_Sale->details()->delete();
                    $current_Sale->update([
                        'deleted_at' => Carbon::now(),
                    ]);

                    // get all payments
                    $payments = PaymentSale::where('sale_id', $id)->get();

                    foreach ($payments as $payment) {

                        $account = Account::find($payment->account_id);

                        if ($account) {
                            $account->update([
                                'initial_balance' => $account->initial_balance - $payment->montant,
                            ]);
                        }

                    }

                    PaymentSale::where('sale_id', $id)->update([
                        'deleted_at' => Carbon::now(),
                    ]);


                }, 10);

                return response()->json(['success' => true], 200);

            }
            return abort('403', __('You are not authorized'));
        }
    }

    //-------------- Print Invoice ---------------\\

    public function Print_Invoice_POS(Request $request, $id)
    {
        $details = array();
        $user_auth = auth()->user();

        $sale = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('sales_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })->findOrFail($id);

        $item['id'] = $sale->id;
        $item['Ref'] = $sale->Ref;
        $item['date'] = Carbon::parse($sale->date)->format('d-m-Y H:i');
        $item['shipping'] = $this->render_price_with_symbol_placement(number_format($sale->shipping, 2, '.', ','));

        if ($sale->discount_type == 'fixed') {
            $item['discount'] = $this->render_price_with_symbol_placement(number_format($sale->discount, 2, '.', ','));
        } else {
            $item['discount'] = $this->render_price_with_symbol_placement(number_format($sale->discount_percent_total, 2, '.', ',')) . '(' . $sale->discount . ' ' . '%)';
        }

        $item['taxe'] = $this->render_price_with_symbol_placement(number_format($sale->TaxNet, 2, '.', ','));
        $item['tax_rate'] = $sale->tax_rate;
        $item['client_name'] = $sale['client']->username;
        $item['GrandTotal'] = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal, 2, '.', ','));
        $item['paid_amount'] = $this->render_price_with_symbol_placement(number_format($sale->paid_amount, 2, '.', ','));

        foreach ($sale['details'] as $detail) {

            $unit = Unit::where('id', $detail->sale_unit_id)->first();
            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                $data['name'] = $productsVariants->name . '-' . $detail['product']['name'];

            } else {
                $data['code'] = $detail['product']['code'];
                $data['name'] = $detail['product']['name'];
            }

            $data['price'] = $detail->price;
            $data['quantity'] = $detail->quantity;
            $data['total'] = $detail->total;
            $data['unit_sale'] = $unit ? $unit->ShortName : '';

            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            $details[] = $data;
        }

        $payments = PaymentSale::with('sale')
            ->where('sale_id', $id)
            ->orderBy('id', 'DESC')
            ->get();

        $settings = Setting::where('deleted_at', '=', null)->first();

        return response()->json([
            'payments' => $payments,
            'setting' => $settings,
            'sale' => $item,
            'details' => $details,
        ]);

    }

    //------------- GET PAYMENTS SALE -----------\\

    public function Payments_Sale(Request $request, $id)
    {

        $Sale = Sale::findOrFail($id);
        $payments = PaymentSale::with('sale')
            ->where('sale_id', $id)
            ->orderBy('id', 'DESC')->get();

        $due = $Sale->GrandTotal - $Sale->paid_amount;

        $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id', 'title']);
        $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id', 'account_name']);


        return response()->json([
            'payments' => $payments,
            'due' => $due,
            'payment_methods' => $payment_methods,
            'accounts' => $accounts,
        ]);

    }



    //------------- Reference Number Order SALE -----------\\

    public function getNumberOrder()
    {

        $last = DB::table('sales')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'V_1';
        }
        return $code;
    }

    //------------- SALE PDF -----------\\

    public function Sale_PDF(Request $request, $id)
    {

        $details = array();
        $user_auth = auth()->user();

        $sale_data = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('sales_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })->findOrFail($id);

        $sale['client_name'] = $sale_data['client']->username;
        $sale['client_phone'] = $sale_data['client']->phone;
        $sale['client_adr'] = $sale_data['client']->address;
        $sale['client_email'] = $sale_data['client']->email;
        $sale['tax_rate'] = number_format($sale_data->tax_rate, 2, '.', ' ');
        $sale['TaxNet'] = $this->render_price_with_symbol_placement(number_format($sale_data->TaxNet, 2, '.', ','));

        if ($sale_data->discount_type == 'fixed') {
            $sale['discount'] = $this->render_price_with_symbol_placement(number_format($sale_data->discount, 2, '.', ','));
        } else {
            $sale['discount'] = $this->render_price_with_symbol_placement(number_format($sale_data->discount_percent_total, 2, '.', ',')) . ' ' . '(' . $sale_data->discount . ' ' . '%)';
        }

        $sale['shipping'] = $this->render_price_with_symbol_placement(number_format($sale_data->shipping, 2, '.', ','));
        $sale['statut'] = $sale_data->statut;
        $sale['Ref'] = $sale_data->Ref;
        $sale['date'] = Carbon::parse($sale_data->date)->format('d-m-Y H:i');
        $sale['GrandTotal'] = $this->render_price_with_symbol_placement(number_format($sale_data->GrandTotal, 2, '.', ','));
        $sale['paid_amount'] = $this->render_price_with_symbol_placement(number_format($sale_data->paid_amount, 2, '.', ','));
        $sale['due'] = $this->render_price_with_symbol_placement(number_format($sale_data->GrandTotal - $sale_data->paid_amount, 2, '.', ','));
        $sale['payment_status'] = $sale_data->payment_statut;

        $detail_id = 0;
        foreach ($sale_data['details'] as $detail) {

            $unit = Unit::where('id', $detail->sale_unit_id)->first();
            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                $data['code'] = $productsVariants->code;
                $data['name'] = '[' . $productsVariants->name . '] ' . $detail['product']['name'];
            } else {
                $data['code'] = $detail['product']['code'];
                $data['name'] = $detail['product']['name'];
            }

            $data['detail_id'] = $detail_id += 1;
            $data['quantity'] = number_format($detail->quantity, 2, '.', '');
            $data['total'] = number_format($detail->total, 2, '.', ' ');
            $data['unitSale'] = $unit ? $unit->ShortName : '';
            $data['price'] = number_format($detail->price, 2, '.', ' ');

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = number_format($detail->discount, 2, '.', '');
            } else {
                $data['DiscountNet'] = number_format($detail->price * $detail->discount / 100, 2, '.', '');
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = number_format($detail->price, 2, '.', '');
            $data['discount'] = number_format($detail->discount, 2, '.', '');

            if ($detail->tax_method == '1') {
                $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['taxe'] = number_format($tax_price, 2, '.', '');
            } else {
                $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe'] = number_format($detail->price - $data['Net_price'] - $data['DiscountNet'], 2, '.', '');
            }

            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            $details[] = $data;
        }
        $settings = Setting::where('deleted_at', '=', null)->first();

        $Html = view('pdf.sale_pdf', [
            'setting' => $settings,
            'sale' => $sale,
            'details' => $details,
        ])->render();

        $arabic = new Arabic();
        $p = $arabic->arIdentify($Html);

        for ($i = count($p) - 1; $i >= 0; $i -= 2) {
            $utf8ar = $arabic->utf8Glyphs(substr($Html, $p[$i - 1], $p[$i] - $p[$i - 1]));
            $Html = substr_replace($Html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
        }

        $pdf = PDF::loadHTML($Html);

        return $pdf->download('Sale.pdf');
        //----------


    }

    //------------- Send sale on Email -----------\\

    public function Send_Email(Request $request)
    {
        //sale
        $sale = Sale::with('client')->where('deleted_at', '=', null)->findOrFail($request->id);

        $helpers = new helpers();
        $currency = $helpers->Get_Currency();

        //settings
        $settings = Setting::where('deleted_at', '=', null)->first();

        //the custom msg of sale
        $emailMessage = EmailMessage::where('name', 'sale')->first();

        if ($emailMessage) {
            $message_body = $emailMessage->body;
            $message_subject = $emailMessage->subject;
        } else {
            $message_body = '';
            $message_subject = '';
        }

        //Tags
        $random_number = Str::random(10);
        $invoice_url = url('/sell_url/' . $request->id . '?' . $random_number);
        $invoice_number = $sale->Ref;

        $total_amount = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal, 2, '.', ','));
        $paid_amount = $this->render_price_with_symbol_placement(number_format($sale->paid_amount, 2, '.', ','));
        $due_amount = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal - $sale->paid_amount, 2, '.', ','));

        $contact_name = $sale['client']->username;
        $business_name = $settings->CompanyName;

        //receiver email
        $receiver_email = $sale['client']->email;

        //replace the text with tags
        $message_body = str_replace('{contact_name}', $contact_name, $message_body);
        $message_body = str_replace('{business_name}', $business_name, $message_body);
        $message_body = str_replace('{invoice_url}', $invoice_url, $message_body);
        $message_body = str_replace('{invoice_number}', $invoice_number, $message_body);

        $message_body = str_replace('{total_amount}', $total_amount, $message_body);
        $message_body = str_replace('{paid_amount}', $paid_amount, $message_body);
        $message_body = str_replace('{due_amount}', $due_amount, $message_body);

        $email['subject'] = $message_subject;
        $email['body'] = $message_body;
        $email['company_name'] = $business_name;

        $this->Set_config_mail();

        $mail = Mail::to($receiver_email)->send(new CustomEmail($email));

        return $mail;
    }

    // Set config mail
    public function Set_config_mail()
    {
        $config = array(
            'driver' => env('MAIL_MAILER'),
            'host' => env('MAIL_HOST'),
            'port' => env('MAIL_PORT'),
            'from' => array('address' => env('MAIL_FROM_ADDRESS'), 'name' => env('MAIL_FROM_NAME')),
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


    //-------------------Sms Notifications -----------------\\

    public function Send_SMS(Request $request)
    {

        //sale
        $sale = Sale::with('client')->where('deleted_at', '=', null)->findOrFail($request->id);

        $helpers = new helpers();
        $currency = $helpers->Get_Currency();

        //settings
        $settings = Setting::where('deleted_at', '=', null)->first();

        //the custom msg of sale
        $smsMessage = SMSMessage::where('name', 'sale')->first();

        if ($smsMessage) {
            $message_text = $smsMessage->text;
        } else {
            $message_text = '';
        }

        //Tags
        $random_number = Str::random(10);
        $invoice_url = url('/sell_url/' . $request->id . '?' . $random_number);
        $invoice_number = $sale->Ref;

        $total_amount = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal, 2, '.', ','));
        $paid_amount = $this->render_price_with_symbol_placement(number_format($sale->paid_amount, 2, '.', ','));
        $due_amount = $this->render_price_with_symbol_placement(number_format($sale->GrandTotal - $sale->paid_amount, 2, '.', ','));

        $contact_name = $sale['client']->username;
        $business_name = $settings->CompanyName;

        //receiver Number
        $receiverNumber = $sale['client']->phone;

        //replace the text with tags
        $message_text = str_replace('{contact_name}', $contact_name, $message_text);
        $message_text = str_replace('{business_name}', $business_name, $message_text);
        $message_text = str_replace('{invoice_url}', $invoice_url, $message_text);
        $message_text = str_replace('{invoice_number}', $invoice_number, $message_text);

        $message_text = str_replace('{total_amount}', $total_amount, $message_text);
        $message_text = str_replace('{paid_amount}', $paid_amount, $message_text);
        $message_text = str_replace('{due_amount}', $due_amount, $message_text);

        //twilio
        if ($settings->default_sms_gateway == "twilio") {
            try {

                $account_sid = env("TWILIO_SID");
                $auth_token = env("TWILIO_TOKEN");
                $twilio_number = env("TWILIO_FROM");

                $client = new Client_Twilio($account_sid, $auth_token);
                $client->messages->create($receiverNumber, [
                    'from' => $twilio_number,
                    'body' => $message_text
                ]);

            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            //nexmo
        } elseif ($settings->default_sms_gateway == "nexmo") {
            try {

                $basic = new \Nexmo\Client\Credentials\Basic(env("NEXMO_KEY"), env("NEXMO_SECRET"));
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
        } elseif ($settings->default_sms_gateway == "infobip") {

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
                echo ("HTTP Code: " . $apiException->getCode() . "\n");
            }

        }

        return response()->json(['success' => true]);


    }


    // render_price_with_symbol_placement

    public function render_price_with_symbol_placement($amount)
    {

        if ($this->symbol_placement == 'before') {
            return $this->currency . ' ' . $amount;
        } else {
            return $amount . ' ' . $this->currency;
        }
    }

}