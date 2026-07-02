<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiFormatter::success(Payment::with('rental.user')->latest()->get(), 'Payments retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rental_id' => ['required', 'integer', 'exists:rentals,id'],
            'payment_method' => ['required', 'in:cash,transfer,qris,ewallet'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return ApiFormatter::validationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $rental = Rental::with([
            'payment' => fn ($query) => $query->withTrashed(),
        ])->findOrFail($validated['rental_id']);

        if ($rental->payment) {
            return ApiFormatter::error('Payment already exists for this rental.', 400);
        }

        if ($this->money($validated['amount']) !== $this->money($rental->total_price)) {
            return ApiFormatter::error('Payment amount must equal rental total_price.', 400);
        }

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'payment_code' => $this->uniquePaymentCode(),
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'status' => 'pending',
        ]);

        return ApiFormatter::success($payment->load('rental'), 'Payment created.', 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        return ApiFormatter::success($payment->load('rental.user'), 'Payment retrieved.');
    }

    public function paid(Payment $payment): JsonResponse
    {
        $payment = DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            $payment->rental()->update(['status' => 'approved']);

            return $payment->fresh();
        });

        return ApiFormatter::success($payment->load('rental'), 'Payment marked as paid.');
    }

    public function failed(Payment $payment): JsonResponse
    {
        if ($payment->status === 'paid') {
            return ApiFormatter::error('Paid payment cannot be changed to failed.', 400);
        }

        $payment->update(['status' => 'failed']);

        return ApiFormatter::success($payment->fresh()->load('rental'), 'Payment marked as failed.');
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->forceDelete();

        return ApiFormatter::success(null, 'Payment permanently deleted.');
    }

    private function uniquePaymentCode(): string
    {
        do {
            $code = 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Payment::withTrashed()->where('payment_code', $code)->exists());

        return $code;
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
