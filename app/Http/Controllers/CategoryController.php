<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function index($slug, Request $request)
    {
        $query = array_filter($request->query(), fn ($v) => $v !== '' && $v !== null);
        $request->merge($query);

        $data = $this->categoryService->getCategoryPage($slug, $request);
        $user = $request->user();

        return view('category', array_merge($data, compact('user')));
    }
}
