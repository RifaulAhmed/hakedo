<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>{{$sale['Ref']}}</title>
      <link rel="stylesheet" href="{{asset('assets/styles/vendor/pdf_style.css')}}">
   </head>

   <body>
      <div class="clearfix">
         <div id="logo">
         <img src="{{asset('images/'.$setting['logo'])}}">
         </div>
         <div id="company">
            <div><strong> {{ __('translate.Date') }} </strong>{{$sale['date']}}</div>
            <div><strong> {{ __('translate.Ref') }} </strong> #{{$sale['Ref']}}</div>
            <div><strong> {{ __('translate.Payment_Status') }} </strong> 
               @if($sale['payment_status'] == 'paid') {{ __('translate.Paid') }}
               @elseif($sale['payment_status'] == 'partial') {{ __('translate.Partial') }}
               @else {{ __('translate.Unpaid') }}
               @endif
            </div>
         </div>
         <div id="Title-heading">
            <strong>{{ __('translate.Sale') }} </strong> {{$sale['Ref']}}
         </div>
      </div>
      <div>
         <div id="details" class="clearfix">
            <div id="client">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">{{ __('translate.Company_Info') }}</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td dir="{{ $languageDirection }}">
                           <div id="comp">{{$setting['CompanyName']}}</div>
                           <div><strong>{{ __('translate.Address') }} </strong>  {{$setting['CompanyAdress']}}</div>
                           <div><strong>{{ __('translate.Phone') }} </strong>  {{$setting['CompanyPhone']}}</div>
                           <div><strong>{{ __('translate.Email') }} </strong>  {{$setting['email']}}</div>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
            <div id="invoice">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">{{ __('translate.Customer_Info') }}</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div><strong>{{ __('translate.Name') }} </strong> {{$sale['client_name']}}</div>
                           <div><strong>{{ __('translate.Phone') }} </strong> {{$sale['client_phone']}}</div>
                           <div><strong>{{ __('translate.Address') }} </strong> {{$sale['client_adr']}}</div>
                        </td>

                     </tr>
                  </tbody>
               </table>
            </div>
         </div>
         <div id="details_inv">
            <table class="table-sm">
               <thead>
                  <tr class="tabletitle">
                     <th>{{ __('translate.Product_Name') }}</th>
                     <th>{{ __('translate.Unit_Price') }}</th>
                     <th>{{ __('translate.Qty') }}</th>
                     <th>{{ __('translate.SubTotal') }}</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($details as $detail)
                  <tr class="service">
                     <td class="tableitem">
                        <span>{{$detail['code']}} ({{$detail['name']}})</span>
                           @if($detail['is_imei'] && $detail['imei_number'] !==null)
                              <p>IMEI/SN : {{$detail['imei_number']}}</p>
                           @endif
                     </td>
                     <td class="tableitem">{{$detail['price']}} </td>
                     <td class="tableitem">{{$detail['quantity']}}/{{$detail['unitSale']}}</td>
                     <td class="tableitem">{{$detail['total']}} </td>
                  </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
         <div id="total">
            <table>
               <tr class="service">
                     <td class="tableitem">{{ __('translate.Order_Tax') }}</td>
                     <td class="tableitem">{{$sale['TaxNet']}} ({{$sale['tax_rate']}} %)</td>
               </tr>

               <tr class="service">
                  <td class="tableitem">{{ __('translate.Discount') }}</td>
                  <td class="tableitem">
                     <span>{{$sale['discount']}}</span>
                  </td>
               </tr>

               <tr class="service">
                     <td class="tableitem">{{ __('translate.Shipping') }}</td>
                     <td class="tableitem">{{$sale['shipping']}} </td>
               </tr>
               <tr class="service">
                     <td class="tableitem">{{ __('translate.Total') }}</td>
                     <td class="tableitem">{{$sale['GrandTotal']}}</td>
               </tr>

               <tr class="service">
                     <td class="tableitem">{{ __('translate.Paid') }}</td>
                     <td class="tableitem">{{$sale['paid_amount']}}</td>
               </tr>

               <tr class="service">
                     <td class="tableitem">{{ __('translate.Due') }}</td>
                     <td class="tableitem">{{$sale['due']}}</td>
               </tr>
            </table>
         </div>
         <div id="signature">
            @if($setting['invoice_footer'] !== null)
               <p>{{$setting['invoice_footer']}}</p>
            @endif
         </div>
      </div>
   </body>
</html>
