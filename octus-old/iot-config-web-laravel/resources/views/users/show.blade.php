@extends('layouts.app')

@section('title', 'Detalhes do Usuário')

@section('content')
<div class="admin-dashboard">
    <div class="page-header">
        <div class="page-header-content">
            <h1 style="color: #eeeeee;">Ver Detalhes do Usuário</h1>
            <p>Visualização completa do usuário #{{ $user['id'] }}</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                ← Voltar
            </a>
            <a href="{{ route('users.edit', $user['id']) }}" class="btn btn-primary">
                Editar Editar
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    <div class="details-container">
        <!-- Card Principal -->
        <div class="details-card main-card">
            <div class="card-header">
                <div class="user-avatar">
                    <span class="avatar-icon">
                        @if($user['tipo'] === 'admin')
                            
                        @else
                            
                        @endif
                    </span>
                </div>
                <div class="user-header-info">
                    <h2 class="user-name">{{ $user['name'] }}</h2>
                    <div class="user-badges">
                        @if($user['tipo'] === 'admin')
                            <span class="badge badge-admin">Administrador</span>
                        @else
                            <span class="badge badge-comum">Comum</span>
                        @endif
                        <span class="badge badge-id">#{{ $user['id'] }}</span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="info-section">
                    <h3 class="section-title">Informações Pessoais</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-icon"></span>
                            <div class="info-content">
                                <span class="info-label">Email</span>
                                <span class="info-value">{{ $user['email'] }}</span>
                            </div>
                        </div>

                        <div class="info-item">
                            <span class="info-icon"></span>
                            <div class="info-content">
                                <span class="info-label">Telefone</span>
                                <span class="info-value">{{ $user['phone'] ?? 'Não informado' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h3 class="section-title">Informações Organizacionais</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-icon"></span>
                            <div class="info-content">
                                <span class="info-label">Empresa</span>
                                <span class="info-value">
                                    @if(isset($user['company']))
                                        <a href="{{ route('companies.show', $user['company']['id']) }}" class="company-link">
                                            {{ $user['company']['name'] }}
                                        </a>
                                    @else
                                        Sem empresa
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <span class="info-icon"></span>
                            <div class="info-content">
                                <span class="info-label">Tipo de Acesso</span>
                                <span class="info-value">
                                    @if($user['tipo'] === 'admin')
                                        Administrador (Acesso Total)
                                    @else
                                        Comum (Acesso Limitado)
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h3 class="section-title">Informações do Sistema</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-icon"></span>
                            <div class="info-content">
                                <span class="info-label">ID do Usuário</span>
                                <span class="info-value mono">#{{ $user['id'] }}</span>
                            </div>
                        </div>

                        <div class="info-item">
                            <span class="info-icon"></span>
                            <div class="info-content">
                                <span class="info-label">Criado em</span>
                                <span class="info-value mono">
                                    {{ isset($user['created_at']) ? date('d/m/Y H:i:s', strtotime($user['created_at'])) : '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <span class="info-icon"></span>
                            <div class="info-content">
                                <span class="info-label">Última Atualização</span>
                                <span class="info-value mono">
                                    {{ isset($user['updated_at']) ? date('d/m/Y H:i:s', strtotime($user['updated_at'])) : '-' }}
                                </span>
                            </div>
                        </div>

                        @if(isset($user['id_comp']))
                        <div class="info-item">
                            <span class="info-icon"></span>
                            <div class="info-content">
                                <span class="info-label">ID da Empresa</span>
                                <span class="info-value mono">#{{ $user['id_comp'] }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Ações -->
        <div class="details-card actions-card">
            <h3 class="card-title">Ações Rápidas</h3>
            <div class="quick-actions">
                <a href="{{ route('users.edit', $user['id']) }}" class="action-button edit">
                    <span class="action-icon"></span>
                    <span class="action-text">Editar Usuário</span>
                </a>

                <form action="{{ route('users.destroy', $user['id']) }}" 
                      method="POST" 
                      onsubmit="return confirm('Excluir Tem certeza que deseja excluir o usuário \'{{ $user['name'] }}\'?\n\nEsta ação não pode ser desfeita.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="action-button delete">
                        <span class="action-icon"></span>
                        <span class="action-text">Excluir Usuário</span>
                    </button>
                </form>

                @if(isset($user['company']))
                <a href="{{ route('companies.show', $user['company']['id']) }}" class="action-button view">
                    <span class="action-icon"></span>
                    <span class="action-text">Ver Empresa</span>
                </a>
                @endif
            </div>
        </div>

        <!-- Card de Estatísticas -->
        <div class="details-card stats-card">
            <h3 class="card-title">Resumo</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-icon"></span>
                    <div class="stat-content">
                        <span class="stat-value">{{ $user['tipo'] === 'admin' ? 'Admin' : 'Comum' }}</span>
                        <span class="stat-label">Tipo</span>
                    </div>
                </div>

                <div class="stat-item">
                    <span class="stat-icon"></span>
                    <div class="stat-content">
                        <span class="stat-value">{{ isset($user['company']) ? 'Sim' : 'Não' }}</span>
                        <span class="stat-label">Empresa</span>
                    </div>
                </div>

                <div class="stat-item">
                    <span class="stat-icon"></span>
                    <div class="stat-content">
                        <span class="stat-value">{{ $user['phone'] ? 'Sim' : 'Não' }}</span>
                        <span class="stat-label">Telefone</span>
                    </div>
                </div>
            </div>
        </div>
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

.page-header-actions {
    display: flex;
    gap: 10px;
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
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
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

.details-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.details-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    color: #333;
}

.main-card {
    grid-row: span 2;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-bottom: 25px;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 25px;
}

.user-avatar {
    width: 80px;
    height: 80px;
    background: #3E4A59;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
}

.user-header-info {
    flex: 1;
}

.user-name {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 2rem;
    font-weight: bold;
}

.user-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.badge {
    padding: 6px 12px;
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

.badge-id {
    background: #6c757d;
    color: white;
}

.info-section {
    margin-bottom: 30px;
}

.section-title {
    color: #2c3e50;
    font-size: 1.3rem;
    margin-bottom: 15px;
    font-weight: 600;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.info-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.info-icon {
    font-size: 1.5rem;
    margin-top: 3px;
}

.info-content {
    display: flex;
    flex-direction: column;
    gap: 5px;
    flex: 1;
}

.info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
}

.info-value {
    color: #2c3e50;
    font-size: 1rem;
    font-weight: 500;
}

.info-value.mono {
    font-family: 'Courier New', monospace;
}

.company-link {
    color: #3E4A59;
    text-decoration: none;
    font-weight: 600;
}

.company-link:hover {
    text-decoration: underline;
}

.card-title {
    color: #2c3e50;
    font-size: 1.3rem;
    margin: 0 0 20px 0;
    font-weight: 600;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.action-button {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    width: 100%;
}

.action-button.edit {
    background: #fff3e0;
    color: #f57c00;
}

.action-button.edit:hover {
    background: #f57c00;
    color: white;
    transform: translateX(5px);
}

.action-button.delete {
    background: #ffebee;
    color: #d32f2f;
}

.action-button.delete:hover {
    background: #d32f2f;
    color: white;
    transform: translateX(5px);
}

.action-button.view {
    background: #e3f2fd;
    color: #1976d2;
}

.action-button.view:hover {
    background: #1976d2;
    color: white;
    transform: translateX(5px);
}

.action-icon {
    font-size: 1.2rem;
}

.action-text {
    flex: 1;
    text-align: left;
}

.stats-grid {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 8px;
}

.stat-icon {
    font-size: 2rem;
}

.stat-content {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.stat-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #2c3e50;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
}

/* Responsividade */
@media (max-width: 1024px) {
    .details-container {
        grid-template-columns: 1fr;
    }
    
    .main-card {
        grid-row: auto;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .page-header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .card-header {
        flex-direction: column;
        text-align: center;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection

