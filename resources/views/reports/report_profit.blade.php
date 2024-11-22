@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/daterangepicker.css')}}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.ProfitandLoss') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div id="profit_report">

    <div class="row">
        <div class="col-md-12">
            <div class="text-end mr-3">
                <a @click="print_profit()" class="btn btn-success">
                    <i class="i-Billing"></i>
                    {{ __('translate.print') }}
                </a>
            </div>
        </div>
    </div>

    <div id="print_section">

        <div class="row">
            <div class="form-group col-md-6">
                <label for="warehouse_id">{{ __('translate.warehouse') }}
                </label>
                <select name="warehouse_id" id="warehouse_id" class="form-control">
                    <option value="0">{{ __('translate.All') }}</option>
                    @foreach ($warehouses as $warehouse)
                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="text-end mb-5">
                    <a id="reportrange">
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span></span> <i class="fa fa-caret-down"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="row">

            <div class="col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Shopping-Cart"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">(@{{infos.sales_count}}) {{ __('translate.Sales') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">@{{infos.sales_sum}}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Shopping-Bag"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">(@{{infos.purchases_count}}) {{ __('translate.Purchases') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">@{{infos.purchases_sum}}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Back"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">(@{{infos.returns_sales_count}}) {{ __('translate.SalesReturn') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">@{{infos.returns_sales_sum}}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Back"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">(@{{infos.returns_purchases_count}}) {{ __('translate.PurchasesReturn') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">@{{infos.returns_purchases_sum}}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Money-2"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">{{ __('translate.Expenses') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">@{{infos.expenses_sum}}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Money-2"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">{{ __('translate.Revenue') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">
                                @{{infos.total_revenue}}</p>
                        </div>
                    </div>
                    <div class="card-footer">
                        (<span class="bold">@{{infos.sales_sum}} {{ __('translate.Sales') }}) -
                            (@{{infos.returns_sales_sum}}</span> {{ __('translate.SalesReturn') }})
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12 col-sm-12 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Money-Bag"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0"> {{ __('translate.Profit_Net_Using_FIFO') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">@{{infos.profit_fifo}}</p>
                        </div>
                    </div>
                    <div class="card-footer">
                        (
                        <span class="bold">@{{infos.sales_sum}}
                            {{ __('translate.Sales') }}) - ( @{{infos.product_cost_fifo}}
                            {{ __('translate.Product_cost') }})</span>
                    </div>
                </div>
            </div>


            <div class="col-lg-6 col-md-12 col-sm-12 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Money-Bag"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">{{ __('translate.Profit_Net_Using_Average_Cost') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">@{{infos.profit_average_cost}}</p>
                        </div>
                    </div>

                    <div class="card-footer">
                        (
                        <span class="bold">@{{infos.sales_sum}}
                            {{ __('translate.Sales') }}) - ( @{{infos.averagecost}}
                            {{ __('translate.Product_cost') }})</span>
                    </div>
                </div>
            </div>


            <div class="col-lg-6 col-md-12 col-sm-12 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Financial"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">{{ __('translate.PaiementsReceived') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">
                                @{{infos.payment_received}}</p>
                        </div>
                    </div>

                    <div class="card-footer">
                        (
                        <span class="bold">@{{infos.paiement_sales}}
                            {{ __('translate.payment_sale') }}) + (
                            @{{infos.PaymentPurchaseReturns}}
                            {{ __('translate.PurchasesReturn') }})</span>
                    </div>
                </div>
            </div>


            <div class="col-lg-6 col-md-12 col-sm-12 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Dollar-Sign"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">{{ __('translate.PaiementsSent') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">
                                @{{infos.payment_sent}}</p>
                        </div>
                    </div>
                    <div class="card-footer">
                        (
                        <span class="bold">@{{infos.paiement_purchases}}
                           {{ __('translate.payment_purchase') }}) +
                            (
                            @{{infos.PaymentSaleReturns}}
                            {{ __('translate.SalesReturn') }}) +
                            ( @{{infos.expenses_sum}} {{ __('translate.Expenses') }})
                        </span>
                    </div>
                </div>
            </div>


            <div class="col-lg-6 col-md-12 col-sm-12 col-12 mb-5">
                <div class="card card-icon-big text-center mb-30">
                    <div class="card-body">
                        <i class="i-Money"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">{{ __('translate.PaiementsNet') }}</p>
                            <p class="text-primary text-24 line-height-1 mb-2">
                                @{{infos.paiement_net}}</p>
                        </div>
                    </div>

                    <div class="card-footer">
                        (
                        <span class="bold">@{{infos.payment_received}}
                                {{ __('translate.PaiementsReceived') }}) 
                                - 
                                (@{{infos.payment_sent}} {{ __('translate.PaiementsSent') }})</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('page-js')
<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>
<script src="{{asset('assets/js/nprogress.js')}}"></script>
<script src="{{asset('assets/js/daterangepicker.min.js')}}"></script>


<script type="text/javascript">
    $(function() {
        "use strict";

            $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
                var start_date = picker.startDate.format('YYYY-MM-DD');
                var end_date = picker.endDate.format('YYYY-MM-DD');
                let warehouse_id = $('#warehouse_id').val();

                get_data(start_date, end_date, warehouse_id);

            });

            var start = moment();
            var end = moment();

            function cb(start, end) {
                $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }
        
            $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                '{{ __('translate.Since_launch') }}' : [moment().subtract(10, 'year'), moment().add(10, 'year')],
                '{{ __('translate.Today') }}': [moment(), moment()],
                '{{ __('translate.Yesterday') }}' : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                '{{ __('translate.Last_7_Days') }}' : [moment().subtract(6, 'days'), moment()],
                '{{ __('translate.Last_30_Days') }}': [moment().subtract(29, 'days') , moment()],
                '{{ __('translate.This_Month') }}': [moment().startOf('month'), moment().endOf('month')],
                '{{ __('translate.Last_Month') }}': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

            cb(start, end);

            function get_data(start_date ='', end_date ='', warehouse_id =''){
                NProgress.start();
                NProgress.set(0.1);

                $.get('/reports/report_profit/' + start_date + '/' + end_date+ '/' + warehouse_id, function(data) {
                    app.infos = data.data;
                });

                NProgress.done();
        }

          // Submit Filter
          $('#warehouse_id').on('change' , function (e) {
                var date_range = $('#reportrange > span').text();
                var dates = date_range.split(" - ");
                var start = dates[0];
                var end = dates[1];
                var start_date = moment(dates[0]).format("YYYY-MM-DD");
                var end_date = moment(dates[1]).format("YYYY-MM-DD");

                let warehouse_id = $('#warehouse_id').val();

                get_data(start_date, end_date, warehouse_id);

            

        });


           
    });
</script>


<script>
    var app = new Vue({
        el: '#profit_report',
        data: {
            SubmitProcessing:false,
            errors:[],
            infos:@json($data),
           
        },
       
        methods: {

            
            //------------------------------ Print -------------------------\\
            print_profit() {
            var divContents = document.getElementById("print_section").innerHTML;
            var a = window.open("", "", "height=500, width=500");
            a.document.write(
                '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"><html>'
            );
            a.document.write("<body >");
            a.document.write(divContents);
            a.document.write("</body></html>");
            a.document.close();
            
            setTimeout(() => {
                a.print();
            }, 1000);
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
   
           
        },
        //-----------------------------Autoload function-------------------
        created() {
        }

    })

</script>



@endsection