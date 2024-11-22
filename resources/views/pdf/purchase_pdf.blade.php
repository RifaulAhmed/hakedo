<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>{{$purchase['Ref']}}</title>
      <link rel="stylesheet" href="{{asset('assets/styles/vendor/pdf_style.css')}}">
   </head>

   <body>
      <header class="clearfix">
         <div id="logo">
               <img src="{{asset('images/'.$setting['logo'])}}">
         </div>
         <div id="company">
            <div><strong> {{ __('translate.Date') }} </strong>{{$purchase['date']}}</div>
            <div><strong> {{ __('translate.Ref') }} </strong> {{$purchase['Ref']}}</div>
            <div><strong> {{ __('translate.Payment_Status') }} </strong> 
               @if($purchase['payment_status'] == 'paid') {{ __('translate.Paid') }}
               @elseif($purchase['payment_status'] == 'partial') {{ __('translate.Partial') }}
               @else {{ __('translate.Unpaid') }}
               @endif
            </div>
         </div>
         <div id="Title-heading">
            <strong>{{ __('translate.Purchase') }}  </strong> {{$purchase['Ref']}}
         </div>
      </header>
      <main>
         <div id="details" class="clearfix">
            <div id="client">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">{{ __('translate.Supplier_Info') }}</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div><strong>{{ __('translate.Name') }} </strong> {{$purchase['supplier_name']}}</div>
                           <div><strong>{{ __('translate.Phone') }} </strong> {{$purchase['supplier_phone']}}</div>
                           <div><strong>{{ __('translate.Address') }} </strong>   {{$purchase['supplier_adr']}}</div>
                           <div><strong>{{ __('translate.Email') }} </strong>  {{$purchase['supplier_email']}}</div>
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
                     <th>{{ __('translate.Unit_cost') }}</th>
                     <th>{{ __('translate.Quantity') }}</th>
                     <th>{{ __('translate.Discount') }}</th>
                     <th>{{ __('translate.Tax') }}</th>
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
                     <td>{{$detail['cost']}} </td>
                     <td>{{$detail['quantity']}}/{{$detail['unit_purchase']}}</td>
                     <td>{{$detail['DiscountNet']}} </td>
                     <td>{{$detail['taxe']}} </td>
                     <td>{{$detail['total']}} </td>
                  </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
         <div id="total">
            <table>
                  <tr class="service">
                        <td class="tableitem">{{ __('translate.Order_Tax') }}</td>
                        <td class="tableitem">{{$purchase['TaxNet']}} ({{$purchase['tax_rate']}} %)</td>
               </tr>
               <tr class="service">
                  <td class="tableitem">{{ __('translate.Discount') }}</td>
                  <td class="tableitem">
                     <span>{{$purchase['discount']}}</span>
                  </td>
               </tr>
               <tr class="service">
                     <td class="tableitem">{{ __('translate.Shipping') }}</td>
                     <td class="tableitem">{{$purchase['shipping']}} </td>
               </tr>
               <tr class="service">
                     <td class="tableitem">{{ __('translate.Total') }}</td>
                     <td class="tableitem">{{$purchase['GrandTotal']}}</td>
               </tr>

               <tr class="service">
                     <td class="tableitem">{{ __('translate.Paid') }}</td>
                     <td class="tableitem">{{$purchase['paid_amount']}}</td>
               </tr>

               <tr class="service">
                     <td class="tableitem">{{ __('translate.Due') }}</td>
                     <td class="tableitem">{{$purchase['due']}}</td>
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