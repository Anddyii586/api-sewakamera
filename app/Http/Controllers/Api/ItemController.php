<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiFormatter::success(Item::with('category')->latest()->get(), 'Items retrieved.');
    }

    public function byCategory(Category $category): JsonResponse
    {
        return ApiFormatter::success($category->items()->with('category')->latest()->get(), 'Items by category retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return ApiFormatter::validationError($validator->errors()->toArray());
        }

        $item = Item::create($validator->validated());

        return ApiFormatter::success($item->load('category'), 'Item created.', 201);
    }

    public function show(Item $item): JsonResponse
    {
        return ApiFormatter::success($item->load('category'), 'Item retrieved.');
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules($item, $request->isMethod('patch')));

        if ($validator->fails()) {
            return ApiFormatter::validationError($validator->errors()->toArray());
        }

        $item->update($validator->validated());

        return ApiFormatter::success($item->fresh()->load('category'), 'Item updated.');
    }

    public function destroy(Item $item): JsonResponse
    {
        $item->forceDelete();

        return ApiFormatter::success(null, 'Item deleted.');
    }

    private function rules(?Item $item = null, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'category_id' => [$required, 'integer', 'exists:categories,id'],
            'code' => [
                $required,
                'string',
                'max:30',
                Rule::unique('items', 'code')
                    ->ignore($item?->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => [$required, 'string', 'max:150'],
            'brand' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:100'],
            'model' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:100'],
            'description' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'string'],
            'daily_price' => [$required, 'numeric', 'min:0'],
            'stock' => [$required, 'integer', 'min:0'],
            'status' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'in:available,unavailable,maintenance'],
        ];
    }
}
