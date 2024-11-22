<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\PosSetting;
use App\Models\Currency;
use App\Models\Client;
use App\Models\Warehouse;
use File;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_auth = auth()->user();
		if ($user_auth->can('settings')){
            
            $setting_data = Setting::where('deleted_at', '=', null)->first();

            $backup_settings['dump_path'] = env('DUMP_PATH');
            $email_settings['mailer']     = env('MAIL_MAILER');
            $email_settings['host']       = env('MAIL_HOST');
            $email_settings['port']       = env('MAIL_PORT');
            $email_settings['username']   = env('MAIL_USERNAME');
            $email_settings['password']   = env('MAIL_PASSWORD');
            $email_settings['encryption'] = env('MAIL_ENCRYPTION');
            $email_settings['from_email'] = env('MAIL_FROM_ADDRESS');
            $email_settings['from_name']  = env('MAIL_FROM_NAME');

            $setting['id']               = $setting_data->id;
            $setting['email']            = $setting_data->email;
            $setting['CompanyName']      = $setting_data->CompanyName;
            $setting['CompanyPhone']     = $setting_data->CompanyPhone;
            $setting['CompanyAdress']    = $setting_data->CompanyAdress;
            $setting['logo']             = "";
            $setting['warehouse_id']     = $setting_data->warehouse_id;
            $setting['client_id']        = $setting_data->client_id;
            $setting['currency_id']      = $setting_data->currency_id;
            $setting['developed_by']     = $setting_data->developed_by;
            $setting['app_name']         = $setting_data->app_name;
            $setting['footer']           = $setting_data->footer;
            $setting['default_language'] = $setting_data->default_language;
            $setting['symbol_placement'] = $setting_data->symbol_placement;
            $setting['invoice_footer']   = $setting_data->invoice_footer;
            $setting['timezone']         = env('APP_TIMEZONE') == null?'UTC':env('APP_TIMEZONE');

            $zones_array = array();
            $timestamp = time();
            foreach(timezone_identifiers_list() as $key => $zone){
                date_default_timezone_set($zone);
                $zones_array[$key]['zone'] = $zone;
                $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
                $zones_array[$key]['label'] = $zones_array[$key]['diff_from_GMT'] . ' - ' . $zones_array[$key]['zone'];
            }

            $currencies = Currency::where('deleted_at', null)->get(['id', 'name']);
            $clients = client::where('deleted_at', '=', null)->get(['id', 'username']);
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            
            return view('settings.system_settings_list', 
            compact('setting','backup_settings','email_settings','currencies','clients','warehouses','zones_array'));

        }
        return abort('403', __('You are not authorized'));
    }


    
     //-------------- Get Pos Settings ---------------\\

     public function get_pos_Settings(Request $request)
     {
        $user_auth = auth()->user();
		if ($user_auth->can('pos_settings')){
 
            $pos_settings = PosSetting::where('deleted_at', '=', null)->first();

            return view('settings.pos_settings', compact('pos_settings'));
        }
        return abort('403', __('You are not authorized'));
    
    }


    //-------------- Update Pos settings ---------------\\

    public function update_pos_settings(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('pos_settings')){

            request()->validate([
                'note_customer' => 'required',
            ]);

            if($request['is_printable'] == '1' || $request['is_printable'] == 'true'){
                $is_printable = 1;
            }else{
                $is_printable = 0;
            }

            PosSetting::whereId($id)->update([
                'note_customer'  => $request['note_customer'],
                'show_note'      => $request['show_note'],
                'show_barcode'   => $request['show_barcode'],
                'show_discount'  => $request['show_discount'],
                'show_customer'  => $request['show_customer'],
                'show_Warehouse' => $request['show_Warehouse'],
                'show_email'     => $request['show_email'],
                'show_phone'     => $request['show_phone'],
                'show_address'   => $request['show_address'],
                'is_printable'   => $is_printable,
            ]);

            return response()->json(['success' => true]);

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
        $user_auth = auth()->user();
		if ($user_auth->can('settings')){

            $request->validate([
                'CompanyName'      => 'required|string|max:255',
                'CompanyPhone'     => 'nullable|numeric',
                'email'            => 'required|string|email|max:255',
                'app_name'         => 'required|string|max:20',
                'CompanyAdress'    => 'required|string',
                'currency_id'      => 'required',
                'default_language' => 'required',
                'symbol_placement' => 'required',
                'logo'             => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
            ]);
            
            $setting = Setting::findOrFail($id);
            $currentAvatar = $setting->logo;

            if ($request->logo != null) {
                if ($request->logo != $currentAvatar) {

                    $image = $request->file('logo');
                    $filename = time().'.'.$image->extension();  
                    $image->move(public_path('/images'), $filename);
                    $path = public_path() . '/images';

                    $userPhoto = $path . '/' . $currentAvatar;
                    if (file_exists($userPhoto)) {
                        if ($setting->logo != 'logo-default.svg') {
                            @unlink($userPhoto);
                        }
                    }
                } else {
                    $filename = $currentAvatar;
                }

            }else{
                $filename = $currentAvatar;
            }

            if ($request['invoice_footer'] == 'null' || $request['invoice_footer'] == '') {
                $invoice_footer = NULL;
            } else {
                $invoice_footer = $request['invoice_footer'];
            }

            if ($request['currency_id'] == 'null' || $request['currency_id'] == '') {
                $currency_id = NULL;
            } else {
                $currency_id = $request['currency_id'];
            }
    
            if ($request['client_id'] == 'null' || $request['client_id'] == '') {
                $client_id = NULL;
            } else {
                $client_id = $request['client_id'];
            }
    
            if ($request['warehouse_id'] == 'null' || $request['warehouse_id'] == '') {
                $warehouse_id = NULL;
            } else {
                $warehouse_id = $request['warehouse_id'];
            }

    
            if ($request['default_language'] == 'null' || $request['default_language'] == '') {
                $default_language = 'en';
            } else {
                $default_language = $request['default_language'];
            }

            if ($request['symbol_placement'] == 'null' || $request['symbol_placement'] == '') {
                $symbol_placement = 'before';
            } else {
                $symbol_placement = $request['symbol_placement'];
            }

            Setting::whereId($id)->update([
                'currency_id'       => $currency_id,
                'client_id'         => $client_id,
                'warehouse_id'      => $warehouse_id,
                'email'             => $request['email'],
                'default_language'  => $default_language,
                'symbol_placement'  => $symbol_placement,
                'CompanyName'       => $request['CompanyName'],
                'app_name'          => $request['app_name'],
                'CompanyPhone'      => $request['CompanyPhone'],
                'CompanyAdress'     => $request['CompanyAdress'],
                'footer'            => $request['footer'],
                'developed_by'      => $request['developed_by'],
                'invoice_footer'    => $invoice_footer,
                'logo'              => $filename,
            ]);
    
            $this->setEnvironmentValue([
                'APP_TIMEZONE' => $request['timezone'] !== null?'"' . $request['timezone'] . '"':'"UTC"',
            ]);
    
            Artisan::call('config:cache');
            Artisan::call('config:clear');

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
        //
    }

    public function update_backup_settings(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('settings')){

            request()->validate([
                'dump_path'   => 'required|string|max:255',
            ]);

            $this->setEnvironmentValue([
                'DUMP_PATH' => $request['dump_path'] !== null?$request['dump_path']:env('DUMP_PATH'),
            ]);

            Artisan::call('config:cache');
            Artisan::call('config:clear');

            return response()->json(['success' => true]);

            

        }
        return abort('403', __('You are not authorized'));
    }


    
    //-------------- Clear_Cache ---------------\\

    public function Clear_Cache(Request $request)
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
    }

       //-------------- Set Environment Value ---------------\\

       public function setEnvironmentValue(array $values)
       {
           $envFile = app()->environmentFilePath();
           $str = file_get_contents($envFile);
           $str .= "\r\n";
           if (count($values) > 0) {
               foreach ($values as $envKey => $envValue) {
       
                   $keyPosition = strpos($str, "$envKey=");
                   $endOfLinePosition = strpos($str, "\n", $keyPosition);
                   $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
       
                   if (is_bool($keyPosition) && $keyPosition === false) {
                       // variable doesnot exist
                       $str .= "$envKey=$envValue";
                       $str .= "\r\n";
                   } else {
                       // variable exist                    
                       $str = str_replace($oldLine, "$envKey=$envValue", $str);
                   }            
               }
           }
       
           $str = substr($str, 0, -1);
           if (!file_put_contents($envFile, $str)) {
               return false;
           }
       
           app()->loadEnvironmentFrom($envFile);    
       
           return true;
       }
}
