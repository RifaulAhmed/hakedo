<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>{{$quote['Ref']}}</title>
      <link rel="stylesheet" href="{{asset('assets/styles/vendor/pdf_style.css')}}">

   </head>

   <body>
      <div class="clearfix">
         <div id="logo">
            <img src="{{asset('images/'.$setting['logo'])}}">
         </div>
         <div id="company">
            <div><strong> {{ __('translate.Date') }}  </strong>{{$quote['date']}}</div>
            <div><strong> {{ __('translate.Ref') }} </strong> {{$quote['Ref']}}</div>
         </div>
         <div id="Title-heading">
            <strong>{{ __('translate.Quote') }}</strong>   {{$quote['Ref']}}
         </div>
      </div>
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
                           <div><strong>{{ __('translate.Name') }} </strong> {{$quote['client_name']}}</div>
                           <div><strong>{{ __('translate.Phone') }} </strong> {{$quote['client_phone']}}</div>
                           <div><strong>{{ __('translate.Email') }} </strong> {{$quote['client_email']}}</div>
                           <div><strong>{{ __('translate.Address') }} </strong> {{$quote['client_adr']}}</div>

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
                  <td class="tableitem">{{$quote['TaxNet']}} </td>
               </tr>
               <tr class="service">
                  <td class="tableitem">{{ __('translate.Discount') }}</td>
                  <td class="tableitem">
                     <span>{{$quote['discount']}}</span>
                  </td>
               </tr>
               <tr class="service">
                  <td class="tableitem"> {{ __('translate.Shipping') }}</td>
                  <td class="tableitem"> {{$quote['shipping']}} </td>
               </tr>
               <tr class="service">
                  <td class="tableitem"> {{ __('translate.Total') }}</td>
                  <td class="tableitem"> {{$quote['GrandTotal']}}</td>
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
