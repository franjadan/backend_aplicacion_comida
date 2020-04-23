<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use App\Ingredient;
use Illuminate\Http\Request;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\AvailableProductRequest;
use App\Http\Resources\ProductResource;
use DataTables;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::query()
            ->where('active', '=', '1')
            ->orderBy('name')
            ->paginate(5);

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        $product = new Product();
        $categories = Category::query()->orderby('name')->get();
        $ingredints = Ingredient::query()->orderby('name')->get();

        return view('products.create', [
            'product' => $product,
            'categories' => $categories,
            'ingredients' => $ingredints,
        ]);
    }

    public function store(CreateProductRequest $request)
    {
        $request->createProduct();

        return redirect()->route('products')->with('success', 'Se ha creado el producto con éxito.');
    }

    public function edit(Product $product)
    {
        $categories = Category::query()->orderby('name')->get();
        $ingredints = Ingredient::query()->orderby('name')->get();

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
            'ingredients' => $ingredints,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $request->updateProduct($product);

        return redirect()->route('products.edit', $product)->with('success', 'Se han guardado los cambios.');
    }

    public function available(AvailableProductRequest $request, Product $product)
    {
        $request->availableProduct($product);

        return redirect()->route('products')->with('success', 'se han guardado los cambios.');
    }

    public function destroy(Product $product)
    {
        if($product->image != null && explode('/', $product->image)[1] != "shared"){
            $image = public_path()."/$product->image";
            if (@getimagesize($image)){
                unlink($image);
            }
        }

        $product->active = false;

        $product->save();

        return redirect()->route('products')->with('success', 'Se ha eliminado con éxito');
    }

    public function productsByCategory(Category $category)
    {
        $products = $category->products;

        return response()->json(['response' => ['code' => 1, 'data' => ProductResource::collection($products)]], 200);
    }

    public function products()
    {
        $products = Product::all();

        return response()->json(['response' => ['code' => 1, 'data' => ProductResource::collection($products)]], 200);
    }
}
