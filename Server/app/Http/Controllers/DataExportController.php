<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;

class DataExportController extends Controller
{
    // Export businesses as CSV or Excel
    public function exportBusinesses(Request $request)
    {
        $format = $request->query('format', 'csv');
        $businesses = Company::all(['id', 'name', 'category', 'owner_name', 'email', 'phone', 'status']);
        $filename = 'businesses.' . $format;
        $headers = [
            'Content-Type' => $format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename"
        ];
        if ($format === 'xlsx') {
            // Use a package like Maatwebsite\Excel in real implementation
            return response()->json(['error' => 'Excel export not implemented'], 501);
        }
        // CSV export
        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($businesses->first()->toArray()));
        foreach ($businesses as $biz) {
            fputcsv($output, $biz->toArray());
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return response($csv, 200, $headers);
    }

    // Export users as CSV or Excel
    public function exportUsers(Request $request)
    {
        $format = $request->query('format', 'csv');
        $users = User::with('company')->get()->map(function($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'business' => $u->company->name ?? '',
                'role' => $u->role->name ?? '',
                'status' => $u->status ?? 'active',
            ];
        });
        $filename = 'users.' . $format;
        $headers = [
            'Content-Type' => $format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename"
        ];
        if ($format === 'xlsx') {
            // Use a package like Maatwebsite\Excel in real implementation
            return response()->json(['error' => 'Excel export not implemented'], 501);
        }
        // CSV export
        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($users->first()));
        foreach ($users as $user) {
            fputcsv($output, $user);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return response($csv, 200, $headers);
    }
}
