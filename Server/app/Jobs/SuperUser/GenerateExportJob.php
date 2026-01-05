<?php
namespace App\Jobs\SuperUser;

use App\Models\DataExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class GenerateExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $export;

    public function __construct(DataExport $export)
    {
        $this->export = $export;
    }

    public function handle()
    {
        try {
            $this->export->update(['status' => 'processing']);

            $data = $this->fetchData();
            $path = $this->saveExport($data);

            $this->export->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        } catch (\Exception $e) {
            $this->export->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }

    private function fetchData()
    {
        $type = $this->export->type;
        $filters = $this->export->filters ?? [];

        switch ($type) {
            case 'businesses':
                return \App\Models\Company::all()->toArray();
            case 'products':
                return \App\Models\Product::all()->toArray();
            case 'audit_logs':
                return \App\Models\AuditLog::all()->toArray();
            case 'transactions':
                return \App\Models\Sale::all()->toArray();
            case 'subscriptions':
                return \App\Models\Subscription::all()->toArray();
            default:
                return [];
        }
    }

    private function saveExport($data)
    {
        if (empty($data)) {
            $data = [['No data found']];
        }

        $csv = Writer::createFromString('');
        $csv->insertOne(array_keys($data[0] ?? []));
        foreach ($data as $row) {
            $csv->insertOne($row);
        }

        $dir = 'exports';
        if (!Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }

        $path = "$dir/{$this->export->filename}";
        Storage::disk('public')->put($path, (string) $csv);

        return $path;
    }
}
