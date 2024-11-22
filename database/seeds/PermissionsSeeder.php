<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       // Insert some stuff
	DB::table('permissions')->insert(
		array(
		[
			'id'    => 1,
			'name'  => 'user_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 2,
			'name'  => 'user_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 3,
			'name'  => 'user_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 4,
			'name'  => 'user_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 5,
			'name'  => 'account_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 6,
			'name'  => 'account_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 7,
			'name'  => 'account_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 8,
			'name'  => 'account_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 9,
			'name'  => 'deposit_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 10,
			'name'  => 'deposit_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 11,
			'name'  => 'deposit_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 12,
			'name'  => 'deposit_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 13,
			'name'  => 'expense_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 14,
			'name'  => 'expense_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 15,
			'name'  => 'expense_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 16,
			'name'  => 'expense_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 17,
			'name'  => 'client_view_all',
			'guard_name'  => 'web',
		],
		[
			'id'    => 18,
			'name'  => 'client_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 19,
			'name'  => 'client_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 20,
			'name'  => 'client_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 21,
			'name'  => 'deposit_category',
			'guard_name'  => 'web',
		],
		[
			'id'    => 22,
			'name'  => 'payment_method',
			'guard_name'  => 'web',
		],
		[
			'id'    => 23,
			'name'  => 'expense_category',
			'guard_name'  => 'web',
		],
		[
			'id'    => 24,
			'name'  => 'settings',
			'guard_name'  => 'web',
		],
		[
			'id'    => 25,
			'name'  => 'currency',
			'guard_name'  => 'web',
		],
		[
			'id'    => 26,
			'name'  => 'backup',
			'guard_name'  => 'web',
		],
		[
			'id'    => 27,
			'name'  => 'group_permission',
			'guard_name'  => 'web',
		],
		[
			'id'    => 28,
			'name'  => 'products_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 29,
			'name'  => 'products_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 30,
			'name'  => 'products_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 31,
			'name'  => 'products_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 32,
			'name'  => 'barcode_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 33,
			'name'  => 'category',
			'guard_name'  => 'web',
		],
		[
			'id'    => 34,
			'name'  => 'brand',
			'guard_name'  => 'web',
		],
		[
			'id'    => 35,
			'name'  => 'unit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 36,
			'name'  => 'warehouse',
			'guard_name'  => 'web',
		],
		[
			'id'    => 37,
			'name'  => 'adjustment_view_all',
			'guard_name'  => 'web',
		],
		[
			'id'    => 38,
			'name'  => 'adjustment_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 39,
			'name'  => 'adjustment_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 40,
			'name'  => 'adjustment_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 41,
			'name'  => 'transfer_view_all',
			'guard_name'  => 'web',
		],
		[
			'id'    => 42,
			'name'  => 'transfer_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 43,
			'name'  => 'transfer_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 44,
			'name'  => 'transfer_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 45,
			'name'  => 'sales_view_all',
			'guard_name'  => 'web',
		],
		[
			'id'    => 46,
			'name'  => 'sales_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 47,
			'name'  => 'sales_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 48,
			'name'  => 'sales_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 49,
			'name'  => 'bon_livraison',
			'guard_name'  => 'web',
		],
		[
			'id'    => 50,
			'name'  => 'purchases_view_all',
			'guard_name'  => 'web',
		],
		[
			'id'    => 51,
			'name'  => 'purchases_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 52,
			'name'  => 'purchases_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 53,
			'name'  => 'purchases_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 54,
			'name'  => 'quotations_view_all',
			'guard_name'  => 'web',
		],
		[
			'id'    => 55,
			'name'  => 'quotations_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 56,
			'name'  => 'quotations_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 57,
			'name'  => 'quotations_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 58,
			'name'  => 'sale_returns_view_all',
			'guard_name'  => 'web',
		],
		[
			'id'    => 59,
			'name'  => 'sale_returns_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 60,
			'name'  => 'sale_returns_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 61,
			'name'  => 'sale_returns_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 62,
			'name'  => 'purchase_returns_view_all',
			'guard_name'  => 'web',
		],
		[
			'id'    => 63,
			'name'  => 'purchase_returns_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 64,
			'name'  => 'purchase_returns_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 65,
			'name'  => 'purchase_returns_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 66,
			'name'  => 'payment_sales_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 67,
			'name'  => 'payment_sales_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 68,
			'name'  => 'payment_sales_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 69,
			'name'  => 'payment_sales_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 70,
			'name'  => 'payment_purchases_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 71,
			'name'  => 'payment_purchases_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 72,
			'name'  => 'payment_purchases_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 73,
			'name'  => 'payment_purchases_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 74,
			'name'  => 'payment_sell_returns_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 75,
			'name'  => 'payment_sell_returns_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 76,
			'name'  => 'payment_sell_returns_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 77,
			'name'  => 'payment_sell_returns_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 78,
			'name'  => 'suppliers_view_all',
			'guard_name'  => 'web',
		],
		[
			'id'    => 79,
			'name'  => 'suppliers_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 80,
			'name'  => 'suppliers_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 81,
			'name'  => 'suppliers_delete',
			'guard_name'  => 'web',
		],
		[
			'id'    => 82,
			'name'  => 'sale_reports',
			'guard_name'  => 'web',
		],
		[
			'id'    => 83,
			'name'  => 'purchase_reports',
			'guard_name'  => 'web',
		],
		[
			'id'    => 84,
			'name'  => 'payment_sale_reports',
			'guard_name'  => 'web',
		],
		[
			'id'    => 85,
			'name'  => 'payment_purchase_reports',
			'guard_name'  => 'web',
		],
		[
			'id'    => 86,
			'name'  => 'payment_return_sale_reports',
			'guard_name'  => 'web',
		],
		[
			'id'    => 87,
			'name'  => 'top_products_report',
			'guard_name'  => 'web',
		],
		[
			'id'    => 88,
			'name'  => 'report_products',
			'guard_name'  => 'web',
		],
		[
			'id'    => 89,
			'name'  => 'report_inventaire',
			'guard_name'  => 'web',
		],

		[
			'id'    => 90,
			'name'  => 'report_clients',
			'guard_name'  => 'web',
		],
		[
			'id'    => 91,
			'name'  => 'report_fournisseurs',
			'guard_name'  => 'web',
		],
		[
			'id'    => 92,
			'name'  => 'reports_devis',
			'guard_name'  => 'web',
		],
		
		[
			'id'    => 93,
			'name'  => 'reports_alert_qty',
			'guard_name'  => 'web',
		],
		[
			'id'    => 94,
			'name'  => 'pos',
			'guard_name'  => 'web',
		],
		
		[
			'id'    => 95,
			'name'  => 'report_profit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 96,
			'name'  => 'dashboard',
			'guard_name'  => 'web',
		],
		[
			'id'    => 97,
			'name'  => 'print_labels',
			'guard_name'  => 'web',
		],
		[
			'id'    => 98,
			'name'  => 'adjustment_details',
			'guard_name'  => 'web',
		],
		[
			'id'    => 99,
			'name'  => 'pay_sale_due',
			'guard_name'  => 'web',
		],
		[
			'id'    => 100,
			'name'  => 'pay_sale_return_due',
			'guard_name'  => 'web',
		],
		[
			'id'    => 101,
			'name'  => 'client_details',
			'guard_name'  => 'web',
		],
		[
			'id'    => 102,
			'name'  => 'supplier_details',
			'guard_name'  => 'web',
		],
		[
			'id'    => 103,
			'name'  => 'pay_purchase_due',
			'guard_name'  => 'web',
		],
		[
			'id'    => 104,
			'name'  => 'pay_purchase_return_due',
			'guard_name'  => 'web',
		],
		[
			'id'    => 105,
			'name'  => 'purchases_details',
			'guard_name'  => 'web',
		],
		[
			'id'    => 106,
			'name'  => 'sales_details',
			'guard_name'  => 'web',
		],
		[
			'id'    => 107,
			'name'  => 'quotation_details',
			'guard_name'  => 'web',
		],

		[
			'id'    => 108,
			'name'  => 'sms_settings',
			'guard_name'  => 'web',
		],
		[
			'id'    => 109,
			'name'  => 'notification_template',
			'guard_name'  => 'web',
		],

		[
			'id'    => 110,
			'name'  => 'payment_purchase_returns_view',
			'guard_name'  => 'web',
		],
		[
			'id'    => 111,
			'name'  => 'payment_purchase_returns_add',
			'guard_name'  => 'web',
		],
		[
			'id'    => 112,
			'name'  => 'payment_purchase_returns_edit',
			'guard_name'  => 'web',
		],
		[
			'id'    => 113,
			'name'  => 'payment_purchase_returns_delete',
			'guard_name'  => 'web',
		],

		[
			'id'    => 114,
			'name'  => 'payment_return_purchase_reports',
			'guard_name'  => 'web',
		],
		[
			'id'    => 115,
			'name'  => 'pos_settings',
			'guard_name'  => 'web',
		],
		

		







		//---------------------------------------

		[
			'id'    => 200,
			'name'  => 'adjustment_view_own',
			'guard_name'  => 'web',
		],
		
		
		[
			'id'    => 201,
			'name'  => 'transfer_view_own',
			'guard_name'  => 'web',
		],
		[
			'id'    => 202,
			'name'  => 'sales_view_own',
			'guard_name'  => 'web',
		],
		[
			'id'    => 203,
			'name'  => 'purchases_view_own',
			'guard_name'  => 'web',
		],
		[
			'id'    => 204,
			'name'  => 'quotations_view_own',
			'guard_name'  => 'web',
		],
		[
			'id'    => 205,
			'name'  => 'sale_returns_view_own',
			'guard_name'  => 'web',
		],
		[
			'id'    => 206,
			'name'  => 'purchase_returns_view_own',
			'guard_name'  => 'web',
		],
		[
			'id'    => 207,
			'name'  => 'client_view_own',
			'guard_name'  => 'web',
		],
		[
			'id'    => 208,
			'name'  => 'suppliers_view_own',
			'guard_name'  => 'web',
		],
		[
			'id'    => 209,
			'name'  => 'attendance_view_own',
			'guard_name'  => 'web',
		],
		
		
		)
	);
    }
}
