<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Provider;
use App\Models\PaymentPurchaseReturns;
use App\Mail\Payment_Purchase_Return;
use App\Models\PurchaseReturn;
use App\Models\Setting;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Config;
use DB;
use PDF;
use ArPHP\I18N\Arabic;

class PaymentPurchaseReturnsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        //
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
		if ($user_auth->can('payment_purchase_returns_add')){

            $request->validate([
                'purchase_return_id'  => 'required',
                'date'  => 'required',
                'payment_method_id'  => 'required',
            ]);

            if($request['montant'] > 0){
                \DB::transaction(function () use ($request) {
                    $PurchaseReturn = PurchaseReturn::findOrFail($request['purchase_return_id']);
            
                    $total_paid = $PurchaseReturn->paid_amount + $request['montant'];
                    $due = $PurchaseReturn->GrandTotal - $total_paid;

                    if ($due === 0.0 || $due < 0.0) {
                        $payment_statut = 'paid';
                    } else if ($due !== $PurchaseReturn->GrandTotal) {
                        $payment_statut = 'partial';
                    } else if ($due === $PurchaseReturn->GrandTotal) {
                        $payment_statut = 'unpaid';
                    }

                    PaymentPurchaseReturns::create([
                        'purchase_return_id' => $request['purchase_return_id'],
                        'Ref'                => $this->generate_random_code_payment_return(),
                        'account_id'         => $request['account_id']?$request['account_id']:NULL,
                        'date'               => $request['date'],
                        'payment_method_id'  => $request['payment_method_id'],
                        'montant'            => $request['montant'],
                        'change'             => 0,
                        'notes'              => $request['notes'],
                        'user_id'            => Auth::user()->id,
                    ]);

                    $account = Account::where('id', $request['account_id'])->exists();

                    if ($account) {
                        // Account exists, perform the update
                        $account = Account::find($request['account_id']);
                        $account->update([
                            'initial_balance' => $account->initial_balance + $request['montant'],
                        ]);
                    }

                    $PurchaseReturn->update([
                        'paid_amount'    => $total_paid,
                        'payment_statut' => $payment_statut,
                    ]);

                }, 10);
            }

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
		if ($user_auth->can('payment_purchase_returns_edit')){

            $request->validate([
                'date'  => 'required',
                'payment_method_id'  => 'required',
            ]);


            \DB::transaction(function () use ($id, $request) {
                $payment = PaymentPurchaseReturns::findOrFail($id);
        
                $PurchaseReturn = PurchaseReturn::find($payment->purchase_return_id);
                $old_total_paid = $PurchaseReturn->paid_amount - $payment->montant;
                $new_total_paid = $old_total_paid + $request['montant'];
                $due = $PurchaseReturn->GrandTotal - $new_total_paid;

                if ($due === 0.0 || $due < 0.0) {
                    $payment_statut = 'paid';
                } else if ($due !== $PurchaseReturn->GrandTotal) {
                    $payment_statut = 'partial';
                } else if ($due === $PurchaseReturn->GrandTotal) {
                    $payment_statut = 'unpaid';
                }

                 //delete old balance
                 $account = Account::where('id', $payment->account_id)->exists();

                 if ($account) {
                     // Account exists, perform the update
                     $account = Account::find($payment->account_id);
                     $account->update([
                         'initial_balance' => $account->initial_balance - $payment->montant,
                     ]);
                 }
                
                $payment->update([
                    'date'      => $request['date'],
                    'payment_method_id'      => $request['payment_method_id'],
                    'account_id'             => $request['account_id']?$request['account_id']:NULL,
                    'montant'   => $request['montant'],
                    'notes'     => $request['notes'],
                ]);

                 //update new account

                 $new_account = Account::where('id', $request['account_id'])->exists();

                 if ($new_account) {
                     // Account exists, perform the update
                     $new_account = Account::find($request['account_id']);
                     $new_account->update([
                         'initial_balance' => $new_account->initial_balance + $request['montant'],
                     ]);
                 }

                $PurchaseReturn->update([
                    'paid_amount'    => $new_total_paid,
                    'payment_statut' => $payment_statut,
                ]);

            }, 10);

            return response()->json(['success' => true, 'message' => 'Payment Update successfully'], 200);

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
		if ($user_auth->can('payment_purchase_returns_delete')){

            \DB::transaction(function () use ($id) {
                $role = Auth::user()->roles()->first();
                $payment = PaymentPurchaseReturns::findOrFail($id);

                $PurchaseReturn = PurchaseReturn::find($payment->purchase_return_id);
                $total_paid = $PurchaseReturn->paid_amount - $payment->montant;
                $due = $PurchaseReturn->GrandTotal - $total_paid;

                if ($due === 0.0 || $due < 0.0) {
                    $payment_statut = 'paid';
                } else if ($due !== $PurchaseReturn->GrandTotal) {
                    $payment_statut = 'partial';
                } else if ($due === $PurchaseReturn->GrandTotal) {
                    $payment_statut = 'unpaid';
                }

                PaymentPurchaseReturns::whereId($id)->update([
                    'deleted_at' => Carbon::now(),
                ]);

                $account = Account::where('id', $payment->account_id)->exists();

                if ($account) {
                    // Account exists, perform the update
                    $account = Account::find($payment->account_id);
                    $account->update([
                        'initial_balance' => $account->initial_balance - $payment->montant,
                    ]);
                }

                $PurchaseReturn->update([
                    'paid_amount'    => $total_paid,
                    'payment_statut' => $payment_statut,
                ]);

            }, 10);

            return response()->json(['success' => true, 'message' => 'Payment Delete successfully'], 200);

        }
        return abort('403', __('You are not authorized'));
    }

      //----------- Get Data for Create payment_sale_returns --------------\\

      public function get_data_create(Request $request, $id)
      {
          $PurchaseReturn = PurchaseReturn::findOrFail($id);
          $due = number_format($PurchaseReturn->GrandTotal - $PurchaseReturn->paid_amount, 2, '.', '');

          $payment_methods = PaymentMethod::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','title']);
          $accounts = Account::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','account_name']);

          return response()->json(
            [
                'due' => $due,
                'payment_methods' => $payment_methods,
                'accounts' => $accounts,
            ]);
 
      }


    //------------- Send Payment Purchase Return To Email -----------\\

    public function SendEmail(Request $request)
    {

        $id = $request->id;
        $payment_data = PaymentPurchaseReturns::with('PurchaseReturn.provider')->findOrFail($id);

        $payment= [];
        $payment['id'] = $request->id;
        $payment['Ref'] =  $payment_data->Ref;
        $payment['to'] = $payment_data['PurchaseReturn']['provider']->email;
        $payment['provider_name'] = $payment_data['PurchaseReturn']['provider']->name;
        
        $pdf = $this->payment_return($request, $payment['id']);
        $this->Set_config_mail(); 
        $mail = Mail::to($payment['to'])->send(new Payment_Purchase_Return($payment, $pdf));
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

         //----------- Payment Purchase Return PDF --------------\\

    public function payment_return(Request $request, $id)
    {
        $payment = PaymentPurchaseReturns::with('payment_method','PurchaseReturn', 'PurchaseReturn.provider')->findOrFail($id);

        $payment_data['return_Ref'] = $payment['PurchaseReturn']->Ref;
        $payment_data['supplier_name'] = $payment['PurchaseReturn']['provider']->name;
        $payment_data['supplier_phone'] = $payment['PurchaseReturn']['provider']->phone;
        $payment_data['supplier_adr'] = $payment['PurchaseReturn']['provider']->address;
        $payment_data['supplier_email'] = $payment['PurchaseReturn']['provider']->email;
        $payment_data['montant'] = $payment->montant;
        $payment_data['Ref'] = $payment->Ref;
        $payment_data['date'] = Carbon::parse($payment->date)->format('d-m-Y H:i');
        $payment_data['Reglement'] = $payment['payment_method']->title;

        $settings = Setting::where('deleted_at', '=', null)->first();

        $Html = view('pdf.Payment_Purchase_Return', [
            'setting' => $settings,
            'payment' => $payment_data,
        ])->render();

        $arabic = new Arabic();
        $p = $arabic->arIdentify($Html);

        for ($i = count($p)-1; $i >= 0; $i-=2) {
            $utf8ar = $arabic->utf8Glyphs(substr($Html, $p[$i-1], $p[$i] - $p[$i-1]));
            $Html = substr_replace($Html, $utf8ar, $p[$i-1], $p[$i] - $p[$i-1]);
        }

        $pdf = PDF::loadHTML($Html);

        return $pdf->download('Payment_Purchase_Return.pdf');

    }
}
