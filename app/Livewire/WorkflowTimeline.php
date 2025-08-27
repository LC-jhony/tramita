<?php

namespace App\Livewire;

use App\Models\Document;
use App\Services\WorkflowService;
use Livewire\Component;

class WorkflowTimeline extends Component
{
    public Document $document;
    public array $workflowProgress = [];
    public ?array $currentStage = null;

    protected WorkflowService $workflowService;

    public function mount(Document $document)
    {
        $this->document = $document;
        $this->workflowService = app(WorkflowService::class);
        $this->loadWorkflowProgress();
    }

    public function loadWorkflowProgress()
    {
        $this->workflowProgress = $this->workflowService->getWorkflowProgress($this->document);
        $this->currentStage = $this->workflowProgress['current_stage'] ?? null;
    }

    public function render()
    {
        return view('livewire.workflow-timeline');
    }
}
