<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Account;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use DataTables;
use DB;
use App\utils\helpers;
use Illuminate\Routing\Controller;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

         //-------------- Get All Adjustments ---------------\\

    public function index(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('expense_view')){
            if ($request->ajax()) {
                $helpers = new helpers();
                // Filter fields With Params to retrieve
                $columns = array(0 => 'expense_category_id');
                $param = array(0 => '=');

                $end_date_default = Carbon::now()->addYear()->format('Y-m-d');
                $start_date_default = Carbon::now()->subYear()->format('Y-m-d');
                $start_date = empty($request->start_date)?$start_date_default:$request->start_date;
                $end_date = empty($request->end_date)?$end_date_default:$request->end_date;

                $data = Expense::where('deleted_at', '=', null)
                    ->whereBetween('date', array($start_date, $end_date))
                    ->with('expense_category','expense_account','expense_payment_method')
                    ->orderBy('id', 'desc');

                //Multiple Filter
                $expense_Filtred = $helpers->filter($data, $columns, $param, $request)->get();

                return Datatables::of($expense_Filtred)
                ->setRowId(function($expense_Filtred)
                {
                    return $expense_Filtred->id;
                })

                ->addColumn('date', function($row){
                    return $row->date;
                })

                ->addColumn('account_name', function($row){
                    return $row->expense_account->account_name;
                })

                ->addColumn('expense_ref', function($row){
                    return $row->expense_ref;
                })

                ->addColumn('amount', function($row){
                    return number_format($row->amount, 2, '.', '');
                })

                ->addColumn('expense_category_title', function($row){
                    return $row->expense_category->title;
                })

                ->addColumn('payment_method', function($row){
                    return $row->expense_payment_method->title;
                })

                
                ->addColumn('action', function($row) use ($user_auth) {
                    $btn = '';
                    if ($user_auth->can('expense_edit')){
                        $btn .= '<a href="/accounting/expense/' .$row->id. '/edit" id="' .$row->id. '"  class="edit cursor-pointer ul-link-action text-success"
                        data-toggle="tooltip" data-placement="top" title="Edit"><i class="i-Edit"></i></a>';
                         $btn .= '&nbsp;&nbsp;';
                    }
                    if ($user_auth->can('expense_delete')){
                        $btn .= '<a id="' .$row->id. '" class="delete cursor-pointer ul-link-action text-danger mr-1"
                        data-toggle="tooltip" data-placement="top" title="Remove"><i class="i-Close-Window"></i></a>';
                         $btn .= '&nbsp;&nbsp;';
                    }
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
            }

            $categories = ExpenseCategory::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);

            return view('accounting.expense.expense_list' , compact('categories'));

        }
        return abort('403', __('You are not authorized'));
     
    }
   
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user_auth = auth()->user();
		if ($user_auth->can('expense_add')){

            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);
            $categories = ExpenseCategory::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);

            return view('accounting.expense.create_expense', compact('accounts','categories','payment_methods'));

        }
        return abort('403', __('You are not authorized'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('expense_add')){

            \DB::transaction(function () use ($request,  $user_auth) {
                $request->validate([
                    'expense_ref'           => 'required|string|max:255',
                    'account_id'            => 'required',
                    'expense_category_id'   => 'required',
                    'amount'                => 'required|numeric',
                    'payment_method_id'     => 'required',
                    'date'                  => 'required',
                    'attachment'            => 'nullable|max:2048',
                ]);

                if ($request->hasFile('attachment')) {

                    $image = $request->file('attachment');
                    $filename = time().'.'.$image->extension();  
                    $image->move(public_path('/images/expenses'), $filename);

                } else {
                    $filename = Null;
                }

                Expense::create([
                    'expense_ref'            => $request['expense_ref'],
                    'account_id'             => $request['account_id'],
                    'expense_category_id'    => $request['expense_category_id'],
                    'amount'                 => $request['amount'],
                    'payment_method_id'      => $request['payment_method_id'],
                    'date'                   => $request['date'],
                    'attachment'             => $filename,
                    'description'            => $request['description'],
                ]);

                $account = Account::findOrFail($request['account_id']);
                $account->update([
                    'initial_balance' => $account->initial_balance - $request['amount'],
                ]);

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
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
        $user_auth = auth()->user();
		if ($user_auth->can('expense_edit')){

            $expense = Expense::where('deleted_at', '=', null)->findOrFail($id);
            $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);
            $categories = ExpenseCategory::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
            $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);

            return view('accounting.expense.edit_expense', compact('expense','accounts','categories','payment_methods'));

        }
        return abort('403', __('You are not authorized'));

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
        $user_auth = auth()->user();
		if ($user_auth->can('expense_edit')){

            \DB::transaction(function () use ($request, $id) {
                $request->validate([
                    'expense_ref'           => 'required|string|max:255',
                    'account_id'            => 'required',
                    'expense_category_id'   => 'required',
                    'amount'                => 'required|numeric',
                    'payment_method_id'     => 'required',
                    'date'                  => 'required',
                    'attachment'            => 'nullable|max:2048',
                ]);

                $expense = Expense::findOrFail($id);

                $Current_attachment = $expense->attachment;
                if ($request->attachment != 'null') {
                    if ($request->attachment != $Current_attachment) {

                        $image = $request->file('attachment');
                        $filename = time().'.'.$image->extension();  
                        $image->move(public_path('/images/expenses'), $filename);
                        $path = public_path() . '/images/expenses';
                        $attachment = $path . '/' . $Current_attachment;
                        if (file_exists($attachment)) {
                            @unlink($attachment);
                        }
                    } else {
                        $filename = $Current_attachment;
                    }
                }else{
                    $filename = $Current_attachment;
                }

                Expense::whereId($id)->update([
                    'expense_ref'            => $request['expense_ref'],
                    'account_id'             => $request['account_id'],
                    'expense_category_id'    => $request['expense_category_id'],
                    'amount'                 => $request['amount'],
                    'payment_method_id'      => $request['payment_method_id'],
                    'date'                   => $request['date'],
                    'attachment'             => $filename,
                    'description'            => $request['description'],
                ]);

                
                $account = Account::findOrFail($request['account_id']);
                $balance = $account->initial_balance + $expense->amount;
                $account->update([
                    'initial_balance' => $balance - $request['amount'],
                ]);

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('expense_delete')){

            Expense::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

     //-------------- Delete by selection  ---------------\\

     public function delete_by_selection(Request $request)
     {
        $user_auth = auth()->user();
        if($user_auth->can('expense_delete')){
            $selectedIds = $request->selectedIds;
    
            foreach ($selectedIds as $expense_id) {
                Expense::whereId($expense_id)->update([
                    'deleted_at' => Carbon::now(),
                ]);
            }
            return response()->json(['success' => true]);
        }
        return abort('403', __('You are not authorized'));
     }
}
