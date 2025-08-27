<div class="workflow-builder bg-white rounded-lg shadow-lg" x-data="workflowBuilder()" x-init="init()">
    <!-- Header -->
    <div class="border-b border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Constructor de Workflow</h2>
                <p class="text-sm text-gray-600">Diseñe su flujo de trabajo arrastrando y conectando pasos</p>
            </div>
            <div class="flex space-x-2">
                <button @click="loadTemplate()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Cargar Template
                </button>
                <button @click="validateWorkflow()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Validar
                </button>
                <button @click="saveWorkflow()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Guardar
                </button>
            </div>
        </div>
    </div>

    <div class="flex h-screen">
        <!-- Sidebar - Palette -->
        <div class="w-64 border-r border-gray-200 bg-gray-50 p-4">
            <h3 class="font-medium text-gray-900 mb-4">Tipos de Pasos</h3>
            
            <div class="space-y-2">
                @foreach($stepTypes as $type => $config)
                    <div class="step-palette-item p-3 bg-white rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50"
                         draggable="true"
                         @dragstart="dragStart($event, '{{ $type }}')"
                         data-step-type="{{ $type }}">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-full bg-{{ $config['color'] }}-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-{{ $config['color'] }}-600" fill="currentColor" viewBox="0 0 20 20">
                                    <!-- Icon based on type -->
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ $config['label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Templates -->
            <div class="mt-6">
                <h3 class="font-medium text-gray-900 mb-4">Templates</h3>
                <div class="space-y-2">
                    @foreach($systemTemplates as $key => $template)
                        <button wire:click="loadTemplate('{{ $key }}')" 
                                class="w-full text-left p-2 bg-white rounded border hover:bg-gray-50">
                            <div class="text-sm font-medium text-gray-700">{{ $template['name'] }}</div>
                            <div class="text-xs text-gray-500">{{ $template['category'] }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Main Canvas -->
        <div class="flex-1 relative overflow-hidden">
            <!-- Canvas Controls -->
            <div class="absolute top-4 right-4 z-10 flex space-x-2">
                <button @click="zoomIn()" class="p-2 bg-white rounded-lg shadow border hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </button>
                <button @click="zoomOut()" class="p-2 bg-white rounded-lg shadow border hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                    </svg>
                </button>
                <button @click="resetView()" class="p-2 bg-white rounded-lg shadow border hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>

            <!-- Canvas -->
            <div class="workflow-canvas w-full h-full bg-gray-100 relative"
                 @drop="drop($event)"
                 @dragover.prevent
                 @click="deselectStep()"
                 style="transform: scale({{ $zoom }}) translate({{ $panX }}px, {{ $panY }}px)">

                <!-- Grid Background -->
                <div class="absolute inset-0 opacity-20">
                    <svg width="100%" height="100%">
                        <defs>
                            <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                                <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#e5e7eb" stroke-width="1"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid)" />
                    </svg>
                </div>

                <!-- Connections -->
                <svg class="absolute inset-0 pointer-events-none" style="z-index: 1;">
                    @foreach($connections as $index => $connection)
                        @php
                            $fromStep = collect($steps)->firstWhere('id', $connection['from']);
                            $toStep = collect($steps)->firstWhere('id', $connection['to']);
                        @endphp
                        @if($fromStep && $toStep)
                            <line x1="{{ $fromStep['position']['x'] + 60 }}" 
                                  y1="{{ $fromStep['position']['y'] + 30 }}"
                                  x2="{{ $toStep['position']['x'] }}" 
                                  y2="{{ $toStep['position']['y'] + 30 }}"
                                  stroke="#6b7280" 
                                  stroke-width="2" 
                                  marker-end="url(#arrowhead)"/>
                            
                            <!-- Connection delete button -->
                            <circle cx="{{ ($fromStep['position']['x'] + $toStep['position']['x']) / 2 + 30 }}"
                                    cy="{{ ($fromStep['position']['y'] + $toStep['position']['y']) / 2 + 30 }}"
                                    r="8" 
                                    fill="red" 
                                    class="cursor-pointer"
                                    style="pointer-events: all;"
                                    wire:click="removeConnection({{ $index }})"/>
                            <text x="{{ ($fromStep['position']['x'] + $toStep['position']['x']) / 2 + 26 }}"
                                  y="{{ ($fromStep['position']['y'] + $toStep['position']['y']) / 2 + 34 }}"
                                  fill="white" 
                                  font-size="10" 
                                  class="cursor-pointer"
                                  style="pointer-events: all;"
                                  wire:click="removeConnection({{ $index }})">×</text>
                        @endif
                    @endforeach

                    <!-- Arrow marker -->
                    <defs>
                        <marker id="arrowhead" markerWidth="10" markerHeight="7" 
                                refX="9" refY="3.5" orient="auto">
                            <polygon points="0 0, 10 3.5, 0 7" fill="#6b7280" />
                        </marker>
                    </defs>
                </svg>

                <!-- Steps -->
                @foreach($steps as $step)
                    <div class="workflow-step absolute bg-white rounded-lg shadow-lg border-2 cursor-move"
                         style="left: {{ $step['position']['x'] }}px; top: {{ $step['position']['y'] }}px; z-index: 2;"
                         :class="{ 'border-blue-500': selectedStep === '{{ $step['id'] }}', 'border-gray-200': selectedStep !== '{{ $step['id'] }}' }"
                         @click.stop="selectStep('{{ $step['id'] }}')"
                         @dragstart="dragStepStart($event, '{{ $step['id'] }}')"
                         @dragend="dragStepEnd($event, '{{ $step['id'] }}')"
                         draggable="true">
                        
                        <div class="p-3 w-32">
                            <!-- Step Header -->
                            <div class="flex items-center justify-between mb-2">
                                <div class="w-6 h-6 rounded-full bg-{{ $stepTypes[$step['type']]['color'] ?? 'gray' }}-100 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-{{ $stepTypes[$step['type']]['color'] ?? 'gray' }}-600" fill="currentColor" viewBox="0 0 20 20">
                                        <!-- Icon -->
                                    </svg>
                                </div>
                                @if(!in_array($step['id'], ['start', 'end']))
                                    <button wire:click="deleteStep('{{ $step['id'] }}')" 
                                            class="text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <!-- Step Content -->
                            <div class="text-xs font-medium text-gray-900 mb-1">{{ $step['name'] }}</div>
                            <div class="text-xs text-gray-500">{{ $stepTypes[$step['type']]['label'] ?? $step['type'] }}</div>
                            
                            @if(isset($step['time_limit']) && $step['time_limit'])
                                <div class="text-xs text-blue-600 mt-1">{{ $step['time_limit'] }}d</div>
                            @endif

                            <!-- Connection Points -->
                            <div class="absolute -right-2 top-1/2 w-4 h-4 bg-blue-500 rounded-full transform -translate-y-1/2 cursor-crosshair"
                                 @click.stop="startConnection('{{ $step['id'] }}')"
                                 title="Conectar desde aquí"></div>
                            <div class="absolute -left-2 top-1/2 w-4 h-4 bg-green-500 rounded-full transform -translate-y-1/2"
                                 title="Punto de conexión"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Properties Panel -->
        @if($selectedStepData)
            <div class="w-80 border-l border-gray-200 bg-white p-4 overflow-y-auto">
                <h3 class="font-medium text-gray-900 mb-4">Propiedades del Paso</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ID</label>
                        <input type="text" 
                               wire:model.live="selectedStepData.id"
                               wire:change="updateSelectedStep('id', $event.target.value)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                               {{ in_array($selectedStepData['id'], ['start', 'end']) ? 'readonly' : '' }}>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" 
                               wire:model.live="selectedStepData.name"
                               wire:change="updateSelectedStep('name', $event.target.value)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select wire:model.live="selectedStepData.type"
                                wire:change="updateSelectedStep('type', $event.target.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                {{ in_array($selectedStepData['id'], ['start', 'end']) ? 'disabled' : '' }}>
                            @foreach($stepTypes as $type => $config)
                                <option value="{{ $type }}">{{ $config['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Área Asignada</label>
                        <select wire:model.live="selectedStepData.assigned_area_id"
                                wire:change="updateSelectedStep('assigned_area_id', $event.target.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="">Seleccionar área...</option>
                            @foreach($areas as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Límite de Tiempo (días)</label>
                        <input type="number" 
                               wire:model.live="selectedStepData.time_limit"
                               wire:change="updateSelectedStep('time_limit', $event.target.value)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                               min="1" max="365">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea wire:model.live="selectedStepData.description"
                                  wire:change="updateSelectedStep('description', $event.target.value)"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                  rows="3"></textarea>
                    </div>

                    <!-- Checkboxes -->
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   wire:model.live="selectedStepData.required"
                                   wire:change="updateSelectedStep('required', $event.target.checked)"
                                   class="rounded border-gray-300 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Requerido</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" 
                                   wire:model.live="selectedStepData.auto_assign"
                                   wire:change="updateSelectedStep('auto_assign', $event.target.checked)"
                                   class="rounded border-gray-300 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Auto-asignar</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" 
                                   wire:model.live="selectedStepData.auto_complete"
                                   wire:change="updateSelectedStep('auto_complete', $event.target.checked)"
                                   class="rounded border-gray-300 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Auto-completar</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" 
                                   wire:model.live="selectedStepData.parallel"
                                   wire:change="updateSelectedStep('parallel', $event.target.checked)"
                                   class="rounded border-gray-300 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Procesamiento paralelo</span>
                        </label>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Save Modal -->
    <div x-show="showSaveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-96">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Guardar Workflow</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" x-model="workflowName" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea x-model="workflowDescription" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3"></textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-2 mt-6">
                <button @click="showSaveModal = false" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">
                    Cancelar
                </button>
                <button @click="confirmSave()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function workflowBuilder() {
    return {
        selectedStep: @entangle('selectedStep'),
        showSaveModal: false,
        workflowName: '',
        workflowDescription: '',
        connectingFrom: null,
        
        init() {
            // Initialize any additional functionality
        },
        
        dragStart(event, stepType) {
            event.dataTransfer.setData('stepType', stepType);
        },
        
        drop(event) {
            event.preventDefault();
            const stepType = event.dataTransfer.getData('stepType');
            if (stepType) {
                const rect = event.currentTarget.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                
                @this.call('addStep', stepType);
                // Update position after step is created
                setTimeout(() => {
                    @this.call('updateStepPosition', @this.selectedStep, x, y);
                }, 100);
            }
        },
        
        selectStep(stepId) {
            this.selectedStep = stepId;
            @this.call('selectStep', stepId);
        },
        
        deselectStep() {
            this.selectedStep = null;
            @this.set('selectedStep', null);
        },
        
        dragStepStart(event, stepId) {
            event.dataTransfer.setData('stepId', stepId);
        },
        
        dragStepEnd(event, stepId) {
            const rect = event.currentTarget.parentElement.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            @this.call('updateStepPosition', stepId, x, y);
        },
        
        startConnection(fromStepId) {
            if (this.connectingFrom === null) {
                this.connectingFrom = fromStepId;
                // Visual feedback
                event.target.classList.add('bg-yellow-500');
            } else if (this.connectingFrom !== fromStepId) {
                @this.call('addConnection', this.connectingFrom, fromStepId);
                this.connectingFrom = null;
                // Remove visual feedback
                document.querySelectorAll('.bg-yellow-500').forEach(el => {
                    el.classList.remove('bg-yellow-500');
                    el.classList.add('bg-blue-500');
                });
            }
        },
        
        zoomIn() {
            @this.set('zoom', Math.min(@this.zoom * 1.2, 3));
        },
        
        zoomOut() {
            @this.set('zoom', Math.max(@this.zoom / 1.2, 0.3));
        },
        
        resetView() {
            @this.set('zoom', 1);
            @this.set('panX', 0);
            @this.set('panY', 0);
        },
        
        validateWorkflow() {
            @this.call('validateWorkflow').then(errors => {
                if (errors.length === 0) {
                    alert('Workflow válido!');
                } else {
                    alert('Errores encontrados:\n' + errors.join('\n'));
                }
            });
        },
        
        saveWorkflow() {
            this.showSaveModal = true;
        },
        
        confirmSave() {
            if (this.workflowName.trim()) {
                @this.call('save', this.workflowName, this.workflowDescription);
                this.showSaveModal = false;
                this.workflowName = '';
                this.workflowDescription = '';
            }
        },
        
        loadTemplate() {
            // Show template selection modal or dropdown
            const templates = @json($systemTemplates);
            const templateKeys = Object.keys(templates);
            
            if (templateKeys.length > 0) {
                const selected = prompt('Seleccione un template:\n' + 
                    templateKeys.map((key, index) => `${index + 1}. ${templates[key].name}`).join('\n'));
                
                const index = parseInt(selected) - 1;
                if (index >= 0 && index < templateKeys.length) {
                    @this.call('loadTemplate', templateKeys[index]);
                }
            }
        }
    }
}
</script>

<style>
.workflow-builder {
    height: calc(100vh - 2rem);
}

.workflow-step {
    transition: all 0.2s ease;
}

.workflow-step:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.step-palette-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.workflow-canvas {
    background-image: 
        linear-gradient(rgba(0, 0, 0, 0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 0, 0, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
}
</style>
