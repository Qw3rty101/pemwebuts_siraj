<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('user')->get();
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|integer',
            'image' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'expired_at' => 'required|date',
            'modified_by' => 'required|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }

        $validated = $validator->validated();
        Product::create($validated);
        return response()->json('Product saved',201);
    }

    public function show($id)
    {
        $products = Product::all();

        return response()->json($products,200);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (is_null($product)) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'string',
            'price' => 'sometimes|required|integer',
            'image' => 'nullable|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'expired_at' => 'sometimes|required|date',
            'modified_by' => 'sometimes|required|exists:users,email',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $product->update($request->all());
        return response()->json($product);
    }
    

    public function destroy($id)
    {
        $product = Product::find($id);
        if (is_null($product)) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
