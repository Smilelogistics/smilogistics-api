<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\NumberFormatter;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function countBusinesses()
    {
        $user = auth()->user();
    }

    public function countBranches()
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        if($user->hasRole('superadministrator')){
            $branches = Branch::all()->count();
            return response()->json([
                'status' => 'success',
                'message' => 'Branches retrieved successfully',
                'branches' => NumberFormatter::formatCount($branches),
            ]);
        }
        else{
            $branches = Customer::where('branch_id', $branchId)->count();
            return response()->json([
                'status' => 'success',
                'message' => 'Branches retrieved successfully',
                'branches' => NumberFormatter::formatCount($branches),
            ]);
        }
    }
    public function transactions()
    {
        $user = auth()->user();
        if ($user->hasRole('superadministrator')) {
            $transactions = Transaction::all()->paginate(15)->latest();

            return response()->json([
                'status' => 'success',
                'message' => 'Transactions retrieved successfully',
                'transactions' => NumberFormatter::formatCount($transactions),
            ]);
        }
        else{
            $transactions = Transaction::with('user')->paginate(15)->latest();

            return response()->json([
                'status' => 'success',
                'message' => 'Transactions retrieved successfully',
                'transactions' => NumberFormatter::formatCount($transactions),
            ]);
        }
    }

    public function totalIncome()
    {
        $totalincome = Transaction::where('status', 'success')->sum('amount');

        return response()->json([
            'status' => 'success',
            'message' => 'Total income retrieved successfully',
            'totalincome' => NumberFormatter::formatCount($totalincome),
        ]);
    }



    // public function monthlyIncome()
    // {
    //     // Get current date and calculate start date (12 months ago)
    //     $currentDate = now();
    //     $startDate = $currentDate->copy()->subMonths(11)->startOfMonth(); // 11 months + current month = 12
        
    //     // Query to get monthly sums
    //     $monthlyData = Transaction::where('status', 'success')
    //         ->where('created_at', '>=', $startDate)
    //         ->selectRaw('
    //             YEAR(created_at) as year,
    //             MONTH(created_at) as month,
    //             SUM(amount) as total_amount,
    //             COUNT(*) as transaction_count
    //         ')
    //         ->groupBy('year', 'month')
    //         ->orderBy('year', 'asc')
    //         ->orderBy('month', 'asc')
    //         ->get();
        
    //     // Format the data with month names and ensure all 12 months are represented
    //     $result = [];
    //     $currentYear = $currentDate->year;
    //     $currentMonth = $currentDate->month;
        
    //     for ($i = 0; $i < 12; $i++) {
    //         $date = $startDate->copy()->addMonths($i);
    //         $year = $date->year;
    //         $month = $date->month;
    //         $monthName = $date->format('F Y');
            
    //         $monthRecord = $monthlyData->firstWhere(function ($item) use ($year, $month) {
    //             return $item->year == $year && $item->month == $month;
    //         });
            
    //         $result[] = [
    //             'year' => $year,
    //             'month' => $month,
    //             'month_name' => $monthName,
    //             'total_amount' => $monthRecord ? $monthRecord->total_amount : 0,
    //             'transaction_count' => $monthRecord ? $monthRecord->transaction_count : 0,
    //             'formatted_amount' => NumberFormatter::formatCount($monthRecord->total_amount ?? 0),
    //         ];
    //     }
        
    //     // Calculate overall total
    //     $totalAmount = $monthlyData->sum('total_amount');
        
    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Monthly income retrieved successfully',
    //         'data' => $result,
    //         'total_amount' => $totalAmount,
    //         'formatted_total_amount' => NumberFormatter::formatCount($totalAmount),
    //     ]);
    // }






    // public function monthlyIncome()
    // {
    //     $currentDate = now();
    //     $startDate = $currentDate->copy()->subMonths(11)->startOfMonth();

    //     // PostgreSQL-compatible query
    //     $monthlyData = Transaction::where('status', 'pending')
    //         ->where('created_at', '>=', $startDate)
    //         ->selectRaw('
    //             EXTRACT(YEAR FROM created_at) as year,
    //             EXTRACT(MONTH FROM created_at) as month,
    //             SUM(amount) as total_amount,
    //             COUNT(*) as transaction_count
    //         ')
    //         ->groupByRaw('EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)')
    //         ->orderBy('year', 'asc')
    //         ->orderBy('month', 'asc')
    //         ->get();

    //     // Format the data (same as before)
    //     $result = [];
    //     for ($i = 0; $i < 12; $i++) {
    //         $date = $startDate->copy()->addMonths($i);
    //         $year = $date->year;
    //         $month = $date->month;
            
    //         $monthRecord = $monthlyData->firstWhere(function ($item) use ($year, $month) {
    //             return $item->year == $year && $item->month == $month;
    //         });
            
    //         $result[] = [
    //             'year' => $year,
    //             'month' => $month,
    //             'month_name' => $date->format('F Y'),
    //             'total_amount' => $monthRecord ? $monthRecord->total_amount : 0,
    //             'transaction_count' => $monthRecord ? $monthRecord->transaction_count : 0,
    //             'formatted_amount' => NumberFormatter::formatCount($monthRecord->total_amount ?? 0),
    //         ];
    //     }

    //     $totalAmount = $monthlyData->sum('total_amount');
        
    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Monthly income retrieved successfully',
    //         'data' => $result,
    //         'total_amount' => $totalAmount,
    //         'formatted_total_amount' => NumberFormatter::formatCount($totalAmount),
    //     ]);
    // }


    public function monthlyIncome()
{
    // Get current date and calculate start date (12 months ago)
    $currentDate = now();
    $startDate = $currentDate->copy()->subMonths(11)->startOfMonth(); // 11 months + current month = 12
    
    // PostgreSQL-compatible query
    $monthlyData = Transaction::where('status', 'success')
        ->where('created_at', '>=', $startDate)
        ->selectRaw('
            EXTRACT(YEAR FROM created_at) as year,
            EXTRACT(MONTH FROM created_at) as month,
            SUM(amount) as total_amount,
            COUNT(*) as transaction_count
        ')
        ->groupByRaw('EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();
    
    // Initialize arrays with default values (0) for all 12 months
    $sales = array_fill(0, 12, 0);
    $revenue = array_fill(0, 12, 0);
    $monthNames = [];
    
    // Fill the data for existing months
    foreach ($monthlyData as $data) {
        $monthIndex = (int)$data->month - 1; // Convert to 0-based index
        $sales[$monthIndex] = (int)$data->transaction_count;
        $revenue[$monthIndex] = (float)$data->total_amount;
    }
    
    // Generate month names (short format)
    for ($i = 0; $i < 12; $i++) {
        $monthNames[] = $startDate->copy()->addMonths($i)->format('M');
    }
    
    // Calculate totals
    $totalSales = array_sum($sales);
    $totalRevenue = array_sum($revenue);
    
    return response()->json([
        'status' => 'success',
        'data' => [
            'series' => [
                [
                    'name' => 'Sales',
                    'data' => $sales
                ],
                [
                    'name' => 'Revenue',
                    'data' => $revenue
                ]
            ],
            'categories' => $monthNames,
            'totals' => [
                'sales' => $totalSales,
                'revenue' => $totalRevenue,
                'formatted_revenue' => NumberFormatter::formatCount($totalRevenue)
            ]
        ]
    ]);
}
}
