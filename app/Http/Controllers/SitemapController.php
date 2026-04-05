<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;

class SitemapController extends Controller
{
    public function __construct(private readonly SitemapService $sitemapService)
    {
    }

    public function generateSitemap()
    {
        return response($this->sitemapService->generate(), 200)
            ->header('Content-Type', 'application/xml');
    }
}
