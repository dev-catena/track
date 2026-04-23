@extends('web.layouts.app')

@section('title', 'Estrutura Organizacional - ' . $company->name)

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">🌳 Estrutura Organizacional</h1>
            <p class="page-description">{{ $company->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('companies.show', $company->id) }}" class="btn btn-secondary">← Voltar</a>
        </div>
    </div>

    <div class="dashboard-card">
        <h2>📊 Estatísticas</h2>
        <div class="stats-grid" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div><strong>Total:</strong> {{ $stats['total_departments'] ?? 0 }} departamentos</div>
            <div><strong>Raiz:</strong> {{ $stats['root_departments'] ?? 0 }}</div>
            <div><strong>Folhas:</strong> {{ $stats['leaf_departments'] ?? 0 }}</div>
            <div><strong>Níveis:</strong> {{ $stats['max_hierarchy_level'] ?? 0 }}</div>
        </div>
    </div>

    <div class="dashboard-card">
        <h2>📋 Hierarquia por Nível</h2>
        @foreach($structure as $level => $departments)
            <div style="margin-bottom: 1.5rem;">
                <h3>Nível {{ $level }} ({{ count($departments) }})</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @foreach($departments as $dept)
                        <a href="{{ route('departments.show', $dept->id) }}" 
                           class="btn btn-outline" style="text-decoration: none;">
                            📁 {{ $dept->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
        @if($structure->isEmpty())
            <p>Nenhum departamento cadastrado.</p>
        @endif
    </div>
</div>
@endsection
