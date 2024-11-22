<?php

$languageDirection = $_COOKIE['language'] == 'ar' ? 'rtl' : 'ltr';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Posly - Ultimate Inventory Management System</title>
  <link rel=icon href={{ asset('images/logo.svg') }}>

  <!-- CSS Files -->
  <link rel="stylesheet" href="{{asset('assets/styles/vendor/invoice_pos.css')}}">

  <script src="{{asset('/assets/js/vue.js')}}"></script>

</head>

<body>

  <div id="in_pos">
    <div class="hidden-print">
    <a @click="print_pos()" class="btn btn-primary"> {{ __('translate.print') }}</a>
      <br>
    </div>
    <div id="invoice-POS">
      <div>
        <div class="info">
          <h2 class="text-center">@{{setting.CompanyName}}</h2>

          <p dir="{{ $languageDirection }}"> 
            <span>{{ __('translate.date') }} : @{{sale.date}} <br></span>
            <span>{{ __('translate.Sale') }}: @{{sale.Ref}} <br></span>
            <span v-show="pos_settings.show_address">{{ __('translate.Address') }} : @{{setting.CompanyAdress}}
              <br></span>
            <span v-show="pos_settings.show_email">{{ __('translate.Email') }} : @{{setting.email}} <br></span>
            <span v-show="pos_settings.show_phone">{{ __('translate.Phone') }} : @{{setting.CompanyPhone}}
              <br></span>
            <span v-show="pos_settings.show_customer">{{ __('translate.Customer') }} : @{{sale.client_name}}
              <br></span>
              <span v-show="pos_settings.show_Warehouse">{{ __('translate.warehouse') }} : @{{sale.warehouse_name}}
              <br></span>
          </p>
        </div>

        <table class="detail_invoice">
          <tbody>
            <tr v-for="detail_invoice in details">
              <td colspan="3">
                @{{detail_invoice.name}}
                <br v-show="detail_invoice.is_imei && detail_invoice.imei_number !==null">
                <span v-show="detail_invoice.is_imei && detail_invoice.imei_number !==null ">IMEI_SN :
                  @{{detail_invoice.imei_number}}</span>
                <br>
                <span>@{{formatNumber(detail_invoice.quantity,2)}} @{{detail_invoice.unit_sale}} x
                  @{{detail_invoice.price}}</span>
              </td>
              <td class="product_detail_invoice">
                @{{detail_invoice.total}}
              </td>
            </tr>

            <tr class="mt-10" v-show="pos_settings.show_discount">
              <td colspan="3" class="total">{{ __('translate.Tax') }}</td>
              <td class="total text-right">
                @{{sale.taxe}} (@{{formatNumber(sale.tax_rate,2)}} %)
              </td>
            </tr>

            {{-- Discount --}}
            <tr class="mt-10" v-show="pos_settings.show_discount">
              <td colspan="3" class="total">{{ __('translate.Discount') }}</td>
              <td class="total text-right">
                <span>@{{sale.discount}}</span>
              </td>
          
            </tr>

            <tr class="mt-10" v-show="pos_settings.show_discount">
              <td colspan="3" class="total">{{ __('translate.Shipping') }}</td>
              <td class="total text-right">
                @{{sale.shipping}}</td>
            </tr>

            <tr class="mt-10">
              <td colspan="3" class="total">{{ __('translate.Total') }}</td>
              <td  class="total text-right">
                @{{sale.GrandTotal}}</td>
            </tr>

            <tr v-show="isPaid">
              <td colspan="3" class="total">{{ __('translate.Paid') }}</td>
              <td class="total text-right">
                 @{{sale.paid_amount}}</td>
            </tr>

            <tr v-show="isPaidLessThanTotal">
              <td colspan="3" class="total">{{ __('translate.Due') }}</td>
              <td class="total text-right">
                @{{sale.due}}
              </td>
            </tr>
          </tbody>
        </table>

        <table class="change mt-3" v-show="isPaid">
          <thead>
            <tr>
              <th class="text-left" colspan="1">{{ __('translate.Paid_by') }}:</th>
              <th class="text-right" colspan="2">{{ __('translate.Amount') }}:</th>
              </th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="payment_pos in payments">
              <td class="text-left" colspan="1">@{{payment_pos.Reglement}}</td>
              <td class="text-right" colspan="2">@{{payment_pos.montant}}
              </td>
            </tr>
          </tbody>
        </table>

        <div id="legalcopy" class="ms-2"  v-show="pos_settings.show_note">
          <p class="legal">
            <strong>{{ __('translate.Thank_You_For_Shopping_With_Us_Please_Come_Again') }}</strong>
          </p>
        </div>

      </div>
    </div>
  </div>

  <script src="{{asset('/assets/js/jquery.min.js')}}"></script>


  <script>
    var app = new Vue({
        el: '#in_pos',

        data: {
           
            payments: @json($payments),
            details: @json($details),
            pos_settings:@json($pos_settings),
            sale: @json($sale),
            setting: @json($setting),
         
        },

        mounted() {
            if (this.pos_settings.is_printable) {
                this.print_pos();
            }
        },

        methods: {

          isPaid() {
            return parseFloat(this.sale.paid_amount) > 0;
          },

          isPaidLessThanTotal() {
          return parseFloat(this.sale.paid_amount) < parseFloat(this.sale.GrandTotal);
        },
          
        //------------------------------Formetted Numbers -------------------------\\
        formatNumber(number, dec) {
            const value = (typeof number === "string"
              ? number
              : number.toString()
            ).split(".");
            if (dec <= 0) return value[0];
            let formated = value[1] || "";
            if (formated.length > dec)
              return `${value[0]}.${formated.substr(0, dec)}`;
            while (formated.length < dec) formated += "0";
            return `${value[0]}.${formated}`;
          },

          //------------------------------ Print -------------------------\\
          print_pos() {
            var divContents = document.getElementById("invoice-POS").innerHTML;
            var a = window.open("", "", "height=500, width=500");
            a.document.write(
              '<link rel="stylesheet"  href="/assets/styles/vendor/pos_print.css"><html>'
            );
            a.document.write("<body>");
            a.document.write(divContents);
            a.document.write("</body></html>");
            a.document.close();

            setTimeout(() => {
              a.print();
            }, 1000);

            

          },
        
        },
        //-----------------------------Autoload function-------------------
        created() {

        }

      })
  
  </script>


</body>

</html>