<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prefix;
use Illuminate\Http\Request;

class PrefixController extends Controller
{
    public function index()
    {
        $prefixes = Prefix::all();
        return view('admin.prefixes.index', compact('prefixes'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'prefix' => 'required|string|max:10',
        ]);
    
        $prefix = Prefix::find($id);
        if (!$prefix) {
            return redirect()->back()->withErrors(['error' => 'Prefix not found']);
        }
    
        $prefix->update([
            'prefix' => $request->input('prefix'),
        ]);
    
        return redirect()->back()->with('success', 'Prefix updated successfully');
    }
}

