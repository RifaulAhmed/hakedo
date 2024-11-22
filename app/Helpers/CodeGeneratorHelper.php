<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\Prefix;
use App\Models\Warehouse;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Models\Adjustment;
use App\Models\WRRForm;
use App\Models\MaterialRequestForm;
use App\Models\LoadingReport;
use DB;

class CodeGeneratorHelper
{
    protected $defaultPrefixes = [
        'product' => 'PRDT',
        'sale' => 'Sal',
        'warehouse' => 'WH',
        'purchase' => 'PUR',
        'purchase_return' => 'PR',
        'sales_return' => 'SR',
        'stock' => 'STK',
        'wrr_form' => 'WRR',
        'provider' => 'SUP',
        'return_material_form' => 'ROMF',
        'material_request_form' => 'MRF/Warehouse/HPM/VII/2024',
    ];

    public static function generateCode($type, $prefix)
{
    $tables = [
        'purchase' => 'purchases',
        'purchase_return' => 'purchase_returns',
        'product' => 'products',
        'adjustment' => 'adjustments',
        'sale' => 'sales',
        'sales_return' => 'sale_returns',
        'wrr_form' => 'wrr_forms',
        'warehouse' => 'warehouses',
        'provider' => 'providers',
        'return_material_form' => 'return_material_forms',
        'material_request_form' => 'material_request_forms'
    ];

    if (!array_key_exists($type, $tables)) {
        throw new \InvalidArgumentException("Invalid type provided for code generation.");
    }

    $table = $tables[$type];

    $latestCode = DB::table($table)
        ->where('Ref', 'LIKE', "$prefix%")
        ->orderBy('id', 'desc')
        ->value('Ref');

    $lastNumber = $latestCode ? intval(str_replace($prefix, '', $latestCode)) : 0;
    $newNumber = $lastNumber + 1;

    return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
}



//----------------------------------Generate Transaction ID----------------------------------//
public static function generateTransactionId($prefix = 'TXN')
{
    return $prefix . strtoupper(uniqid());
}


    private function getModel($entityType)
    {
        // \Log::debug('Entity Type: ' . $entityType);
        switch ($entityType) {
            case 'product':
                return Product::class;
            case 'sale':
                return Sale::class;
            case 'warehouse':
                return Warehouse::class;
            case 'purchase':
                return Purchase::class;
            case 'purchase_return':
                return PurchaseReturn::class;
            case 'sales_return':
                return SaleReturn::class;
            case 'stock':
                return Adjustment::class;
            case 'wrr_form':
                return WRRForm::class;
            case 'provider':
                return Provider::class;
            case 'return_material_forms':
                return ReturnOfMaterialForm::class;
            case 'material_request_forms';
                return MaterialRequestForm::class;
            default:
                throw new \Exception("Invalid entity type");
        }
    }
}
