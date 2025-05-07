<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use App\Models\User;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\NumberFormatter;
use App\Models\ConsolidateShipment;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function dashboardStats()
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        if($user->hasRole('superadministrator')) {
            $totalincome = Transaction::where('status', 'success')->sum('amount');
            $userCount = User::count();
            $recentTransactions = Transaction::latest()->take(10)->get();
            $plans = Plan::count();
            $branches = Branch::count();
            // $myCustomers = Customer::where('branch_id', $branchId)->count();
            // $myDrivers = Driver::where('branch_id', $branchId)->count();
            // $totalBiz = $myCustomers+$myDrivers;

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'totalincome' => NumberFormatter::formatCount($totalincome),
                    'userCount' => $userCount,
                    'recentTransactions' => $recentTransactions,
                    'plans' => $plans
                ]
            ]);
        }
        elseif($user->hasRole('businessadministrator')) {

            $branch = $user->branch;

            // Ensure the branch exists before calling relationships
            if ($branch) {
                $totalCustomers = $branch->customer()->count();
                $totalDrivers = $branch->driver()->count();
                $userCount = $totalCustomers + $totalDrivers;
                $myShipments = Shipment::where('branch_id', $branchId)->count();
                $myConsolidated = ConsolidateShipment::where('branch_id', $branchId)->count();
            } else {
                $totalCustomers = 0;
                $totalDrivers = 0;
                $userCount = 0;
            }
            $recentTransactions = Transaction::where('user_id', $user->id)->latest()->take(10)->get();
            $cardTransactions = Transaction::where('user_id', $user->id)
            ->where('status', 'success')
            ->sum('amount');
        
            $plans = Plan::count();

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'userCount' => $userCount,
                    'recentTransactions' => $recentTransactions,
                    'myShipments' => $myShipments,
                    'myConsolidated' => $myConsolidated,
                    'cardTransactions' => $cardTransactions
                ]
            ]);
        }elseif($user->hasRole('driver')) {
            // When you need the totals later:
            // $totals = ShipmentExpense::where('shipment_id', $shipment->id)
            // ->selectRaw('SUM(amount) as expense_total, SUM(credit_reimbursement_amount) as credit_total')
            // ->first();
            // $net_total = $totals->expense_total - $totals->credit_total;


            
            $myShipments = Shipment::where('branch_id', $branchId)
            ->where('driver_id', $user->driver->id)
            ->count();
            $myConsolidated = ConsolidateShipment::where('branch_id', $branchId)
            ->where('driver_id', $user->driver->id)
            ->count();
        }
        
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
        $result = [];
        
        // Create all 12 months first with zero values
        for ($i = 0; $i < 12; $i++) {
            $date = $startDate->copy()->addMonths($i);
            $year = $date->year;
            $month = $date->month;
            
            $result[$year.'-'.$month] = [
                'year' => $year,
                'month' => $month,
                'month_name' => $date->format('M'),
                'sales' => 0,
                'revenue' => 0,
            ];
        }
        
        // Fill the data for existing months
        foreach ($monthlyData as $data) {
            $key = $data->year.'-'.$data->month;
            if (isset($result[$key])) {
                $result[$key]['sales'] = (int)$data->transaction_count;
                $result[$key]['revenue'] = (float)$data->total_amount;
            }
        }
        
        // Extract the ordered values for response
        $orderedResult = array_values($result);
        $sales = array_column($orderedResult, 'sales');
        $revenue = array_column($orderedResult, 'revenue');
        $monthNames = array_column($orderedResult, 'month_name');
        
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
                ],
                'detailed_data' => $orderedResult // Optional: include detailed data for debugging
            ]
        ]);
    }
}
