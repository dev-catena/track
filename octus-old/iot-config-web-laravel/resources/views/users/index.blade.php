@extends('layouts.app')

@section('title', 'Usuários')

@section('content')
<div class="admin-dashboard">
    <div class="page-header">
        <div class="page-header-content">
            <h1 style="color: #eeeeee;">Usuários</h1>
            <p>Gerenciar usuários do sistema</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                + Novo Usuário
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning">
        {{ session('warning') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    <div class="dashboard-card">
        <h2>Lista de Usuários</h2>

        <div class="filters-section">
            <form method="GET" action="{{ route('users.index') }}" class="search-form">
                <div class="search-box">
                    <input type="text"
                           name="search"
                           placeholder="Buscar usuários..."
                           class="search-input"
                           value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="filter-controls">
                    <select name="tipo" class="filter-select">
                        <option value="">Todos os tipos</option>
                        <option value="admin" {{ ($filters['tipo'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="comum" {{ ($filters['tipo'] ?? '') === 'comum' ? 'selected' : '' }}>Comum</option>
                    </select>
                    <button type="submit" class="btn-filter">Filtrar</button>
                    <a href="{{ route('users.index') }}" class="btn-clear">Limpar</a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th class="id-column">ID</th>
                        <th class="name-column">Nome</th>
                        <th class="email-column">Email</th>
                        <th class="phone-column">Telefone</th>
                        <th class="company-column">Empresa</th>
                        <th class="role-column">Tipo</th>
                        <th class="date-column">Criado em</th>
                        <th class="actions-column">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr class="user-row">
                        <td class="id-column">
                            <span class="entity-id">#{{ $user['id'] }}</span>
                        </td>
                        <td class="name-column">
                            <div class="entity-name">
                                <span class="name">{{ $user['name'] }}</span>
                            </div>
                        </td>
                        <td class="email-column">
                            <span class="email">{{ $user['email'] }}</span>
                        </td>
                        <td class="phone-column">
                            <span class="phone">{{ $user['phone'] ?? '-' }}</span>
                        </td>
                        <td class="company-column">
                            <span class="company-name">
                                {{ $user['company']['name'] ?? 'Sem empresa' }}
                            </span>
                        </td>
                        <td class="role-column">
                        @if($user['tipo'] === 'admin')
                            <span class="badge badge-admin">Admin</span>
                        @else
                            <span class="badge badge-comum">Comum</span>
                        @endif
                        </td>
                        <td class="date-column">
                            <span class="date">
                                {{ isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '-' }}
                            </span>
                        </td>
                        <td class="actions-column">
                            <div class="actions-menu">
                                <a href="{{ route('users.show', $user['id']) }}"
                                   class="btn-action view"
                                   title="Visualizar usuário">
                                    Ver
                                </a>
                                <a href="{{ route('users.edit', $user['id']) }}"
                                   class="btn-action edit"
                                   title="Editar usuário">
                                    Editar
                                </a>
                                <form action="{{ route('users.destroy', $user['id']) }}"
                                      method="POST"
                                      style="display: inline;"
                                      onsubmit="return confirm('Tem certeza que deseja excluir o usuário \'{{ $user['name'] }}\'?\n\nEsta ação não pode ser desfeita.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn-action delete"
                                            title="Deletar usuário">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center no-data">
                            <div class="empty-state">
                                <span class="empty-icon">-</span>
                                <p>Nenhum usuário encontrado</p>
                                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                                    + Criar primeiro usuário
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($pagination) && count($users) > 0)
        <div class="table-footer">
            <div class="table-info">
                <span>Mostrando <strong>{{ $pagination['from'] ?? 1 }}</strong> a <strong>{{ $pagination['to'] ?? count($users) }}</strong> de <strong>{{ $pagination['total'] ?? count($users) }}</strong> usuários</span>
            </div>
            <div class="pagination">
                @if(isset($pagination['prev_page_url']))
                    <a href="{{ $pagination['prev_page_url'] }}" class="pagination-link">Anterior</a>
                @endif
                <span class="pagination-info">Página {{ $pagination['current_page'] ?? 1 }} de {{ $pagination['last_page'] ?? 1 }}</span>
                @if(isset($pagination['next_page_url']))
                    <a href="{{ $pagination['next_page_url'] }}" class="pagination-link">Próxima</a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.admin-dashboard {
    padding: 20px;
    background: #3E4A59;
    min-height: 100vh;
    color: #ffffff;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px 0;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.page-header-content h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: bold;
}

.page-header-content p {
    margin: 5px 0 0 0;
    opacity: 0.8;
    font-size: 1.1rem;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.btn-primary {
    background: #3E4A59;
    color: white;
    border: 1px solid #2d3642;
}

.btn-primary:hover {
    background: #2d3642;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(62, 74, 89, 0.4);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.875rem;
}

.dashboard-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    color: #333;
}

.dashboard-card h2 {
    margin: 0 0 25px 0;
    color: #2c3e50;
    font-size: 1.8rem;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
    animation: slideDown 0.3s ease;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.filters-section {
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.search-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.search-box {
    flex: 1;
    min-width: 250px;
}

.search-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3E4A59;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.filter-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.filter-select {
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #3E4A59;
}

.btn-filter, .btn-clear {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-filter {
    background: #3E4A59;
    color: white;
}

.btn-filter:hover {
    background: #2d3642;
}

.btn-clear {
    background: #6c757d;
    color: white;
}

.btn-clear:hover {
    background: #5a6268;
}

.table-container {
    overflow-x: auto;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.data-table th {
    background: #3E4A59;
    color: white;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    border: none;
    position: sticky;
    top: 0;
    z-index: 1;
}

.data-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.data-table tbody tr:hover {
    background-color: #f8f9fa;
    transition: background-color 0.2s ease;
}

.entity-id {
    font-weight: bold;
    color: #6c757d;
    font-family: 'Courier New', monospace;
}

.entity-name .name {
    font-weight: 600;
    color: #2c3e50;
}

.email {
    color: #3E4A59;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
}

.phone {
    color: #6c757d;
    font-family: 'Courier New', monospace;
}

.company-name {
    color: #495057;
    font-weight: 500;
}

.badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-admin {
    background: #dc3545;
    color: white;
}

.badge-comum {
    background: #3E4A59;
    color: white;
}

.date {
    color: #6c757d;
    font-family: 'Courier New', monospace;
}

.actions-menu {
    display: flex;
    gap: 8px;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.2s ease;
    text-decoration: none;
    font-weight: 500;
}

.btn-action.view {
    background: #e3f2fd;
    color: #1976d2;
}

.btn-action.view:hover {
    background: #1976d2;
    color: white;
}

.btn-action.edit {
    background: #fff3e0;
    color: #f57c00;
}

.btn-action.edit:hover {
    background: #f57c00;
    color: white;
}

.btn-action.delete {
    background: #ffebee;
    color: #d32f2f;
}

.btn-action.delete:hover {
    background: #d32f2f;
    color: white;
}

.text-center {
    text-align: center;
}

.no-data {
    padding: 60px 20px !important;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.empty-icon {
    font-size: 4rem;
    opacity: 0.5;
}

.empty-state p {
    color: #6c757d;
    font-size: 1.1rem;
    margin: 0;
}

.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 0 0 10px 10px;
    margin-top: -1px;
}

.table-info {
    color: #6c757d;
    font-weight: 500;
}

.pagination {
    display: flex;
    gap: 15px;
    align-items: center;
}

.pagination-info {
    color: #6c757d;
    font-weight: 500;
}

.pagination-link {
    padding: 8px 16px;
    background: #3E4A59;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.pagination-link:hover {
    background: #2d3642;
    transform: translateY(-2px);
}

/* Responsividade */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .filters-section {
        padding: 15px;
    }

    .search-form {
        flex-direction: column;
    }

    .search-box {
        width: 100%;
    }

    .filter-controls {
        width: 100%;
        flex-wrap: wrap;
    }

    .filter-select {
        flex: 1;
    }

    .data-table {
        font-size: 0.875rem;
    }

    .data-table th,
    .data-table td {
        padding: 10px 8px;
    }

    .actions-menu {
        flex-direction: column;
        gap: 4px;
    }

    .table-footer {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}
</style>
@endsection
