<?php

namespace App\Http\Controllers;

use App\Services\IndexService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct(private readonly IndexService $indexService)
    {
    }

    public function index()
    {
        $data = $this->indexService->getHomePageData();

        return view('welcome', $data);
    }

    public function getPaginatedProducts(Request $request)
    {
        $data = $this->indexService->getPaginatedProducts(
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 24)
        );

        return response()->json($data);
    }
}
