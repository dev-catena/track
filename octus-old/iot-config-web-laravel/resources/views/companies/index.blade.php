@extends('layouts.app')

@section('title', 'Empresas')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">
                Gerenciamento de Empresas
            </h1>
            <p class="page-description">
                Gerencie as empresas do sistema, visualize estruturas organizacionais e controle departamentos
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('companies.create') }}" class="btn btn-primary">
                + Nova Empresa
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filtros e Pesquisa -->
    <div class="search-filters">
        <div class="search-container">
            <form method="GET" action="{{ route('companies.index') }}" class="search-form">
                <div class="search-input-group">
                    <input type="text" 
                           name="search" 
                           placeholder="Pesquisar empresas..." 
                           value="{{ request('search') }}"
                           class="search-input">
                    <button type="submit" class="search-btn">Buscar</button>
                    @if(request('search'))
                        <a href="{{ route('companies.index') }}" class="clear-search"> Limpar</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Empresas -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="table-title">Lista de Empresas</h2>
            <div class="table-stats">
                <span class="stat-item">
                    Total: <strong>{{ count($companies) }}</strong>
                </span>
            </div>
        </div>

        @if(count($companies) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome da Empresa</th>
                        <th>Departamentos</th>
                        <th>Criada em</th>
                        <th class="actions-column">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $company)
                        <tr class="company-row">
                            <td class="id-column">
                                {{-- Debug: Tipo de $company: {{ gettype($company) }} --}}
                                <span class="entity-id">#{{ is_array($company) ? $company['id'] : 'ERROR' }}</span>
                            </td>
                            <td class="name-column">
                                <div class="entity-name">
                                    <span class="name">{{ $company['name'] }}</span>
                                </div>
                            </td>
                            <td class="departments-column">
                                <span class="departments-count">
                                    {{ $company['departments_count'] ?? 0 }} departamento(s)
                                </span>
                            </td>
                            <td class="date-column">
                                <span class="date">
                                    {{ \Carbon\Carbon::parse($company['created_at'])->format('d/m/Y H:i') }}
                                </span>
                            </td>
                            <td class="actions-column">
                                <div class="action-buttons">
                                    <button onclick="viewCompany({{ $company['id'] }})" 
                                            class="btn-action btn-view" 
                                            title="Ver Detalhes">
                                        
                                    </button>
                                    <a href="{{ route('companies.edit', $company['id']) }}" 
                                       class="btn-action btn-edit" 
                                       title="Editar Empresa">
                                        
                                    </a>
                                    <button onclick="deleteCompany({{ $company['id'] }}, '{{ $company['name'] }}')" 
                                            class="btn-action btn-delete" 
                                            title="Deletar Empresa">
                                        
                                    </button>
                                    @if(($company['departments_count'] ?? 0) > 0)
                                        <a href="{{ route('companies.organizational-structure', $company['id']) }}" 
                                           class="btn-action btn-org" 
                                           title="Estrutura Organizacional">
                                            
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <div class="empty-icon"></div>
                <h3>Nenhuma empresa encontrada</h3>
                <p>{{ request('search') ? 'Nenhuma empresa corresponde à sua pesquisa.' : 'Comece criando sua primeira empresa.' }}</p>
                <a href="{{ route('companies.create') }}" class="btn btn-primary">
                    + Criar Primeira Empresa
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Modal para visualização de detalhes -->
<div id="companyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Detalhes da Empresa</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="modalContent">
                <p>Carregando...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal()" class="btn btn-secondary">Fechar</button>
        </div>
    </div>
</div>

<style>
/* Estilos da página */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.page-title {
    font-size: 2rem;
    font-weight: bold;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.page-description {
    color: #6b7280;
    margin: 0;
    font-size: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: #3E4A59;
    color: white;
}

.btn-primary:hover {
    background-color: #2d3642;
}

.btn-secondary {
    background-color: #6b7280;
    color: white;
}

/* Alertas */
.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.alert-success {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-error {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

/* Filtros e Pesquisa */
.search-filters {
    margin-bottom: 2rem;
}

.search-form {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.search-input-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.search-input {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 1rem;
    width: 300px;
}

.search-btn {
    padding: 0.75rem 1.5rem;
    background-color: #3E4A59;
    color: white;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
}

.clear-search {
    color: #ef4444;
    text-decoration: none;
    padding: 0.5rem;
}

/* Tabela */
.table-container {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.table-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.table-stats {
    display: flex;
    gap: 1rem;
}

.stat-item {
    color: #6b7280;
    font-size: 0.875rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    color: #374151;
    vertical-align: middle;
}

.data-table th {
    background-color: #f9fafb;
    font-weight: 600;
    color: #1f2937;
}

.company-row:hover {
    background-color: #f8fafc;
}

.entity-id {
    font-family: 'Courier New', monospace;
    background-color: #e5e7eb;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: #374151;
}

.entity-name .name {
    font-weight: 500;
    color: #1f2937;
}

.departments-count {
    background-color: #dbeafe;
    color: #1e40af;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.date {
    color: #6b7280;
    font-size: 0.875rem;
}

/* Ações */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    padding: 0.5rem;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-view {
    background-color: #e0f2fe;
    color: #0277bd;
}

.btn-view:hover {
    background-color: #b3e5fc;
}

.btn-edit {
    background-color: #fff3e0;
    color: #f57c00;
}

.btn-edit:hover {
    background-color: #ffe0b2;
}

.btn-delete {
    background-color: #ffebee;
    color: #d32f2f;
}

.btn-delete:hover {
    background-color: #ffcdd2;
}

.btn-org {
    background-color: #e8f5e8;
    color: #2e7d32;
}

.btn-org:hover {
    background-color: #c8e6c9;
}

/* Estado vazio */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    color: #374151;
    margin-bottom: 0.5rem;
}

.empty-state p {
    margin-bottom: 2rem;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 0.75rem;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    color: #1f2937;
}

.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
}

.close:hover {
    color: #374151;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
    }

    .search-input {
        width: 100%;
    }

    .action-buttons {
        flex-wrap: wrap;
    }

    .data-table {
        font-size: 0.875rem;
    }

    .data-table th,
    .data-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<script>
function viewCompany(companyId) {
    const modal = document.getElementById('companyModal');
    const modalContent = document.getElementById('modalContent');
    const modalTitle = document.getElementById('modalTitle');
    
    modalTitle.textContent = 'Detalhes da Empresa';
    modalContent.innerHTML = '<p>Carregando dados da empresa...</p>';
    modal.style.display = 'block';
    
    fetch(`/companies/${companyId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const company = data.company;
                modalContent.innerHTML = `
                    <div class="company-details">
                        <div class="detail-item">
                            <label>ID:</label>
                            <div class="detail-value">#${company.id}</div>
                        </div>
                        <div class="detail-item">
                            <label>Nome:</label>
                            <div class="detail-value">${company.name}</div>
                        </div>
                        <div class="detail-item">
                            <label>Departamentos:</label>
                            <div class="detail-value">
                                ${company.departments && company.departments.length > 0 ? 
                                    company.departments.map(dept => `
                                        <div class="department-item">
                                            ${dept.name}
                                            <small>(Nível ${dept.nivel_hierarquico})</small>
                                        </div>
                                    `).join('') : 
                                    '<span class="no-departments">Nenhum departamento cadastrado</span>'
                                }
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Criada em:</label>
                            <div class="detail-value">${new Date(company.created_at).toLocaleString('pt-BR')}</div>
                        </div>
                        <div class="detail-item">
                            <label>Atualizada em:</label>
                            <div class="detail-value">${new Date(company.updated_at).toLocaleString('pt-BR')}</div>
                        </div>
                    </div>
                `;
            } else {
                modalContent.innerHTML = '<p class="error">Erro ao carregar dados da empresa</p>';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar empresa:', error);
            modalTitle.textContent = 'Erro';
            modalContent.innerHTML = `
                <div class="error-message">
                    <p class="error">Erro ao carregar dados da empresa</p>
                    <p class="error-details">${error.message}</p>
                    <p class="error-hint">Verifique se a API está rodando em http://10.102.0.103:8000</p>
                </div>
            `;
        });
}

function deleteCompany(companyId, companyName) {
    if (confirm(`Excluir Tem certeza que deseja deletar a empresa "${companyName}"?\n\nEsta ação não pode ser desfeita.`)) {
        // Mostrar loading
        const modal = document.getElementById('companyModal');
        const modalContent = document.getElementById('modalContent');
        const modalTitle = document.getElementById('modalTitle');
        
        modalTitle.textContent = 'Deletando Empresa...';
        modalContent.innerHTML = '<p>Processando exclusão...</p>';
        modal.style.display = 'block';
        
        // Tentar deletar via AJAX para capturar erros
        fetch(`/companies/${companyId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalTitle.textContent = 'Sucesso';
                modalContent.innerHTML = '<p>Empresa deletada com sucesso!</p>';
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                // Verificar se há departamentos que impedem a exclusão
                if (data.departments && data.departments.length > 0) {
                    showDepartmentsBlockingDeletion(companyName, data.departments);
                } else {
                    modalTitle.textContent = 'Erro';
                    modalContent.innerHTML = `<p class="error">${data.message}</p>`;
                }
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            modalTitle.textContent = 'Erro';
            modalContent.innerHTML = '<p class="error">Erro de conexão ao deletar empresa</p>';
        });
    }
}

function closeModal() {
    document.getElementById('companyModal').style.display = 'none';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('companyModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Adicionar estilo para os detalhes no modal
const style = document.createElement('style');
style.textContent = `
    .company-details {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .detail-item label {
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
    }
    
    .detail-value {
        padding: 0.75rem;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        color: #1f2937;
    }
    
    .department-item {
        padding: 0.5rem;
        margin-bottom: 0.5rem;
        background-color: #dbeafe;
        border-radius: 0.375rem;
        border-left: 3px solid #3E4A59;
    }
    
    .no-departments {
        color: #6b7280;
        font-style: italic;
    }
    
    .error-message {
        padding: 1rem;
        background: #fff5f5;
        border-radius: 8px;
        border: 1px solid #fc8181;
    }
    
    .error-details {
        color: #c53030;
        font-size: 0.875rem;
        margin-top: 0.5rem;
        font-family: monospace;
    }
    
    .error-hint {
        color: #718096;
        font-size: 0.875rem;
        margin-top: 0.5rem;
        background: #edf2f7;
        padding: 0.5rem;
        border-radius: 4px;
    }
    
    .error {
        color: #ef4444;
        text-align: center;
        padding: 1rem;
    }
`;
document.head.appendChild(style);

function deleteCompany(companyId, companyName) {
    if (confirm(`Excluir Tem certeza que deseja deletar a empresa "${companyName}"?\n\nEsta ação não pode ser desfeita.`)) {
        // Mostrar loading
        const modal = document.getElementById('companyModal');
        const modalContent = document.getElementById('modalContent');
        const modalTitle = document.getElementById('modalTitle');
        
        modalTitle.textContent = 'Deletando Empresa...';
        modalContent.innerHTML = '<p>Processando exclusão...</p>';
        modal.style.display = 'block';
        
        // Capturar token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('CSRF Token:', csrfToken);
        
        if (!csrfToken) {
            modalTitle.textContent = 'Erro';
            modalContent.innerHTML = '<p class="error">Token CSRF não encontrado</p>';
            return;
        }
        
        // Tentar deletar via AJAX para capturar erros
        fetch(`/companies/${companyId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalTitle.textContent = 'Sucesso';
                modalContent.innerHTML = '<p>Empresa deletada com sucesso!</p>';
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                // Verificar se há departamentos que impedem a exclusão
                if (data.departments && data.departments.length > 0) {
                    showDepartmentsBlockingDeletion(companyName, data.departments);
                } else {
                    modalTitle.textContent = 'Erro';
                    modalContent.innerHTML = `<p class="error">${data.message}</p>`;
                }
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            modalTitle.textContent = 'Erro';
            modalContent.innerHTML = '<p class="error">Erro de conexão ao deletar empresa</p>';
        });
    }
}

function showDepartmentsBlockingDeletion(companyName, departments) {
    const modal = document.getElementById('companyModal');
    const modalContent = document.getElementById('modalContent');
    const modalTitle = document.getElementById('modalTitle');
    
    modalTitle.textContent = 'Não é possível deletar';
    
    let departmentsHtml = `
        <div class="blocking-deletion-content">
            <div class="warning-message">
                <p><strong>A empresa "${companyName}" não pode ser deletada</strong></p>
                <p>Esta empresa possui <strong>${departments.length} departamento(s)</strong> associado(s).</p>
                <p>Para deletar a empresa, primeiro você deve:</p>
                <ul class="action-list">
                    <li>Transferir os departamentos para outra empresa</li>
                    <li>Excluir Deletar todos os departamentos</li>
                    <li>📝 Ou editar os departamentos individualmente</li>
                </ul>
            </div>
            
            <div class="departments-blocking">
                <h3>Departamentos que impedem a exclusão:</h3>
                <div class="departments-list">
    `;
    
    departments.forEach(dept => {
        const statusIcon = dept.is_active ? '' : '';
        const statusText = dept.is_active ? 'Ativo' : 'Inativo';
        
        departmentsHtml += `
            <div class="department-blocking-item">
                <div class="dept-header">
                    <span class="dept-name">${dept.name}</span>
                    <span class="dept-status ${dept.is_active ? 'active' : 'inactive'}">${statusIcon} ${statusText}</span>
                </div>
                <div class="dept-details">
                    <small>ID: #${dept.id} | Nível: ${dept.nivel_hierarquico}</small>
                    ${dept.description ? `<br><em>${dept.description}</em>` : ''}
                </div>
                <div class="dept-actions">
                    <a href="/departments/${dept.id}/edit" class="btn-small btn-edit" target="_blank">
                        Editar Editar
                    </a>
                    <a href="/departments/${dept.id}" class="btn-small btn-view" target="_blank">
                        Ver Ver
                    </a>
                </div>
            </div>
        `;
    });
    
    departmentsHtml += `
                </div>
            </div>
            
            <div class="blocking-actions">
                <a href="/departments" class="btn btn-primary" target="_blank">
                    Gerenciar Departamentos
                </a>
                <button onclick="closeModal()" class="btn btn-secondary">
                    Fechar
                </button>
            </div>
        </div>
    `;
    
    modalContent.innerHTML = departmentsHtml;
    modal.style.display = 'block';
}
</script>

<style>
.blocking-deletion-content {
    max-height: 70vh;
    overflow-y: auto;
}

.warning-message {
    background-color: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.warning-message p {
    margin: 0.5rem 0;
    color: #92400e;
}

.action-list {
    margin: 1rem 0;
    padding-left: 1.5rem;
    color: #92400e;
}

.action-list li {
    margin: 0.5rem 0;
}

.departments-blocking {
    background-color: #f9fafb;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.departments-blocking h3 {
    margin: 0 0 1rem 0;
    color: #374151;
    font-size: 1.1rem;
}

.departments-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 300px;
    overflow-y: auto;
}

.department-blocking-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 0.75rem;
    border-left: 4px solid #ef4444;
}

.dept-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.dept-name {
    font-weight: 600;
    color: #1f2937;
}

.dept-status {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 500;
}

.dept-status.active {
    background-color: #d1fae5;
    color: #065f46;
}

.dept-status.inactive {
    background-color: #fee2e2;
    color: #991b1b;
}

.dept-details {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.dept-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-small {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-small.btn-edit {
    background-color: #fff3e0;
    color: #f57c00;
    border: 1px solid #ffcc02;
}

.btn-small.btn-view {
    background-color: #e0f2fe;
    color: #0277bd;
    border: 1px solid #81d4fa;
}

.btn-small:hover {
    opacity: 0.8;
}

.blocking-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    border-top: 1px solid #e5e7eb;
    padding-top: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: #3E4A59;
    color: white;
}

.btn-primary:hover {
    background-color: #2d3642;
}

.btn-secondary {
    background-color: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background-color: #4b5563;
}

@media (max-width: 768px) {
    .dept-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .blocking-actions {
        flex-direction: column;
    }
    
    .departments-list {
        max-height: 200px;
    }
}
</style>

<script>
function deleteCompanyFixed(companyId, companyName) {
    if (confirm(`Excluir Tem certeza que deseja deletar a empresa "${companyName}"?\n\nEsta ação não pode ser desfeita.`)) {
        // Primeiro verificar se há departamentos
        const modal = document.getElementById('companyModal');
        const modalContent = document.getElementById('modalContent');
        const modalTitle = document.getElementById('modalTitle');
        
        modalTitle.textContent = 'Verificando...';
        modalContent.innerHTML = '<p>Verificando dependências...</p>';
        modal.style.display = 'block';
        
        // Verificar via GET se há departamentos primeiro
        fetch(`/companies/${companyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.company && data.company.departments && data.company.departments.length > 0) {
                    // Há departamentos, mostrar modal de bloqueio
                    showDepartmentsBlockingDeletion(companyName, data.company.departments);
                } else {
                    // Não há departamentos, prosseguir com formulário
                    closeModal();
                    submitDeleteForm(companyId);
                }
            })
            .catch(error => {
                console.error('Erro ao verificar:', error);
                // Em caso de erro, tentar deletar mesmo assim
                closeModal();
                submitDeleteForm(companyId);
            });
    }
}

function submitDeleteForm(companyId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/companies/${companyId}`;
    form.innerHTML = `
        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''}">
        <input type="hidden" name="_method" value="DELETE">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Alias para manter compatibilidade
function deleteCompany(companyId, companyName) {
    deleteCompanyFixed(companyId, companyName);
}
</script>
@endsection 