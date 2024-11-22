@extends('layouts.master')
@section('main-content')
@section('page-css')
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Details_Sale') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div class="row" id="section_sale_detail">
  <div class="col-md-12">

    @if (auth()->user()->can('sales_edit'))
      <a v-if="sale.sale_has_return == 'no'" class="btn-sm btn btn-success ripple btn-icon m-1" :href="'/sale/sales/'+sale.id+'/edit'">
        <i class="i-Edit"></i>
        <span>{{ __('translate.Edit_Sale') }}</span>
      </a>
    @endif

    <a @click="Sale_Email(sale.id)" class="btn-sm btn btn-info ripple btn-icon m-1">
      <i class="i-Envelope-2"></i>
      {{ __('translate.Send_Email') }}
    </a>

    <a @click="Sale_SMS(sale.id)" class="btn-sm btn btn-info ripple btn-icon m-1">
        <i class="i-Envelope-2"></i>
        {{ __('translate.Send_sms') }}
      </a>

    <a @click="Sale_PDF(sale.id)" class="btn-sm btn btn-secondary ripple btn-icon m-1">
      <i class="i-File-TXT"></i> {{ __('translate.Download_PDF') }}
    </a>
    <a onclick="printDiv()" class="btn-sm btn btn-warning ripple btn-icon m-1">
      <i class="i-Billing"></i>
      {{ __('translate.Print_Sale') }}
    </a>
    @can('sales_delete')
    <a v-if="sale.sale_has_return == 'no'" @click="Delete_Sale(sale.id)" class="btn btn-danger btn-icon icon-left btn-sm m-1">
      <i class="i-Close-Window"></i>
      {{ __('translate.Delete_Sale') }}
    </a>
    @endcan

    <hr>

    <div class="invoice" id="print_Invoice">
      <div class="invoice-print">
        <div class="row mt-5">
          <div class="col-md-4 mb-4">
            <h5 class="font-weight-bold">{{ __('translate.Customer_Info') }}</h5>
            <div>@{{sale.client_name}}</div>
            <div>@{{sale.client_email}}</div>
            <div>@{{sale.client_phone}}</div>
            <div>@{{sale.client_adr}}</div>
          </div>
          <div class="col-md-4 mb-4">
            <h5 class="font-weight-bold">{{ __('translate.Company_Info') }}</h5>
            <div>@{{company.CompanyName}}</div>
            <div>@{{company.email}}</div>
            <div>@{{company.CompanyPhone}}</div>
            <div>@{{company.CompanyAdress}}</div>
          </div>
          <div class="col-md-4 mb-4">
            <h5 class="font-weight-bold">{{ __('translate.Sale_Info') }}</h5>
            <div>{{ __('translate.Reference') }} : @{{sale.Ref}}</div>
            <div>
              {{ __('translate.Status') }} :
              <span v-if="sale.statut == 'completed'"
                class="badge badge-outline-success">{{ __('translate.Completed') }}</span>
              <span v-else-if="sale.statut == 'pending'"
                class="badge badge-outline-info">{{ __('translate.Pending') }}</span>
              <span v-else class="badge badge-outline-warning">{{ __('translate.Ordered') }}</span>
            </div>
            <div>
              {{ __('translate.Payment_Status') }} :
              <span v-if="sale.payment_status  == 'paid'"
                class="badge badge-outline-success">{{ __('translate.Paid') }}</span>
              <span v-else-if="sale.payment_status  == 'partial'"
                class="badge badge-outline-info">{{ __('translate.Partial') }}</span>
              <span v-else class="badge badge-outline-warning">{{ __('translate.Unpaid') }}</span>
            </div>
            <div>{{ __('translate.date') }} : @{{sale.date}}</div>
            <div>{{ __('translate.warehouse') }} : @{{sale.warehouse}}</div>
          </div>

        </div>
        <div class="row mt-3">
          <div class="col-md-12">
            <h5 class="font-weight-bold">{{ __('translate.Order_Summary') }}</h5>
            <div class="table-responsive">
              <table class="table table-hover table-md">
                <thead>
                  <tr>
                    <th scope="col">{{ __('translate.Product_Name') }}</th>
                    <th scope="col">{{ __('translate.Net_Unit_Price') }}</th>
                    <th scope="col">{{ __('translate.Quantity') }}</th>
                    <th scope="col">{{ __('translate.Unit_Price') }}</th>
                    <th scope="col">{{ __('translate.Discount') }}</th>
                    <th scope="col">{{ __('translate.Tax') }}</th>
                    <th scope="col">{{ __('translate.SubTotal') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="detail in details">
                    <td><span>@{{detail.code}} (@{{detail.name}})</span>
                      <p v-show="detail.is_imei && detail.imei_number !==null ">IMEI_SN : @{{detail.imei_number}}
                      </p>
                    </td>
                    <td>@{{formatNumber(detail.Net_price,2)}}</td>
                    <td>@{{formatNumber(detail.quantity,2)}} @{{detail.unit_sale}}</td>
                    <td>@{{formatNumber(detail.price,2)}}</td>
                    <td>@{{formatNumber(detail.DiscountNet,2)}}</td>
                    <td>@{{formatNumber(detail.taxe,2)}}</td>
                    <td>@{{detail.total.toFixed(2)}}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="offset-md-9 col-md-3 mt-4">
            <table class="table table-striped table-sm">
              <tbody>
                <tr>
                  <td class="bold">{{ __('translate.Order_Tax') }}</td>
                  <td>
                    <span>@{{sale.TaxNet}} (@{{formatNumber(sale.tax_rate ,2)}} %)</span>
                  </td>
                </tr>
                <tr>
                  <td class="bold">{{ __('translate.Discount') }}</td>
                  <td>@{{sale.discount}}</td>
                </tr>

                <tr>
                  <td class="bold">{{ __('translate.Shipping') }}</td>
                  <td>@{{sale.shipping}}</td>
                </tr>
                <tr>
                  <td>
                    <span class="font-weight-bold">{{ __('translate.Total') }}</span>
                  </td>
                  <td>
                    <span class="font-weight-bold">@{{sale.GrandTotal}}</span>
                  </td>
                </tr>

                <tr>
                  <td>
                    <span class="font-weight-bold">{{ __('translate.Paid') }}</span>
                  </td>
                  <td>
                    <span class="font-weight-bold">@{{sale.paid_amount}}</span>
                  </td>
                </tr>

                <tr>
                  <td>
                    <span class="font-weight-bold">{{ __('translate.Due') }}</span>
                  </td>
                  <td>
                    <span class="font-weight-bold">@{{sale.due}}</span>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>
        </div>
        <hr v-show="sale.note">
        <div class="row mt-4">
          <div class="col-md-12">
            <p>@{{sale.note}}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-js')

<script src="{{asset('assets/js/nprogress.js')}}"></script>

<script>
  function printDiv() {
    var printContents = document.getElementById('print_Invoice').outerHTML;
    var printWindow = window.open('', '_blank', 'width=600,height=600');

    printWindow.document.open();
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write(
      '<link rel="stylesheet"  href="/assets_setup/css/bootstrap.css"><html>'
    );
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContents);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    setTimeout(() => {
      printWindow.print();
    }, 1000);
  }
</script>

<script>

        var app = new Vue({
        el: '#section_sale_detail',
        data: {
            editmode: false,
            SubmitProcessing:false,
            errors:[],
            sale: @json($sale),
            details: @json($details),
            company: @json($company),
            email: {
                to: "",
                subject: "",
                message: "",
                client_name: "",
                Sale_Ref: ""
            }
        },
       
        methods: {

          //----------------------------------- Invoice Sale PDF  -------------------------\\
          Sale_PDF(id) {
            // Start the progress bar.
            NProgress.start();
            NProgress.set(0.1);          
            axios
                .get('/Sale_PDF/'+ id, {
                responseType: "blob", // important
                headers: {
                  "Content-Type": "application/json"
                }
              })
              .then(response => {
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement("a");
                link.href = url;
                link.setAttribute("download", "Sale_" + this.sale.Ref + ".pdf");
                document.body.appendChild(link);
                link.click();
                // Complete the animation of the  progress bar.
                setTimeout(() => NProgress.done(), 500);
              })
              .catch(() => {
                // Complete the animation of the  progress bar.
                setTimeout(() => NProgress.done(), 500);
              });
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



             //---------SMS notification
      Sale_SMS(id) {
          // Start the progress bar.
          NProgress.start();
          NProgress.set(0.1);
          axios
            .post("/sales_send_sms", {
              id: id,
            })
            .then(response => {
              // Complete the animation of the  progress bar.
              setTimeout(() => NProgress.done(), 500);
              toastr.success('{{ __('translate.sent_in_successfully') }}');
            })
            .catch(error => {
              // Complete the animation of the  progress bar.
              setTimeout(() => NProgress.done(), 500);
              toastr.error('{{ __('translate.There_was_something_wronge') }}');
            });
        },
        

    //--------------------------------- Send Sale in Email ------------------------------\\
    Sale_Email(id) {
      this.email.to = this.sale.client_email;
      this.email.Sale_Ref = this.sale.Ref;
      this.email.client_name = this.sale.client_name;
      this.Send_Email(id);
    },

    Send_Email(id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .post("/sale/sales/send/email", {
          id: id,
          to: this.email.to,
          client_name: this.email.client_name,
          Ref: this.email.Sale_Ref
        })
        .then(response => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          toastr.success('{{ __('translate.sent_in_successfully') }}');
        })
        .catch(error => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          toastr.error('{{ __('translate.There_was_something_wronge') }}');
        });
    },
 
 
     //--------------------------------- Delete_Sale ---------------------------\\
     Delete_Sale(id) {
      swal({
          title: '{{ __('translate.Are_you_sure') }}',
          text: '{{ __('translate.You_wont_be_able_to_revert_this') }}',
          type: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#0CC27E',
          cancelButtonColor: '#FF586B',
          confirmButtonText: '{{ __('translate.Yes_delete_it') }}',
          cancelButtonText: '{{ __('translate.No_cancel') }}',
          confirmButtonClass: 'btn btn-primary me-5',
          cancelButtonClass: 'btn btn-danger',
          buttonsStyling: false
      }).then(function () {
              axios
                  .delete("/sale/sales/" + id)
                  .then(() => {
                      toastr.success('{{ __('translate.Deleted_in_successfully') }}');
                      window.location.href = '/sale/sales';
                  })
                  .catch(() => {
                      toastr.error('{{ __('translate.There_was_something_wronge') }}');
                  });
          });
      },
    },


    //-----------------------------Autoload function-------------------
    created() {
    }

  })

</script>



@endsection