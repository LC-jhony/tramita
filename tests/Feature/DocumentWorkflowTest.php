<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Area;
use App\Models\User;
use App\Services\DocumentDerivationService;
use App\Services\WorkflowService;
use App\Enums\WorkflowStageType;
use App\Enums\MovementType;
use App\Enums\Priority;
use App\Enums\DocumentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentDerivationService $derivationService;
    protected WorkflowService $workflowService;
    protected DocumentType $documentType;
    protected Document $document;
    protected Area $area1;
    protected Area $area2;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->workflowService = new WorkflowService();
        $this->derivationService = new DocumentDerivationService($this->workflowService);
        
        // Create test areas
        $this->area1 = Area::factory()->create(['name' => 'Mesa de Partes', 'status' => true]);
        $this->area2 = Area::factory()->create(['name' => 'Secretaría', 'status' => true]);
        
        $this->user = User::factory()->create(['area_id' => $this->area1->id]);
        
        // Create document type with workflow
        $this->documentType = DocumentType::factory()->create([
            'workflow' => [
                [
                    'id' => 0,
                    'name' => 'Recepción',
                    'type' => WorkflowStageType::RECEPTION->value,
                    'area_id' => $this->area1->id,
                    'required' => true,
                    'time_limit' => 1,
                    'order' => 0,
                ],
                [
                    'id' => 1,
                    'name' => 'Procesamiento',
                    'type' => WorkflowStageType::PROCESSING->value,
                    'area_id' => $this->area2->id,
                    'required' => true,
                    'time_limit' => 5,
                    'order' => 1,
                ],
            ]
        ]);
        
        $this->document = Document::factory()->create([
            'document_type_id' => $this->documentType->id,
            'status' => DocumentStatus::RECEIVED->value,
            'current_area_id' => $this->area1->id,
        ]);
        
        $this->actingAs($this->user);
    }

    public function test_can_derive_document_following_workflow()
    {
        $derivationData = [
            'to_area_id' => $this->area2->id,
            'movement_type' => MovementType::ACTION->value,
            'priority' => Priority::NORMAL->value,
            'observations' => 'Derivación para procesamiento',
        ];
        
        $movement = $this->derivationService->deriveDocument($this->document, $derivationData);
        
        $this->assertNotNull($movement);
        $this->assertEquals($this->area2->id, $movement->to_area_id);
        $this->assertEquals(MovementType::ACTION->value, $movement->movement_type);
    }

    public function test_cannot_derive_document_to_invalid_workflow_stage()
    {
        // Create another area not in the workflow
        $invalidArea = Area::factory()->create(['name' => 'Invalid Area', 'status' => true]);
        
        $derivationData = [
            'to_area_id' => $invalidArea->id,
            'movement_type' => MovementType::ACTION->value,
            'priority' => Priority::NORMAL->value,
            'observations' => 'Invalid derivation',
        ];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no está permitida desde la etapa actual');
        
        $this->derivationService->deriveDocument($this->document, $derivationData);
    }

    public function test_workflow_validation_checks_movement_type_compatibility()
    {
        // Try to use archive movement type for processing stage
        $derivationData = [
            'to_area_id' => $this->area2->id,
            'movement_type' => MovementType::ARCHIVE->value,
            'priority' => Priority::NORMAL->value,
            'observations' => 'Invalid movement type',
        ];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no es compatible con la etapa');
        
        $this->derivationService->deriveDocument($this->document, $derivationData);
    }

    public function test_can_get_workflow_suggestions()
    {
        $suggestions = $this->derivationService->getWorkflowSuggestions($this->document);
        
        $this->assertIsArray($suggestions);
        $this->assertCount(1, $suggestions); // Should suggest the processing stage
        
        $suggestion = $suggestions[0];
        $this->assertEquals('Procesamiento', $suggestion['stage']['name']);
        $this->assertEquals($this->area2->id, $suggestion['area']->id);
        $this->assertEquals(MovementType::ACTION, $suggestion['recommended_movement_type']);
    }

    public function test_auto_advance_workflow_when_enabled()
    {
        // Update workflow to enable auto-advance for reception stage
        $workflow = $this->documentType->workflow;
        $workflow[0]['auto_advance'] = true;
        $this->documentType->update(['workflow' => $workflow]);
        
        $movement = $this->derivationService->autoAdvanceWorkflow($this->document);
        
        $this->assertNotNull($movement);
        $this->assertEquals($this->area2->id, $movement->to_area_id);
    }

    public function test_auto_advance_returns_null_when_disabled()
    {
        // Auto-advance is disabled by default
        $movement = $this->derivationService->autoAdvanceWorkflow($this->document);
        
        $this->assertNull($movement);
    }

    public function test_document_without_workflow_allows_any_derivation()
    {
        // Create document type without workflow
        $documentTypeWithoutWorkflow = DocumentType::factory()->create(['workflow' => null]);
        $documentWithoutWorkflow = Document::factory()->create([
            'document_type_id' => $documentTypeWithoutWorkflow->id,
            'status' => DocumentStatus::RECEIVED->value,
            'current_area_id' => $this->area1->id,
        ]);
        
        $derivationData = [
            'to_area_id' => $this->area2->id,
            'movement_type' => MovementType::INFORMATION->value,
            'priority' => Priority::NORMAL->value,
            'observations' => 'Free derivation',
        ];
        
        $movement = $this->derivationService->deriveDocument($documentWithoutWorkflow, $derivationData);
        
        $this->assertNotNull($movement);
        $this->assertEquals($this->area2->id, $movement->to_area_id);
    }

    public function test_workflow_progress_tracking()
    {
        // Initially at reception stage
        $progress = $this->workflowService->getWorkflowProgress($this->document);
        $this->assertEquals(0, $progress['completed_count']);
        $this->assertEquals('Recepción', $progress['current_stage']['name']);
        
        // Derive to processing stage
        $derivationData = [
            'to_area_id' => $this->area2->id,
            'movement_type' => MovementType::ACTION->value,
            'priority' => Priority::NORMAL->value,
            'observations' => 'Moving to processing',
        ];
        
        $movement = $this->derivationService->deriveDocument($this->document, $derivationData);
        
        // Process the movement
        $this->derivationService->processDocument($movement);
        
        // Check progress after processing
        $this->document->refresh();
        $progress = $this->workflowService->getWorkflowProgress($this->document);
        
        $this->assertEquals(1, $progress['completed_count']);
        $this->assertEquals('Procesamiento', $progress['current_stage']['name']);
        $this->assertEquals(50.0, $progress['progress_percentage']); // 1 of 2 stages completed
    }
}
