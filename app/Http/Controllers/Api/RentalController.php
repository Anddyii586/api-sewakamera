<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class RentalController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiFormatter::success(
            Rental::with(['user', 'details.item', 'payment'])->latest()->get(),
            'Rentals retrieved.',
        );
    }

    public function myRentals(): JsonResponse
    {
        return ApiFormatter::success(
            Rental::with(['details.item', 'payment'])
                ->where('user_id', $this->user()->id)
                ->latest()
                ->get(),
            'My rentals retrieved.',
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return ApiFormatter::validationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $startDate = CarbonImmutable::parse($validated['start_date'])->startOfDay();
        $endDate = CarbonImmutable::parse($validated['end_date'])->startOfDay();
        $totalDays = max(1, (int) $startDate->diffInDays($endDate) + 1);
        $quantities = collect($validated['items'])
            ->groupBy('item_id')
            ->map(fn ($rows) => array_sum(array_column($rows->all(), 'quantity')));
        $items = Item::query()->whereIn('id', $quantities->keys())->get()->keyBy('id');
        $details = [];
        $totalPrice = 0;

        foreach ($quantities as $itemId => $quantity) {
            $item = $items->get((int) $itemId);

            if (! $item || $item->status !== 'available') {
                return ApiFormatter::error('Item is not available for rental.', 400);
            }

            if ($quantity > $item->stock) {
                return ApiFormatter::error('Quantity exceeds available stock.', 400);
            }

            $subtotal = (float) $item->daily_price * $quantity * $totalDays;
            $totalPrice += $subtotal;

            $details[] = [
                'item_id' => $item->id,
                'quantity' => $quantity,
                'daily_price' => $item->daily_price,
                'subtotal' => $subtotal,
            ];
        }

        $rental = DB::transaction(function () use ($validated, $details, $totalDays, $totalPrice) {
            $rental = Rental::create([
                'user_id' => $this->user()->id,
                'rental_code' => $this->uniqueRentalCode(),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            $rental->details()->createMany($details);

            return $rental;
        });

        return ApiFormatter::success($rental->load(['user', 'details.item', 'payment']), 'Rental created.', 201);
    }

    public function show(Rental $rental): JsonResponse
    {
        return ApiFormatter::success($rental->load(['user', 'details.item', 'payment']), 'Rental retrieved.');
    }

    public function approve(Rental $rental): JsonResponse
    {
        return $this->updateStatus($rental, 'approved', 'Rental approved.');
    }

    public function rented(Rental $rental): JsonResponse
    {
        return $this->updateStatus($rental, 'rented', 'Rental marked as rented.');
    }

    public function markReturned(Rental $rental): JsonResponse
    {
        return $this->updateStatus($rental, 'returned', 'Rental marked as returned.');
    }

    public function cancel(Rental $rental): JsonResponse
    {
        return $this->updateStatus($rental, 'cancelled', 'Rental cancelled.');
    }

    public function destroy(Rental $rental): JsonResponse
    {
        $rental->forceDelete();

        return ApiFormatter::success(null, 'Rental permanently deleted.');
    }

    private function updateStatus(Rental $rental, string $status, string $message): JsonResponse
    {
        $rental->update(['status' => $status]);

        return ApiFormatter::success($rental->fresh()->load(['user', 'details.item', 'payment']), $message);
    }

    private function uniqueRentalCode(): string
    {
        do {
            $code = 'RENT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Rental::query()->where('rental_code', $code)->exists());

        return $code;
    }

    private function user(): User
    {
        return JWTAuth::parseToken()->authenticate();
    }
}
