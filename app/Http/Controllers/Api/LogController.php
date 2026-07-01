<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\LogModel;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiFormatter::success(LogModel::with('user')->latest('log_id')->get(), 'Logs retrieved.');
    }

    public function show(LogModel $log): JsonResponse
    {
        return ApiFormatter::success($log->load('user'), 'Log retrieved.');
    }
}
