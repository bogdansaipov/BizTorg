<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function fetchProductAttributes()
    {
        $categories    = Category::get();
        $subcategories = Subcategory::get();
        $section       = 'add';

        return view('products.add_product', compact('categories', 'section', 'subcategories'));
    }

    public function fetchAttributesBySubcategory(Request $request)
    {
        $subcategoryId = $request->input('subcategory_id');

        if (!$subcategoryId) {
            return response()->json(['error' => 'Subcategory ID is required'], 400);
        }

        $subcategory = Subcategory::with('attributes.attributeValues')->find($subcategoryId);

        if (!$subcategory) {
            return response()->json(['error' => 'Subcategory not found'], 404);
        }

        $attributes = $subcategory->attributes->map(fn ($attribute) => [
            'id'     => $attribute->id,
            'name'   => $attribute->name,
            'values' => $attribute->attributeValues->map(fn ($v) => ['id' => $v->id, 'name' => $v->value]),
        ]);

        return response()->json($attributes);
    }

    public function createProduct(StoreProductRequest $request)
    {
        try {
            $this->productService->createWebProduct($request->user(), $request->validated(), $request);

            return redirect(route('profile.products'))->with('success', 'Product created successfully!');
        } catch (\Exception $e) {
            Log::error('Product creation failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while creating the product. Please try again.');
        }
    }

    public function editProduct(UpdateProductRequest $request)
    {
        try {
            $this->productService->updateWebProduct($request->input('product_id'), $request->validated(), $request);

            return redirect(route('profile.products'))->with('success', 'Product updated successfully!');
        } catch (\Exception $e) {
            Log::error('Product editing failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while updating the product. Please try again.');
        }
    }

    public function getProduct($slug)
    {
        $data = $this->productService->getWebProductDetail($slug);

        return view('products.product_detail', $data);
    }

    public function fetchSingleProduct($id)
    {
        $data          = $this->productService->getWebProductForEdit($id);
        $categories    = Category::get();
        $subcategories = Subcategory::get();
        $section       = 'product_update';

        return view('products.edit_product', array_merge($data, compact('categories', 'subcategories', 'section')));
    }

    public function deleteImage($id)
    {
        Log::info('Attempting to delete image with ID: ' . $id);

        try {
            $this->productService->deleteWebImage($id);

            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Error deleting image: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete image.'], 500);
        }
    }

    public function getParentRegions()
    {
        return response()->json(\App\Models\Region::whereNull('parent_id')->get());
    }

    public function getChildRegions($parentId)
    {
        return response()->json(\App\Models\Region::where('parent_id', $parentId)->get());
    }
}
