<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

test('api model binding not found errors use the api response format', function () {
    config(['jwt.secret' => str_repeat('a', 64)]);

    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/categories/999999')
        ->assertNotFound()
        ->assertExactJson([
            'success' => false,
            'message' => 'Resource not found.',
        ]);
});

test('category delete endpoint permanently deletes the category', function () {
    config(['jwt.secret' => str_repeat('a', 64)]);

    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Permanent Delete Test',
        'description' => 'Temporary category for delete endpoint test.',
        'status' => 'active',
    ]);
    $token = JWTAuth::fromUser($user);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'message' => 'Category permanently deleted.',
            'data' => null,
        ]);

    expect(Category::withTrashed()->find($category->id))->toBeNull();
});
