<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use App\Models\User;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Shipment;
use App\Models\Settlement;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\NumberFormatter;
use Illuminate\Support\Facades\DB;
use App\Models\ConsolidateShipment;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function dashboardStats()
    {
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
        if($user->hasRole('superadministrator')) {
            $totalincome = Transaction::where('status', 'success')->sum('amount');
            $userCount = User::count();
            $Totalsubsribers = Branch::where('isSubscribed', 1)->count();
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
                    'branches' => $branches,
                    'Totalsubsribers' => $Totalsubsribers,
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
           // $recentTransactions = Transaction::where('user_id', $user->id)->latest()->take(10)->get();
           // Get recent shipments (assuming they have a created_at field)
            $shipments = $branch->shipment()
            ->select('id', 'created_at', 'shipment_status', 'total_fuel_cost', DB::raw("'shipment' as type"))
            ->latest()
            ->take(10);

            // Get recent consolidated shipments
            // $consolidatedShipments = $branch->consolidateShipment()
            // ->select('id', 'created_at', 'status', 'total_fuel_cost', DB::raw("'consolidated' as type"))
            // ->latest()
            // ->take(10);

            // Combine and sort the results
            $recentTransactions = Shipment::where('branch_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
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
            // $shipmentRevenue = Shipment::where('driver_id', $user->driver->id)->sum('net_total_charges');
            // $consolidatedRevenue = ConsolidateShipment::where('driver_id', $user->driver->id)->sum('total_shipping_cost');
            // $grandFuelCost = Shipment::where('driver_id', $user->driver->id)->sum('total_fuel_cost');
            //$totalRevenue = $shipmentRevenue + $consolidatedRevenue + $grandFuelCost;
            $totalRevenue = Settlement::where('driver_id', $user->driver->id)->sum('net_total_payments');
    
        // Count of shipments
        $shipmentCount = Shipment::where('driver_id', $user->driver->id)->count();
        $consolidatedCount = ConsolidateShipment::where('driver_id', $user->driver->id)->count();
        $deliveryCount = Delivery::where('driver_id', $user->driver->id)->count();

        // $recentTransactions = Shipment::where('created_by_driver_id', $user->creatorDriver->id)
        // ->orderBy('created_at', 'desc')
        // ->take(10);
         $recentTransactions = Shipment::where('driver_id', $user->driver->id)
        ->orderBy('created_at', 'desc')
        ->take(10)
        ->get();
    
        
        // Return response
         return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'myShipmentCount' => $shipmentCount,
                    'myConsolidatedCount' => $consolidatedCount,
                    'myDeliveryCount' => $deliveryCount,
                    // 'grand_fuel_cost' => $grandFuelCost,
                    // 'grand_expense_total' => $grandExpenseTotal,
                    // 'grand_charges_total' => $grandChargesTotal,
                    'grand_total_amount' => $totalRevenue,
                    'recentTransactions' => $recentTransactions
                ]
            ]);
            
            // When you need the totals later:
            // $totals = ShipmentExpense::where('shipment_id', $shipment->id)
            // ->selectRaw('SUM(amount) as expense_total, SUM(credit_reimbursement_amount) as credit_total')
            // ->first();
            // $net_total = $totals->expense_total - $totals->credit_total;


            
            // $myShipments = Shipment::where('branch_id', $branchId)
            // ->where('created_by_driver_id', $user->driver->id)
            // ->count();
            // $myConsolidated = ConsolidateShipment::where('branch_id', $branchId)
            // ->where('created_by_driver_id', $user->driver->id)
            // ->count();
        }
        elseif($user->hasRole('customer')) {
           //dd($user->customer->id);
        $shipmentCount = Shipment::where('customer_id', $user->customer->id)->count();
        $consolidatedCount = ConsolidateShipment::where('customer_id', $user->customer->id)->count();
        //dd($shipmentCount, $consolidatedCount);
          
            
        $recentTransactions = Shipment::where('customer_id', $user->customer->id)
        ->orderBy('created_at', 'desc')
        ->take(10);
    
        // Totals
       $shipmentRevenue = Shipment::where('customer_id', $user->customer->id)->sum('net_total_charges');
            $consolidatedRevenue = ConsolidateShipment::where('customer_id', $user->customer->id)->sum('total_shipping_cost');
            $grandFuelCost = Shipment::where('customer_id', $user->customer->id)->sum('total_fuel_cost');
            $totalRevenue = $shipmentRevenue + $consolidatedRevenue + $grandFuelCost;
    
    
        // Return response
          return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'myShipmentCount' => $shipmentCount,
                    'myConsolidatedCount' => $consolidatedCount,
                    // 'grand_fuel_cost' => $grandFuelCost,
                    // 'grand_expense_total' => $grandExpenseTotal,
                    // 'grand_charges_total' => $grandChargesTotal,
                    'grand_total_amount' => $totalRevenue,
                    'recentTransactions' => $recentTransactions
                ]
            ]);
        // return response()->json([
        //     'myShipmentCount' => $shipmentCount,
        //     'myConsolidatedCount' => $consolidatedCount,
        //     // 'grand_fuel_cost' => $grandFuelCost,
        //     // 'grand_expense_total' => $grandExpenseTotal,
        //     // 'grand_charges_total' => $grandChargesTotal,
        //     'grand_total_amount' => $grandTotalAmount,
        //     'recentTransactions' => $recentTransactions
        // ]);
            
           
        }
        
    }


    public function countBranches()
    {
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
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
         $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
        if ($user->hasRole('superadministrator')) {
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
                'detailed_data' => $orderedResult
            ]
        ]);
    }elseif ($user->hasRole('businessadministrator') || $user->hasRole('customer') || $user->hasRole('driver')) {
        // Get current date and calculate start date (12 months ago)
        $currentDate = now();
        $startDate = $currentDate->copy()->subMonths(11)->startOfMonth();
        
        // Initialize query based on role
        $shipmentQuery = Shipment::query();
        $consolidatedQuery = ConsolidateShipment::query();
        
        if ($user->hasRole('businessadministrator')) {
            $shipmentQuery->where('branch_id', $branchId);
            $consolidatedQuery->where('branch_id', $branchId);
        } elseif ($user->hasRole('customer')) {
            $shipmentQuery->where('customer_id', $user->customer->id);
            $consolidatedQuery->where('customer_id', $user->customer->id);
        } elseif ($user->hasRole('driver')) {
            $shipmentQuery->where('driver_id', $user->driver->id);
            $consolidatedQuery->where('driver_id', $user->driver->id);
        }
        
        // Get shipment data
        $shipmentData = $shipmentQuery
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                EXTRACT(YEAR FROM created_at) as year,
                EXTRACT(MONTH FROM created_at) as month,
                COUNT(*) as shipment_count
            ')
            ->groupByRaw('EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
        
        // Get consolidated shipment data
        $consolidatedData = $consolidatedQuery
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                EXTRACT(YEAR FROM created_at) as year,
                EXTRACT(MONTH FROM created_at) as month,
                COUNT(*) as consolidated_count
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
                'shipments' => 0,
                'consolidated' => 0,
                'total_shipments' => 0,
            ];
        }
        
        // Fill the data for existing months from shipments
        foreach ($shipmentData as $data) {
            $key = $data->year.'-'.$data->month;
            if (isset($result[$key])) {
                $result[$key]['shipments'] = (int)$data->shipment_count;
                $result[$key]['total_shipments'] += (int)$data->shipment_count;
            }
        }
        
        // Fill the data for existing months from consolidated shipments
        foreach ($consolidatedData as $data) {
            $key = $data->year.'-'.$data->month;
            if (isset($result[$key])) {
                $result[$key]['consolidated'] = (int)$data->consolidated_count;
                $result[$key]['total_shipments'] += (int)$data->consolidated_count;
            }
        }
        
        // Extract the ordered values for response
        $orderedResult = array_values($result);
        $shipments = array_column($orderedResult, 'shipments');
        $consolidated = array_column($orderedResult, 'consolidated');
        $totalShipments = array_column($orderedResult, 'total_shipments');
        $monthNames = array_column($orderedResult, 'month_name');
        
        // Calculate totals
        $totalAllShipments = array_sum($shipments) + array_sum($consolidated);
        
        // For businessadministrator, add invoice data if needed
        $additionalData = [];
        if ($user->hasRole('businessadministrator')) {
            $invoiceData = Invoice::where('branch_id', $branchId)
                ->where('status', 'paid')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    EXTRACT(YEAR FROM created_at) as year,
                    EXTRACT(MONTH FROM created_at) as month,
                    SUM(net_total) as total_amount
                ')
                ->groupByRaw('EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
            
            // Initialize revenue data
            foreach ($result as $key => $value) {
                $result[$key]['revenugit e'] = 0;
            }
            
            // Fill revenue data
            foreach ($invoiceData as $data) {
                $key = $data->year.'-'.$data->month;
                if (isset($result[$key])) {
                    $result[$key]['revenue'] = (float)$data->total_amount;
                }
            }
            
            $revenue = array_column($orderedResult, 'revenue');
            $totalRevenue = array_sum($revenue);
            
            $additionalData = [
                'revenue_series' => [
                    'name' => 'Revenue',
                    'data' => $revenue
                ],
                'totals' => [
                    'revenue' => $totalRevenue,
                    'formatted_revenue' => NumberFormatter::formatCount($totalRevenue)
                ]
            ];
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'series' => [
                    [
                        'name' => 'Shipments',
                        'data' => $shipments
                    ],
                    [
                        'name' => 'Consolidated',
                        'data' => $consolidated
                    ],
                    [
                        'name' => 'Total Shipments',
                        'data' => $totalShipments
                    ]
                ],
                'categories' => $monthNames,
                'totals' => [
                    'shipments' => array_sum($shipments),
                    'consolidated' => array_sum($consolidated),
                    'total_shipments' => $totalAllShipments
                ],
                'detailed_data' => $orderedResult,
                ...$additionalData
            ]
        ]);
    }
    } 
}
