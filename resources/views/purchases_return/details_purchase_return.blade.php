@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">

@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Details_Return') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div class="row" id="section_details_purchas_return">
  <div class="col-md-12 mb-5">
    <div class="card">
      <div class="card-body">
        @can('purchase_returns_edit')
        <a class="btn-sm btn btn-success ripple btn-icon m-1"
          :href="'/purchase-return/edit_returns_purchase/'+purchase_return.id+'/'+purchase_return.purchase_id">
          <i class="i-Edit"></i>
          <span>{{ __('translate.Edit_Return') }}</span>
        </a>
        @endcan

        <a @click="Return_PDF(purchase_return.id)" class="btn-sm btn btn-secondary ripple btn-icon m-1">
          <i class="i-File-TXT"></i> {{ __('translate.Download_PDF') }}
        </a>
        <a onclick="printDiv()" class="btn-sm btn btn-warning ripple btn-icon m-1">
          <i class="i-Billing"></i>
          {{ __('translate.Print_Return') }}
        </a>
        @can('purchase_returns_delete')
        <a @click="Delete_Return(purchase_return.id)" class="btn btn-danger btn-icon icon-left btn-sm m-1">
          <i class="i-Close-Window"></i>
          {{ __('translate.Delete_Return') }}
        </a>
        @endcan
      </div>
    </div>
    <div class="invoice" id="print_Invoice">
      <div class="invoice-print">
       
        <hr>
        <div class="row mt-5">
          <div class="col-md-4 mb-4">
            <h5 class="font-weight-bold">{{ __('translate.Supplier_Info') }}</h5>
            <div>@{{purchase_return.supplier_name}}</div>
            <div>@{{purchase_return.supplier_email}}</div>
            <div>@{{purchase_return.supplier_phone}}</div>
            <div>@{{purchase_return.supplier_adr}}</div>
          </div>
          <div class="col-md-4 mb-4">
            <h5 class="font-weight-bold">{{ __('translate.Company_Info') }}</h5>
            <div>@{{company.CompanyName}}</div>
            <div>@{{company.email}}</div>
            <div>@{{company.CompanyPhone}}</div>
            <div>@{{company.CompanyAdress}}</div>
          </div>
          <div class="col-md-4 mb-4">
            <h5 class="font-weight-bold">{{ __('translate.Return_Info') }}</h5>
            <div>{{ __('translate.Reference') }} : @{{purchase_return.Ref}}</div>
            <div>{{ __('translate.Purchase_Ref') }} : @{{purchase_return.purchase_ref}}</div>
            <div>
              {{ __('translate.Status') }} :
              <span v-if="purchase_return.statut == 'completed'"
                class="badge badge-outline-success">{{ __('translate.Completed') }}</span>
              <span v-else class="badge badge-outline-warning">{{ __('translate.Pending') }}</span>
            </div>
            <div>
              {{ __('translate.Payment_Status') }} :
              <span v-if="purchase_return.payment_status  == 'paid'"
                class="badge badge-outline-success">{{ __('translate.Paid') }}</span>
              <span v-else-if="purchase_return.payment_status  == 'partial'"
                class="badge badge-outline-info">{{ __('translate.Partial') }}</span>
              <span v-else class="badge badge-outline-warning">{{ __('translate.Unpaid') }}</span>
            </div>
            <div>{{ __('translate.date') }} : @{{purchase_return.date}}</div>
            <div>{{ __('translate.warehouse') }} : @{{purchase_return.warehouse}}</div>
          </div>

        </div>
        <div class="row mt-3">
          <div class="col-md-12">
            <h5 class="font-weight-bold">{{ __('translate.Order_Summary') }}</h5>
            <div class="table-responsive">
              <table class="table table-hover table-md">
                <thead class="bg-gray-300">
                  <tr>
                    <th scope="col">{{ __('translate.Product_Name') }}</th>
                    <th scope="col">{{ __('translate.Net_Unit_Cost') }}</th>
                    <th scope="col">{{ __('translate.Quantity') }}</th>
                    <th scope="col">{{ __('translate.Unit_cost') }}</th>
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
                    <td>@{{formatNumber(detail.Net_cost,2)}}</td>
                    <td>@{{formatNumber(detail.quantity,2)}} @{{detail.unit_purchase}}</td>
                    <td>@{{formatNumber(detail.cost,2)}}</td>
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
                    <span>@{{purchase_return.TaxNet}} (@{{formatNumber(purchase_return.tax_rate ,2)}} %)</span>
                  </td>
                </tr>
                <tr>
                  <td class="bold">{{ __('translate.Discount') }}</td>
                  <td>@{{purchase_return.discount}}</td>
                </tr>
                <tr>
                  <td class="bold">{{ __('translate.Shipping') }}</td>
                  <td>@{{purchase_return.shipping}}</td>
                </tr>
                <tr>
                  <td>
                    <span class="font-weight-bold">{{ __('translate.Total') }}</span>
                  </td>
                  <td>
                    <span class="font-weight-bold">@{{purchase_return.GrandTotal}}</span>
                  </td>
                </tr>

                <tr>
                  <td>
                    <span class="font-weight-bold">{{ __('translate.Paid') }}</span>
                  </td>
                  <td>
                    <span class="font-weight-bold">@{{purchase_return.paid_amount}}</span>
                  </td>
                </tr>

                <tr>
                  <td>
                    <span class="font-weight-bold">{{ __('translate.Due') }}</span>
                  </td>
                  <td>
                    <span class="font-weight-bold">@{{purchase_return.due}}</span>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>
        </div>
        <hr v-show="purchase_return.note">
        <div class="row mt-4">
          <div class="col-md-12">
            <p>@{{purchase_return.note}}</p>
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
        el: '#section_details_purchas_return',
        data: {
            editmode: false,
            SubmitProcessing:false,
            errors:[],
            purchase_return: @json($purchase_return),
            details: @json($details),
            company: @json($company),
            email: {},
        },
       
        methods: {

           //----------------------------------- Return PDF -------------------------\\
    Return_PDF(id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
    
       axios
       .get('/return_purchase_pdf/'+ id, {
          responseType: "blob", // important
          headers: {
            "Content-Type": "application/json"
          }
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute(
            "download",
            "Return_Purchase_" + this.purchase_return.Ref + ".pdf"
          );
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
    //------------------------------ Print -------------------------\\
    print() {
      this.$htmlToPaper('print_Invoice');
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

    //--------------------- Send Return on Email ------------------------\\
    Return_Email() {
      this.email.to = this.purchase_return.supplier_email;
      this.email.Return_Ref = this.purchase_return.Ref;
      this.email.supplier_name = this.purchase_return.supplier_name;
      this.Send_Email();
    },

    Send_Email(id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .post("/purchase-return/returns/purchase/send/email", {
          id: id,
          to: this.email.to,
          client_name: this.email.supplier_name,
          Ref: this.email.Return_Ref
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

     //--------------------------------- Delete_Return ---------------------------\\
     Delete_Return(id) {
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
                  .delete("/purchase-return/returns_purchase/" + id)
                  .then(() => {
                      toastr.success('{{ __('translate.Deleted_in_successfully') }}');
                      window.location.href = '/purchase-return/returns_purchase';
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