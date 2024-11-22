@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">

@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Details_Sale_Return') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div class="row" id="section_details_sale_return">
  <div class="col-md-12">
        @can('sale_returns_edit')
        <a class="btn-sm btn btn-success ripple btn-icon m-1" :href="'/sales-return/edit_returns_sale/'+sale_return.id+'/'+sale_return.sale_id">
          <i class="i-Edit"></i>
          <span>{{ __('translate.Edit_Return') }}</span>
        </a>
        @endcan

        <a @click="Return_PDF(sale_return.id)" class="btn-sm btn btn-secondary ripple btn-icon m-1">
          <i class="i-File-TXT"></i> {{ __('translate.Download_PDF') }}
        </a>
        <a onclick="printDiv()" class="btn-sm btn btn-warning ripple btn-icon m-1">
          <i class="i-Billing"></i>
          {{ __('translate.Print_Return') }}
        </a>
        @can('sale_returns_delete')
        <a @click="Delete_Return(sale_return.id)" class="btn btn-danger btn-icon icon-left btn-sm m-1">
          <i class="i-Close-Window"></i>
          {{ __('translate.Delete_Return') }}
        </a>
        @endcan
        <hr>
    <div class="invoice" id="print_Invoice">
      <div class="invoice-print">
        <div class="row mt-5">
          <div class="col-md-4 mb-4">
            <h5 class="font-weight-bold">{{ __('translate.Customer_Info') }}</h5>
            <div>@{{sale_return.client_name}}</div>
            <div>@{{sale_return.client_email}}</div>
            <div>@{{sale_return.client_phone}}</div>
            <div>@{{sale_return.client_adr}}</div>
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
            <div>{{ __('translate.Reference') }} : @{{sale_return.Ref}}</div>
            <div>{{ __('translate.Sale_Ref') }} : @{{sale_return.sale_ref}}</div>
            <div>
              {{ __('translate.Status') }} :
              <span v-if="sale_return.statut == 'received'"
                class="badge badge-outline-success">{{ __('translate.Completed') }}</span>
              <span v-else class="badge badge-outline-warning">{{ __('translate.Pending') }}</span>
            </div>
            <div>
              {{ __('translate.Payment_Status') }} :
              <span v-if="sale_return.payment_status  == 'paid'"
                class="badge badge-outline-success">{{ __('translate.Paid') }}</span>
              <span v-else-if="sale_return.payment_status  == 'partial'"
                class="badge badge-outline-info">{{ __('translate.Partial') }}</span>
              <span v-else class="badge badge-outline-warning">{{ __('translate.Unpaid') }}</span>
            </div>
            <div>{{ __('translate.date') }} : @{{sale_return.date}}</div>
            <div>{{ __('translate.warehouse') }} : @{{sale_return.warehouse}}</div>
          </div>

        </div>
        <div class="row mt-3">
          <div class="col-md-12">
            <h5 class="font-weight-bold">{{ __('translate.Return_List') }}</h5>
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
                    <span>@{{sale_return.TaxNet}} (@{{formatNumber(sale_return.tax_rate ,2)}}%)</span>
                  </td>
                </tr>
                <tr>
                  <td class="bold">{{ __('translate.Discount') }}</td>
                  <td>@{{sale_return.discount}}</td>
                </tr>
                <tr>
                  <td class="bold">{{ __('translate.Shipping') }}</td>
                  <td>@{{sale_return.shipping}}</td>
                </tr>
                <tr>
                  <td>
                    <span class="font-weight-bold">{{ __('translate.Total') }}</span>
                  </td>
                  <td>
                    <span class="font-weight-bold">@{{sale_return.GrandTotal}}</span>
                  </td>
                </tr>

                <tr>
                  <td>
                    <span class="font-weight-bold">{{ __('translate.Paid') }}</span>
                  </td>
                  <td>
                    <span class="font-weight-bold">@{{sale_return.paid_amount}}</span>
                  </td>
                </tr>

                <tr>
                  <td>
                    <span class="font-weight-bold">{{ __('translate.Due') }}</span>
                  </td>
                  <td>
                    <span class="font-weight-bold"> @{{sale_return.due}}</span>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>
        </div>
        <hr v-show="sale_return.note">
        <div class="row mt-4">
          <div class="col-md-12">
            <p>@{{sale_return.note}}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-js')

<script src="{{asset('assets/js/nprogress.js')}}"></script>
<script src="https://unpkg.com/vue-html-to-paper/build/vue-html-to-paper.js"></script>


<script>

        var app = new Vue({
        el: '#section_details_sale_return',
        data: {
            editmode: false,
            SubmitProcessing:false,
            errors:[],
            sale_return: @json($sale_Return),
            details: @json($details),
            company: @json($company),
            email: {},
        },
       
        methods: {

 //-----------------------------------  Sale Return PDF -------------------------\\
 Return_PDF(id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);     
       axios
          .get('/return_sale_pdf/'+ id, {
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
            "Sale_Return_" + this.sale_return.Ref + ".pdf"
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


    //--------------------- Send Return in Email ------------------------\\
    Return_Email(id) {
      this.email.to = this.sale_return.client_email;
      this.email.Return_Ref = this.sale_return.Ref;
      this.email.client_name = this.sale_return.client_name;
      this.Send_Email();
    },

    Send_Email(id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .post("/sales-return/returns/sale/send/email", {
          id: id,
          to: this.email.to,
          client_name: this.email.client_name,
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
                  .delete("/sales-return/returns_sale/" + id)
                  .then(() => {
                      toastr.success('{{ __('translate.Deleted_in_successfully') }}');
                      window.location.href = '/sales-return/returns_sale';
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