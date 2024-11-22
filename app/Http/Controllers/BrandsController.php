<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Carbon\Carbon;
use DB;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class BrandsController extends Controller
{

    //------------ GET ALL Brands -----------\\

    public function index(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('brand')){

            if ($request->ajax()) {
                $data = Brand::where('deleted_at', '=', null)->orderBy('id', 'desc')->get();

                return Datatables::of($data)->addIndexColumn()

                ->addColumn('image', function($row){
                    $url = url("images/brands/".$row->image);
                    $avatar = 
                    '<div class="avatar mr-2 avatar-md">
                        <img 
                            src="'.$url.'" alt="">
                    </div>';

                    return $avatar;
                })

                ->addColumn('action', function($row){

                        $btn = '<a id="' .$row->id. '"  class="edit cursor-pointer ul-link-action text-success"
                        data-toggle="tooltip" data-placement="top" title="Edit"><i class="i-Edit"></i></a>';
                        $btn .= '&nbsp;&nbsp;';

                        $btn .= '<a id="' .$row->id. '" class="delete cursor-pointer ul-link-action text-danger"
                        data-toggle="tooltip" data-placement="top" title="Remove"><i class="i-Close-Window"></i></a>';
                        $btn .= '&nbsp;&nbsp;';

                        return $btn;
                    })
                    ->rawColumns(['action','image'])
                    ->make(true);
            }

            return view('products.brands');

        }
        return abort('403', __('You are not authorized'));

    }

    //---------------- STORE NEW Brand -------------\\

    public function store(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('brand')){

            request()->validate([
                'name'      => 'required',
                'image'     => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
            ]);

            \DB::transaction(function () use ($request) {

                if ($request->hasFile('image')) {

                    $image = $request->file('image');
                    $filename = time().'.'.$image->extension();  
                    $image->move(public_path('/images/brands'), $filename);


                } else {
                    $filename = 'image_default.png';
                }

                $Brand = new Brand;

                $Brand->name = $request['name'];
                $Brand->description = $request['description'];
                $Brand->image = $filename;
                $Brand->save();

            }, 10);

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));

    }

     //------------ function show -----------\\

     public function show($id){
        //
    
    }

    public function edit(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('brand')){

            $brand = Brand::where('deleted_at', '=', null)->findOrFail($id);
                
            return response()->json([
                'brand' => $brand,
            ]);

        }
        return abort('403', __('You are not authorized'));
    }

     //---------------- UPDATE Brand -------------\\

     public function update(Request $request, $id)
     {
        // dd($request->all());
        $user_auth = auth()->user();
		if ($user_auth->can('brand')){

            request()->validate([
                'name'     => 'required',
                'image'    => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
            ]);
            
            \DB::transaction(function () use ($request, $id) {
                $Brand = Brand::findOrFail($id);
                $currentImage = $Brand->image;
    
                if ($currentImage && $request->image && $request->image != $currentImage) {
                    $image = $request->file('image');
                    $filename = time().'.'.$image->extension();  
                    $image->move(public_path('/images/brands'), $filename);
                    $path = public_path() . '/images/brands';
                    $brand_image = $path . '/' . $currentImage;
                    if (file_exists($brand_image)) {
                        if ($currentImage != 'image_default.png') {
                            @unlink($brand_image);
                        }
                    }

                } else if (!$currentImage && $request->image != 'null'){
                    $image = $request->file('image');
                    $filename = time().'.'.$image->extension();  
                    $image->move(public_path('/images/brands'), $filename);
                }
    
                else {
                    $filename = $currentImage?$currentImage:'image_default.png';
                }
    
                Brand::whereId($id)->update([
                    'name' => $request['name'],
                    'description' => $request['description'],
                    'image' => $filename,
                ]);
    
            }, 10);
    
            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
     }

    //------------ Delete Brand -----------\\

    public function destroy(Request $request, $id)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('brand')){

            $brand = Brand::findOrFail($id);
            $brand->deleted_at = Carbon::now();
            $brand->save();

            $path = public_path() . '/images/brands';
            $brand_image = $path . '/' . $brand->image;
            if (file_exists($brand_image)) {
                if ($brand->image != 'image_default.png') {
                    @unlink($brand_image);
                }
            }

            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $user_auth = auth()->user();
		if ($user_auth->can('brand')){

            $selectedIds = $request->selectedIds;
            foreach ($selectedIds as $brand_id) {
                $brand = Brand::findOrFail($brand_id);
                $brand->deleted_at = Carbon::now();
                $brand->save();

                $path = public_path() . '/images/brands';
                $brand_image = $path . '/' . $brand->image;
                if (file_exists($brand_image)) {
                    if ($brand->image != 'image_default.png') {
                        @unlink($brand_image);
                    }
                }

            }
            return response()->json(['success' => true]);

        }
        return abort('403', __('You are not authorized'));

    }

}
