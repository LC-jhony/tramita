<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Area;
use App\Models\User;
use App\Services\WorkflowService;
use App\Enums\WorkflowStageType;
use App\Enums\DocumentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowService $workflowService;
    protected DocumentType $documentType;
    protected Document $document;
    protected Area $area;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->workflowService = new WorkflowService();
        
        // Create test data
        $this->area = Area::factory()->create(['status' => true]);
        $this->user = User::factory()->create(['area_id' => $this->area->id]);
        
        $this->documentType = DocumentType::factory()->create([
            'workflow' => [
                [
                    'id' => 0,
                    'name' => 'Recepción',
                    'type' => WorkflowStageType::RECEPTION->value,
                    'area_id' => $this->area->id,
                    'required' => true,
                    'time_limit' => 1,
                    'order' => 0,
                ],
                [
                    'id' => 1,
                    'name' => 'Revisión',
                    'type' => WorkflowStageType::REVIEW->value,
                    'area_id' => $this->area->id,
                    'required' => true,
                    'time_limit' => 5,
                    'order' => 1,
                ],
                [
                    'id' => 2,
                    'name' => 'Aprobación',
                    'type' => WorkflowStageType::APPROVAL->value,
                    'area_id' => $this->area->id,
                    'required' => false,
                    'time_limit' => 3,
                    'order' => 2,
                ],
            ]
        ]);
        
        $this->document = Document::factory()->create([
            'document_type_id' => $this->documentType->id,
            'status' => DocumentStatus::RECEIVED->value,
        ]);
    }

    public function test_can_get_current_stage_for_new_document()
    {
        $currentStage = $this->workflowService->getCurrentStage($this->document);
        
        $this->assertNotNull($currentStage);
        $this->assertEquals('Recepción', $currentStage['name']);
        $this->assertEquals(WorkflowStageType::RECEPTION->value, $currentStage['type']);
    }

    public function test_can_get_next_stages()
    {
        $nextStages = $this->workflowService->getNextStages($this->document);
        
        $this->assertCount(2, $nextStages); // Should have Revisión and Aprobación
        $this->assertEquals('Revisión', $nextStages->first()['name']);
    }

    public function test_can_check_if_can_advance_to_stage()
    {
        $stages = $this->documentType->getWorkflowStages();
        $reviewStage = collect($stages)->firstWhere('name', 'Revisión');
        
        $canAdvance = $this->workflowService->canAdvanceToStage($this->document, $reviewStage);
        
        $this->assertTrue($canAdvance);
    }

    public function test_cannot_advance_to_non_sequential_required_stage()
    {
        $stages = $this->documentType->getWorkflowStages();
        $approvalStage = collect($stages)->firstWhere('name', 'Aprobación');
        
        // Should not be able to skip to approval without going through review first
        $canAdvance = $this->workflowService->canAdvanceToStage($this->document, $approvalStage);
        
        $this->assertFalse($canAdvance);
    }

    public function test_can_get_workflow_progress()
    {
        $progress = $this->workflowService->getWorkflowProgress($this->document);
        
        $this->assertIsArray($progress);
        $this->assertArrayHasKey('current_stage', $progress);
        $this->assertArrayHasKey('completed_stages', $progress);
        $this->assertArrayHasKey('remaining_stages', $progress);
        $this->assertArrayHasKey('progress_percentage', $progress);
        $this->assertArrayHasKey('total_stages', $progress);
        
        $this->assertEquals(3, $progress['total_stages']);
        $this->assertEquals(0, $progress['completed_count']);
        $this->assertEquals(0, $progress['progress_percentage']);
    }

    public function test_can_check_if_workflow_is_complete()
    {
        $isComplete = $this->workflowService->isWorkflowComplete($this->document);
        
        $this->assertFalse($isComplete);
    }

    public function test_can_get_workflow_statistics()
    {
        $statistics = $this->workflowService->getWorkflowStatistics($this->documentType);
        
        $this->assertIsArray($statistics);
        $this->assertArrayHasKey('total_documents', $statistics);
        $this->assertArrayHasKey('completed_workflows', $statistics);
        $this->assertArrayHasKey('in_progress', $statistics);
        $this->assertArrayHasKey('overdue', $statistics);
        
        $this->assertEquals(1, $statistics['total_documents']);
        $this->assertEquals(0, $statistics['completed_workflows']);
        $this->assertEquals(1, $statistics['in_progress']);
    }

    public function test_returns_null_for_document_without_workflow()
    {
        $documentTypeWithoutWorkflow = DocumentType::factory()->create(['workflow' => null]);
        $documentWithoutWorkflow = Document::factory()->create([
            'document_type_id' => $documentTypeWithoutWorkflow->id,
        ]);
        
        $currentStage = $this->workflowService->getCurrentStage($documentWithoutWorkflow);
        
        $this->assertNull($currentStage);
    }

    public function test_can_estimate_completion_date()
    {
        $progress = $this->workflowService->getWorkflowProgress($this->document);
        
        $this->assertNotNull($progress['estimated_completion']);
        
        // Should be approximately 9 days from now (1+5+3 days for the three stages)
        $estimatedDate = \Carbon\Carbon::parse($progress['estimated_completion']);
        $expectedDate = now()->addDays(9);
        
        $this->assertTrue($estimatedDate->diffInDays($expectedDate) <= 1);
    }
}
