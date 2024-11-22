<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>{{$return_sale['Ref']}}</title>
      <link rel="stylesheet" href="{{asset('assets/styles/vendor/pdf_style.css')}}">
   </head>

   <body>
      <header class="clearfix">
         <div id="logo">
               <img src="{{asset('images/'.$setting['logo'])}}">
         </div>
         <div id="company">
            <div><strong> {{ __('translate.Date') }}  </strong>{{$return_sale['date']}}</div>
            <div><strong> {{ __('translate.Ref') }}  </strong> {{$return_sale['Ref']}}</div>
            <div><strong> {{ __('translate.Payment_Status') }} </strong> 
               @if($return_sale['payment_status'] == 'paid') {{ __('translate.Paid') }}
               @elseif($return_sale['payment_status'] == 'partial') {{ __('translate.Partial') }}
               @else {{ __('translate.Unpaid') }}
               @endif
            </div>

         </div>
         <div id="Title-heading">
             <strong>{{ __('translate.Sell_Return') }}  </strong> {{$return_sale['Ref']}}
         </div>
      </header>
      <main>
         <div id="details" class="clearfix">
            <div id="client">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">{{ __('translate.Customer_Info') }}</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div><strong>{{ __('translate.Name') }} </strong> {{$return_sale['client_name']}}</div>
                           <div><strong>{{ __('translate.Phone') }} </strong> {{$return_sale['client_phone']}}</div>
                           <div><strong>{{ __('translate.Address') }} </strong>   {{$return_sale['client_adr']}}</div>
                           <div><strong>{{ __('translate.Email') }} </strong>  {{$return_sale['client_email']}}</div>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
            <div id="invoice">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">{{ __('translate.Company_Info') }}</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div id="comp">{{$setting['CompanyName']}}</div>
                           <div><strong>{{ __('translate.Address') }} </strong>  {{$setting['CompanyAdress']}}</div>
                           <div><strong>{{ __('translate.Phone') }} </strong>  {{$setting['CompanyPhone']}}</div>
                           <div><strong>{{ __('translate.Email') }} </strong>  {{$setting['email']}}</div>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
         </div>
         <div id="details_inv">
            <table class="table-sm">
               <thead>
                  <tr>
                     <th>{{ __('translate.Product_Name') }}</th>
                     <th>{{ __('translate.Unit_Price') }}</th>
                     <th>{{ __('translate.Qty') }}</th>
                     <th>{{ __('translate.SubTotal') }}</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($details as $detail)    
                  <tr>
                     <td>
                        <span>{{$detail['code']}} ({{$detail['name']}})</span>
                           @if($detail['is_imei'] && $detail['imei_number'] !==null)
                              <p>IMEI/SN : {{$detail['imei_number']}}</p>
                           @endif
                     </td>
                     <td>{{$detail['price']}} </td>
                     <td>{{$detail['quantity']}}/{{$detail['unitSale']}}</td>
                     <td>{{$detail['total']}} </td>
                  </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
         <div id="total">
            <table>
               <tr>
                  <td>{{ __('translate.Order_Tax') }}</td>
                  <td>{{$return_sale['TaxNet']}} </td>
               </tr>
               <tr>
                  <td>{{ __('translate.Discount') }}</td>
                  <td>{{$return_sale['discount']}} </td>
               </tr>
               <tr>
                  <td>{{ __('translate.Shipping') }}</td>
                  <td>{{$return_sale['shipping']}} </td>
               </tr>
               <tr>
                  <td>{{ __('translate.Total') }}</td>
                  <td>{{$return_sale['GrandTotal']}}</td>
               </tr>

               <tr>
                  <td>{{ __('translate.Paid') }}</td>
                  <td>{{$return_sale['paid_amount']}}</td>
               </tr>

               <tr>
                  <td>{{ __('translate.Due') }}</td>
                  <td>{{$return_sale['due']}}</td>
               </tr>
            </table>
         </div>
         <div id="signature">
            @if($setting['invoice_footer'] !== null)
               <p>{{$setting['invoice_footer']}}</p>
            @endif
         </div>
      </main>
   </body>
</html>