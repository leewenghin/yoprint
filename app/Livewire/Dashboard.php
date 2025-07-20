<?php

namespace App\Livewire;

use Livewire\Attributes\Validate;
use App\Jobs\ImportProductJob;
use App\Models\Import;
use Livewire\Component;
use Livewire\WithFileUploads;

class Dashboard extends Component
{
    use WithFileUploads;
    #[Validate('file|mimes:csv,txt|max:51200')]

    public $file;
    public $jobs;
    public $stats;

    public function mount()
    {
        $this->refreshJobs();
    }

    public function updatedFile()
    {
        // Create an import record first
        $import = Import::createNew($this->file->getClientOriginalName());

        // Dispatch the job with the import ID for tracking
        ImportProductJob::dispatch($this->file->getRealPath(), $import->id);

        // Refresh the jobs list
        $this->refreshJobs();

        session()->flash('success', 'The file is being processed. You can track its progress below.');

        // Reset the file input
        $this->file = null;
    }

    public function refreshJobs()
    {
        $this->jobs = Import::orderBy('created_at', 'desc')->get();

        // Calculate statistics
        $this->stats = [
            'total' => $this->jobs->count(),
            'pending' => $this->jobs->where('status', Import::STATUS_PENDING)->count(),
            'processing' => $this->jobs->where('status', Import::STATUS_PROCESSING)->count(),
            'completed' => $this->jobs->where('status', Import::STATUS_COMPLETED)->count(),
            'failed' => $this->jobs->where('status', Import::STATUS_FAILED)->count(),
        ];
    }

    public function render()
    {
        // Auto-refresh jobs data on every render for real-time updates
        $this->refreshJobs();

        return view('livewire.dashboard');
    }
}
