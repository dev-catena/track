@extends('layouts.app')

@section('title', 'Criar Usuário')

@section('content')
<div class="admin-dashboard">
    <div class="page-header">
        <div class="page-header-content">
            <h1 style="color: #eeeeee;">+ Criar Usuário</h1>
            <p>Cadastrar novo usuário no sistema</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                ← Voltar
            </a>
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    <div class="dashboard-card">
        <form action="{{ route('users.store') }}" method="POST" class="user-form">
            @csrf

            <div class="form-section">
                <h3 class="section-title">Dados Pessoais</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="form-label">
                            Nome Completo <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-input" 
                               placeholder="Digite o nome completo"
                               value="{{ old('name') }}"
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="form-label">
                             Email <span class="required">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-input" 
                               placeholder="email@exemplo.com"
                               value="{{ old('email') }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">
                            Telefone
                        </label>
                        <input type="text" 
                               id="phone" 
                               name="phone" 
                               class="form-input" 
                               placeholder="+55 (11) 99999-9999"
                               value="{{ old('phone') }}">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Credenciais de Acesso</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">
                             Senha <span class="required">*</span>
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input" 
                               placeholder="Mínimo 6 caracteres"
                               required
                               minlength="6">
                        <small class="form-help">Mínimo de 6 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">
                             Confirmar Senha <span class="required">*</span>
                        </label>
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               class="form-input" 
                               placeholder="Repita a senha"
                               required
                               minlength="6">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Informações Organizacionais</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_comp" class="form-label">
                            Empresa
                        </label>
                        <select id="id_comp" name="id_comp" class="form-select">
                            <option value="">Sem empresa</option>
                            @foreach($companies as $company)
                                <option value="{{ $company['id'] }}" {{ old('id_comp') == $company['id'] ? 'selected' : '' }}>
                                    {{ $company['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tipo" class="form-label">
                            Tipo de Usuário <span class="required">*</span>
                        </label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            <option value="">Selecione o tipo</option>
                            <option value="admin" {{ old('tipo') == 'admin' ? 'selected' : '' }}>Administrador</option>
                            <option value="comum" {{ old('tipo') == 'comum' ? 'selected' : '' }}>Comum</option>
                        </select>
                        <small class="form-help">
                            <strong>Admin:</strong> Acesso total ao sistema |
                            <strong>Comum:</strong> Acesso limitado
                        </small>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Criar Usuário
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
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

.dashboard-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    color: #333;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
    animation: slideDown 0.3s ease;
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

.user-form {
    max-width: 900px;
}

.form-section {
    margin-bottom: 35px;
    padding-bottom: 30px;
    border-bottom: 2px solid #e9ecef;
}

.form-section:last-of-type {
    border-bottom: none;
}

.section-title {
    color: #2c3e50;
    font-size: 1.4rem;
    margin-bottom: 20px;
    font-weight: 600;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    font-size: 1rem;
}

.required {
    color: #dc3545;
    font-weight: bold;
}

.form-input,
.form-select {
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #3E4A59;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-select {
    cursor: pointer;
}

.form-help {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 5px;
}

.form-actions {
    display: flex;
    gap: 15px;
    padding-top: 20px;
    border-top: 2px solid #e9ecef;
    margin-top: 30px;
}

/* Responsividade */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Validação de senha
document.getElementById('password_confirmation').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmation = this.value;
    
    if (password !== confirmation && confirmation.length >= 6) {
        this.setCustomValidity('As senhas não coincidem');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('password').addEventListener('input', function() {
    const confirmation = document.getElementById('password_confirmation');
    if (confirmation.value) {
        confirmation.dispatchEvent(new Event('input'));
    }
});
</script>
@endsection

