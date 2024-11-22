<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WRRForm;
use App\Models\Provider;
use App\Models\Material;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WRRFormControllerMain extends Controller
{
    //---------------------------------------------------------------Create WRR Form----------------------------------------------------//
//   public function store(Request $request)
// {
//     try {
//         $validator = Validator::make($request->all(), [
//             'prefix' => 'nullable|string|max:100',
//             'dn_number' => 'required|string|max:8|unique:wrr_forms,dn_number',
//             'po_number' => 'required|string|max:255',
//             'supplier_id' => 'nullable|exists:providers,id',
//             'supplier_name' => 'required|string|max:255',
//             'materials' => 'required|array',   
//             'materials.*.material_id' => 'required|string|max:255',
//             'materials.*.material_description' => 'nullable|string|max:500',
//             'materials.*.dn_quantity' => 'required|string|min:0',
//             'materials.*.received_quantity' => 'required|string|min:0',
//             'date' => 'required|date',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Validation errors occurred',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $validatedData = $validator->validated();
        
//          $supplier = Provider::where('first_name', $request->supplier_name)->first();  

//         if (!$supplier) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Supplier not found in the database.',
//             ], 404);
//         }
 
//         $prefix = $validatedData['prefix'] ?? 'WRR/WH/HPM/VIII/2024';
//         $wrrNumber = CodeGeneratorHelper::generateCode('wrr_form', $prefix);
 
//         $encodedMaterials = json_encode($validatedData['materials']);
 
//         $wrr = WRRForm::create([
//             'Ref' => $wrrNumber,
//             'dn_number' => $validatedData['dn_number'],
//             'po_number' => $validatedData['po_number'],
//             'supplier_id' => $validatedData['supplier_id'] ?? null,
//             'supplier_name' => $validatedData['supplier_name'],
//             'material_description' => $encodedMaterials,  
//             'date' => $validatedData['date'],
//         ]);

//         return response()->json([
//             'status' => true,
//             'message' => 'Warehouse Receiving Report Form Created Successfully',
//             'data' => $wrr,
//         ], 201);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }

//   public function store(Request $request)
// {
//     try {
       
//         $customMessages = [
//             'materials.*.material_id.required' => 'The material ID field is required.',
//             'materials.*.material_id.integer' => 'The material ID must be a valid integer.',
//             'materials.*.material_id.exists' => 'The selected material ID does not exist in the materials list.',
//             'materials.*.dn_quantity.required' => 'The DN quantity field is required.',
//             'materials.*.received_quantity.required' => 'The received quantity field is required.',
//         ];

//         $validator = Validator::make($request->all(), [
//             'prefix' => 'nullable|string|max:100',
//             'dn_number' => 'required|string|max:8|unique:wrr_forms,dn_number',
//             'po_number' => 'required|string|max:255',
//             'supplier_id' => 'nullable|exists:providers,id',
//             'supplier_name' => 'required|string|max:255',
//             'materials' => 'required|array',
//             'materials.*.material_id' => 'required|integer|exists:materials,id',
//             'materials.*.material_description' => 'nullable|string|max:500',
//             'materials.*.dn_quantity' => 'required|string|min:0',
//             'materials.*.received_quantity' => 'required|string|min:0',
//             'date' => 'required|date',
//         ], $customMessages);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Validation errors occurred',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $validatedData = $validator->validated();

//         // Verify if supplier exists
//         $supplier = Provider::where('first_name', $request->supplier_name)->first();
//         if (!$supplier) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Supplier not found in the database.',
//             ], 404);
//         }

//         // Default prefix if none provided
//         $defaultPrefix = '00001/WRR/Warehouse/HPM/XI/2024';
//         $prefix = $validatedData['prefix'] ?? $defaultPrefix;

//         // Split the prefix to extract the number
//         $prefixParts = explode('/', $prefix);
//         if (count($prefixParts) < 1 || !is_numeric($prefixParts[0])) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Invalid prefix format. The prefix number must be numeric.',
//             ], 422);
//         }

//         $prefixNumber = (int) $prefixParts[0];
//         $lastWRR = WRRForm::latest('id')->first();
//         $nextNumber = $prefixNumber;

//         if ($lastWRR) {
//             preg_match('/^(\d{5})/', $lastWRR->Ref, $matches);
//             $lastNumber = (int) ($matches[1] ?? 0);
//             $nextNumber = max($lastNumber + 1, $prefixNumber);
//         }

//         $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
//         $refNumber = "$formattedNumber/" . implode('/', array_slice($prefixParts, 1));

//         // Process each material to include details from the Material model
//         $processedMaterials = array_map(function ($material) {
//             $materialData = Material::find($material['material_id']);
//             return array_merge($material, [
//                 'material_description' => $materialData->description ?? $material['material_description'],
//                 'material_type' => $materialData->material_type ?? $material['material_type'],
//                 'code' => $materialData->code ?? null,
//             ]);
//         }, $validatedData['materials']);

//         $encodedMaterials = json_encode($processedMaterials);

//         $wrr = WRRForm::create([
//             'Ref' => $refNumber,
//             'dn_number' => $validatedData['dn_number'],
//             'po_number' => $validatedData['po_number'],
//             'supplier_id' => $validatedData['supplier_id'] ?? null,
//             'supplier_name' => $validatedData['supplier_name'],
//             'material_description' => $encodedMaterials,
//             'date' => $validatedData['date'],
//         ]);

//         return response()->json([
//             'status' => true,
//             'message' => 'Warehouse Receiving Report Form Created Successfully',
//             'data' => [
//                 'id' => $wrr->id,
//                 'Ref' => $wrr->Ref,
//                 'dn_number' => $wrr->dn_number,
//                 'po_number' => $wrr->po_number,
//                 'supplier_id' => $wrr->supplier_id,
//                 'supplier_name' => $wrr->supplier_name,
//                 'date' => $wrr->date,
//                 'materials' => $processedMaterials, 
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
        $customMessages = [
            'materials.*.material_id.required' => 'The material ID field is required.',
            'materials.*.material_id.integer' => 'The material ID must be a valid integer.',
            'materials.*.material_id.exists' => 'The selected material ID does not exist in the materials list.',
            'materials.*.dn_quantity.required' => 'The DN quantity field is required.',
            'materials.*.received_quantity.required' => 'The
            received quantity field is required.',
        ];

        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:100',
            'dn_number' => 'required|string|max:8|unique:wrr_forms,dn_number',
            'po_number' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:providers,id',
            'supplier_name' => 'required|string|max:255',
            'materials' => 'required|array',
            'materials.*.material_id' => 'required|integer|exists:materials,id',
            'materials.*.material_description' => 'nullable|string|max:500',
            'materials.*.dn_quantity' => 'required|string|min:0',
            'materials.*.received_quantity' => 'required|string|min:0',
            'date' => 'required|date',
        ], $customMessages);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        // Verify if supplier exists
        $supplier = Provider::where('first_name', $request->supplier_name)->first();
        if (!$supplier) {
            return response()->json([
                'status' => false,
                'message' => 'Supplier not found in the database.',
            ], 404);
        }

        // Default prefix if none provided
        $defaultPrefix = '00001/WRR/Warehouse/HPM/XI/2024';
        $prefix = $validatedData['prefix'] ?? $defaultPrefix;

        // Split the prefix to extract the number
        $prefixParts = explode('/', $prefix);
        if (count($prefixParts) < 1 || !is_numeric($prefixParts[0])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid prefix format. The prefix number must be numeric.',
            ], 422);
        }

        $prefixNumber = (int) $prefixParts[0];
        $lastWRR = WRRForm::latest('id')->first();
        $nextNumber = $prefixNumber;

        if ($lastWRR) {
            preg_match('/^(\d{5})/', $lastWRR->Ref, $matches);
            $lastNumber = (int) ($matches[1] ?? 0);
            $nextNumber = max($lastNumber + 1, $prefixNumber);
        }

        $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        $refNumber = "$formattedNumber/" . implode('/', array_slice($prefixParts, 1));

        // Process each material to include details from the Material model
        $processedMaterials = array_map(function ($material) {
            $materialData = Material::find($material['material_id']);
            return array_merge($material, [
                'material_description' => $materialData->description ?? $material['material_description'],
                'material_type' => $materialData->material_type ?? $material['material_type'],
                'code' => $materialData->code ?? null,
            ]);
        }, $validatedData['materials']);

        $encodedMaterials = json_encode($processedMaterials);

        // Create WRR Form
        $wrr = WRRForm::create([
            'Ref' => $refNumber,
            'dn_number' => $validatedData['dn_number'],
            'po_number' => $validatedData['po_number'],
            'supplier_id' => $validatedData['supplier_id'] ?? null,
            'supplier_name' => $validatedData['supplier_name'],
            'material_description' => $encodedMaterials,
            'date' => $validatedData['date'],
        ]);

        // Include company name in the response
        $companyName = $supplier->company_name ?? null;
        $Ref2 = $supplier->Ref ?? null;

        return response()->json([
            'status' => true,
            'message' => 'Warehouse Receiving Report Form Created Successfully',
            'data' => [
                'id' => $wrr->id,
                'Ref' => $wrr->Ref,
                'dn_number' => $wrr->dn_number,
                'po_number' => $wrr->po_number,
                'supplier_id' => $wrr->supplier_id,
                'supplier_name' => $wrr->supplier_name,
                'company_name' => $companyName,
                'Ref2' => $Ref2,
                'date' => $wrr->date,
                'materials' => $processedMaterials,
            ],
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}






    
    
    
    
    
    
    //------------------------------------------------Update WRR Form---------------------------------------//
   public function update(Request $request, $id)
{
    try {
        $wrr = WRRForm::findOrFail($id);

        $customMessages = [
            'materials.*.material_id.required' => 'The material ID field is required.',
            'materials.*.material_id.integer' => 'The material ID must be a valid integer.',
            'materials.*.material_id.exists' => 'The selected material ID does not exist in the materials list.',
            'materials.*.dn_quantity.required' => 'The DN quantity field is required.',
            'materials.*.received_quantity.required' => 'The received quantity field is required.',
        ];

        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:100',
            'dn_number' => 'nullable|string|max:8|unique:wrr_forms,dn_number,' . $wrr->id,
            'po_number' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:providers,id',
            'supplier_name' => 'nullable|string|max:255',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required|integer|exists:materials,id',
            'materials.*.material_description' => 'nullable|string|max:500',
            'materials.*.dn_quantity' => 'required|numeric|min:0',
            'materials.*.received_quantity' => 'required|numeric|min:0',
            'date' => 'nullable|date',
        ], $customMessages);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        // Handle supplier lookup if supplier_name is provided
        if (isset($validatedData['supplier_name'])) {
            $supplier = Provider::where('first_name', $validatedData['supplier_name'])->first();
            if (!$supplier) {
                return response()->json([
                    'status' => false,
                    'message' => 'Supplier not found in the database.',
                ], 404);
            }
            $validatedData['supplier_id'] = $supplier->id;
        }

        // Process materials if provided
        if (isset($validatedData['materials'])) {
            $processedMaterials = array_map(function ($material) {
                $materialData = Material::find($material['material_id']);
                return array_merge($material, [
                    'material_description' => $materialData->description ?? $material['material_description'],
                    'material_type' => $materialData->material_type ?? null,
                    'code' => $materialData->code ?? null,
                ]);
            }, $validatedData['materials']);

            $encodedMaterials = json_encode($processedMaterials);
        }

        // Update WRR Form
        $wrr->update([
            'dn_number' => $validatedData['dn_number'] ?? $wrr->dn_number,
            'po_number' => $validatedData['po_number'] ?? $wrr->po_number,
            'supplier_id' => $validatedData['supplier_id'] ?? $wrr->supplier_id,
            'supplier_name' => $validatedData['supplier_name'] ?? $wrr->supplier_name,
            'material_description' => $encodedMaterials ?? $wrr->material_description,
            'date' => $validatedData['date'] ?? $wrr->date,
        ]);

        // Include company name and Ref2 in the response
        $companyName = $wrr->provider ? $wrr->provider->company_name : null;
        $Ref2 = $wrr->provider ? $wrr->provider->Ref : null;

        return response()->json([
            'status' => true,
            'message' => 'Warehouse Receiving Report Form Updated Successfully',
            'data' => [
                'id' => $wrr->id,
                'Ref' => $wrr->Ref,
                'dn_number' => $wrr->dn_number,
                'po_number' => $wrr->po_number,
                'supplier_id' => $wrr->supplier_id,
                'supplier_name' => $wrr->supplier_name,
                'company_name' => $companyName,
                'Ref2' => $Ref2,
                'date' => $wrr->date,
                'materials' => isset($processedMaterials) ? $processedMaterials : json_decode($wrr->material_description, true),
            ],
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'WRR Form not found.',
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}






    
    //-------------------------------------------------------Show All WRR Forms--------------------------------------------//
  public function showAll(Request $request)
{
    try {
        // Check if the user is authenticated
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Pagination parameters
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Start query for WRRForm
        $wrrFormQuery = WRRForm::with('provider'); // Assuming you have a relationship defined

        // Search by dn_number or supplier_name
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            
            $wrrFormQuery->where(function ($query) use ($search) {
                $query->where('dn_number', 'like', '%' . $search . '%')
                    ->orWhere('Ref', 'like', '%' . $search . '%')
                    ->orWhere('date', 'like', '%' . $search . '%')
                    ->orWhere('supplier_name', 'like', '%' . $search . '%');
            });
        }
        
        $wrrFormQuery->orderBy('created_at', 'desc');

        // Paginate results
        $wrrForms = $wrrFormQuery->paginate($perPage, ['*'], 'page', $page);

        // Check if any WRR forms are found
        if ($wrrForms->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No WRR Forms found',
                'wrrForms' => [],
            ], 404);
        }

        // Format the response to include company_name, Ref2, and decoded materials
        $formattedWRRForms = $wrrForms->map(function ($wrrForm) {
            return [
                'id' => $wrrForm->id,
                'Ref' => $wrrForm->Ref,
                'dn_number' => $wrrForm->dn_number,
                'po_number' => $wrrForm->po_number,
                'supplier_id' => $wrrForm->supplier_id,
                'supplier_name' => $wrrForm->supplier_name,
                'company_name' => $wrrForm->provider->company_name ?? null,
                'Ref2' => $wrrForm->provider->Ref ?? null,
                'date' => $wrrForm->date,
                'materials' => json_decode($wrrForm->material_description, true),  
            ];
        });

        // Return successful response with paginated data
        return response()->json([
            'success' => true,
            'message' => 'WRR Forms Retrieved Successfully',
            'wrrForms' => [
                'current_page' => $wrrForms->currentPage(),
                'data' => $formattedWRRForms,
                'first_page_url' => $wrrForms->url(1),
                'from' => $wrrForms->firstItem(),
                'last_page' => $wrrForms->lastPage(),
                'last_page_url' => $wrrForms->url($wrrForms->lastPage()),
                'next_page_url' => $wrrForms->nextPageUrl(),
                'path' => $wrrForms->path(),
                'per_page' => $wrrForms->perPage(),
                'prev_page_url' => $wrrForms->previousPageUrl(),
                'to' => $wrrForms->lastItem(),
                'total' => $wrrForms->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}






    
    
    
    

    
    
    
    //----------------------------------------------------------Show WRR Forms By ID---------------------------------------------//

public function showByID($id)
{
    try {
       
        $wrr = WRRForm::with('provider')->find($id);

        if (!$wrr) {
            return response()->json([
                'status' => false,
                'message' => 'WRR Form not found.',
            ], 404);
        }

        $decodedMaterials = json_decode($wrr->material_description, true);

        // Log the WRRForm and provider details to verify
        Log::info('WRRForm Details:', ['wrr' => $wrr]);
        Log::info('Provider Details:', ['provider' => $wrr->provider]);

        return response()->json([
            'status' => true,
            'message' => 'WRR Form Retrieved Successfully',
            'wrr' => [
                'id' => $wrr->id,
                'Ref' => $wrr->Ref,
                'dn_number' => $wrr->dn_number,
                'po_number' => $wrr->po_number,
                'supplier_id' => $wrr->supplier_id,
                'supplier_name' => $wrr->supplier_name,
                'company_name' => $wrr->provider->company_name ?? null,
                'Ref2' => $wrr->provider->Ref ?? null,
                'date' => $wrr->date,
                'materials' => $decodedMaterials ?? [], 
            ]
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}







//-----------------------------------------------------Delete WRR Form----------------------------------------------------//
public function delete($id)
    {
        try{
        $wrr = WRRForm::find($id);

        if (!$wrr) {
            return response()->json([
                'status' => false,
                'message' => 'WRR Form not found.',
            ], 404);
        }

        $wrr->delete();

        return response()->json([
            'status' => true,
            'message' => 'Warehouse Receiving Report Form Deleted Successfully',
        ], 200);
    }catch(\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            ], 422);
    }
}


  
}
