<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UncountableUnloadingForm;
use App\Models\Provider;
use App\Models\Product;
use App\Models\Material;
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;
use Auth;

class UncountableUnloadingControllerMain extends Controller
{
    //----------------------------Create Uncountable Unloading Form----------------------------//
   public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'dn_number' => 'required|string|max:50',   
            // 'product_id' => 'required|integer|exists:products,id',  
            'material_id' => 'required|integer|exists:materials,id',  
            'supplier_id' => 'required|integer|exists:providers,id', 
            'standard_weight' => 'nullable|string|min:0',
            'boxes' => 'required|array|min:1',  // Require at least one item in boxes array
            'boxes.*.weight' => 'nullable|numeric|min:0',  // Allow null, but if present, it should be numeric and >= 0
        ]);

        // Custom validation to ensure at least one box has a weight
        $validator->after(function ($validator) use ($request) {
            $hasValidWeight = false;
            foreach ($request->input('boxes', []) as $box) {
                if (isset($box['weight']) && is_numeric($box['weight'])) {
                    $hasValidWeight = true;
                    break;
                }
            }

            if (!$hasValidWeight) {
                $validator->errors()->add('boxes', 'At least one box must have a valid weight.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        $existingUncountableForm = UncountableUnloadingForm::where('dn_number', $validatedData['dn_number'])->first();
        if ($existingUncountableForm) {
            return response()->json([
                'status' => false,
                'message' => 'Uncountable Unloading Form already exists with this DN number.'
            ], 409);
        }

        $supplier = Provider::find($validatedData['supplier_id']);
        // $product = Product::find($validatedData['product_id']);
        $material = Material::find($validatedData['material_id']);
  
        if (!$supplier || !$material) {
            return response()->json([
                'status' => false,
                'message' => 'Supplier or Material not found in the database.',
            ], 404);
        }

        $batchNumber = Carbon::now()->format('Ymd');
        $encodedBoxes = json_encode($request->boxes);
  
        $incomingForm = UncountableUnloadingForm::create([
            'date' => $validatedData['date'] ? Carbon::parse($validatedData['date'])->format('Y-m-d') : null,
            'batch_number' => $batchNumber,  
            'dn_number' => $validatedData['dn_number'],
            // 'product_id' => $product->id,
            'material_id' => $material->id,
            'supplier_id' => $supplier->id,
            'standard_weight' => $validatedData['standard_weight'] ?? null,
            'no_of_boxes' => $encodedBoxes ?? null, 
        ]);
 
        return response()->json([
            'status' => true,
            'message' => 'Uncountable Unloading Form created successfully!',
            'data' => [
                'id' => $incomingForm->id,
                'batch_number' => $incomingForm->batch_number, 
                'date' => $incomingForm->date,
                'dn_number' => $incomingForm->dn_number,
                // 'product' => [
                //     'id' => $product->id,
                //     'name' => $product->name,
                //     'product_description' => $product->product_description,
                //     'weight' => $product->weight,
                //     'price' => $product->price,
                // ],
                'material' => [
                        'id' => $material->id,
                        'code' => $material->code,
                        'material_type' => $material->material_type,
                        'material_description' => $material->material_description,
                    ],
                'supplier' => [
                    'id' => $supplier->id,
                    'name' => $supplier->first_name . ' ' . $supplier->last_name,
                    'company_name' => $supplier->company_name,
                    'contact_number' => $supplier->contact_number,
                    'email' => $supplier->email,
                ],
                'standard_weight' => $incomingForm->standard_weight,
                'boxes' => $request->boxes,
            ],
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}





    
    
    
     //----------------------------Update Uncountable Unloading Form----------------------------//
   public function update(Request $request, $id)
{
    try {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'dn_number' => 'nullable|string|max:50',
            // 'product_id' => 'nullable|integer|exists:products,id',
            'material_id' => 'nullable|integer|exists:materials,id',
            'supplier_id' => 'nullable|integer|exists:providers,id',
            'standard_weight' => 'nullable|string|min:0',
            'boxes' => 'nullable|array',
            'boxes.*.weight' => 'nullable|numeric|min:0',  // Allow null but if present, it must be numeric and non-negative
        ]);

        // Custom validation to ensure at least one box has a valid weight
        $validator->after(function ($validator) use ($request) {
            if ($request->has('boxes')) {
                $hasValidWeight = false;
                foreach ($request->input('boxes', []) as $box) {
                    if (isset($box['weight']) && is_numeric($box['weight'])) {
                        $hasValidWeight = true;
                        break;
                    }
                }

                if (!$hasValidWeight) {
                    $validator->errors()->add('boxes', 'At least one box must have a valid weight.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the form by ID
        $form = UncountableUnloadingForm::find($id);
        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Uncountable Unloading Form not found',
            ], 404);
        }

        // Fetch existing product and supplier data if no new ID is provided
        // $product = $request->product_id ? Product::find($request->product_id) : Product::find($form->product_id);
        $material = $request->material_id ? Material::find($request->material_id) : Material::find($form->material_id);
        $supplier = $request->supplier_id ? Provider::find($request->supplier_id) : Provider::find($form->supplier_id);

        // Update the boxes if provided
        if ($request->has('boxes')) {
            $encodedBoxes = json_encode($request->boxes);
            $form->no_of_boxes = $encodedBoxes;
        }

        // Update form fields
        $form->update([
            'date' => $request->date ? Carbon::parse($request->date)->format('Y-m-d') : $form->date,
            'dn_number' => $request->dn_number ?? $form->dn_number,
            // 'product_id' => $product->id,
            'material_id' => $material->id,
            'supplier_id' => $supplier->id,
            'standard_weight' => $request->standard_weight ?? $form->standard_weight,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Uncountable Unloading Form updated successfully!',
            'data' => [
                'id' => $form->id,
                'batch_number' => $form->batch_number,
                'date' => $form->date,
                'dn_number' => $form->dn_number,
                // 'product' => [
                //     'id' => $product->id,
                //     'name' => $product->name,
                //     'product_description' => $product->product_description,
                //     'weight' => $product->weight,
                //     'price' => $product->price,
                // ],
                'material' => [
                    'id' => $material->id,
                    'code' => $material->code,
                    'material_type' => $material->material_type,
                    'material_description' => $material->material_description,
                    ],
                'supplier' => [
                    'id' => $supplier->id,
                    'first_name' => $supplier->first_name,
                    'company_name' => $supplier->company_name,
                    'contact_number' => $supplier->contact_number,
                    'email' => $supplier->email,
                ],
                'standard_weight' => $form->standard_weight,
                'boxes' => $request->boxes ?? json_decode($form->no_of_boxes),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}






    
    
    
    
    //----------------------------Get All Uncountable Unloading Forms----------------------------//
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

        // Start query with eager loading
        $formQuery = UncountableUnloadingForm::with(['provider', 'material']); // Updated to use 'material' relationship

        // Unified search by material name, dn_number, date, standard weight, or supplier name
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');

            $formQuery->where(function ($query) use ($search) {
                $query->whereHas('material', function ($q) use ($search) {
                    $q->where('material_type', 'like', '%' . $search . '%')
                    ->orWhere('material_description', 'like', '%' . $search . '%');
                })
                ->orWhere('dn_number', 'like', '%' . $search . '%')
                ->orWhere('date', 'like', '%' . $search . '%')
                // ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('standard_weight', 'like', '%' . $search . '%')
                ->orWhereHas('provider', function ($q) use ($search) {
                    $q->where('company_name', 'like', '%' . $search . '%');
                });
            });
        }
        
        $formQuery->orderBy('created_at', 'desc');

        // Paginate results
        $forms = $formQuery->paginate($perPage, ['*'], 'page', $page);

        // Check if any forms are found
        if ($forms->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No forms found',
                'data' => [],
            ], 404);
        }

        // Transform forms data with related entities
        $formsData = $forms->map(function ($form) {
            return [
                'id' => $form->id,
                'batch_number' => $form->batch_number,
                'date' => $form->date,
                'dn_number' => $form->dn_number,
                'material' => $form->material ? [ // Updated to retrieve material details
                    'id' => $form->material->id,
                    'code' => $form->material->code,
                    'material_type' => $form->material->material_type,
                    'material_description' => $form->material->material_description,
                ] : null,
                'supplier' => $form->provider ? [
                    'id' => $form->provider->id,
                    'first_name' => $form->provider->first_name,
                    'company_name' => $form->provider->company_name,
                    'contact_number' => $form->provider->contact_number,
                    'email' => $form->provider->email,
                ] : null,
                'standard_weight' => $form->standard_weight,
                'boxes' => $form->no_of_boxes ? json_decode($form->no_of_boxes, true) : [],
            ];
        });

        // Return successful response with paginated data
        return response()->json([
            'status' => true,
            'message' => 'Uncountable Unloading Forms retrieved successfully!',
            'forms' => [
                'current_page' => $forms->currentPage(),
                'data' => $formsData,
                'first_page_url' => $forms->url(1),
                'from' => $forms->firstItem(),
                'last_page' => $forms->lastPage(),
                'last_page_url' => $forms->url($forms->lastPage()),
                'next_page_url' => $forms->nextPageUrl(),
                'path' => $forms->path(),
                'per_page' => $forms->perPage(),
                'prev_page_url' => $forms->previousPageUrl(),
                'to' => $forms->lastItem(),
                'total' => $forms->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}








    //----------------------------Get Uncountable Unloading Form by ID----------------------------//
 public function showById($id)
{
    try {
        
        $form = UncountableUnloadingForm::with(['provider', 'product', 'material'])->find($id);

        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Uncountable Unloading Form not found',
            ], 404);
        }

        $decodedBoxes = $form->no_of_boxes ? json_decode($form->no_of_boxes, true) : [];

        return response()->json([
            'status' => true,
            'message' => 'Uncountable Unloading Form retrieved successfully!',
            'data' => [
                'id' => $form->id,
                'batch_number' => $form->batch_number,
                'date' => $form->date,
                'dn_number' => $form->dn_number,
                'material' => [
                    'id' => $form->material->id,
                    'code' => $form->material->code,
                    'material_type' => $form->material->material_type,
                    'material_description' => $form->material->material_description,
                    ],
                // 'product' => [
                //     'id' => $form->product->id,
                //     'Ref' => $form->product->Ref,
                //     'name' => $form->product->name,
                //     'product_description' => $form->product->product_description,
                //     'weight' => $form->product->weight,
                //     'price' => $form->product->price,
                // ],
                'supplier' => [
                    'id' => $form->provider->id,
                    'Ref' => $form->provider->Ref,
                    'first_name' => $form->provider->first_name,
                    'company_name' => $form->provider->company_name,
                    'contact_number' => $form->provider->contact_number,
                    'email' => $form->provider->email,
                ],
                'standard_weight' => $form->standard_weight,
                'boxes' => $decodedBoxes,
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}









   




    //----------------------------Delete Uncountable Unloading Form----------------------------//
    public function delete($id)
    {
        try {
            $form = UncountableUnloadingForm::find($id);

            if (!$form) {
                return response()->json([
                    'status' => false,
                    'message' => 'Uncountable Unloading Form not found',
                ], 404);
            }

            $form->delete();

            return response()->json([
                'status' => true,
                'message' => 'Uncountable Unloading Form deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    
    
}
