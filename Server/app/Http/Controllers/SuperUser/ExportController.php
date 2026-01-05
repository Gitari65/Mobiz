<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SuperUser\GenerateExportJob;
use App\Models\DataExport;

class ExportController extends Controller
{
    // POST /api/super/exports - enqueue export job
    public function store(Request $request)
    {
        $payload = $request->validate([
            'type' => 'required|in:businesses,transactions,products,subscriptions,audit_logs',
            'format' => 'required|in:csv,xlsx,pdf',
            'filters' => 'nullable|array'
        ]);

        $exportId = Str::uuid()->toString();
        $filename = "{$payload['type']}_{$exportId}.{$payload['format']}";
        DataExport::create([
            'id' => $exportId,
            'type' => $payload['type'],
            'format' => $payload['format'],
            'status' => 'pending',
            'requested_by' => auth()->id(),
            'filename' => $filename,
            'filters' => $payload['filters'] ?? []
        ]);

        GenerateExportJob::dispatch($exportId);
        return response()->json(['export_id' => $exportId], 202);
    }
    // GET /api/super/exports/{exportId} - get export status or download link
    public function show($exportId)
    {
        $export = DataExport::findOrFail($exportId);
        if ($export->status === 'completed') {
            $url = Storage::disk('exports')->url($export->filename);
            return response()->json([
                'status' => $export->status,
                'download_url' => $url
            ]);
        }
        return response()->json(['status' => $export->status]);
    }
}

