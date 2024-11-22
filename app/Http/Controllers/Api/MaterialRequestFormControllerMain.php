<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaterialRequestForm;
use App\Models\Material;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeGeneratorHelper;
use Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MaterialRequestFormControllerMain extends Controller
{
//--------------------------------------------------Create Material Request Form-------------------------------------------//


//   public function store(Request $request)
// {
//     try {
        
//         $validator = Validator::make($request->all(), [
//             'prefix' => 'nullable|string|max:100',   
//             'date' => 'required|date',
//             'shift' => 'required|string|max:255',
//             'operator_name' => 'required|string|max:255',
//             'materials' => 'required|array',
//             'materials.*.mo_no' => 'required|string|max:255',
//             'materials.*.material_id' => 'required|integer|exists:materials,id',
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

//         // Process materials to include material_description and code
//         $processedMaterials = array_map(function ($material) {
//             $materialData = Material::find($material['material_id']);

//             return array_merge($material, [
//                 'material_description' => $materialData->material_type ?? null,
//                 'code' => $materialData->code ?? null,
//             ]);
//         }, $validatedData['materials']);

//         // If no prefix is provided, generate a default one
//         if (empty($validatedData['prefix'])) {
//             // If prefix is not provided, use a default pattern like `00001/MRF/Warehouse/HPM/VII/2024`
//             $prefix = '00001/MRF/Warehouse/HPM/XI/2024';
//         } else {
//             // Use the provided prefix
//             $prefix = $validatedData['prefix'];
//         }

//         // Extract the prefix number from the provided prefix
//         $prefixParts = explode('/', $prefix);
//         $prefixNumber = $prefixParts[0];  // The first part of the prefix

//         // Check if the prefix number is valid (contains digits)
//         if (!is_numeric($prefixNumber)) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Invalid prefix format. The prefix number must be numeric.',
//             ], 422);
//         }

//         // Get the last reference number from the database
//         $lastMRF = MaterialRequestForm::latest('id')->first();

//         // Determine the next number based on the prefix
//         if ($lastMRF) {
//             // Extract the number from the last Ref
//             preg_match('/^(\d{5})/', $lastMRF->Ref, $matches);
//             $lastNumber = (int) $matches[1];

//             // If a higher number is provided in the request, use that instead
//             $requestedNumber = (int)$prefixNumber;
//             if ($requestedNumber > $lastNumber) {
//                 $nextNumber = $requestedNumber;
//             } else {
//                 // Increment the number if the requested number is not higher
//                 $nextNumber = $lastNumber + 1;
//             }
//         } else {
//             // If no previous records exist, start with the prefix number or default number
//             $nextNumber = (int)$prefixNumber;
//         }

//         // Format the next number with leading zeros
//         $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

//         // Construct the final Ref with the incremented number
//         $refNumber = "$formattedNumber/" . implode('/', array_slice($prefixParts, 1));

//         // Store the material request form
//         $materialRequest = MaterialRequestForm::create([
//             'Ref' => $refNumber,
//             'date' => $validatedData['date'],
//             'shift' => $validatedData['shift'],
//             'operator_name' => $validatedData['operator_name'],
//             'mo_no' => json_encode($processedMaterials),  
//         ]);

//         return response()->json([
//             'status' => true,
//             'message' => 'Material Request Form Created Successfully',
//             'data' => [
//                 'id' => $materialRequest->id,
//                 'Ref' => $materialRequest->Ref,
//                 'date' => $materialRequest->date,
//                 'shift' => $materialRequest->shift,
//                 'operator_name' => $materialRequest->operator_name,
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
        // Custom error messages
        $messages = [
            'materials.*.material_id.required' => ' field is required.',
            'materials.*.uom.required' => ' field is required.',
            // 'materials.*.mo_no.required' => 'The mo number field is required.',
            // 'materials.*.quantity.required' => 'The quantity field is required.',
            // 'materials.*.uom.max' => 'The uom field may not be greater than 50 characters.',
            // 'materials.*.remarks.max' => 'The remarks may not be greater than 500 characters.',
        ];

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:100',  // Prefix is now optional
            'date' => 'required|date',
            'shift' => 'required|string|max:255',
            'operator_name' => 'required|string|max:255',
            'materials' => 'required|array',
            'materials.*.mo_no' => 'required|string|max:255',
            'materials.*.material_id' => 'required|integer|exists:materials,id',
            'materials.*.quantity' => 'required|numeric|min:0',
            'materials.*.uom' => 'required|string|max:50',
            'materials.*.remarks' => 'nullable|string|max:500',
        ], $messages);
        
        if ($validator->fails()) {
            // Remove array indices from the error messages
            $errors = collect($validator->errors()->getMessages())->mapWithKeys(function ($messages, $key) {
                // Remove the array index from the error key (e.g., "materials.0.uom" becomes "materials.uom")
                $cleanKey = preg_replace('/\.\d+\./', '.', $key);

                return [$cleanKey => $messages];
            });

            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        // Process materials to include material_description and code
        $processedMaterials = array_map(function ($material) {
            $materialData = Material::find($material['material_id']);

            return array_merge($material, [
                'material_description' => $materialData->material_type ?? null,
                'code' => $materialData->code ?? null,
                'description' => $materialData->material_description ?? null,
            ]);
        }, $validatedData['materials']);

        // Function to convert month number to Roman numeral
        function monthToRoman($month)
        {
            $map = [
                1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
                5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
                9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
            ];
            return $map[$month];
        }

        // Get the current month and year
        $currentMonth = (int)date('n');
        $currentYear = date('Y');
        $romanMonth = monthToRoman($currentMonth);

        // If no prefix is provided, generate a default one
        if (empty($validatedData['prefix'])) {
            // Default prefix pattern
            $prefix = "00001/MRF/Warehouse/HPM/$romanMonth/$currentYear";
        } else {
            // Use the provided prefix
            $prefix = $validatedData['prefix'];
        }

        // Extract the prefix number from the provided prefix
        $prefixParts = explode('/', $prefix);
        $prefixNumber = $prefixParts[0];  // The first part of the prefix

        // Check if the prefix number is valid (contains digits)
        if (!is_numeric($prefixNumber)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid prefix format. The prefix number must be numeric.',
            ], 422);
        }

        // Get the last reference number from the database
        $lastMRF = MaterialRequestForm::latest('id')->first();

        // Determine the next number based on the prefix
        if ($lastMRF) {
            // Extract the number from the last Ref
            preg_match('/^(\d{5})/', $lastMRF->Ref, $matches);
            $lastNumber = (int)$matches[1];

            // If a higher number is provided in the request, use that instead
            $requestedNumber = (int)$prefixNumber;
            if ($requestedNumber > $lastNumber) {
                $nextNumber = $requestedNumber;
            } else {
                // Increment the number if the requested number is not higher
                $nextNumber = $lastNumber + 1;
            }
        } else {
            // If no previous records exist, start with the prefix number or default number
            $nextNumber = (int)$prefixNumber;
        }

        // Format the next number with leading zeros
        $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // Construct the final Ref with the incremented number and dynamic month/year
        $refNumber = "$formattedNumber/MRF/Warehouse/HPM/$romanMonth/$currentYear";

        // Store the material request form
        $materialRequest = MaterialRequestForm::create([
            'Ref' => $refNumber,
            'date' => $validatedData['date'],
            'shift' => $validatedData['shift'],
            'operator_name' => $validatedData['operator_name'],
            'mo_no' => json_encode($processedMaterials),  // Store materials with additional data
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Material Request Form Created Successfully',
            'data' => [
                'id' => $materialRequest->id,
                'Ref' => $materialRequest->Ref,
                'date' => $materialRequest->date,
                'shift' => $materialRequest->shift,
                'operator_name' => $materialRequest->operator_name,
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

// public function store(Request $request)
// {
//     try {
//         // Define custom error messages
//          $customMessages = [
//             'materials.*.material_id.required' => 'The material ID field is required for each material.',
//             'materials.*.uom.required' => 'The UOM field is required for each material.',
//         ];

//         // Validate the incoming request with custom messages
//         $validator = Validator::make($request->all(), [
//             'prefix' => 'nullable|string|max:100',
//             'date' => 'required|date',
//             'shift' => 'required|string|max:255',
//             'operator_name' => 'required|string|max:255',
//             'materials' => 'required|array',
//             'materials.*.mo_no' => 'required|string|max:255',
//             'materials.*.material_id' => 'required|integer|exists:materials,id',
//             'materials.*.quantity' => 'required|numeric|min:0',
//             'materials.*.uom' => 'required|string|max:50',
//             'materials.*.remarks' => 'nullable|string|max:500',
//         ], $customMessages);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Validation failed',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $validatedData = $validator->validated();

//         // Process materials to include material_description and code
//         $processedMaterials = array_map(function ($material) {
//             $materialData = Material::find($material['material_id']);

//             return array_merge($material, [
//                 'material_description' => $materialData->material_type ?? null,
//                 'code' => $materialData->code ?? null,
//             ]);
//         }, $validatedData['materials']);

//         // Function to convert month number to Roman numeral
//         function monthToRoman($month)
//         {
//             $map = [
//                 1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
//                 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
//                 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
//             ];
//             return $map[$month];
//         }

//         // Get the current month and year
//         $currentMonth = (int)date('n');
//         $currentYear = date('Y');
//         $romanMonth = monthToRoman($currentMonth);

//         // Generate default prefix if none is provided
//         if (empty($validatedData['prefix'])) {
//             $prefix = "00001/MRF/Warehouse/HPM/$romanMonth/$currentYear";
//         } else {
//             $prefix = $validatedData['prefix'];
//         }

//         $prefixParts = explode('/', $prefix);
//         $prefixNumber = $prefixParts[0];

//         if (!is_numeric($prefixNumber)) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Invalid prefix format. The prefix number must be numeric.',
//             ], 422);
//         }

//         $lastMRF = MaterialRequestForm::latest('id')->first();

//         if ($lastMRF) {
//             preg_match('/^(\d{5})/', $lastMRF->Ref, $matches);
//             $lastNumber = (int)$matches[1];
//             $requestedNumber = (int)$prefixNumber;
//             $nextNumber = $requestedNumber > $lastNumber ? $requestedNumber : $lastNumber + 1;
//         } else {
//             $nextNumber = (int)$prefixNumber;
//         }

//         $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
//         $refNumber = "$formattedNumber/MRF/Warehouse/HPM/$romanMonth/$currentYear";

//         $materialRequest = MaterialRequestForm::create([
//             'Ref' => $refNumber,
//             'date' => $validatedData['date'],
//             'shift' => $validatedData['shift'],
//             'operator_name' => $validatedData['operator_name'],
//             'mo_no' => json_encode($processedMaterials),
//         ]);

//         return response()->json([
//             'status' => true,
//             'message' => 'Material Request Form Created Successfully',
//             'data' => [
//                 'id' => $materialRequest->id,
//                 'Ref' => $materialRequest->Ref,
//                 'date' => $materialRequest->date,
//                 'shift' => $materialRequest->shift,
//                 'operator_name' => $materialRequest->operator_name,
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











    
    
    
    //-------------------------------------------------------Update Material Request Form-----------------------------------------//
 


   public function update(Request $request, $id)
{
    try {
        $materialRequest = MaterialRequestForm::find($id);

        if (!$materialRequest) {
            return response()->json([
                'status' => false,
                'message' => 'Material Request Form not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:50',
            'date' => 'nullable|date',
            'shift' => 'nullable|string|max:255',
            'operator_name' => 'nullable|string|max:255',
            'materials' => 'nullable|array', // Array of materials
            'materials.*.mo_no' => 'nullable|string|max:255',
            'materials.*.material_id' => 'nullable|integer|exists:materials,id',
            'materials.*.quantity' => 'nullable|numeric|min:0',
            'materials.*.uom' => 'nullable|string|max:50',
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

        // Process materials to include material_description and code
        if (isset($validatedData['materials'])) {
            $processedMaterials = array_map(function ($material) {
                $materialData = Material::find($material['material_id']);

                return array_merge($material, [
                    'material_description' => $materialData->material_type ?? null,
                    'code' => $materialData->code ?? null,
                    'description' => $materialData->material_description ?? null,
                ]);
            }, $validatedData['materials']);

            $validatedData['mo_no'] = json_encode($processedMaterials);
        }

        // Generate the reference number if required fields are present
        if ($request->hasAny(['prefix', 'date', 'shift', 'materials'])) {
            $prefix = $validatedData['prefix'] ?? 'MRF';
            $currentYear = now()->format('Y');
            $warehouseCode = 'Warehouse';
            $plantCode = 'HPM';
            $shiftCode = 'XI';

            $refNumber = str_pad($materialRequest->id, 5, '0', STR_PAD_LEFT) . "/$prefix/$warehouseCode/$plantCode/$shiftCode/$currentYear";
            $validatedData['Ref'] = $refNumber;
        }

        // Update the Material Request Form
        $materialRequest->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Material Request Form Updated Successfully',
            'data' => [
                'id' => $materialRequest->id,
                'Ref' => $materialRequest->Ref,
                'date' => $materialRequest->date,
                'shift' => $materialRequest->shift,
                'operator_name' => $materialRequest->operator_name,
                'materials' => json_decode($materialRequest->mo_no, true),
            ],
        ], 200);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Material Request Form not found',
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}





    
    
    
    
    
    
    //-----------------------------------------------------Show All Material Request Form-----------------------------------------------//
 
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

        // Start query for MaterialRequestForm
        $materialRequestQuery = MaterialRequestForm::query();

        // Search for material requests by multiple fields (operator_name, dn_number, supplier_name, etc.)
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            
            $materialRequestQuery->where(function ($query) use ($search) {
                $query->where('operator_name', 'like', '%' . $search . '%')
                      ->orWhere('date', 'like', '%' . $search . '%')
                      ->orWhere('shift', 'like', '%' . $search . '%')
                      ->orWhere('Ref', 'like', '%' . $search . '%');
            });
        }

        // Order the results by created_at in descending order
        $materialRequestQuery->orderBy('created_at', 'desc');

        // Paginate the results
        $materialRequests = $materialRequestQuery->paginate($perPage, ['*'], 'page', $page);

        // Check if any material request forms are found
        if ($materialRequests->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Material Request Forms found',
                'data' => [],
            ], 404);
        }

        // Process each material request to format the materials
        $materialRequestsData = $materialRequests->map(function ($materialRequest) {
            $materials = json_decode($materialRequest->mo_no, true); // Decode the materials array

            // Add material descriptions and codes if needed
            foreach ($materials as &$material) {
                $materialData = Material::find($material['material_id']);
                if ($materialData) {
                    $material['material_description'] = $materialData->material_description;
                    $material['code'] = $materialData->code;
                }
            }

            // Return the material request with processed materials
            return [
                'id' => $materialRequest->id,
                'Ref' => $materialRequest->Ref,
                'date' => $materialRequest->date,
                'shift' => $materialRequest->shift,
                'operator_name' => $materialRequest->operator_name,
                'materials' => $materials,
            ];
        });

        // Return successful response with paginated data
        return response()->json([
            'status' => true,
            'message' => 'Material Request Forms Retrieved Successfully',
            'data' => [
                'current_page' => $materialRequests->currentPage(),
                'data' => $materialRequestsData,
                'first_page_url' => $materialRequests->url(1),
                'from' => $materialRequests->firstItem(),
                'last_page' => $materialRequests->lastPage(),
                'last_page_url' => $materialRequests->url($materialRequests->lastPage()),
                'next_page_url' => $materialRequests->nextPageUrl(),
                'path' => $materialRequests->path(),
                'per_page' => $materialRequests->perPage(),
                'prev_page_url' => $materialRequests->previousPageUrl(),
                'to' => $materialRequests->lastItem(),
                'total' => $materialRequests->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}




    
    
    
    
    
    //------------------------------------------------------Show Material Request Form By ID--------------------------------------------------------//
 
 public function showById($id)
{
    try {
        
        $materialRequest = MaterialRequestForm::find($id);
 
        if (!$materialRequest) {
            return response()->json([
                'status' => false,
                'message' => 'Material Request Form not found.',
            ], 404);
        }
 
        $decodedMoNo = $materialRequest->mo_no ? json_decode($materialRequest->mo_no, true) : [];

        // Process materials for additional data (material description, code, etc.)
        $materials = collect($decodedMoNo)->map(function ($material) {
            $materialData = Material::find($material['material_id']);
            if ($materialData) {
                $material['material_description'] = $materialData->material_description;
                $material['code'] = $materialData->code;
            }
            return $material;
        });
 
        return response()->json([
            'status' => true,
            'message' => 'Material Request Form Retrieved Successfully',
            'data' => [
                'id' => $materialRequest->id,
                'Ref' => $materialRequest->Ref,
                'date' => $materialRequest->date,
                'shift' => $materialRequest->shift,
                'operator_name' => $materialRequest->operator_name,
                'materials' => $materials, // Processed materials
                'quantity' => $materialRequest->quantity,
                'uom' => $materialRequest->uom,
                'remarks' => $materialRequest->remarks,
                'created_at' => $materialRequest->created_at,
                'updated_at' => $materialRequest->updated_at,
            ],
        ], 200);

    } catch (\Exception $e) {
        
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}



    
    
    
    
    //----------------------------------------------------Delete Material Request Form----------------------------------------------//
 
    public function delete($id)
{
    try {
        // Attempt to find the MaterialRequestForm by ID
        $materialRequest = MaterialRequestForm::find($id);

        // Check if the record was found
        if (!$materialRequest) {
            return response()->json([
                'status' => false,
                'message' => 'Material Request Form not found.',
            ], 404);
        }

        // Delete the found MaterialRequestForm
        $materialRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Material Request Form Deleted Successfully'
        ], 200);
        
    } catch (\Exception $e) {
        // Catch any exceptions and return an error message
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 422);
    }
}

    
    
}







