<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Services\CustomerAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Every action answers with the customer's full, freshly ordered address
 * list. The app just replaces its list, so default / delete side effects
 * (a new default, for example) can never drift out of sync.
 */
class AddressController extends Controller
{
    public function __construct(private readonly CustomerAddressService $addresses)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->listResponse($request);
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $this->addresses->create($request->user(), $request->validated());

        return $this->listResponse($request, 201);
    }

    public function update(AddressRequest $request, int $address): JsonResponse
    {
        $model = $this->addresses->findFor($request->user(), $address);
        $this->addresses->update($request->user(), $model, $request->validated());

        return $this->listResponse($request);
    }

    public function setDefault(Request $request, int $address): JsonResponse
    {
        $model = $this->addresses->findFor($request->user(), $address);
        $this->addresses->setDefault($request->user(), $model);

        return $this->listResponse($request);
    }

    public function destroy(Request $request, int $address): JsonResponse
    {
        $model = $this->addresses->findFor($request->user(), $address);
        $this->addresses->delete($request->user(), $model);

        return $this->listResponse($request);
    }

    private function listResponse(Request $request, int $status = 200): JsonResponse
    {
        $list = $this->addresses->listFor($request->user());

        return response()->json([
            'data' => AddressResource::collection($list)->resolve($request),
        ], $status);
    }
}
