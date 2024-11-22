<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Mail\Purchase_Return_Mail;
use App\Models\PaymentMethod;
use App\Models\Account;
use App\Models\Product;
use App\Models\PaymentPurchaseReturns;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetails;
use App\Models\Currency;
use App\Models\Unit;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
use App\Models\Provider;
use App\Models\User;
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

class PurchasesReturnController extends Controller
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
        if ($user_auth->can('purchase_returns_view_all') || $user_auth->can('purchase_returns_view_own')) {

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
                $param = array(
                    0 => 'like',
                    1 => '=',
                    2 => 'like',
                    3 => '=',
                    4 => '=',
                );
                $columns = array(
                    0 => 'Ref',
                    1 => 'provider_id',
                    2 => 'payment_statut',
                    3 => 'warehouse_id',
                    4 => 'purchase_id',
                );

                $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
                $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
                $start_date = empty($request->start_date) ? $start_date_default : $request->start_date;
                $end_date = empty($request->end_date) ? $end_date_default : $request->end_date;

                $data = PurchaseReturn::where('deleted_at', '=', null)
                    ->where(function ($query) use ($request, $warehouse_id, $array_warehouses_id) {
                        if ($warehouse_id !== 0) {
                            return $query->where('warehouse_id', $warehouse_id);
                        } else {
                            return $query->whereIn('warehouse_id', $array_warehouses_id);
                        }
                    })

                    ->whereDate('date', '>=', $start_date)
                    ->whereDate('date', '<=', $end_date)
                    ->where(function ($query) use ($user_auth) {
                        if (!$user_auth->can('purchase_returns_view_all')) {
                            return $query->where('user_id', '=', $user_auth->id);
                        }
                    })->with('purchase', 'facture', 'provider', 'warehouse')
                    ->orderBy('id', 'desc');

                //Multiple Filter
                $return_Filtred = $helpers->filter($data, $columns, $param, $request)->get();

                return Datatables::of($return_Filtred)
                    ->setRowId(function ($return_Filtred) {
                        return $return_Filtred->id;
                    })

                    ->addColumn('date', function ($row) {
                        return Carbon::parse($row->date)->format('d-m-Y H:i');
                    })

                    ->addColumn('purchase_id', function ($row) {
                        return $row->purchase->id;
                    })

                    ->addColumn('purchase_ref', function ($row) {
                        return $row->purchase->Ref;
                    })

                    ->addColumn('Ref', function ($row) {
                        return $row->Ref;
                    })

                    ->addColumn('warehouse_name', function ($row) {
                        return $row->warehouse->name;
                    })
                    ->addColumn('provider_name', function ($row) {
                        return $row->provider->name;
                    })
                    ->addColumn('GrandTotal', function ($row) {
                        return $this->render_price_with_symbol_placement(number_format($row->GrandTotal, 2, '.', ','));
                    })
                    ->addColumn('paid_amount', function ($row) {
                        return $this->render_price_with_symbol_placement(number_format($row->paid_amount, 2, '.', ','));
                    })
                    ->addColumn('due', function ($row) {
                        return $this->render_price_with_symbol_placement(number_format($row->GrandTotal - $row->paid_amount, 2, '.', ','));
                    })


                    ->addColumn('payment_status', function ($row) {
                        if ($row->payment_statut == 'paid') {
                            $span = '<span class="badge badge-success">' . trans('translate.Paid') . '</span>';
                        } else if ($row->payment_statut == 'partial') {
                            $span = '<span class="badge badge-info">' . trans('translate.Partial') . '</span>';
                        } else {
                            $span = '<span class="badge badge-warning">' . trans('translate.Unpaid') . '</span>';
                        }
                        return $span;
                    })

                    ->addColumn('action', function ($row) use ($user_auth) {

                        $btn = '<div class="dropdown">
                                <button class="btn btn-outline-info btn-rounded dropdown-toggle" id="dropdownMenuButton" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                            . trans('translate.Action') .

                            '</button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 34px, 0px);">
                                    <a class="dropdown-item" href="/purchase-return/returns_purchase/' . $row->id . '"> <i class="nav-icon i-Eye font-weight-bold mr-2"></i> ' . trans('translate.Details_Return') . '</a>';

                        //check if user has permission "purchase_returns_edit"
    
                        if ($user_auth->can('purchase_returns_edit')) {
                            $btn .= '<a class="dropdown-item" href="/purchase-return/edit_returns_purchase/' . $row->id . '/' . $row->purchase_id . '" ><i class="nav-icon i-Edit font-weight-bold mr-2"></i> ' . trans('translate.Edit_Return') . '</a>';
                        }

                        //check if user has permission "payment_purchase_returns_view"
                        if ($user_auth->can('payment_purchase_returns_view')) {
                            $btn .= '<a class="dropdown-item Show_Payments cursor-pointer"  id="' . $row->id . '" > <i class="nav-icon i-Money-Bag font-weight-bold mr-2"></i> ' . trans('translate.ShowPayment') . '</a>';
                        }

                        //check if user has permission "payment_purchase_returns_add"
                        if ($user_auth->can('payment_purchase_returns_add')) {
                            $btn .= '<a class="dropdown-item New_Payment cursor-pointer" payment_status="' . $row->payment_statut . '"  id="' . $row->id . '" > <i class="nav-icon i-Add font-weight-bold mr-2"></i> ' . trans('translate.AddPayment') . '</a>';
                        }

                        $btn .= '<a class="dropdown-item download_pdf cursor-pointer" Ref="' . $row->Ref . '" id="' . $row->id . '" ><i class="nav-icon i-File-TXT font-weight-bold mr-2"></i> ' . trans('translate.DownloadPdf') . '</a>';

                        //check if user has permission "purchase_returns_delete"
                        if ($user_auth->can('purchase_returns_delete')) {
                            $btn .= '<a class="dropdown-item delete cursor-pointer" id="' . $row->id . '" > <i class="nav-icon i-Close-Window font-weight-bold mr-2"></i> ' . trans('translate.Delete_Return') . '</a>';
                        }
                        $btn .= '</div>
                            </div>';


                        return $btn;
                    })
                    ->rawColumns(['action', 'payment_status'])
                    ->make(true);
            }

            $suppliers = Provider::where('deleted_at', '=', null)->get(['id', 'name']);
            $purchases = Purchase::where('deleted_at', '=', null)->get(['id', 'Ref']);

            return view('purchases_return.list_purchase_return', compact('suppliers', 'purchases', 'warehouses'));

        }
        return abort('403', __('You are not authorized'));
    }



    //------------------- create_purchase_return -----------------\\

    public function create_purchase_return(Request $request, $id)
    {

        $user_auth = auth()->user();
        if ($user_auth->can('purchase_returns_add')) {

            //get warehouses 
            if ($user_auth->is_all_warehouses) {
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();

            } else {
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }


            $PurchaseReturn = Purchase::with('details.product.unitPurchase')
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

            $Return_detail['supplier_id'] = $PurchaseReturn->provider_id;
            $Return_detail['warehouse_id'] = $PurchaseReturn->warehouse_id;
            $Return_detail['purchase_id'] = $PurchaseReturn->id;
            $Return_detail['purchase_ref'] = $PurchaseReturn->Ref;
            $Return_detail['date'] = Carbon::now()->format('Y-m-d H:i');

            $Return_detail['tax_rate'] = $PurchaseReturn->tax_rate;
            $Return_detail['TaxNet'] = $PurchaseReturn->TaxNet;
            $Return_detail['discount'] = $PurchaseReturn->discount;
            $Return_detail['discount_type'] = $PurchaseReturn->discount_type;
            $Return_detail['discount_percent_total'] = $PurchaseReturn->discount_percent_total;
            $Return_detail['shipping'] = $PurchaseReturn->shipping;

            $Return_detail['statut'] = "completed";
            $Return_detail['notes'] = "";

            $detail_id = 0;
            foreach ($PurchaseReturn['details'] as $detail) {

                $unit = Unit::where('id', $detail->purchase_unit_id)->first();

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('deleted_at', '=', null)
                        ->where('warehouse_id', $PurchaseReturn->warehouse_id)
                        ->first();

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;

                    $data['code'] = $productsVariants->code;
                    $data['name'] = '[' . $productsVariants->name . '] ' . $detail['product']['name'];


                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $PurchaseReturn->warehouse_id)
                        ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                        ->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];

                }

                $data['id'] = $detail->id;
                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = 0;
                $data['purchase_quantity'] = $detail->quantity;
                $data['product_id'] = $detail->product_id;
                $data['unitPurchase'] = $unit ? $unit->ShortName : '';
                $data['purchase_unit_id'] = $unit ? $unit->id : '';
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


            return view(
                'purchases_return.create_purchase_return',
                [
                    'details' => $details,
                    'purchase_return' => $Return_detail,
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
        if ($user_auth->can('purchase_returns_add')) {

            \DB::transaction(function () use ($request) {
                $order = new PurchaseReturn;

                $order->date = $request->date;
                $order->Ref = $this->getNumberOrder();
                $order->purchase_id = $request->purchase_id;
                $order->provider_id = $request->supplier_id;
                $order->warehouse_id = $request->warehouse_id;
                $order->tax_rate = $request->tax_rate;
                $order->TaxNet = $request->TaxNet;
                $order->discount = $request->discount;
                $order->discount_type = $request->discount_type;
                $order->discount_percent_total = $request->discount_percent_total;
                $order->shipping = $request->shipping;
                $order->statut = 'completed';
                $order->GrandTotal = $request->GrandTotal;
                $order->payment_statut = 'unpaid';
                $order->notes = $request->notes;
                $order->user_id = Auth::user()->id;

                $order->save();

                $data = $request['details'];
                foreach ($data as $key => $value) {
                    $unit = Unit::where('id', $value['purchase_unit_id'])->first();
                    $orderDetails[] = [
                        'purchase_return_id' => $order->id,
                        'quantity' => $value['quantity'],
                        'cost' => $value['Unit_cost'],
                        'purchase_unit_id' => $value['purchase_unit_id'] ? $value['purchase_unit_id'] : NULL,
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
                PurchaseReturnDetails::insert($orderDetails);
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
    public function show(Request $request, $id)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('purchase_returns_view_all') || $user_auth->can('purchase_returns_view_own')) {

            if ($user_auth->is_all_warehouses) {
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
            } else {
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            $Purchase_Return = PurchaseReturn::with('purchase', 'details.product.unitPurchase')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($array_warehouses_id) {
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
                })
                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('purchase_returns_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                })->findOrFail($id);

            $details = array();

            $return_details['id'] = $Purchase_Return->id;
            $return_details['Ref'] = $Purchase_Return->Ref;
            $return_details['purchase_ref'] = $Purchase_Return['purchase']->Ref;
            $return_details['purchase_id'] = $Purchase_Return['purchase']->id;
            $return_details['date'] = $Purchase_Return->date;
            $return_details['statut'] = $Purchase_Return->statut;
            $return_details['note'] = $Purchase_Return->notes;


            if ($Purchase_Return->discount_type == 'fixed') {
                $return_details['discount'] = $this->render_price_with_symbol_placement(number_format($Purchase_Return->discount, 2, '.', ','));
            } else {
                $return_details['discount'] = $this->render_price_with_symbol_placement(number_format($Purchase_Return->discount_percent_total, 2, '.', ',')) . ' ' . '(' . $Purchase_Return->discount . ' ' . '%)';
            }

            $return_details['shipping'] = $this->render_price_with_symbol_placement(number_format($Purchase_Return->shipping, 2, '.', ','));
            $return_details['tax_rate'] = $Purchase_Return->tax_rate;
            $return_details['TaxNet'] = $this->render_price_with_symbol_placement(number_format($Purchase_Return->TaxNet, 2, '.', ','));
            $return_details['supplier_name'] = $Purchase_Return['provider']->name;
            $return_details['supplier_email'] = $Purchase_Return['provider']->email;
            $return_details['supplier_phone'] = $Purchase_Return['provider']->phone;
            $return_details['supplier_adr'] = $Purchase_Return['provider']->address;
            $return_details['warehouse'] = $Purchase_Return['warehouse']->name;
            $return_details['GrandTotal'] = $this->render_price_with_symbol_placement(number_format($Purchase_Return->GrandTotal, 2, '.', ','));
            $return_details['paid_amount'] = $this->render_price_with_symbol_placement(number_format($Purchase_Return->paid_amount, 2, '.', ','));
            $return_details['due'] = $this->render_price_with_symbol_placement(number_format($Purchase_Return->GrandTotal - $Purchase_Return->paid_amount, 2, '.', ','));
            $return_details['payment_status'] = $Purchase_Return->payment_statut;

            foreach ($Purchase_Return['details'] as $detail) {

                //-------check if detail has purchase_unit_id Or Null
                if ($detail->purchase_unit_id) {
                    $unit = Unit::where('id', $detail->purchase_unit_id)->first();
                } else {
                    $product_unit_purchase_id = Product::with('unitPurchase')
                        ->where('id', $detail->product_id)
                        ->first();
                    $unit = Unit::where('id', $product_unit_purchase_id['unitPurchase']->id)->first();
                }

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

                if ($detail->quantity > 0) {
                    $details[] = $data;
                }
            }

            $company = Setting::where('deleted_at', '=', null)->first();

            return view(
                'purchases_return.details_purchase_return',
                [
                    'purchase_return' => $return_details,
                    'details' => $details,
                    'company' => $company,
                ]
            );

        }
        return abort('403', __('You are not authorized'));

    }



    //------------- edit_purchase_return-----------\\

    public function edit_purchase_return(Request $request, $id, $purchase_id)
    {

        $user_auth = auth()->user();
        if ($user_auth->can('purchase_returns_edit')) {

            //get warehouses 
            if ($user_auth->is_all_warehouses) {
                $array_warehouses_id = Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();

            } else {
                $array_warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            }

            $Purchase_Return = PurchaseReturn::with('details.product.unitPurchase')
                ->where('deleted_at', '=', null)
                ->where(function ($query) use ($array_warehouses_id) {
                    return $query->whereIn('warehouse_id', $array_warehouses_id);
                })
                ->where(function ($query) use ($user_auth) {
                    if (!$user_auth->can('purchase_returns_view_all')) {
                        return $query->where('user_id', '=', $user_auth->id);
                    }
                })->findOrFail($id);

            $details = array();

            $Return_detail['id'] = $Purchase_Return->id;
            $Return_detail['supplier_id'] = $Purchase_Return->provider_id;
            $Return_detail['warehouse_id'] = $Purchase_Return->warehouse_id;
            $Return_detail['purchase_id'] = $Purchase_Return->purchase_id ? $Purchase_Return['purchase']->id : NULL;
            $Return_detail['purchase_ref'] = $Purchase_Return['purchase'] ? $Purchase_Return['purchase']->Ref : '---';
            $Return_detail['date'] = $Purchase_Return->date;
            $Return_detail['tax_rate'] = $Purchase_Return->tax_rate;
            $Return_detail['TaxNet'] = $Purchase_Return->TaxNet;
            $Return_detail['discount'] = $Purchase_Return->discount;
            $Return_detail['discount_type'] = $Purchase_Return->discount_type;
            $Return_detail['discount_percent_total'] = $Purchase_Return->discount_percent_total;
            $Return_detail['shipping'] = $Purchase_Return->shipping;
            $Return_detail['notes'] = $Purchase_Return->notes;
            $Return_detail['statut'] = $Purchase_Return->statut;
            $Return_detail['GrandTotal'] = $Purchase_Return->GrandTotal;

            $detail_id = 0;
            foreach ($Purchase_Return['details'] as $detail) {

                $unit = Unit::where('id', $detail->purchase_unit_id)->first();

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('deleted_at', '=', null)
                        ->where('warehouse_id', $Purchase_Return->warehouse_id)
                        ->first();

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $Purchase_Return->warehouse_id)
                        ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                        ->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];

                }

                $data['id'] = $detail->id;
                $data['detail_id'] = $detail_id += 1;

                $purchase_detail = PurchaseDetail::where('purchase_id', $purchase_id)
                    ->where('product_id', $detail->product_id)
                    ->where('product_variant_id', $detail->product_variant_id)
                    ->first();

                $data['purchase_quantity'] = $purchase_detail->quantity;

                $data['quantity'] = $detail->quantity;
                $data['quantity_copy'] = $detail->quantity;
                $data['product_id'] = $detail->product_id;
                $data['name'] = $detail['product']['name'];
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


            return view(
                'purchases_return.edit_purchase_return',
                [
                    'details' => $details,
                    'purchase_return' => $Return_detail,
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
        if ($user_auth->can('purchase_returns_edit')) {

            \DB::transaction(function () use ($request, $id) {
                $current_PurchaseReturn = PurchaseReturn::findOrFail($id);

                $old_Return_Details = PurchaseReturnDetails::where('purchase_return_id', $id)->get();
                $New_Return_Details = $request['details'];
                $length = sizeof($New_Return_Details);

                // Get Ids details
                $new_products_id = [];
                foreach ($New_Return_Details as $new_detail) {
                    $new_products_id[] = $new_detail['id'];
                }

                // Init Data with old Parametre
                $old_products_id = [];
                foreach ($old_Return_Details as $key => $value) {
                    $old_products_id[] = $value->id;

                    //check if detail has purchase_unit_id Or Null
                    if ($value['purchase_unit_id']) {
                        $unit = Unit::where('id', $value['purchase_unit_id'])->first();
                    } else {
                        $product_unit_purchase_id = Product::with('unitPurchase')
                            ->where('id', $value['product_id'])
                            ->first();
                        $unit = Unit::where('id', $product_unit_purchase_id['unitPurchase']->id)->first();
                    }

                    if ($value['purchase_unit_id']) {
                        if ($value['product_variant_id']) {

                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_PurchaseReturn->warehouse_id)
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
                                ->where('warehouse_id', $current_PurchaseReturn->warehouse_id)
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

                        // Delete Detail
                        if (!in_array($old_products_id[$key], $new_products_id)) {
                            $PurchaseReturnDetails = PurchaseReturnDetails::findOrFail($value->id);
                            $PurchaseReturnDetails->delete();
                        }
                    }

                }

                // Update Data with New request
                foreach ($New_Return_Details as $key => $product_detail) {

                    $unit_prod = Unit::where('id', $product_detail['purchase_unit_id'])->first();

                    if ($product_detail['product_variant_id']) {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $request->warehouse_id)
                            ->where('product_id', $product_detail['product_id'])
                            ->where('product_variant_id', $product_detail['product_variant_id'])
                            ->first();

                        if ($unit_prod && $product_warehouse) {
                            if ($unit_prod->operator == '/') {
                                $product_warehouse->qte -= $product_detail['quantity'] / $unit_prod->operator_value;
                            } else {
                                $product_warehouse->qte -= $product_detail['quantity'] * $unit_prod->operator_value;
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
                                $product_warehouse->qte -= $product_detail['quantity'] / $unit_prod->operator_value;
                            } else {
                                $product_warehouse->qte -= $product_detail['quantity'] * $unit_prod->operator_value;
                            }
                            $product_warehouse->save();
                        }
                    }

                    $orderDetails['purchase_return_id'] = $id;
                    $orderDetails['cost'] = $product_detail['Unit_cost'];
                    $orderDetails['purchase_unit_id'] = $product_detail['purchase_unit_id'] ? $product_detail['purchase_unit_id'] : NULL;
                    $orderDetails['TaxNet'] = $product_detail['tax_percent'];
                    $orderDetails['tax_method'] = $product_detail['tax_method'];
                    $orderDetails['discount'] = $product_detail['discount'];
                    $orderDetails['discount_method'] = $product_detail['discount_Method'];
                    $orderDetails['quantity'] = $product_detail['quantity'];
                    $orderDetails['product_id'] = $product_detail['product_id'];
                    $orderDetails['product_variant_id'] = $product_detail['product_variant_id'] ? $product_detail['product_variant_id'] : NULL;
                    $orderDetails['total'] = $product_detail['subtotal'];
                    $orderDetails['imei_number'] = $product_detail['imei_number'];

                    if (!in_array($product_detail['id'], $old_products_id)) {
                        PurchaseReturnDetails::Create($orderDetails);
                    } else {
                        PurchaseReturnDetails::where('id', $product_detail['id'])->update($orderDetails);
                    }

                }

                $due = $request['GrandTotal'] - $current_PurchaseReturn->paid_amount;
                if ($due === 0.0 || $due < 0.0) {
                    $payment_statut = 'paid';
                } else if ($due != $request['GrandTotal']) {
                    $payment_statut = 'partial';
                } else if ($due == $request['GrandTotal']) {
                    $payment_statut = 'unpaid';
                }

                $current_PurchaseReturn->update([
                    'date' => $request['date'],
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

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('purchase_returns_delete')) {

            \DB::transaction(function () use ($id) {
                $current_PurchaseReturn = PurchaseReturn::findOrFail($id);
                $old_Return_Details = PurchaseReturnDetails::where('purchase_return_id', $id)->get();

                foreach ($old_Return_Details as $key => $value) {

                    $unit = Unit::where('id', $value['purchase_unit_id'])->first();

                    if ($value['product_variant_id']) {

                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_PurchaseReturn->warehouse_id)
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
                            ->where('warehouse_id', $current_PurchaseReturn->warehouse_id)
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
                $current_PurchaseReturn->details()->delete();
                $current_PurchaseReturn->update([
                    'deleted_at' => Carbon::now(),
                ]);

                // get all payments
                $payments = PaymentPurchaseReturns::where('purchase_return_id', $id)->get();

                foreach ($payments as $payment) {

                    $account = Account::find($payment->account_id);

                    if ($account) {
                        $account->update([
                            'initial_balance' => $account->initial_balance - $payment->montant,
                        ]);
                    }

                }

                PaymentPurchaseReturns::where('purchase_return_id', $id)->update([
                    'deleted_at' => Carbon::now(),
                ]);

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    //------------- Send Purchase Return on Email -----------\\

    public function Send_Email(Request $request)
    {

        $id = $request->id;
        $purchase_return_data = PurchaseReturn::with('provider')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $purchase_return = [];
        $purchase_return['id'] = $request->id;
        $purchase_return['Ref'] = $purchase_return_data->Ref;
        $purchase_return['to'] = $purchase_return_data['provider']->email;
        $purchase_return['provider_name'] = $purchase_return_data['provider']->name;

        $pdf = $this->Return_pdf($request, $purchase_return['id']);
        $this->Set_config_mail();
        $mail = Mail::to($purchase_return['to'])->send(new Purchase_Return_Mail($purchase_return, $pdf));
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

    //------------- GET Payments Purchase Return BY ID-----------\\

    public function Payment_Returns(Request $request, $id)
    {
        $user_auth = auth()->user();
        if ($user_auth->can('payment_purchase_returns_view')) {

            $PurchaseReturn = PurchaseReturn::findOrFail($id);

            $payments = PaymentPurchaseReturns::with('PurchaseReturn')
                ->where('purchase_return_id', $id)
                ->orderBy('id', 'DESC')->get();

            $due = $PurchaseReturn->GrandTotal - $PurchaseReturn->paid_amount;

            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id', 'title']);
            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id', 'account_name']);

            return response()->json([
                'payments' => $payments,
                'due' => $due,
                'payment_methods' => $payment_methods,
                'accounts' => $accounts,
            ]);

        }
        return abort('403', __('You are not authorized'));

    }

    //------------ Reference Number Purchase Return --------------\\

    public function getNumberOrder()
    {
        $last = DB::table('purchase_returns')->latest('id')->first();

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


    //------------- Purchase Return PDF-----------\\

    public function Return_pdf(Request $request, $id)
    {

        $details = array();
        $user_auth = auth()->user();

        $PurchaseReturn = PurchaseReturn::with('purchase', 'details.product.unitPurchase')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($user_auth) {
                if (!$user_auth->can('purchase_returns_view_all')) {
                    return $query->where('user_id', '=', $user_auth->id);
                }
            })->findOrFail($id);

        $return_details['purchase_ref'] = $PurchaseReturn['purchase']->Ref;
        $return_details['supplier_name'] = $PurchaseReturn['provider']->name;
        $return_details['supplier_phone'] = $PurchaseReturn['provider']->phone;
        $return_details['supplier_adr'] = $PurchaseReturn['provider']->address;
        $return_details['supplier_email'] = $PurchaseReturn['provider']->email;
        $return_details['TaxNet'] = $this->render_price_with_symbol_placement(number_format($PurchaseReturn->TaxNet, 2, '.', ','));
        $return_details['discount'] = $this->render_price_with_symbol_placement(number_format($PurchaseReturn->discount, 2, '.', ','));
        $return_details['shipping'] = $this->render_price_with_symbol_placement(number_format($PurchaseReturn->shipping, 2, '.', ','));
        $return_details['statut'] = $PurchaseReturn->statut;
        $return_details['Ref'] = $PurchaseReturn->Ref;
        $return_details['date'] = Carbon::parse($PurchaseReturn->date)->format('d-m-Y H:i');
        $return_details['GrandTotal'] = $this->render_price_with_symbol_placement(number_format($PurchaseReturn->GrandTotal, 2, '.', ','));
        $return_details['paid_amount'] = $this->render_price_with_symbol_placement(number_format($PurchaseReturn->paid_amount, 2, '.', ','));
        $return_details['due'] = $this->render_price_with_symbol_placement(number_format($PurchaseReturn->GrandTotal - $PurchaseReturn->paid_amount, 2, '.', ','));
        $return_details['payment_status'] = $PurchaseReturn->payment_statut;

        $detail_id = 0;
        foreach ($PurchaseReturn['details'] as $detail) {

            $unit = Unit::where('id', $detail['purchase_unit_id'])->first();

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
            $data['total'] = number_format($detail->total, 2, '.', ',');
            $data['cost'] = number_format($detail->cost, 2, '.', ',');
            $data['unit_purchase'] = $unit ? $unit->ShortName : '';

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

            if ($detail->quantity > 0) {
                $details[] = $data;
            }
        }

        $settings = Setting::where('deleted_at', '=', null)->first();

        $Html = view('pdf.Purchase_Return_pdf', [
            'setting' => $settings,
            'return_purchase' => $return_details,
            'details' => $details,
        ])->render();

        $arabic = new Arabic();
        $p = $arabic->arIdentify($Html);

        for ($i = count($p) - 1; $i >= 0; $i -= 2) {
            $utf8ar = $arabic->utf8Glyphs(substr($Html, $p[$i - 1], $p[$i] - $p[$i - 1]));
            $Html = substr_replace($Html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
        }

        $pdf = PDF::loadHTML($Html);

        return $pdf->download('Purchase_Return.pdf');
        //------------------


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
