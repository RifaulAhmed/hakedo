const saleAndPurchaseOptions = {
    series: [
        {
            name: 'Net Profit',
            data: [44, 55, 57, 56, 61, 58, 63]
        }, 
        {
            name: 'Revenue',
            data: [76, 85, 101, 98, 87, 105, 91]
        }
    ],
    chart: {
        type: 'bar',
        height: 300,
        toolbar: {
            show: false,
        },
    },
    colors: ['#4E97FD', '#B8DEFE'],
    plotOptions: {
        bar: {
            borderRadius: 0,
            horizontal: false,
            columnWidth: '70%',
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 0,
        colors: ['transparent']
    },
    xaxis: {
        categories: ['2022-06-23', '2022-06-24', '2022-06-25', '2022-06-26', '2022-06-27', '2022-06-28', '2022-06-29'],
    },
    fill: {
        opacity: 1
    },
    grid: {
        padding: {
            top: 0,
            right: 20,
            bottom: 0,
            left: 0,
        },
    },
    legend: {
        show: true,
        position: 'top',
    },
    tooltip: {
        y: {
        formatter: function (val) {
            return "$ " + val + " thousands"
        }
        }
    }
};

const saleAndPurchaseChart = new ApexCharts(document.querySelector("#sales_and_purchases"), saleAndPurchaseOptions);
saleAndPurchaseChart.render();


const sellingProductsOptions = {
    series: [44, 55, 13, 43, 22],
    chart: {
        type: 'pie',
        height: 320,

    },
    labels: ['Team A', 'Team B', 'Team C', 'Team D', 'Team E'],
    colors: ['#4E97FD', '#7AB6FD', '#94C9FE', '#B8DEFE', '#DBF0FE'],
    legend: {
        position: 'bottom'
    },
    stroke: {
        show: true,
        width: 0,
        colors: ['transparent']
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                height: 260
            },
        }
    }]
};

const sellingProductsChart = new ApexCharts(document.querySelector("#selling_products"), sellingProductsOptions);
sellingProductsChart.render();


const sentAndReceivedOption = {
    series: [
        {
            name: 'Net Profit',
            data: [44, 55, 57, 56, 61, 58, 63]
        }, 
        {
            name: 'Revenue',
            data: [76, 85, 101, 98, 87, 105, 91]
        }
    ],
    chart: {
        type: 'line',
        height: 300,
        toolbar: {
            show: false,
        },
    },
    colors: ['#4E97FD', '#B8DEFE'],
    plotOptions: {
        bar: {
            borderRadius: 0,
            horizontal: false,
            columnWidth: '70%',
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth',
        width: 2,
    },
    xaxis: {
        categories: ['2022-06-23', '2022-06-24', '2022-06-25', '2022-06-26', '2022-06-27', '2022-06-28', '2022-06-29'],
    },
    fill: {
        opacity: 1
    },
    grid: {
        padding: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0,
        },
    },
    legend: {
        show: true,
        position: 'top',
    },
    tooltip: {
        y: {
        formatter: function (val) {
            return "$ " + val + " thousands"
        }
        }
    }
};

const sentAndReceivedChart = new ApexCharts(document.querySelector("#sent_and_received"), sentAndReceivedOption);
sentAndReceivedChart.render();

const topCustomersOptions = {
    series: [44, 55, 13, 43, 22],
    chart: {
        type: 'pie',
        height: 320,

    },
    labels: ['Team A', 'Team B', 'Team C', 'Team D', 'Team E'],
    colors: ['#4E97FD', '#7AB6FD', '#94C9FE', '#B8DEFE', '#DBF0FE'],
    legend: {
        position: 'bottom'
    },
    stroke: {
        show: true,
        width: 0,
        colors: ['transparent']
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                height: 260
            },
        }
    }]
};

const topCustomersChart = new ApexCharts(document.querySelector("#top_customers"), topCustomersOptions);
topCustomersChart.render();