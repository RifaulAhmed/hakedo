<?php
namespace App\utils;

use App\Models\Currency;
use App\Models\Role;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class helpers
{

    
    //  Helper Multiple Filter
    public function filter($model, $columns, $param, $request)
    {
        // Loop through the fields checking if they've been input, if they have add
        //  them to the query.
        $fields = [];
        for ($key = 0; $key < count($columns); $key++) {
            $fields[$key]['param'] = $param[$key];
            $fields[$key]['value'] = $columns[$key];
        }

        foreach ($fields as $field) {
            $model->where(function ($query) use ($request, $field, $model) {
                return $model->when($request->filled($field['value']), function ($query) use ($request, $model, $field) {
                        
                        if($field['param'] == 'like'){
                            $query->where($field['value'], 'LIKE', $request[$field['value']]);
                        }elseif($field['param'] == '='){
                            $query->where($field['value'] ,'=', $request[$field['value']]);
                        }else{
                            $query->where($field['value'] , $request[$field['value']]);
                        }
                       
                    });
            });
        }

        // Finally return the model
        return $model;
    }

    //  Check If Hass Permission Show All records
    public function Show_Records($model)
    {
        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        if (!$ShowRecord) {
            return $model->where('user_id', '=', Auth::user()->id);
        }
        return $model;
    }

    // Get Currency
    public function Get_Currency()
    {
        $settings = Setting::with('Currency')->where('deleted_at', '=', null)->first();

        if ($settings && $settings->currency_id) {
            if (Currency::where('id', $settings->currency_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $symbol = $settings['Currency']->symbol;
            } else {
                $symbol = '';
            }
        } else {
            $symbol = '';
        }
        return $symbol;
    }

    //get_symbol_placement
    public function get_symbol_placement()
    {
        $settings = Setting::where('deleted_at', '=', null)->first();

        if ($settings) {
            if ($settings->symbol_placement == 'before') {
                $symbol_placement = 'before';
            } else {
                $symbol_placement = 'after';
            }
        } else {
            $symbol_placement = 'before';
        }
        return $symbol_placement;
    }

     //get_number_decimal
     public function get_number_decimal()
     {
         $settings = Setting::where('deleted_at', '=', null)->first();
 
         if ($settings) {
             if ($settings->number_decimal == '2') {
                 $number_decimal = 2;
             } else {
                 $number_decimal = 3;
             }
         } else {
             $number_decimal = 2;
         }
         return $number_decimal;
     }

    // Get Currency COde
    public function Get_Currency_Code()
    {
        $settings = Setting::with('Currency')->where('deleted_at', '=', null)->first();

        if ($settings && $settings->currency_id) {
            if (Currency::where('id', $settings->currency_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $code = $settings['Currency']->code;
            } else {
                $code = 'usd';
            }
        } else {
            $code = 'usd';
        }
        return $code;
    }

}
