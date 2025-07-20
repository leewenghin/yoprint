<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Imports\productImport;
use App\Models\Import;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class ImportProductJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public $file;
    public $importId;

    public function __construct($file, $importId = null)
    {
        $this->file = $file;
        $this->importId = $importId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $import = null;

        try {
            // Get the import record if ID is provided
            if ($this->importId) {
                $import = Import::find($this->importId);

                if ($import) {
                    $import->markAsProcessing();
                }
            }

            // Create the product import instance with file path
            $productImport = new productImport($this->file);

            // If we have an import record, pass it to the productImport
            if ($import) {
                $productImport->setImport($import);
            }

            // Perform the import
            Excel::import($productImport, $this->file);

            // Don't mark as completed immediately for chunked/queued imports
            // The chunks will process in the background and completion will be
            // handled by monitoring the progress or using a different mechanism

            Log::info('Product import chunks queued successfully', [
                'file' => $this->file,
                'import_id' => $this->importId
            ]);
        } catch (Exception $e) {
            Log::error('Product import failed', [
                'file' => $this->file,
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark as failed if we have an import record
            if ($import) {
                $import->markAsFailed($e->getMessage());
            }

            // Re-throw the exception so the queue system knows the job failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        if ($this->importId) {
            $import = Import::find($this->importId);
            if ($import && !$import->isFailed()) {
                $import->markAsFailed($exception->getMessage());
            }
        }

        Log::error('ImportProductJob failed', [
            'file' => $this->file,
            'import_id' => $this->importId,
            'error' => $exception->getMessage()
        ]);
    }
}
