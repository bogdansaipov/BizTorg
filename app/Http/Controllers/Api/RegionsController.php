<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\RegionRepositoryInterface;
use OpenApi\Attributes as OA;

class RegionsController extends Controller
{
    public function __construct(private readonly RegionRepositoryInterface $regionRepository)
    {
    }

    #[OA\Get(
        path: '/api/v1/regions',
        summary: 'Get all parent (top-level) regions',
        tags: ['Regions'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of parent regions',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'parent_regions', type: 'array', items: new OA\Items(ref: '#/components/schemas/Region')),
                ])
            ),
        ]
    )]
    public function fetchRegions()
    {
        return response()->json(['parent_regions' => $this->regionRepository->getParents()]);
    }

    #[OA\Get(
        path: '/api/v1/{parentRegionId}/child_regions',
        summary: 'Get child regions for a given parent region',
        tags: ['Regions'],
        parameters: [
            new OA\Parameter(name: 'parentRegionId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of child regions',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'child_regions', type: 'array', items: new OA\Items(ref: '#/components/schemas/Region')),
                ])
            ),
        ]
    )]
    public function fetchChildRegions($parentRegionId)
    {
        return response()->json(['child_regions' => $this->regionRepository->getChildren((int) $parentRegionId)], 200);
    }
}
