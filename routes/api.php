<?php

use App\Http\Controllers\Api\CountableUnloadingControllerMain;
use App\Http\Controllers\Api\PlasticIncomingQCControllerMain;
use App\Http\Controllers\Api\PreformIncomingQCControllerMain;
use App\Http\Controllers\Api\PurchaseControllerMain;
use App\Http\Controllers\Api\PurchaseReturnControllerMain;
use App\Http\Controllers\Api\SalesControllerMain;
use App\Http\Controllers\Api\StockControllerMain;
use App\Http\Controllers\Api\RegisterControllerMain;
use App\Http\Controllers\Api\IncomingMaterialTagControllerMain;
use App\Http\Controllers\Api\UncountableUnloadingControllerMain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Models\Brand;
use App\Http\Controllers\Api\ProductsControllerMain;
use App\Http\Controllers\Api\ProvidersControllerMain;
use App\Http\Controllers\Api\WarehouseControllerMain;
use App\Http\Controllers\Api\SalesReturnControllerMain;
use App\Http\Controllers\Api\RoleControllerMain;
use App\Http\Controllers\Api\WRRFormControllerMain;
use App\Http\Controllers\Api\CustomerControllerMain;
use App\Http\Controllers\Api\EmailSettingsControllerMain;
use App\Http\Controllers\Api\SystemSettingsControllerMain;
use App\Http\Controllers\Api\ROMFControllerMain;
use App\Http\Controllers\Api\MaterialRequestFormControllerMain;
use App\Http\Controllers\Api\LoadingReportControllerMain;
use App\Http\Controllers\Api\GoodsReceiveFormControllerMain;
use App\Http\Controllers\Api\NeckTypeControllerMain;
use App\Http\Controllers\Api\MaterialTypeControllerMain;
use App\Http\Controllers\Api\FinishedGoodsControllerMain;
use App\Http\Controllers\Api\MaterialControllerMain;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//get categories
Route::get('categories', function(Request $request) {
    $perPage = $request->perPage ?: 7;
    $categories = Category::where('deleted_at', '=', null)
      ->paginate($perPage, ['id', 'name']);
    return response()->json([
      'data' => $categories->items(),
      'current_page' => $categories->currentPage(),
      'last_page' => $categories->lastPage()
    ]);
  });

  //get brands
Route::get('brands', function(Request $request) {
    $perPage = $request->perPage ?: 7;
    $brands = Brand::where('deleted_at', '=', null)
      ->paginate($perPage, ['id', 'name']);
    return response()->json([
      'data' => $brands->items(),
      'current_page' => $brands->currentPage(),
      'last_page' => $brands->lastPage()
    ]);
  });

//----------------------------------Product-----------------------------------//
  Route::post('/product', [ProductsControllerMain::class, 'store']);
  Route::post('/product/{id}', [ProductsControllerMain::class, 'update']);
  Route::get('/product', [ProductsControllerMain::class, 'showAll']);
  Route::get('/product/{id}', [ProductsControllerMain::class, 'showbyID']);
  Route::delete('/product/{id}', [ProductsControllerMain::class, 'delete']);
  
  Route::get('Allproducts', [ProductsControllerMain::class, 'Allproducts']);



//-----------------------------------Sales--------------------------------------------//
  Route::post('/sales', [SalesControllerMain::class, 'store']);
  Route::put('/sales/{id}', [SalesControllerMain::class, 'update']);
  Route::get('/sales', [SalesControllerMain::class, 'showAll']);
  Route::get('/sales/{id}', [SalesControllerMain::class, 'showById']);
  Route::delete('/sales/{id}', [SalesControllerMain::class, 'delete']);



//-----------------------------------------Warehouse------------------------------------//
  Route::post('/warehouse', [WarehouseControllerMain::class, 'store']);
  Route::put('/warehouse/{id}', [WarehouseControllerMain::class, 'update']);
  Route::get('/get_warehouse', [WarehouseControllerMain::class, 'showAll']);
  Route::get('/warehouse/{id}', [WarehouseControllerMain::class, 'showById']);
  Route::delete('/warehouse/{id}', [WarehouseControllerMain::class, 'delete']);



//------------------------------------Purchases-----------------------------------------------//
  Route::post('/purchase', [PurchaseControllerMain::class, 'store']);
  Route::put('/purchase/{id}', [PurchaseControllerMain::class, 'update']);
  Route::get('/purchase', [PurchaseControllerMain::class, 'showAll']);
  Route::get('/purchase/{id}', [PurchaseControllerMain::class, 'showById']);
  Route::delete('/purchase/{id}', [PurchaseControllerMain::class, 'delete']);




//------------------------------------Purchase Return-----------------------------------------//
  Route::post('/purchase_return', [PurchaseReturnControllerMain::class, 'store']);
  Route::put('/purchase_return/{id}', [PurchaseReturnControllerMain::class, 'update']);
  Route::get('/purchase_return', [PurchaseReturnControllerMain::class, 'showAll']);
  Route::get('/purchase_return/{id}', [PurchaseReturnControllerMain::class, 'showById']);
  Route::delete('/purchase_return/{id}', [PurchaseReturnControllerMain::class, 'delete']);




//----------------------------------------Sales Return-------------------------------------------//
  Route::post('/sales_return', [SalesReturnControllerMain::class, 'store']);
  Route::put('/sales_return/{id}', [SalesReturnControllerMain::class, 'update']);
  Route::get('/sales_return', [SalesReturnControllerMain::class, 'showAll']);
  Route::get('/sales_return/{id}', [SalesReturnControllerMain::class, 'showById']);
  Route::delete('/sales_return/{id}', [SalesReturnControllerMain::class, 'delete']);




//---------------------------------------Stock Management----------------------------------------//
  Route::post('stock', [StockControllerMain::class, 'store']);
  Route::put('stock/{id}', [StockControllerMain::class, 'update']);
  Route::get('stock', [StockControllerMain::class, 'showAll']);
  Route::get('stock/{id}', [StockControllerMain::class, 'showById']);
  Route::delete('stock/{id}', [StockControllerMain::class, 'delete']);




//-------------------------------------Roles-----------------------------------------------------//
  Route::post('role', [RoleControllerMain::class, 'store']);
  Route::put('role/{id}', [RoleControllerMain::class, 'update']);
  Route::get('role', [RoleControllerMain::class, 'showAll']);
  Route::get('role/{id}', [RoleControllerMain::class, 'showById']);
  Route::delete('role/{id}', [RoleControllerMain::class, 'delete']);  




//-----------------------------------Users Registration-----------------------------------------//
  Route::post('login', [RegisterControllerMain::class, 'login']);
  Route::post('user_register', [RegisterControllerMain::class, 'register']);
  Route::post('user_register/{id}', [RegisterControllerMain::class, 'update']);
  Route::get('user_register', [RegisterControllerMain::class, 'showAll']);
  Route::get('user_register/{id}', [RegisterControllerMain::class, 'showById']);
  Route::delete('user_register/{id}', [RegisterControllerMain::class, 'delete']);
Route::get('profile', [RegisterControllerMain::class, 'getUserProfile']);


  
//   Route::middleware('auth:sanctum')->get('/user-details', [RegisterControllerMain::class, 'showByToken']);



//----------------------------------------WRR FORM-----------------------------------------------//
  Route::post('wrr_form', [WRRFormControllerMain::class, 'store']);
  Route::put('wrr_form/{id}', [WRRFormControllerMain::class, 'update']);
  Route::get('wrr_form', [WRRFormControllerMain::class, 'showAll']);
  Route::get('wrr_form/{id}', [WRRFormControllerMain::class, 'showById']);
  Route::delete('wrr_form/{id}', [WRRFormControllerMain::class, 'delete']);






//------------------------------------Incoming Material Tag------------------------------------//
  Route::post('material_tag', [IncomingMaterialTagControllerMain::class, 'store']);
  Route::put('material_tag/{id}', [IncomingMaterialTagControllerMain::class, 'update']);
  Route::get('material_tag', [IncomingMaterialTagControllerMain::class, 'showAll']);
  Route::get('material_tag/{id}', [IncomingMaterialTagControllerMain::class, 'showById']);
  Route::delete('material_tag/{id}', [IncomingMaterialTagControllerMain::class, 'delete']);






//---------------------------------Uncountable Unloading Form----------------------------------//
  Route::post('uncountable_unloading_form', [UncountableUnloadingControllerMain::class, 'store']);
  Route::put('uncountable_unloading_form/{id}', [UncountableUnloadingControllerMain::class, 'update']);
  Route::get('uncountable_unloading_form', [UncountableUnloadingControllerMain::class, 'showAll']);
  Route::get('uncountable_unloading_form/{id}', [UncountableUnloadingControllerMain::class, 'showById']);
  Route::delete('uncountable_unloading_form/{id}', [UncountableUnloadingControllerMain::class, 'delete']);







//-----------------------------------Countable Unloading Form-----------------------------------//
  Route::post('countable_unloading_form', [CountableUnloadingControllerMain::class, 'store']);
  Route::put('countable_unloading_form/{id}', [CountableUnloadingControllerMain::class, 'update']);
  Route::get('countable_unloading_form', [CountableUnloadingControllerMain::class, 'showAll']);
  Route::get('countable_unloading_form/{id}', [CountableUnloadingControllerMain::class, 'showById']);
  Route::delete('countable_unloading_form/{id}', [CountableUnloadingControllerMain::class, 'delete']);
  
  
  






//------------------------------------Preform Incoming QC----------------------------------------//
  Route::post('preform_qc', [PreformIncomingQCControllerMain::class, 'store']);






//----------------------------------Plastic Incoming QC-------------------------------------//
  Route::post('plastic_qc', [PlasticIncomingQCControllerMain::class, 'store']);





//-----------------------------Incoming Material QC Label------------------------------------//
  Route::post('incoming_material_qc', [IncomingMaterialTagControllerMain::class, 'store']);






//-----------------------------------------Suppliers------------------------------------------//
  Route::post('supplier', [ProvidersControllerMain::class, 'store']);
  Route::put('supplier/{id}', [ProvidersControllerMain::class, 'update']);
  Route::get('supplier', [ProvidersControllerMain::class, 'showAll']);
  Route::get('supplier/{id}', [ProvidersControllerMain::class, 'showById']);
  Route::delete('supplier/{id}', [ProvidersControllerMain::class, 'delete']);
  
  Route::get('Allsuppliers', [ProvidersControllerMain::class, 'Allsuppliers']);
  
  
  
  
  
  
  
  
  //--------------------------------------Customers-----------------------------------------//
  Route::post('customer', [CustomerControllerMain::class, 'store']);
  Route::post('customer/{id}', [CustomerControllerMain::class, 'update']);
  Route::get('customer', [CustomerControllerMain::class, 'showAll']);
  Route::get('customer/{id}', [CustomerControllerMain::class, 'showById']);
  Route::delete('customer/{id}', [CustomerControllerMain::class, 'delete']);
  
  
  
  
  
  
  //------------------------------------Email Settings----------------------------------------//
  Route::post('email', [EmailSettingsControllerMain::class, 'store']);
  Route::put('email/{id}', [EmailSettingsControllerMain::class, 'update']);
  Route::get('email', [EmailSettingsControllerMain::class, 'showAll']);
  Route::get('email/{id}', [EmailSettingsControllerMain::class, 'showById']);
  Route::delete('email/{id}', [EmailSettingsControllerMain::class, 'delete']);
  
  
  
  
  
  //-------------------------------------System Setting----------------------------------------//
  Route::post('system', [SystemSettingsControllerMain::class, 'store']);
  Route::post('system/{id}', [SystemSettingsControllerMain::class, 'update']);
  Route::get('system', [SystemSettingsControllerMain::class, 'showAll']);
  Route::get('system/{id}', [SystemSettingsControllerMain::class, 'showById']);
  Route::delete('system/{id}', [SystemSettingsControllerMain::class, 'delete']);
  
  
  
  
  
    //----------------------------------ROMF Form---------------------------------------//
    Route::post('romf', [ROMFControllerMain::class, 'store']);
    Route::put('return_of_material/{id}', [ROMFControllerMain::class, 'update']);
    Route::get('romf', [ROMFControllerMain::class, 'showAll']);
    Route::get('romf/{id}', [ROMFControllerMain::class, 'showByID']);
    Route::delete('romf/{id}', [ROMFControllerMain::class, 'delete']);
    
    
    
    //-----------------------------------Material Request Form (MRF)-------------------------------------------//
    Route::post('material_request_form', [MaterialRequestFormControllerMain::class, 'store']); 
    Route::put('material_request_form/{id}', [MaterialRequestFormControllerMain::class, 'update']);    
    Route::get('material_request_form', [MaterialRequestFormControllerMain::class, 'showAll']); 
    Route::get('material_request_form/{id}', [MaterialRequestFormControllerMain::class, 'showById']);
    Route::delete('material_request_form/{id}', [MaterialRequestFormControllerMain::class, 'delete']);  
    
    
    
    
    
    //-----------------------------------Loading Reports-------------------------------------------//
    Route::post('loading_report', [LoadingReportControllerMain::class, 'store']); 
    Route::put('loading_report/{id}', [LoadingReportControllerMain::class, 'update']);    
    Route::get('loading_report', [LoadingReportControllerMain::class, 'showAll']); 
    Route::get('loading_report/{id}', [LoadingReportControllerMain::class, 'showById']);
    Route::delete('loading_report/{id}', [LoadingReportControllerMain::class, 'delete']);  

         
        
       
     //-----------------------------------Goods Receive Form-------------------------------------------//
    Route::post('goods_receive', [GoodsReceiveFormControllerMain::class, 'store']); 
    Route::put('goods_receive/{id}', [GoodsReceiveFormControllerMain::class, 'update']);    
    Route::get('goods_receive', [GoodsReceiveFormControllerMain::class, 'showAll']); 
    Route::get('goods_receive/{id}', [GoodsReceiveFormControllerMain::class, 'showById']);
    Route::delete('goods_receive/{id}', [GoodsReceiveFormControllerMain::class, 'delete']); 
    
    
    
    
    
    //--------------------------------------------------Neck Type------------------------------------------//
    Route::post('neck_type', [NeckTypeControllerMain::class, 'store']);
    Route::put('neck_type/{id}', [NeckTypeControllerMain::class, 'update']);
    Route::get('neck_type', [NeckTypeControllerMain::class, 'showAll']);
    Route::get('neck_type/{id}', [NeckTypeControllerMain::class, 'showById']);
    Route::delete('neck_type/{id}', [NeckTypeControllerMain::class, 'delete']);
    
    
    
    
      //--------------------------------------------------Neck Type------------------------------------------//
    Route::post('material_type', [MaterialTypeControllerMain::class, 'store']);
    Route::put('material_type/{id}', [MaterialTypeControllerMain::class, 'update']);
    Route::get('material_type', [MaterialTypeControllerMain::class, 'showAll']);
    Route::get('material_type/{id}', [MaterialTypeControllerMain::class, 'showById']);
    Route::delete('material_type/{id}', [MaterialTypeControllerMain::class, 'delete']);
    
    
    
    
    
     //--------------------------------------------------Finish Goods------------------------------------------//
    Route::post('finished_goods', [FinishedGoodsControllerMain::class, 'store']);
    Route::put('finished_goods/{id}', [FinishedGoodsControllerMain::class, 'update']);
    Route::get('finished_goods', [FinishedGoodsControllerMain::class, 'showAll']);
    Route::get('finished_goods/{id}', [FinishedGoodsControllerMain::class, 'showById']);
    Route::delete('finished_goods/{id}', [FinishedGoodsControllerMain::class, 'delete']);
    
    
    
    
    
    
    //--------------------------------------------------Materials------------------------------------------//
    Route::post('materials', [MaterialControllerMain::class, 'store']);
    Route::put('materials/{id}', [MaterialControllerMain::class, 'update']);
    Route::get('materials', [MaterialControllerMain::class, 'showAll']);
    Route::get('materials/{id}', [MaterialControllerMain::class, 'showById']);
    Route::delete('materials/{id}', [MaterialControllerMain::class, 'delete']);



  
  
  
  
  
  
  
  
  
  
  