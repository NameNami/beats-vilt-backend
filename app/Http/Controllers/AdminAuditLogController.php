<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name,email');

        // Filter by search term (model type or user name/email)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('model_type', 'like', "%{$searchTerm}%")
                  ->orWhereHas('user', function ($uq) use ($searchTerm) {
                      $uq->where('name', 'like', "%{$searchTerm}%")
                         ->orWhere('email', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date (e.g. today, last 7 days, etc.)
        if ($request->filled('date_range')) {
            if ($request->date_range === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($request->date_range === 'last_7_days') {
                $query->where('created_at', '>=', now()->subDays(7));
            } elseif ($request->date_range === 'last_30_days') {
                $query->where('created_at', '>=', now()->subDays(30));
            }
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        // Whitelist allowed sort columns to prevent SQL injection
        $allowedSorts = ['created_at', 'action', 'model_type'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest(); // fallback
        }

        $logs = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/AuditLogs', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date_range', 'sort', 'direction'])
        ]);
    }
}
