<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses
     */
    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['user', 'approver']);

        // Apply filters
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'expense_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $expenses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $expenses,
            'summary' => $this->getExpensesSummary($request)
        ]);
    }

    /**
     * Store a newly created expense
     */
    // public function store(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'category' => 'required|string|max:255',
    //         'subcategory' => 'nullable|string|max:255',
    //         'description' => 'required|string|max:500',
    //         'amount' => 'required|numeric|min:0.01',
    //         'tax_amount' => 'nullable|numeric|min:0',
    //         'tax_rate' => 'nullable|numeric|min:0|max:100',
    //         'payment_method' => 'required|in:cash,bank_transfer,credit_card,debit_card,mobile_money,cheque,online_payment',
    //         'vendor_name' => 'nullable|string|max:255',
    //         'receipt_number' => 'nullable|string|max:255',
    //         'expense_date' => 'required|date',
    //         'notes' => 'nullable|string|max:1000',
    //         'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'is_recurring' => 'boolean',
    //         'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:weekly,monthly,quarterly,yearly',
    //         'next_due_date' => 'nullable|required_if:is_recurring,true|date|after:expense_date'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     try {
    //         DB::beginTransaction();

    //         $expenseData = $request->except('receipt_image');
    //         $expenseData['user_id'] = Auth::id() ?? 1; // Default to user 1 if not authenticated

    //         // Clean up empty string values
    //         if (empty($expenseData['tax_rate']) || $expenseData['tax_rate'] === '') {
    //             $expenseData['tax_rate'] = null;
    //         }
    //         if (empty($expenseData['tax_amount']) || $expenseData['tax_amount'] === '') {
    //             $expenseData['tax_amount'] = 0;
    //         }
    //         if (empty($expenseData['notes']) || $expenseData['notes'] === '') {
    //             $expenseData['notes'] = null;
    //         }

    //         // Handle recurring frequency logic
    //         if (!isset($expenseData['is_recurring']) || !$expenseData['is_recurring']) {
    //             $expenseData['recurring_frequency'] = null;
    //             $expenseData['next_due_date'] = null;
    //         }

    //         // Calculate tax amount if not provided
    //         if (!$request->has('tax_amount') && $request->has('tax_rate') && is_numeric($request->tax_rate) && $request->tax_rate > 0) {
    //             $expenseData['tax_amount'] = ($request->amount * $request->tax_rate) / 100;
    //         }

    //         // Handle receipt image upload
    //         if ($request->hasFile('receipt_image')) {
    //             $file = $request->file('receipt_image');
    //             $filename = time() . '_' . $file->getClientOriginalName();
    //             $path = $file->storeAs('receipts', $filename, 'public');
    //             $expenseData['receipt_image'] = $path;
    //         }

    //         $expense = Expense::create($expenseData);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Expense created successfully',
    //             'data' => $expense->load(['user', 'approver'])
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create expense: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'description'       => 'required|string|max:255',
            'amount'            => 'required|numeric|min:0',
            'category'          => 'required|string|max:255',
            'subcategory'       => 'nullable|string|max:255',
            'payment_method'    => 'required|string|in:cash,bank_transfer,credit_card,debit_card,mobile_money,cheque,online_payment',
            'expense_date'      => 'required|date',
            'vendor_name'       => 'nullable|string|max:255',
            'receipt_number'    => 'nullable|string|max:255|unique:expenses,receipt_number',
            'receipt_image'     => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
            'tax_rate'          => 'nullable|numeric|min:0|max:100',
            'notes'             => 'nullable|string',
            'is_recurring'      => 'boolean',
            'recurring_frequency' => 'nullable|string|in:daily,weekly,monthly,yearly',
            // status will be set to default, but allow override for admin workflows
            'status'            => 'nullable|string|in:pending,approved,paid,rejected',
        ]);

        // start with validated fields
        $expenseData = $validated;

        // calculate tax if both fields present
        if (!empty($request->amount) && !empty($request->tax_rate)) {
            $expenseData['tax_amount'] = ($request->amount * $request->tax_rate) / 100;
        } else {
            $expenseData['tax_amount'] = 0;
        }

        // default status if none passed
        $expenseData['status'] = $expenseData['status'] ?? 'pending';

        // set user_id (fallback if no auth user)
        $expenseData['user_id'] = Auth::id() ?? 1;

        // handle file upload
        if ($request->hasFile('receipt_image')) {
            $filename = time() . '_' . $request->file('receipt_image')->getClientOriginalName();
            $path = $request->file('receipt_image')->storeAs('receipts', $filename, 'public');
            $expenseData['receipt_image'] = $path;
        }

        $expense = Expense::create($expenseData);

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully',
            'data'    => $expense
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        // log the exact error in laravel.log
        Log::error('Expense store error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while saving expense',
            'error'   => $e->getMessage()
        ], 500);
    }
}


    /**
     * Display the specified expense
     */
    public function show(Expense $expense): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $expense->load(['user', 'approver'])
        ]);
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, Expense $expense): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|required|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'description' => 'sometimes|required|string|max:500',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'sometimes|required|in:cash,bank_transfer,credit_card,debit_card,mobile_money,cheque,online_payment',
            'vendor_name' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'expense_date' => 'sometimes|required|date',
            'notes' => 'nullable|string|max:1000',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:weekly,monthly,quarterly,yearly',
            'next_due_date' => 'nullable|required_if:is_recurring,true|date|after:expense_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updateData = $request->except('receipt_image');

            // Calculate tax amount if needed
            if ($request->has('tax_rate') && $request->has('amount')) {
                $updateData['tax_amount'] = ($request->amount * ($request->tax_rate ?? 0)) / 100;
            }

            // Handle receipt image upload
            if ($request->hasFile('receipt_image')) {
                // Delete old image
                if ($expense->receipt_image) {
                    Storage::disk('public')->delete($expense->receipt_image);
                }

                $file = $request->file('receipt_image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('receipts', $filename, 'public');
                $updateData['receipt_image'] = $path;
            }

            $expense->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'data' => $expense->fresh(['user', 'approver'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy(Expense $expense): JsonResponse
    {
        try {
            // Delete receipt image if exists
            if ($expense->receipt_image) {
                Storage::disk('public')->delete($expense->receipt_image);
            }

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve an expense
     */
    public function approve(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending expenses can be approved'
            ], 400);
        }

        $expense->approve(Auth::id() ?? 1);

        return response()->json([
            'success' => true,
            'message' => 'Expense approved successfully',
            'data' => $expense->fresh(['user', 'approver'])
        ]);
    }

    /**
     * Reject an expense
     */
    public function reject(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending expenses can be rejected'
            ], 400);
        }

        $expense->reject();

        return response()->json([
            'success' => true,
            'message' => 'Expense rejected successfully',
            'data' => $expense->fresh(['user', 'approver'])
        ]);
    }

    /**
     * Mark expense as paid
     */
    public function markAsPaid(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved expenses can be marked as paid'
            ], 400);
        }

        $expense->markAsPaid();

        return response()->json([
            'success' => true,
            'message' => 'Expense marked as paid successfully',
            'data' => $expense->fresh(['user', 'approver'])
        ]);
    }

    /**
     * Get expense categories
     */
    public function getCategories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Expense::getCategories()
        ]);
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Expense::getPaymentMethods()
        ]);
    }

    /**
     * Get expenses dashboard data
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month'); // month, quarter, year
        $date = Carbon::now();

        switch ($period) {
            case 'quarter':
                $startDate = $date->startOfQuarter()->format('Y-m-d');
                $endDate = $date->endOfQuarter()->format('Y-m-d');
                break;
            case 'year':
                $startDate = $date->startOfYear()->format('Y-m-d');
                $endDate = $date->endOfYear()->format('Y-m-d');
                break;
            default: // month
                $startDate = $date->startOfMonth()->format('Y-m-d');
                $endDate = $date->endOfMonth()->format('Y-m-d');
                break;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $this->getDashboardSummary($startDate, $endDate),
                'chart_data' => $this->getChartData($startDate, $endDate),
                'recent_expenses' => $this->getRecentExpenses(),
                'top_categories' => $this->getTopCategories($startDate, $endDate),
                'profit_loss' => $this->getProfitLossData($startDate, $endDate)
            ]
        ]);
    }

    /**
     * Get profit and loss report
     */
    public function getProfitLoss(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $data = $this->getProfitLossData($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Export expenses to CSV
     */
    public function export(Request $request)
    {
        $query = Expense::with(['user', 'approver']);

        // Apply same filters as index method
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        $csvData = [];
        $csvData[] = ['Date', 'Category', 'Subcategory', 'Description', 'Amount', 'Tax', 'Total', 'Payment Method', 'Vendor', 'Status', 'Notes'];

        foreach ($expenses as $expense) {
            $csvData[] = [
                $expense->expense_date->format('Y-m-d'),
                $expense->category,
                $expense->subcategory,
                $expense->description,
                $expense->amount,
                $expense->tax_amount,
                $expense->total_amount,
                $expense->payment_method,
                $expense->vendor_name,
                $expense->status,
                $expense->notes
            ];
        }

        $filename = 'expenses_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Private helper methods

  private function getExpensesSummary($request)
{
    $baseQuery = Expense::query();

    if ($request->has('category') && $request->category) {
        $baseQuery->where('category', $request->category);
    }
    if ($request->has('status') && $request->status) {
        $baseQuery->where('status', $request->status);
    }
    if ($request->has('date_from') && $request->date_from) {
        $baseQuery->whereDate('expense_date', '>=', $request->date_from);
    }
    if ($request->has('date_to') && $request->date_to) {
        $baseQuery->whereDate('expense_date', '<=', $request->date_to);
    }

    return [
        'total_amount'     => (clone $baseQuery)->sum('amount'),
        'total_tax'        => (clone $baseQuery)->sum('tax_amount'),
        'total_expenses'   => (clone $baseQuery)->count(),
        'pending_amount'   => (clone $baseQuery)->where('status', 'pending')->sum('amount'),
        'approved_amount'  => (clone $baseQuery)->where('status', 'approved')->sum('amount'),
        'paid_amount'      => (clone $baseQuery)->where('status', 'paid')->sum('amount'),
    ];
}


    private function getDashboardSummary($startDate, $endDate)
    {
        $currentPeriod = Expense::whereBetween('expense_date', [$startDate, $endDate]);
        $previousStart = Carbon::parse($startDate)->subDays(Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate)) + 1);
        $previousEnd = Carbon::parse($startDate)->subDay();
        $previousPeriod = Expense::whereBetween('expense_date', [$previousStart, $previousEnd]);

        return [
            'total_expenses' => $currentPeriod->sum('amount'),
            'total_tax' => $currentPeriod->sum('tax_amount'),
            'expense_count' => $currentPeriod->count(),
            'pending_count' => $currentPeriod->where('status', 'pending')->count(),
            'previous_total' => $previousPeriod->sum('amount'),
            'growth_percentage' => $this->calculateGrowthPercentage(
                $previousPeriod->sum('amount'),
                $currentPeriod->sum('amount')
            )
        ];
    }

    private function getChartData($startDate, $endDate)
    {
        $expenses = Expense::selectRaw('DATE(expense_date) as date, SUM(amount) as total')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $expenses->pluck('date'),
            'data' => $expenses->pluck('total')
        ];
    }

    private function getRecentExpenses()
    {
        return Expense::with(['user', 'approver'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getTopCategories($startDate, $endDate)
    {
        return Expense::selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();
    }

    private function getProfitLossData($startDate, $endDate)
    {
        // Get revenue from sales
        $revenue = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        // Get cost of goods sold
        $cogs = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->sum(DB::raw('sale_items.quantity * products.cost_price'));

        // Get total expenses
        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', '!=', 'rejected')
            ->sum('amount');

        // Get expenses by category
        $expensesByCategory = Expense::selectRaw('category, SUM(amount) as total')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', '!=', 'rejected')
            ->groupBy('category')
            ->get();

        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $totalExpenses;

        return [
            'revenue' => $revenue,
            'cost_of_goods_sold' => $cogs,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'gross_margin' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0,
            'net_margin' => $revenue > 0 ? ($netProfit / $revenue) * 100 : 0,
            'expenses_by_category' => $expensesByCategory
        ];
    }

    private function calculateGrowthPercentage($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }
}
