<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['jwt.secret' => str_repeat('a', 64)]);
});

test('authenticated users can create rentals and payments', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $category = Category::create([
        'name' => 'Camera',
        'status' => 'active',
    ]);
    $item = Item::create([
        'category_id' => $category->id,
        'code' => 'CAM-001',
        'name' => 'Canon EOS R',
        'daily_price' => 100000,
        'stock' => 3,
        'status' => 'available',
    ]);

    $rentalResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/rentals', [
            'start_date' => '2026-07-03',
            'end_date' => '2026-07-04',
            'items' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 2,
                ],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.total_days', 2);

    $rentalId = $rentalResponse->json('data.id');

    $this->assertDatabaseHas('rental_details', [
        'rental_id' => $rentalId,
        'item_id' => $item->id,
        'quantity' => 2,
    ]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/payments', [
            'rental_id' => $rentalId,
            'payment_method' => 'cash',
            'amount' => 400000,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.rental_id', $rentalId);
});

test('deleted payments do not block creating a replacement payment for the rental', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $rental = Rental::create([
        'user_id' => $user->id,
        'rental_code' => 'RENT-20260703-ABC123',
        'start_date' => '2026-07-03',
        'end_date' => '2026-07-03',
        'total_days' => 1,
        'total_price' => 150000,
        'status' => 'pending',
    ]);
    $payment = Payment::create([
        'rental_id' => $rental->id,
        'payment_code' => 'PAY-20260703-ABC123',
        'payment_method' => 'cash',
        'amount' => 150000,
        'status' => 'pending',
    ]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/payments/{$payment->id}")
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'message' => 'Payment permanently deleted.',
            'data' => null,
        ]);

    $this->assertDatabaseMissing('payments', [
        'id' => $payment->id,
    ]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/payments', [
            'rental_id' => $rental->id,
            'payment_method' => 'transfer',
            'amount' => 150000,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.rental_id', $rental->id)
        ->assertJsonPath('data.payment_method', 'transfer');
});
