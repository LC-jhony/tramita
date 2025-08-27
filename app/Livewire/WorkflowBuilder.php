<?php

namespace App\Livewire;

use App\Models\WorkflowTemplate;
use App\Models\Area;
use Livewire\Component;

class WorkflowBuilder extends Component
{
    public $workflowId = null;
    public $steps = [];
    public $connections = [];
    public $rules = [];
    public $selectedStep = null;
    public $canvasWidth = 1200;
    public $canvasHeight = 800;
    public $zoom = 1;
    public $panX = 0;
    public $panY = 0;

    protected $listeners = [
        'stepMoved' => 'updateStepPosition',
        'stepSelected' => 'selectStep',
        'connectionCreated' => 'addConnection',
        'stepDeleted' => 'deleteStep',
        'saveWorkflow' => 'save'
    ];

    public function mount($workflowId = null)
    {
        $this->workflowId = $workflowId;
        
        if ($workflowId) {
            $this->loadWorkflow($workflowId);
        } else {
            $this->initializeEmptyWorkflow();
        }
    }

    public function loadWorkflow($workflowId)
    {
        $template = WorkflowTemplate::find($workflowId);
        
        if ($template) {
            $config = $template->config;
            $this->steps = $config['steps'] ?? [];
            $this->connections = $config['connections'] ?? [];
            $this->rules = $config['rules'] ?? [];
        }
    }

    public function initializeEmptyWorkflow()
    {
        $this->steps = [
            [
                'id' => 'start',
                'name' => 'Inicio',
                'type' => 'start',
                'position' => ['x' => 100, 'y' => 200],
                'auto_assign' => true,
                'required' => true
            ],
            [
                'id' => 'end',
                'name' => 'Fin',
                'type' => 'end',
                'position' => ['x' => 800, 'y' => 200],
                'auto_complete' => true,
                'required' => true
            ]
        ];
        
        $this->connections = [
            ['from' => 'start', 'to' => 'end']
        ];
    }

    public function addStep($type = 'task')
    {
        $stepId = 'step_' . uniqid();
        
        $newStep = [
            'id' => $stepId,
            'name' => $this->getDefaultStepName($type),
            'type' => $type,
            'position' => ['x' => 400, 'y' => 200],
            'time_limit' => 5,
            'required' => true,
            'auto_assign' => false,
            'auto_complete' => false,
            'parallel' => false,
            'assigned_area_id' => null,
            'description' => ''
        ];

        $this->steps[] = $newStep;
        $this->selectedStep = $stepId;
    }

    public function deleteStep($stepId)
    {
        // Don't allow deleting start or end steps
        if (in_array($stepId, ['start', 'end'])) {
            return;
        }

        // Remove step
        $this->steps = array_filter($this->steps, fn($step) => $step['id'] !== $stepId);
        
        // Remove connections involving this step
        $this->connections = array_filter($this->connections, function($conn) use ($stepId) {
            return $conn['from'] !== $stepId && $conn['to'] !== $stepId;
        });

        if ($this->selectedStep === $stepId) {
            $this->selectedStep = null;
        }
    }

    public function selectStep($stepId)
    {
        $this->selectedStep = $stepId;
    }

    public function updateStepPosition($stepId, $x, $y)
    {
        foreach ($this->steps as &$step) {
            if ($step['id'] === $stepId) {
                $step['position'] = ['x' => $x, 'y' => $y];
                break;
            }
        }
    }

    public function updateSelectedStep($field, $value)
    {
        if (!$this->selectedStep) return;

        foreach ($this->steps as &$step) {
            if ($step['id'] === $this->selectedStep) {
                $step[$field] = $value;
                break;
            }
        }
    }

    public function addConnection($fromStepId, $toStepId)
    {
        // Check if connection already exists
        $exists = collect($this->connections)->contains(function($conn) use ($fromStepId, $toStepId) {
            return $conn['from'] === $fromStepId && $conn['to'] === $toStepId;
        });

        if (!$exists) {
            $this->connections[] = [
                'from' => $fromStepId,
                'to' => $toStepId,
                'condition' => null
            ];
        }
    }

    public function removeConnection($index)
    {
        unset($this->connections[$index]);
        $this->connections = array_values($this->connections);
    }

    public function addRule()
    {
        $this->rules[] = [
            'condition' => '',
            'action' => 'skip_step',
            'target' => '',
            'value' => ''
        ];
    }

    public function removeRule($index)
    {
        unset($this->rules[$index]);
        $this->rules = array_values($this->rules);
    }

    public function loadTemplate($templateKey)
    {
        $templates = WorkflowTemplate::getSystemTemplates();
        
        if (isset($templates[$templateKey])) {
            $template = $templates[$templateKey];
            $this->steps = $template['steps'] ?? [];
            $this->connections = $template['connections'] ?? [];
            $this->rules = $template['rules'] ?? [];
            $this->selectedStep = null;
        }
    }

    public function validateWorkflow()
    {
        $errors = [];
        
        // Check for start step
        if (!collect($this->steps)->contains('type', 'start')) {
            $errors[] = 'El workflow debe tener un paso de inicio';
        }
        
        // Check for end step
        if (!collect($this->steps)->contains('type', 'end')) {
            $errors[] = 'El workflow debe tener un paso de finalización';
        }
        
        // Check for duplicate IDs
        $stepIds = collect($this->steps)->pluck('id');
        if ($stepIds->count() !== $stepIds->unique()->count()) {
            $errors[] = 'Los pasos no pueden tener IDs duplicados';
        }
        
        // Validate connections
        foreach ($this->connections as $connection) {
            if (!$stepIds->contains($connection['from'])) {
                $errors[] = "Conexión inválida: paso '{$connection['from']}' no existe";
            }
            if (!$stepIds->contains($connection['to'])) {
                $errors[] = "Conexión inválida: paso '{$connection['to']}' no existe";
            }
        }
        
        return $errors;
    }

    public function save($name, $description = '')
    {
        $errors = $this->validateWorkflow();
        
        if (!empty($errors)) {
            session()->flash('error', 'Errores de validación: ' . implode(', ', $errors));
            return;
        }

        $config = [
            'steps' => $this->steps,
            'connections' => $this->connections,
            'rules' => $this->rules
        ];

        if ($this->workflowId) {
            // Update existing template
            $template = WorkflowTemplate::find($this->workflowId);
            $template->update([
                'name' => $name,
                'description' => $description,
                'config' => $config
            ]);
        } else {
            // Create new template
            $template = WorkflowTemplate::create([
                'name' => $name,
                'description' => $description,
                'category' => 'custom',
                'is_active' => true,
                'is_system' => false,
                'config' => $config,
                'created_by' => auth()->id(),
                'version' => '1.0'
            ]);
            
            $this->workflowId = $template->id;
        }

        session()->flash('success', 'Workflow guardado exitosamente');
        $this->dispatch('workflowSaved', $template->id);
    }

    public function getStepTypes()
    {
        return [
            'start' => ['label' => 'Inicio', 'icon' => 'play', 'color' => 'green'],
            'task' => ['label' => 'Tarea', 'icon' => 'clipboard-list', 'color' => 'blue'],
            'approval' => ['label' => 'Aprobación', 'icon' => 'check-circle', 'color' => 'yellow'],
            'validation' => ['label' => 'Validación', 'icon' => 'shield-check', 'color' => 'purple'],
            'inspection' => ['label' => 'Inspección', 'icon' => 'magnifying-glass', 'color' => 'orange'],
            'report' => ['label' => 'Informe', 'icon' => 'document-text', 'color' => 'indigo'],
            'notification' => ['label' => 'Notificación', 'icon' => 'bell', 'color' => 'pink'],
            'issuance' => ['label' => 'Emisión', 'icon' => 'document-duplicate', 'color' => 'cyan'],
            'end' => ['label' => 'Fin', 'icon' => 'flag', 'color' => 'red']
        ];
    }

    public function getAreas()
    {
        return Area::where('status', true)->pluck('name', 'id')->toArray();
    }

    public function getSelectedStepData()
    {
        if (!$this->selectedStep) return null;
        
        return collect($this->steps)->firstWhere('id', $this->selectedStep);
    }

    protected function getDefaultStepName($type)
    {
        $names = [
            'task' => 'Nueva Tarea',
            'approval' => 'Aprobación',
            'validation' => 'Validación',
            'inspection' => 'Inspección',
            'report' => 'Generar Informe',
            'notification' => 'Notificar',
            'issuance' => 'Emitir Documento'
        ];

        return $names[$type] ?? 'Nuevo Paso';
    }

    public function render()
    {
        return view('livewire.workflow-builder', [
            'stepTypes' => $this->getStepTypes(),
            'areas' => $this->getAreas(),
            'selectedStepData' => $this->getSelectedStepData(),
            'systemTemplates' => WorkflowTemplate::getSystemTemplates()
        ]);
    }
}
