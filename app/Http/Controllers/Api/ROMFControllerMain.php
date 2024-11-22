<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReturnMaterialForm;
use App\Models\Material;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use Illuminate\Support\Facades\Auth;

class ROMFcontrollerMain extends Controller
{
    //----------------------------------------------- Create Return of Material Form ------------------------------------------------//
    
    
//   public function store(Request $request)
// {
//     try {
         
//         $validator = Validator::make($request->all(), [
//             'prefix' => 'nullable|string|max:10',
//             'date' => 'required|date',
//             'shift' => 'required|string|max:255',
//             'operator_name' => 'required|string|max:255',
//             'materials' => 'required|array',   
//             'materials.*.mo_no' => 'required|string|max:255',
//             'materials.*.material_id' => 'required|string|max:255',  
//             'materials.*.material_description' => 'nullable|string|max:500',
//             'materials.*.quantity' => 'required|numeric|min:0',
//             'materials.*.uom' => 'required|string|max:50',
//             'materials.*.remarks' => 'nullable|string|max:500',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Validation failed',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $validatedData = $validator->validated();

//         // Check for existing ROMF forms based on the material_id and mo_no
//         foreach ($validatedData['materials'] as $material) {
//             $existingRomf = ReturnMaterialForm::where('material_id', $material['material_id'])
//                 ->where('mo_no', $material['mo_no'])
//                 ->first();

//             if ($existingRomf) {
//                 return response()->json([
//                     'status' => false,
//                     'message' => 'ROMF Form already exists for material ID ' . $material['material_id'] . ' and MO number ' . $material['mo_no'],
//                 ], 422);
//             }
//         }

//         // Process materials to include material_description and code
//         $processedMaterials = array_map(function ($material) {
//             // Assuming Material model has a relationship to fetch description and code
//             $materialData = Material::find($material['material_id']);

//             return array_merge($material, [
//                 'material_description' => $materialData->material_type ?? null,   
//                 'code' => $materialData->code ?? null, 
//             ]);
//         }, $validatedData['materials']);
 
//         $prefix = $validatedData['prefix'] ?? 'ROM';
//         $refNumber = CodeGeneratorHelper::generateCode('return_material_form', $prefix);
 
//         $encodedMaterials = json_encode($processedMaterials);
 
//         $rom = ReturnMaterialForm::create([
//             'Ref' => $refNumber,
//             'date' => $validatedData['date'],
//             'shift' => $validatedData['shift'],
//             'operator_name' => $validatedData['operator_name'],
//             'mo_no' => $encodedMaterials,  
//         ]);

//         return response()->json([
//             'status' => true,
//             'message' => 'Return of Material Form Created Successfully',
//             'data' => [
//                 'id' => $rom->id,
//                 'Ref' => $rom->Ref,
//                 'date' => $rom->date,
//                 'shift' => $rom->shift,
//                 'operator_name' => $rom->operator_name,
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
        // Validate the input data
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:50',
            'date' => 'required|date',
            'shift' => 'required|string|max:255',
            'operator_name' => 'required|string|max:255',
            'materials' => 'required|array',
            'materials.*.mo_no' => 'required|string|max:255',
            'materials.*.material_id' => 'required|integer|exists:materials,id',
            'materials.*.material_description' => 'nullable|string|max:500',
            'materials.*.quantity' => 'required|numeric|min:0',
            'materials.*.uom' => 'required|string|max:50',
            'materials.*.remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        // Check for existing ROMF forms based on the material_id and mo_no
        foreach ($validatedData['materials'] as $material) {
            $existingRomf = ReturnMaterialForm::where('material_id', $material['material_id'])
                ->where('mo_no', $material['mo_no'])
                ->first();

            if ($existingRomf) {
                return response()->json([
                    'status' => false,
                    'message' => 'ROMF Form already exists for material ID ' . $material['material_id'] . ' and MO number ' . $material['mo_no'],
                ], 422);
            }
        }

        // Process materials to include material details (description and code)
        $processedMaterials = array_map(function ($material) {
            $materialData = Material::find($material['material_id']);

            return array_merge($material, [
                'material_description' => $materialData->material_type ?? null,
                'code' => $materialData->code ?? null,
                'description' => $materialData->material_description ?? null,
            ]);
        }, $validatedData['materials']);

        // Handle the prefix
        $prefix = $validatedData['prefix'] ?? '';
        $lastRomf = ReturnMaterialForm::latest('id')->first();

        if (empty($prefix)) {
            if ($lastRomf) {
                $prefix = $lastRomf->Ref; // Use the last reference as the base prefix
            } else {
                $prefix = '00001/ROMF/PROD/HPM/XI/2024'; // Default prefix for the first record
            }
        }

        // Extract the numeric part of the prefix
        $prefixParts = explode('/', $prefix);
        if (count($prefixParts) < 1 || !is_numeric($prefixParts[0])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid prefix format. The prefix number must be numeric.',
            ], 422);
        }

        $prefixNumber = (int) $prefixParts[0];
        $nextNumber = $prefixNumber;

        if ($lastRomf) {
            preg_match('/^(\d{5})/', $lastRomf->Ref, $matches);
            $lastNumber = (int) ($matches[1] ?? 0);
            $nextNumber = max($lastNumber + 1, $prefixNumber);
        }

        // Create the new reference number by incrementing the numeric part
        $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        $refNumber = "$formattedNumber/" . implode('/', array_slice($prefixParts, 1));

        // Ensure "PROD" is included in the prefix
        if (!in_array('PROD', $prefixParts)) {
            $prefixParts = array_slice($prefixParts, 0, 2) + ['PROD'] + array_slice($prefixParts, 2);
            $refNumber = "$formattedNumber/" . implode('/', $prefixParts);
        }

        // Encode the processed materials to JSON for storage
        $encodedMaterials = json_encode($processedMaterials);

        // Create the Return Material Form record
        $rom = ReturnMaterialForm::create([
            'Ref' => $refNumber,
            'date' => $validatedData['date'],
            'shift' => $validatedData['shift'],
            'operator_name' => $validatedData['operator_name'],
            'mo_no' => $encodedMaterials,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Return of Material Form Created Successfully',
            'data' => [
                'id' => $rom->id,
                'Ref' => $rom->Ref,
                'date' => $rom->date,
                'shift' => $rom->shift,
                'operator_name' => $rom->operator_name,
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












    //----------------------------------------------- Update Return of Material Form ------------------------------------------------//
  public function update(Request $request, $id)
{
    try {
        // Find the existing ReturnMaterialForm record by ID
        $returnMaterialForm = ReturnMaterialForm::find($id);

        if (!$returnMaterialForm) {
            return response()->json([
                'status' => false,
                'message' => 'Return Material Form not found.',
            ], 404);
        }

        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:50',
            'date' => 'nullable|date',
            'shift' => 'nullable|string|max:255',
            'operator_name' => 'nullable|string|max:255',
            'materials' => 'nullable|array',
            'materials.*.mo_no' => 'required|string|max:255',
            'materials.*.material_id' => 'required|integer|exists:materials,id',
            'materials.*.quantity' => 'required|numeric|min:0',
            'materials.*.uom' => 'required|string|max:50',
            'materials.*.remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        // Check for duplicate materials if new materials data is provided
        if (isset($validatedData['materials'])) {
            foreach ($validatedData['materials'] as $material) {
                $existingRomf = ReturnMaterialForm::where('material_id', $material['material_id'])
                    ->where('mo_no', $material['mo_no'])
                    ->where('id', '!=', $id) // Exclude the current ROMF being updated
                    ->first();

                if ($existingRomf) {
                    return response()->json([
                        'status' => false,
                        'message' => 'ROMF Form already exists for material ID ' . $material['material_id'] . ' and MO number ' . $material['mo_no'],
                    ], 422);
                }
            }

            // Process materials to add material_description and code
            $processedMaterials = array_map(function ($material) {
                $materialData = Material::find($material['material_id']);

                return array_merge($material, [
                    'material_description' => $materialData->material_type ?? null,
                    'code' => $materialData->code ?? null,
                    'description' => $materialData->material_description ?? null,
                ]);
            }, $validatedData['materials']);

            // Encode processed materials
            $encodedMaterials = json_encode($processedMaterials);
            $returnMaterialForm->mo_no = $encodedMaterials;
        }

        // Handle the prefix
        $prefix = $validatedData['prefix'] ?? $returnMaterialForm->Ref; // Fallback to the existing Ref
        if (!empty($prefix)) {
            $prefixParts = explode('/', $prefix);
            if (count($prefixParts) < 1 || !is_numeric($prefixParts[0])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid prefix format. The prefix number must be numeric.',
                ], 422);
            }

            $prefixNumber = (int)$prefixParts[0];
            preg_match('/^(\d{5})/', $returnMaterialForm->Ref, $matches);
            $currentNumber = (int)($matches[1] ?? 0);
            $nextNumber = max($currentNumber, $prefixNumber);

            $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            $refNumber = "$formattedNumber/" . implode('/', array_slice($prefixParts, 1));
            $returnMaterialForm->Ref = $refNumber;
        }

        // Update other fields
        $returnMaterialForm->date = $validatedData['date'] ?? $returnMaterialForm->date;
        $returnMaterialForm->shift = $validatedData['shift'] ?? $returnMaterialForm->shift;
        $returnMaterialForm->operator_name = $validatedData['operator_name'] ?? $returnMaterialForm->operator_name;

        // Save the updated data
        $returnMaterialForm->save();

        return response()->json([
            'status' => true,
            'message' => 'Return Material Form Updated Successfully',
            'data' => [
                'id' => $returnMaterialForm->id,
                'Ref' => $returnMaterialForm->Ref,
                'date' => $returnMaterialForm->date,
                'shift' => $returnMaterialForm->shift,
                'operator_name' => $returnMaterialForm->operator_name,
                'materials' => json_decode($returnMaterialForm->mo_no),
            ],
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Return Material Form not found.',
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}









    //-------------------------------------------- Show All Return of Material Forms ------------------------------------------------//
    public function showAll(Request $request)
{
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

    // Start query for ReturnMaterialForm
    $romFormsQuery = ReturnMaterialForm::query();

    // Search for return material forms by multiple fields
    if ($request->has('search') && !empty($request->input('search'))) {
        $search = $request->input('search');
        $romFormsQuery->where(function ($query) use ($search) {
            $query->where('Ref', 'like', '%' . $search . '%')
                  ->orWhere('operator_name', 'like', '%' . $search . '%')
                  ->orWhere('shift', 'like', '%' . $search . '%')
                  ->orWhere('date', 'like', '%' . $search . '%');
        });
    }

    $romFormsQuery->orderBy('created_at', 'desc');

    // Paginate the results
    $romForms = $romFormsQuery->paginate($perPage, ['*'], 'page', $page);

    // Check if any return material forms are found
    if ($romForms->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No Return of Material Forms found',
            'romForms' => [],
        ], 404);
    }

    // Transform each ReturnMaterialForm to include formatted materials data
    $romForms->getCollection()->transform(function ($form) {
        $form->materials = json_decode($form->mo_no, true);

        if (is_array($form->materials)) {
            $form->materials = array_map(function ($material) {
                $materialModel = Material::find($material['material_id']);
                return array_merge($material, [
                    'material_description' => $materialModel->material_type ?? null,
                    'code' => $materialModel->code ?? null,
                ]);
            }, $form->materials);
        }

        // Remove unnecessary fields for cleaner output
        unset($form->mo_no, $form->material_id, $form->quantity, $form->uom, $form->remarks, 
              $form->received_quantity, $form->created_at, $form->updated_at, $form->deleted_at);

        return $form;
    });

    // Return successful response with paginated data
    return response()->json([
        'status' => true,
        'message' => 'All Return of Material Forms Retrieved Successfully',
        'data' => [
            'current_page' => $romForms->currentPage(),
            'data' => $romForms->items(),
            'first_page_url' => $romForms->url(1),
            'from' => $romForms->firstItem(),
            'last_page' => $romForms->lastPage(),
            'last_page_url' => $romForms->url($romForms->lastPage()),
            'next_page_url' => $romForms->nextPageUrl(),
            'path' => $romForms->path(),
            'per_page' => $romForms->perPage(),
            'prev_page_url' => $romForms->previousPageUrl(),
            'to' => $romForms->lastItem(),
            'total' => $romForms->total(),
        ]
    ], 200);
}



    
    
    
    
    
    
    

    //----------------------------------------- Show Return of Material Form By ID ------------------------------------------------//
   public function showByID($id)
{
    try {
        // Find the ReturnMaterialForm by ID
        $rom = ReturnMaterialForm::find($id);

        if (!$rom) {
            return response()->json([
                'status' => false,
                'message' => 'Return of Material Form not found.',
            ], 404);
        }

        // Decode the materials stored in the mo_no field if it's JSON
        if ($rom->mo_no) {
            $rom->materials = array_map(function ($material) {
                $materialModel = Material::find($material['material_id']);
                return array_merge($material, [
                    'material_description' => $materialModel->material_type ?? null,
                    'code' => $materialModel->code ?? null,
                ]);
            }, json_decode($rom->mo_no, true));
        } else {
            $rom->materials = [];
        }

        // Remove unnecessary fields for cleaner output
        unset($rom->mo_no, $rom->material_id, $rom->quantity, $rom->uom, $rom->remarks, 
              $rom->received_quantity, $rom->created_at, $rom->updated_at, $rom->deleted_at);

        return response()->json([
            'success' => true,
            'message' => 'Return of Material Form Retrieved Successfully',
            'data' => $rom,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}






    //-------------------------------------------- Delete Return of Material Form ------------------------------------------------//
    public function delete($id)
{
    \Log::info("Deleting ReturnOfMaterialForm with ID: " . $id);

    $rom = ReturnMaterialForm::find($id);

    if (!$rom) {
        return response()->json([
            'status' => false,
            'message' => 'Return of Material Form not found.',
        ], 404);
    }

    try {
        $rom->delete(); // or use `->forceDelete()` if you want to permanently delete

        return response()->json([
            'status' => true,
            'message' => 'Return of Material Form Deleted Successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}

}
