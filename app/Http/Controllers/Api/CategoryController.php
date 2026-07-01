<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiFormatter::success(Category::withCount('items')->latest()->get(), 'Categories retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return ApiFormatter::validationError($validator->errors()->toArray());
        }

        $category = Category::create($validator->validated());

        return ApiFormatter::success($category, 'Category created.', 201);
    }

    public function show(Category $category): JsonResponse
    {
        return ApiFormatter::success($category->load('items'), 'Category retrieved.');
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules($category, $request->isMethod('patch')));

        if ($validator->fails()) {
            return ApiFormatter::validationError($validator->errors()->toArray());
        }

        $category->update($validator->validated());

        return ApiFormatter::success($category->fresh(), 'Category updated.');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return ApiFormatter::success(null, 'Category deleted.');
    }

    private function rules(?Category $category = null, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'name' => [
                $required,
                'string',
                'max:100',
                Rule::unique('categories', 'name')
                    ->ignore($category?->id)
                    ->whereNull('deleted_at'),
            ],
            'description' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'string'],
            'status' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'in:active,inactive'],
        ];
    }
}
