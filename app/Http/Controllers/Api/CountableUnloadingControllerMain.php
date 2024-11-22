<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CountableUnloadingForm;
use App\Models\Product;
use App\Models\Material;
use App\Models\Provider;
use Illuminate\Http\Request;
use Validator;
use Auth;
use Carbon\Carbon;

class CountableUnloadingControllerMain extends Controller
{
    
    //-----------------------------------------------Create Countable Unloading Form-----------------------------------------------------//
//  public function store(Request $request)
// {
//     try {
//         // Validate the incoming request data
//         $validator = Validator::make($request->all(), [
//             'date' => 'required|date',
//             'do_number' => 'nullable|string|max:50',
//             'license_plate_number' => 'required|string|max:20',
//             'dn_number' => 'nullable|string|max:255',
//             'start' => 'nullable|date_format:H:i',  
//             'finish' => 'nullable|date_format:H:i', 
//             'product_id' => 'nullable|integer|exists:products,id',  
//             'supplier_id' => 'required|integer|exists:providers,id', 
//             'material_id' => 'required|integer|exists:materials,id', 
//             'tally_sheets' => 'required|array', 
//             'tally_sheets.*.product_id' => 'nullable|integer|exists:products,id',
//             'tally_sheets.*.product_description' => 'nullable|string|max:255',
//             'tally_sheets.*.production_lot' => 'nullable|string|max:50',
//             'tally_sheets.*.qty_do' => 'nullable|integer',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Validation errors occurred.',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }
 
//         $validatedData = $validator->validated();
 
//         $supplier = Provider::find($validatedData['supplier_id']);
//         $material = Material::find($validatedData['material_id']);  
         
//         if (!$supplier) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Supplier not found in the database.',
//             ], 404);
//         }
        
//         if (!$material) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Material not found in the database.',
//             ], 404);
//         }
 
//         $batchNumber = Carbon::now()->format('Ymd');
 
//         $processedTallySheets = array_map(function($sheet) {
//             $tsValues = [];
//             foreach ($sheet as $key => $value) {
//                 if (strpos($key, 'ts_') === 0) {
//                     $tsValues[] = (int)$value;
//                 }
//             }
 
//             $product = Product::find($sheet['product_id']);
            
//             return [
//                 'product_id' => $sheet['product_id'] ?? null,
//                 'product_name' => $product ? $product->name : null,
//                 'Ref' => $product ? $product->Ref : null,
//                 'product_description' => $sheet['product_description'] ?? null,
//                 'production_lot' => $sheet['production_lot'],
//                 'qty_do' => $sheet['qty_do'],
//                 'ts_1' => $tsValues,
//                 'total' => array_sum($tsValues),
//             ];
//         }, $validatedData['tally_sheets']);
 
//         $countableUnloadingForm = CountableUnloadingForm::create([
//             'date' => $validatedData['date'],
//             'batch_number' => $batchNumber,
//             'do_number' => $validatedData['do_number'] ?? null,
//             'license_plate_number' => $validatedData['license_plate_number'],
//             'dn_number' => $validatedData['dn_number'] ?? null,
//             'start' => $validatedData['start'] ?? null,  
//             'finish' => $validatedData['finish'] ?? null, 
//             'ts_1' => json_encode($processedTallySheets),
//             'product_id' => $validatedData['product_id'] ?? null,  
//             'supplier_id' => $supplier->id,
//             'material_id' => $material->id,  
//         ]);
 
//         return response()->json([
//             'status' => true,
//             'message' => 'Countable Unloading Form created successfully',
//             'data' => [
//                 'id' => $countableUnloadingForm->id,
//                 'batch_number' => $countableUnloadingForm->batch_number,
//                 'date' => $countableUnloadingForm->date,
//                 'do_number' => $countableUnloadingForm->do_number,
//                 'license_plate_number' => $countableUnloadingForm->license_plate_number,
//                 'dn_number' => $countableUnloadingForm->dn_number,
//                 'start' => $countableUnloadingForm->start,
//                 'finish' => $countableUnloadingForm->finish,
//                 'supplier' => [
//                     'id' => $supplier->id,
//                     'name' => $supplier->first_name . ' ' . $supplier->last_name,
//                     'company_name' => $supplier->company_name,
//                     'contact_number' => $supplier->contact_number,
//                     'email' => $supplier->email,
//                 ],
//                 'material' => [ 
//                     'id' => $material->id,
//                     'code' => $material->code,
//                     'material_type' => $material->material_type,
//                     'material_description' => $material->material_description,
//                     'rop' => $material->rop,
//                 ],
//                 'tally_sheets' => json_encode($processedTallySheets), // Encoding the array here
//             ],
//         ], 201);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }



   public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'do_number' => 'nullable|string|max:50',
            'license_plate_number' => 'required|string|max:20',
            'dn_number' => 'nullable|string|max:255',
            'start' => 'nullable|date_format:H:i',
            'finish' => 'nullable|date_format:H:i',
            'supplier_id' => 'required|integer|exists:providers,id',
            'tally_sheets' => 'required|array',
            'tally_sheets.*.production_lot' => 'required|string|max:50',
            'tally_sheets.*.qty_do' => 'nullable|integer',
            'tally_sheets.*.material_id' => 'nullable|integer|exists:materials,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $supplier = Provider::find($validatedData['supplier_id']);

        if (!$supplier) {
            return response()->json([
                'status' => false,
                'message' => 'Supplier not found in the database.',
            ], 404);
        }

        $batchNumber = Carbon::now()->format('Ymd');

        // Process tally sheets to include material_description and Ref based on material_id
        $processedTallySheets = array_map(function($sheet) {
            $tsValues = [];
            foreach ($sheet as $key => $value) {
                if (strpos($key, 'ts_') === 0) {
                    $tsValues[] = (int)$value;
                }
            }

            // Fetch material data if material_id is provided
            $materialData = null;
            if (isset($sheet['material_id'])) {
                $material = Material::find($sheet['material_id']);
                if ($material) {
                    $materialData = [
                        'material_id' => $material->id,
                        'material_type' => $material->material_type,
                        'material_description' => $material->material_description,
                        'code' => $material->code,
                    ];
                }
            }

            // Include material information, production lot, qty_do, and tally sheet data
            return array_merge([
                'production_lot' => $sheet['production_lot'],
                'qty_do' => $sheet['qty_do'],
                'ts_values' => $tsValues,
                'total' => array_sum($tsValues),
            ], $materialData ?? []);
        }, $validatedData['tally_sheets']);

        $countableUnloadingForm = CountableUnloadingForm::create([
            'date' => $validatedData['date'],
            'batch_number' => $batchNumber,
            'do_number' => $validatedData['do_number'] ?? null,
            'license_plate_number' => $validatedData['license_plate_number'],
            'dn_number' => $validatedData['dn_number'] ?? null,
            'start' => $validatedData['start'] ?? null,
            'finish' => $validatedData['finish'] ?? null,
            'ts_1' => json_encode($processedTallySheets), 
            'supplier_id' => $supplier->id,
            'material_id' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Countable Unloading Form created successfully',
            'data' => [
                'id' => $countableUnloadingForm->id,
                'batch_number' => $countableUnloadingForm->batch_number,
                'date' => $countableUnloadingForm->date,
                'do_number' => $countableUnloadingForm->do_number,
                'license_plate_number' => $countableUnloadingForm->license_plate_number,
                'dn_number' => $countableUnloadingForm->dn_number,
                'start' => $countableUnloadingForm->start,
                'finish' => $countableUnloadingForm->finish,
                'supplier' => [
                    'id' => $supplier->id,
                    'Ref' => $supplier->Ref,
                    'name' => $supplier->first_name . ' ' . $supplier->last_name,
                    'company_name' => $supplier->company_name,
                    'contact_number' => $supplier->contact_number,
                    'email' => $supplier->email,
                ],
                'tally_sheets' => $processedTallySheets,
            ],
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}







    
    
    
    //------------------------------------------------Update Countable Unloading Form-----------------------------------------------//
    
    
    
   public function update(Request $request, $id)
{
    try {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'do_number' => 'nullable|string|max:50',
            'license_plate_number' => 'nullable|string|max:20',
            'dn_number' => 'nullable|string|max:255',
            'start' => 'nullable|date_format:H:i',
            'finish' => 'nullable|date_format:H:i',
            'supplier_id' => 'nullable|integer|exists:providers,id',
            'tally_sheets' => 'required|array',
            'tally_sheets.*.production_lot' => 'required|string|max:50',
            'tally_sheets.*.qty_do' => 'nullable|integer',
            'tally_sheets.*.material_id' => 'nullable|integer|exists:materials,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $countableForm = CountableUnloadingForm::find($id);

        if (!$countableForm) {
            return response()->json([
                'status' => false,
                'message' => 'Countable Unloading Form not found',
            ], 404);
        }

        // Process tally sheets to include material_description and Ref based on material_id
        $processedTallySheets = array_map(function($sheet) {
            $tsValues = [];
            foreach ($sheet as $key => $value) {
                if (strpos($key, 'ts_') === 0) {
                    $tsValues[] = (int)$value;
                }
            }

            // Fetch material data if material_id is provided
            $materialData = null;
            if (isset($sheet['material_id'])) {
                $material = Material::find($sheet['material_id']);
                if ($material) {
                    $materialData = [
                        'material_id' => $material->id,
                        'material_type' => $material->material_type,
                        'material_description' => $material->material_description,
                        'code' => $material->code,
                    ];
                }
            }

            // Include material information, production lot, qty_do, and tally sheet data
            return array_merge([
                'production_lot' => $sheet['production_lot'],
                'qty_do' => $sheet['qty_do'],
                'ts_values' => $tsValues,
                'total' => array_sum($tsValues),
            ], $materialData ?? []);
        }, $validatedData['tally_sheets']);

        $countableForm->update([
            'date' => $validatedData['date'] ?? $countableForm->date,
            'do_number' => $validatedData['do_number'] ?? $countableForm->do_number,
            'license_plate_number' => $validatedData['license_plate_number'] ?? $countableForm->license_plate_number,
            'dn_number' => $validatedData['dn_number'] ?? $countableForm->dn_number,
            'start' => $validatedData['start'] ?? $countableForm->start,
            'finish' => $validatedData['finish'] ?? $countableForm->finish,
            'ts_1' => json_encode($processedTallySheets), // Encode to JSON
            'supplier_id' => $validatedData['supplier_id'] ?? $countableForm->supplier_id,
        ]);

        $supplier = Provider::find($countableForm->supplier_id);

        return response()->json([
            'status' => true,
            'message' => 'Countable Unloading Form updated successfully',
            'data' => [
                'id' => $countableForm->id,
                'batch_number' => $countableForm->batch_number,
                'date' => $countableForm->date,
                'do_number' => $countableForm->do_number,
                'license_plate_number' => $countableForm->license_plate_number,
                'dn_number' => $countableForm->dn_number,
                'start' => $countableForm->start,
                'finish' => $countableForm->finish,
                'supplier' => [
                    'id' => $supplier->id,
                    'Ref' => $supplier->Ref,
                    'name' => $supplier->first_name . ' ' . $supplier->last_name,
                    'company_name' => $supplier->company_name,
                    'contact_number' => $supplier->contact_number,
                    'email' => $supplier->email,
                ],
                'tally_sheets' => $processedTallySheets,
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}











//-----------------------------------------------Show all Countable Unloading Form------------------------------------------------//


    public function showAll(Request $request)
{
    try {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $formQuery = CountableUnloadingForm::query();

        // Check if search term is provided
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');

            // Search by do_number, license_plate_number, etc.
            $formQuery->where(function ($query) use ($searchTerm) {
                $query->where('do_number', 'like', '%' . $searchTerm . '%')
                      ->orWhere('start', 'like', '%' . $searchTerm . '%')
                      ->orWhere('finish', 'like', '%' . $searchTerm . '%')
                      ->orWhere('date', 'like', '%' . $searchTerm . '%')
                      ->orWhere('license_plate_number', 'like', '%' . $searchTerm . '%');
            });
        }

        $formQuery->orderBy('created_at', 'desc');

        $countableForms = $formQuery->paginate($perPage, ['*'], 'page', $page);

        if ($countableForms->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No forms found',
                'data' => [],
            ], 404);
        }

        // Transform each form to include product and supplier details
        $countableForms->getCollection()->transform(function ($form) {
            $form->standard_weight = $form->standard_weight ?? null;

            // Add product details with specific fields
            $form->product = optional(Product::find($form->product_id))->only(['id', 'name', 'product_description', 'weight', 'price']);

            // Add supplier details with specific fields
            $supplier = Provider::find($form->supplier_id);
            $form->supplier = $supplier ? [
                'id' => $supplier->id,
                'Ref' => $supplier->Ref,
                'name' => $supplier->first_name . ' ' . $supplier->last_name,
                'company_name' => $supplier->company_name,
                'contact_number' => $supplier->contact_number,
                'email' => $supplier->email,
            ] : null;

            // Decode the tally sheets if available
            $tallySheets = json_decode($form->ts_1, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $form->tally_sheets = [];
            } else {
                $form->tally_sheets = array_map(function ($sheet) {
                    $material = isset($sheet['material_id']) ? Material::find($sheet['material_id']) : null;
                    $tsValues = $sheet['ts_values'] ?? [];

                    return [
                        'material_id' => $material->id ?? null,
                        'material_type' => $material->material_type ?? null,
                        'material_description' => $material->material_description ?? null,
                        'material_id' => $material->code ?? null,
                        'production_lot' => $sheet['production_lot'],
                        'qty_do' => $sheet['qty_do'],
                        'ts_values' => $tsValues,
                        'total' => array_sum($tsValues),
                    ];
                }, $tallySheets ?: []);
            }

            // Remove ts_1 from the response
            unset($form->ts_1);

            return $form;
        });

        return response()->json([
            'status' => true,
            'message' => 'Countable Unloading Forms Retrieved Successfully',
            'data' => [
                'current_page' => $countableForms->currentPage(),
                'data' => $countableForms->items(),
                'first_page_url' => $countableForms->url(1),
                'from' => $countableForms->firstItem(),
                'last_page' => $countableForms->lastPage(),
                'last_page_url' => $countableForms->url($countableForms->lastPage()),
                'next_page_url' => $countableForms->nextPageUrl(),
                'path' => $countableForms->path(),
                'per_page' => $countableForms->perPage(),
                'prev_page_url' => $countableForms->previousPageUrl(),
                'to' => $countableForms->lastItem(),
                'total' => $countableForms->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}












//---------------------------------------------------------Show Countable Unlaoding Form By IDs-------------------------------------------------------//

//  public function showById($id)
// {
//     try {
//         // Retrieve the Countable Unloading Form by ID with related provider and material
//         $form = CountableUnloadingForm::with(['provider', 'material'])->find($id);

//         if (!$form) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Countable Unloading Form not found',
//             ], 404);
//         }

//         // Return the response with the structured data
//         return response()->json([
//             'status' => true,
//             'message' => 'Countable Unloading Form retrieved successfully!',
//             'data' => [
//                 'id' => $form->id,
//                 'batch_number' => $form->batch_number,
//                 'date' => $form->date,
//                 'do_number' => $form->do_number,
//                 'license_plate_number' => $form->license_plate_number,
//                 'dn_number' => $form->dn_number,
//                 'provider' => $form->supplier ? [
//                     'id' => $form->supplier->id,
//                     'name' => $form->supplier->first_name . ' ' . $form->supplier->last_name,
//                     'company_name' => $form->supplier->company_name,
//                     'contact_number' => $form->supplier->contact_number,
//                     'email' => $form->supplier->email,
//                 ] : null,
//                 'material' => $form->material ? [
//                     'id' => $form->material->id,
//                     'code' => $form->material->code,
//                     'material_type' => $form->material->material_type,
//                     'material_description' => $form->material->material_description,
//                     'rop' => $form->material->rop,
//                 ] : null,
//                 'tally_sheets' => $form->tallySheets, // Return tally sheets directly
//             ],
//         ], 200);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Error retrieving Countable Unloading Form: ' . $e->getMessage(),
//         ], 500);
//     }
// }

// public function showById($id)
// {
//     try {
//         $user = Auth::guard('sanctum')->user();

//         if (!$user) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Unauthorized',
//             ], 401);
//         }

//         $form = CountableUnloadingForm::find($id);

//         if (!$form) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Form not found',
//                 'data' => [],
//             ], 404);
//         }

//         // Include product and supplier details
//         $form->standard_weight = $form->standard_weight ?? null;
//         $form->product = optional(Product::find($form->product_id))->only(['id', 'name', 'product_description', 'weight', 'price']);
        
//         $supplier = Provider::find($form->supplier_id);
//         $form->supplier = $supplier ? [
//             'id' => $supplier->id,
//             'name' => $supplier->first_name . ' ' . $supplier->last_name,
//             'company_name' => $supplier->company_name,
//             'contact_number' => $supplier->contact_number,
//             'email' => $supplier->email,
//         ] : null;

//         // Decode the main ts_1 field
//         $tallySheets = json_decode($form->ts_1, true) ?: [];

//         // Decode each ts_1 within the tally sheets
//         $form->tally_sheets = array_map(function($sheet) {
//             $sheet['ts_1'] = isset($sheet['ts_1']) ? json_decode($sheet['ts_1'], true) : [];
//             return $sheet;
//         }, $tallySheets);

//         return response()->json([
//             'status' => true,
//             'message' => 'Countable Unloading Form Retrieved Successfully',
//             'data' => $form,
//         ], 200);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }

   public function showById($id)
{
    try {
        // Find the Countable Unloading Form record by ID
        $countableUnloadingForm = CountableUnloadingForm::findOrFail($id);

        // Decode the tally_sheets data
        $tallySheets = json_decode($countableUnloadingForm->ts_1, true);

        // Check if decoding was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to decode tally sheets data. Error: ' . json_last_error_msg(),
            ], 500);
        }

        // Process each tally sheet to retrieve material details
        $processedTallySheets = array_map(function ($sheet) {
            $material = isset($sheet['material_id']) ? Material::find($sheet['material_id']) : null;
            $tsValues = $sheet['ts_values'] ?? [];

            return [
                'id' => $material->id ?? null,
                // 'material_id' => $material->id ?? null,
                'material_type' => $material->material_type ?? null,
                'material_description' => $material->material_description ?? null,
                'material_id' => $material->code ?? null,
                'production_lot' => $sheet['production_lot'],
                'qty_do' => $sheet['qty_do'],
                'ts_values' => $tsValues,
                'total' => array_sum($tsValues),
            ];
        }, $tallySheets);

        // Get related supplier information
        $supplier = Provider::find($countableUnloadingForm->supplier_id);

        return response()->json([
            'status' => true,
            'message' => 'Countable Unloading Form retrieved successfully',
            'data' => [
                'id' => $countableUnloadingForm->id,
                'batch_number' => $countableUnloadingForm->batch_number,
                'date' => $countableUnloadingForm->date,
                'do_number' => $countableUnloadingForm->do_number,
                'license_plate_number' => $countableUnloadingForm->license_plate_number,
                'dn_number' => $countableUnloadingForm->dn_number,
                'start' => $countableUnloadingForm->start,
                'finish' => $countableUnloadingForm->finish,
                'supplier' => [
                    'id' => $supplier->id,
                    'Ref' => $supplier->Ref,
                    'name' => $supplier->first_name . ' ' . $supplier->last_name,
                    'company_name' => $supplier->company_name,
                    'contact_number' => $supplier->contact_number,
                    'email' => $supplier->email,
                ],
                'tally_sheets' => $processedTallySheets,
            ],
        ], 200);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Countable Unloading Form not found.',
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}




















//-----------------------------------------------------------Delete Countable Unloading Form-------------------------------------------------------//
    public function delete($id)
{
    try {
        $countableForm = CountableUnloadingForm::find($id);

        if (!$countableForm) {
            return response()->json([
                'status' => false,
                'message' => 'Countable Unloading Form not found',
            ], 404);
        }

        $countableForm->delete();

        return response()->json([
            'status' => true,
            'message' => 'Countable Unloading Form Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}


  
    
}
