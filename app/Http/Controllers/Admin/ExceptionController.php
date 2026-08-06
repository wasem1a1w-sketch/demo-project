<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExceptionController extends Controller
{
    public function index()
    {
        $classes = AppException::select('class')
            ->distinct()
            ->orderBy('class')
            ->pluck('class');

        return Inertia::render('Admin/Exceptions/Index', [
            'classes' => $classes,
        ]);
    }

    public function getExceptions(Request $request)
    {
        $query = AppException::with('user');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('class', 'like', "%{$request->search}%")
                    ->orWhere('message', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('class')) {
            $query->where('class', $request->class);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $exceptions = $query->orderByDesc('created_at')->paginate(25);

        return response()->json($exceptions);
    }

    public function show(AppException $exception)
    {
        return response()->json($exception->load('user'));
    }

    public function test()
    {
        report($this->simulatePaymentTimeout());

        return back()->with('success', 'Test exception recorded.');
    }

    protected function simulatePaymentTimeout(): \Throwable
    {
        try {
            $this->chargePaymentMethod(new \stdClass);
        } catch (\Throwable $e) {
            return $e;
        }

        return new \RuntimeException('Unknown failure');
    }

    protected function chargePaymentMethod(object $paymentMethod): void
    {
        throw new \RuntimeException('Payment provider timeout after 30s (demo).');
    }
}
