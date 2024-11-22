<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>{{$payment['Ref']}}</title>
      <link rel="stylesheet" href="{{asset('assets/styles/vendor/pdf_style.css')}}">
   </head>

   <body>
      <header class="clearfix">
         <div id="logo">
               <img src="{{asset('images/'.$setting['logo'])}}">
         </div>
         <div id="company">
            <div><strong> {{ __('translate.Date') }} </strong>{{$payment['date']}}</div>
            <div><strong> {{ __('translate.Ref') }} </strong> {{$payment['Ref']}}</div>
         </div>
         <div id="Title-heading">
             <strong> {{ __('translate.Payment') }} </strong>{{$payment['Ref']}}
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
                           <div><strong>{{ __('translate.Name') }} </strong> {{$payment['supplier_name']}}</div>
                           <div><strong>{{ __('translate.Phone') }} </strong> {{$payment['supplier_phone']}}</div>
                           <div><strong>{{ __('translate.Address') }} </strong> {{$payment['supplier_adr']}}</div>
                           <div><strong>{{ __('translate.Email') }} </strong> {{$payment['supplier_email']}}</div>
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
                     <th>{{ __('translate.Purchase') }}</th>
                     <th>{{ __('translate.PayeBy') }}</th>
                     <th>{{ __('translate.Amount') }}</th>
                  </tr>
               </thead>
               <tbody>
                  <tr>
                     <td>{{$payment['purchase_Ref']}}</td>
                     <td>{{$payment['Reglement']}}</td>
                     <td>{{$currency}} {{$payment['montant']}}</td>
                  </tr>
               </tbody>
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