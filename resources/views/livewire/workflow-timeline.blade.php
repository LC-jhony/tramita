<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Progreso del Flujo de Trabajo</h3>
        @if($workflowProgress['progress_percentage'] ?? 0 > 0)
            <span class="text-sm text-gray-500">
                {{ $workflowProgress['progress_percentage'] }}% completado
            </span>
        @endif
    </div>

    @if(!empty($workflowProgress['completed_stages']) || $currentStage)
        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                     style="width: {{ $workflowProgress['progress_percentage'] ?? 0 }}%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-2">
                <span>Inicio</span>
                <span>{{ $workflowProgress['completed_count'] ?? 0 }}/{{ $workflowProgress['total_stages'] ?? 0 }} etapas</span>
                <span>Finalización</span>
            </div>
        </div>

        <!-- Timeline -->
        <div class="relative">
            <!-- Vertical line -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

            <!-- Completed Stages -->
            @foreach($workflowProgress['completed_stages'] ?? [] as $stage)
                <div class="relative flex items-start mb-6">
                    <!-- Icon -->
                    <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center relative z-10">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    
                    <!-- Content -->
                    <div class="ml-4 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-900">{{ $stage['name'] }}</h4>
                            <span class="text-xs text-gray-500">
                                {{ $stage['completed_at']?->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        @if($stage['area'] ?? null)
                            <p class="text-xs text-gray-600 mt-1">
                                Procesado por: {{ $stage['area'] }}
                            </p>
                        @endif
                        @if($stage['description'] ?? null)
                            <p class="text-xs text-gray-500 mt-1">{{ $stage['description'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Current Stage -->
            @if($currentStage)
                <div class="relative flex items-start mb-6">
                    <!-- Icon -->
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center relative z-10">
                        <svg class="w-4 h-4 text-blue-600 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    
                    <!-- Content -->
                    <div class="ml-4 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-blue-900">{{ $currentStage['name'] }}</h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                En proceso
                            </span>
                        </div>
                        @if($currentStage['description'] ?? null)
                            <p class="text-xs text-gray-500 mt-1">{{ $currentStage['description'] }}</p>
                        @endif
                        @if($currentStage['time_limit'] ?? null)
                            <p class="text-xs text-gray-600 mt-1">
                                Tiempo límite: {{ $currentStage['time_limit'] }} días
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Remaining Stages -->
            @foreach($workflowProgress['remaining_stages'] ?? [] as $stage)
                @if($stage['id'] !== ($currentStage['id'] ?? null))
                    <div class="relative flex items-start mb-6 opacity-50">
                        <!-- Icon -->
                        <div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center relative z-10">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        
                        <!-- Content -->
                        <div class="ml-4 flex-1">
                            <h4 class="text-sm font-medium text-gray-500">{{ $stage['name'] }}</h4>
                            @if($stage['description'] ?? null)
                                <p class="text-xs text-gray-400 mt-1">{{ $stage['description'] }}</p>
                            @endif
                            @if($stage['required'] ?? true)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 mt-1">
                                    Requerida
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-600 mt-1">
                                    Opcional
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Summary -->
        @if($workflowProgress['estimated_completion'] ?? null)
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Finalización estimada:</span>
                    <span class="font-medium text-gray-900">
                        {{ \Carbon\Carbon::parse($workflowProgress['estimated_completion'])->format('d/m/Y') }}
                    </span>
                </div>
            </div>
        @endif
    @else
        <!-- No workflow defined -->
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Sin flujo de trabajo definido</h3>
            <p class="mt-1 text-sm text-gray-500">Este tipo de documento no tiene un flujo de trabajo configurado.</p>
        </div>
    @endif
</div>
