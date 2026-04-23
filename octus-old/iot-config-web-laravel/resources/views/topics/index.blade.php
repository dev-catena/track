@extends('layouts.app')

@section('title', 'Tópicos MQTT')

@section('content')
<div class="admin-dashboard">
    <div class="page-header">
        <h1>Tópicos MQTT</h1>
        <p>Gerencie e monitore todos os tópicos MQTT do sistema</p>
    </div>

    <!-- Estatísticas dos Tópicos -->
    <div class="quick-stats">
        <h2>Estatísticas dos Tópicos</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="stat-item">
                <div class="stat-value">{{ $stats['totalTopics'] ?? 0 }}</div>
                <div class="stat-label">Total de Tópicos</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['activeTopics'] ?? 0 }}</div>
                <div class="stat-label">Tópicos Ativos</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['deviceTopics'] ?? 0 }}</div>
                <div class="stat-label">Tópicos de Dispositivos</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $stats['systemTopics'] ?? 0 }}</div>
                <div class="stat-label">Tópicos do Sistema</div>
            </div>
        </div>
    </div>

    <!-- Lista de Tópicos -->
    <div class="dashboard-card">
        <div class="flex justify-between items-center mb-6">
            <h2>Lista de Tópicos</h2>
            <button onclick="refreshTopics()" class="btn-primary">
                Atualizar
            </button>
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

        @if($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(isset($topics) && count($topics) > 0)
            <div class="topics-list">
                @foreach($topics as $topic)
                    <div class="topic-item">
                        <div class="topic-header">
                            <div class="topic-name">
                                <h3>{{ $topic['name'] ?? 'N/A' }}</h3>
                                <span class="topic-type">{{ $topic['type'] ?? 'device' }}</span>
                            </div>
                            <div class="topic-actions">
                                <button onclick="viewTopic('{{ $topic['id'] ?? '' }}'); event.stopPropagation();" class="btn-outline-primary">
                                    Ver Ver
                                </button>
                                <button onclick="editTopic('{{ $topic['id'] ?? '' }}'); event.stopPropagation();" class="btn-outline-primary">
                                    Editar Editar
                                </button>
                                <button onclick="showTestCommands('{{ $topic['name'] ?? '' }}'); event.stopPropagation();" class="btn-outline-success">
                                    🎮 Testar MQTT
                                </button>
                                <button onclick="deleteTopic('{{ $topic['id'] ?? '' }}'); event.stopPropagation();" class="btn-outline-danger">
                                    Excluir Excluir
                                </button>
                            </div>
                        </div>
                        <div class="topic-details">
                            <p><strong>ID:</strong> {{ $topic['id'] ?? 'N/A' }}</p>
                            <p><strong>Descrição:</strong> {{ $topic['description'] ?? 'Sem descrição' }}</p>
                            <p><strong>Criado em:</strong> {{ $topic['created_at'] ?? 'N/A' }}</p>
                            <p><strong>Status:</strong>
                                <span class="status-badge {{ $topic['status'] ?? 'active' }}">
                                    {{ $topic['status'] ?? 'Ativo' }}
                                </span>
                            </p>
                            @if(isset($topic['name']) && (str_starts_with($topic['name'], 'iot-') || str_starts_with($topic['name'], 'iot/')))
                                <div class="api-endpoint">
                                    <p><strong>URL para Postman:</strong></p>
                                    <div class="endpoint-box">
                                        <code class="endpoint-url">POST http://{{ request()->getHost() }}:8000/api/mqtt/send-command</code>
                                        <button onclick="copyToClipboard('POST http://{{ request()->getHost() }}:8000/api/mqtt/send-command')" class="copy-btn" title="Copiar URL">
                                            
                                        </button>
                                    </div>
                                    <div class="endpoint-example">
                                        <p><strong>Body (JSON):</strong></p>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <code style="flex: 1; margin-right: 0;">{"topic": "{{ $topic['name'] }}", "command": "led_on"}</code>
                                            <button onclick="copyToClipboard('{\"topic\": \"{{ $topic['name'] }}\", \"command\": \"led_on\"}')" class="copy-btn"></button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="no-topics">
                <div class="text-center py-8">
                    <div class="text-6xl mb-4"></div>
                    <h3 class="text-xl font-semibold mb-2">Nenhum tópico encontrado</h3>
                    <p class="text-gray-600 mb-4">Não há tópicos MQTT cadastrados no sistema.</p>
                    <button onclick="createTopic()" class="btn-primary">
                        + Criar Primeiro Tópico
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal para criar/editar tópico -->
    <div id="topicModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Criar Tópico MQTT</h3>
                <button onclick="closeModal()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="topicForm">
                    <div class="form-group">
                        <label for="topicName">Nome do Tópico</label>
                        <input type="text" id="topicName" name="name" required class="form-input" placeholder="Ex: device/sensor/temperature">
                    </div>
                    <div class="form-group">
                        <label for="topicDescription">Descrição</label>
                        <textarea id="topicDescription" name="description" class="form-input" rows="3" placeholder="Descreva o propósito deste tópico"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="topicType">Tipo</label>
                        <select id="topicType" name="type" class="form-input">
                            <option value="device">Dispositivo</option>
                            <option value="system">Sistema</option>
                            <option value="sensor">Sensor</option>
                            <option value="actuator">Atuador</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" class="btn-secondary">Cancelar</button>
                <button onclick="saveTopic()" class="btn-primary">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Teste MQTT -->
<div id="mqttTestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🎮 Teste de Comandos MQTT</h2>
            <button onclick="closeMqttTestModal()" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="mqtt-test-info">
                <p><strong>Tópico:</strong> <span id="testTopicName">-</span></p>
                <p><strong>Status da Conexão:</strong> <span id="connectionStatus" class="status-checking">Verificando...</span></p>
            </div>

            <div class="mqtt-commands-grid">
                <div class="command-group">
                    <h3>Controle de LED</h3>
                    <div class="command-buttons">
                        <button onclick="sendMqttCommand('led_on')" class="cmd-btn cmd-led-on">
                            Ligar LED
                        </button>
                        <button onclick="sendMqttCommand('led_off')" class="cmd-btn cmd-led-off">
                            Desligar LED
                        </button>
                        <button onclick="sendMqttCommand('led_blink')" class="cmd-btn cmd-led-blink">
                            💫 LED Piscar
                        </button>
                    </div>
                </div>

                <div class="command-group">
                    <h3>Monitoramento</h3>
                    <div class="command-buttons">
                        <button onclick="sendMqttCommand('status')" class="cmd-btn cmd-status">
                            Solicitar Status
                        </button>
                    </div>
                </div>

                <div class="command-group">
                    <h3>Controle Avançado</h3>
                    <div class="command-buttons">
                        <button onclick="sendMqttCommand('reset')" class="cmd-btn cmd-reset" data-confirm="true">
                            Factory Reset
                        </button>
                    </div>
                </div>

                <div class="command-group">
                    <h3>Comando Personalizado</h3>
                    <div class="custom-command">
                        <input type="text" id="customCommand" placeholder='Ex: {"command": "custom", "value": 123}' class="form-input">
                        <button onclick="sendCustomCommand()" class="cmd-btn cmd-custom">
                            📤 Enviar
                        </button>
                    </div>
                </div>
            </div>

            <div class="mqtt-response">
                <h4>📨 Resposta do Comando:</h4>
                <div id="mqttResponse" class="response-area">
                    <em>Nenhum comando enviado ainda...</em>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="clearResponse()" class="btn-secondary">Excluir Limpar</button>
            <button onclick="closeMqttTestModal()" class="btn-primary">Fechar</button>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Desativação -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Excluir Confirmar Exclusão</h2>
            <button onclick="closeDeleteModal()" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="delete-warning">
                <div class="warning-icon"></div>
                <h3>Excluir Tópico</h3>
                <p>Você tem certeza que deseja <strong>excluir permanentemente</strong> o tópico:</p>
                <div class="topic-name-highlight">
                    <strong id="deleteTopicName">-</strong>
                </div>
                <div class="warning-details">
                    <p><strong>Esta ação irá:</strong></p>
                    <ul>
                        <li>Excluir <strong>Excluir o tópico permanentemente</strong></li>
                        <li>Remover da listagem completamente</li>
                        <li> Interromper todos os comandos MQTT</li>
                        <li>Apagar todos os dados relacionados</li>
                    </ul>
                    <p><em><strong>ATENÇÃO:</strong> Esta ação não pode ser desfeita!</em></p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeDeleteModal()" class="btn-secondary">
                Cancelar
            </button>
            <button onclick="confirmDelete()" class="btn-danger">
                Excluir Confirmar Exclusão
            </button>
        </div>
    </div>
</div>

<style>
.topics-list {
    space-y: 1rem;
}

.topic-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden;
}

.topic-item:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.topic-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.topic-name h3 {
    margin: 0;
    color: var(--color-primary-dark);
    font-size: 1.2rem;
    font-weight: 600;
}

.topic-type {
    background: var(--color-primary-lightest);
    color: var(--color-primary-dark);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: 0.5rem;
}

.topic-actions {
    display: flex;
    gap: 0.5rem;
}

.topic-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
}

.topic-details p {
    margin: 0.25rem 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.no-topics {
    text-align: center;
    padding: 2rem;
}

.text-6xl {
    font-size: 4rem;
    line-height: 1;
}

.text-xl {
    font-size: 1.25rem;
    line-height: 1.75rem;
}

.font-semibold {
    font-weight: 600;
}

.text-gray-600 {
    color: #6b7280;
}

.py-8 {
    padding-top: 2rem;
    padding-bottom: 2rem;
}

.mb-4 {
    margin-bottom: 1rem;
}

.mb-2 {
    margin-bottom: 0.5rem;
}

/* Modal styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
    margin: 0;
    color: #000000 !important;
    font-size: 1.5rem;
    font-weight: 600;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-btn:hover {
    color: #495057;
}

.modal-body {
    padding: 1.5rem;
    color: #000000 !important;
}

.modal-body * {
    color: #000000 !important;
}

.modal h2 {
    color: #000000 !important;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.flex {
    display: flex;
}

.justify-between {
    justify-content: space-between;
}

.items-center {
    align-items: center;
}

.mb-6 {
    margin-bottom: 1.5rem;
}

.text-center {
    text-align: center;
}

.grid {
    display: grid;
}

.grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
    .md\:grid-cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

.gap-4 {
    gap: 1rem;
}

.space-y-1 > * + * {
    margin-top: 0.25rem;
}

/* Alert styles */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-weight: 500;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert ul {
    margin: 0.5rem 0 0 0;
    padding-left: 1.5rem;
}

.alert li {
    margin: 0.25rem 0;
}

/* Botão de exclusão com cor vermelha */
.text-red-600 {
    color: #dc2626 !important;
}

.btn-outline-primary.text-red-600 {
    color: #dc2626 !important;
    border-color: #dc2626 !important;
}

.btn-outline-primary.text-red-600:hover {
    background-color: #dc2626 !important;
    color: white !important;
}

/* Animações para exclusão */
.topic-item {
    transition: all 0.3s ease;
}

.topic-item.removing {
    opacity: 0.5;
    transform: scale(0.95);
}

/* Estilos do Modal de Teste MQTT */
.mqtt-test-info {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    color: #000000 !important;
}

.mqtt-test-info p {
    margin: 0.5rem 0;
    font-size: 0.9rem;
    color: #000000 !important;
}

.status-checking {
    color: #6c757d;
    font-style: italic;
}

.status-connected {
    color: #28a745;
    font-weight: bold;
}

.status-warning {
    color: #ffc107;
    font-weight: bold;
}

.status-error {
    color: #dc3545;
    font-weight: bold;
}

.mqtt-commands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.command-group {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.command-group h3 {
    margin: 0 0 1rem 0;
    color: #343a40;
    font-size: 1.1rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.command-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.cmd-btn {
    background: linear-gradient(135deg, #3E4A59, #2d3642);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: left;
}

.cmd-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

.cmd-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.cmd-led-on {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}

.cmd-led-off {
    background: linear-gradient(135deg, #6c757d, #495057);
}

.cmd-led-blink {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #212529;
}

.cmd-status {
    background: linear-gradient(135deg, #17a2b8, #117a8b);
}

.cmd-reset {
    background: linear-gradient(135deg, #dc3545, #b02a37);
}

.cmd-custom {
    background: linear-gradient(135deg, #6f42c1, #59359a);
}

.custom-command {
    display: flex;
    gap: 0.75rem;
    align-items: stretch;
}

.custom-command input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 0.9rem;
}

.mqtt-response {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
}

.mqtt-response h4 {
    margin: 0 0 1rem 0;
    color: #495057;
    font-size: 1rem;
}

.response-area {
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem;
    min-height: 100px;
    max-height: 300px;
    overflow-y: auto;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.85rem;
    line-height: 1.4;
    white-space: pre-wrap;
}

.response-success {
    border-left: 4px solid #28a745;
    background: #d4edda;
}

.response-error {
    border-left: 4px solid #dc3545;
    background: #f8d7da;
}

.response-info {
    border-left: 4px solid #17a2b8;
    background: #d1ecf1;
}

.btn-outline-success {
    background: transparent;
    color: #28a745;
    border: 1px solid #28a745;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-outline-success:hover {
    background: #28a745;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.btn-outline-danger {
    background: transparent;
    color: #dc3545;
    border: 1px solid #dc3545;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-outline-danger:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

/* Estilos da Modal de Confirmação de Desativação */
.delete-warning {
    text-align: center;
    padding: 1rem;
}

.warning-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: #dc3545;
}

.delete-warning h3 {
    color: #dc3545;
    margin: 1rem 0;
    font-size: 1.5rem;
}

.topic-name-highlight {
    background: #f8f9fa;
    border: 2px solid #dc3545;
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
    font-size: 1.1rem;
    color: #dc3545;
}

.warning-details {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
    text-align: left;
}

.warning-details p {
    margin: 0.5rem 0;
    color: #856404;
}

.warning-details ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.warning-details li {
    margin: 0.25rem 0;
    color: #856404;
}

.warning-details em {
    color: #0c5460;
    font-style: italic;
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #b02a37);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c82333, #9a1e2a);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.btn-danger:active {
    transform: translateY(0);
}

.btn-danger:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Estilos do Endpoint API */
.api-endpoint {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

/* Definição principal da endpoint-box - responsiva */
.endpoint-box {
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden;
}

.endpoint-url {
    flex: 1;
    background: transparent;
    border: none;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.85rem;
    color: #495057;
    word-break: break-word;
    overflow-wrap: anywhere;
    padding: 0.5rem;
    min-width: 0;
    max-width: 100%;
    white-space: normal;
    line-height: 1.4;
    hyphens: auto;
}

.copy-btn {
    background: #3E4A59;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.25rem 0.5rem;
    margin-left: 0.5rem;
    cursor: pointer;
    font-size: 0.8rem;
    transition: background 0.3s ease;
}

.copy-btn:hover {
    background: #2d3642;
}

.copy-btn:active {
    background: #28a745;
}

.endpoint-example {
    background: #e9ecef;
    border-radius: 4px;
    padding: 0.5rem;
    margin-top: 0.5rem;
}

.endpoint-example {
    background: #e9ecef;
    border-radius: 6px;
    padding: 1rem;
    margin-top: 1rem;
    width: 100%;
}

.endpoint-example code {
    background: transparent;
    color: #28a745;
    font-size: 0.95rem;
    font-weight: 500;
    padding: 0.5rem;
    border-radius: 4px;
    background: white;
    border: 1px solid #dee2e6;
    display: inline-block;
    width: 100%;
    margin-right: 0.5rem;
}

/* Estilos para comandos MQTT */
.command-examples {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 1rem;
    margin-top: 0.5rem;
}

.command-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 0.5rem;
    margin: 0.25rem 0;
}

.command-item code {
    flex: 1;
    background: transparent;
    color: #28a745;
    font-weight: 500;
    font-size: 0.9rem;
}

.endpoint-box label {
    display: block;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}

.api-endpoint {
    border: 2px solid #e3f2fd;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1rem;
    background: linear-gradient(145deg, #f8f9ff, #ffffff);
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden;
}

/* Definições duplicadas removidas - usando apenas a principal acima */

@media (max-width: 768px) {
    .mqtt-commands-grid {
        grid-template-columns: 1fr;
    }
    
    .custom-command {
        flex-direction: column;
    }
    
    .endpoint-box {
        flex-direction: column;
        min-width: auto;
        width: 100%;
        align-items: stretch;
    }
    
    .copy-btn {
        margin-left: 0;
        margin-top: 0.5rem;
    }
}
</style>

<script>
let currentTopicId = null;

function refreshTopics() {
    location.reload();
}


function createTopic() {
    currentTopicId = null;
    document.getElementById('modalTitle').textContent = 'Criar Tópico MQTT';
    document.getElementById('topicForm').reset();
    document.getElementById('topicModal').classList.add('show');
}

function editTopic(topicId) {
    currentTopicId = topicId;
    document.getElementById('modalTitle').textContent = 'Editar Tópico MQTT';
    
    // Carregar dados do tópico
    fetch(`/topics/${topicId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const topic = data.topic;
                document.getElementById('topicName').value = topic.name || '';
                document.getElementById('topicDescription').value = topic.description || '';
                document.getElementById('topicType').value = topic.type || 'device';
            } else {
                showAlert('Erro ao carregar dados do tópico', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao carregar dados do tópico', 'error');
        });
    
    document.getElementById('topicModal').classList.add('show');
}

function viewTopic(topicId) {
    alert('Visualizar tópico: ' + topicId);
}

// Variáveis para modal de confirmação
let pendingDeleteTopicId = null;
let pendingDeleteTopicName = null;

function deleteTopic(topicId) {
    // Encontrar o nome do tópico na interface
    const topicElement = event.target.closest('.topic-item');
    const topicNameElement = topicElement.querySelector('.topic-name h3');
    const topicName = topicNameElement ? topicNameElement.textContent.trim() : 'tópico selecionado';
    
    // Salvar informações para confirmação
    pendingDeleteTopicId = topicId;
    pendingDeleteTopicName = topicName;
    
    // Atualizar modal e mostrar
    document.getElementById('deleteTopicName').textContent = topicName;
    document.getElementById('deleteConfirmModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').classList.remove('show');
    pendingDeleteTopicId = null;
    pendingDeleteTopicName = null;
}

function confirmDelete() {
    if (!pendingDeleteTopicId) {
        console.error('Erro: ID do tópico não encontrado');
        closeDeleteModal();
        return;
    }

    // Mostrar loading no botão de confirmação
    const confirmBtn = document.querySelector('#deleteConfirmModal .btn-danger');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = '⏳ Excluindo...';
    confirmBtn.disabled = true;

    // Criar formulário para exclusão permanente
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/topics/${pendingDeleteTopicId}`;

    // Adicionar CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    // Adicionar método DELETE
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);

    // Adicionar ao DOM e submeter
    document.body.appendChild(form);
    form.submit();
}

function showAlert(message, type = 'success') {
    // Remover alertas existentes
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());

    // Criar novo alerta
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;

    // Inserir no topo da lista de tópicos
    const topicsCard = document.querySelector('.dashboard-card');
    const topicsList = topicsCard.querySelector('.topics-list, .no-topics');
    topicsCard.insertBefore(alert, topicsList);

    // Remover após 5 segundos
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function updateStats() {
    const topicItems = document.querySelectorAll('.topic-item');
    const totalTopics = topicItems.length;
    const activeTopics = Array.from(topicItems).filter(item =>
        !item.querySelector('.status-badge')?.textContent.includes('inactive')
    ).length;

    // Atualizar estatísticas na página
    const statValues = document.querySelectorAll('.stat-value');
    if (statValues.length >= 2) {
        statValues[0].textContent = totalTopics;
        statValues[1].textContent = activeTopics;
    }
}

function closeModal() {
    document.getElementById('topicModal').classList.remove('show');
}

function saveTopic() {
    const form = document.getElementById('topicForm');
    const formData = new FormData(form);

    // Validar campos obrigatórios
    const name = formData.get('name');
    const description = formData.get('description');
    const type = formData.get('type');

    if (!name || !type) {
        showAlert('Por favor, preencha todos os campos obrigatórios', 'error');
        return;
    }

    if (currentTopicId) {
        // Editar tópico existente
        fetch(`/topics/${currentTopicId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                name: name,
                description: description,
                type: type
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Tópico editado com sucesso!', 'success');
                closeModal();
                // Recarregar a página para mostrar as mudanças
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Erro ao editar tópico', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro de conexão ao editar tópico', 'error');
        });
    } else {
        // Criar novo tópico
        const newTopicId = Date.now(); // ID único baseado em timestamp

        // Criar elemento do tópico
        const topicItem = document.createElement('div');
        topicItem.className = 'topic-item';
        topicItem.innerHTML = `
            <div class="topic-header">
                <div class="topic-name">
                    <h3>${name}</h3>
                    <span class="topic-type">${type}</span>
                </div>
                <div class="topic-actions">
                    <button onclick="viewTopic('${newTopicId}')" class="btn-outline-primary">
                        Ver Ver
                    </button>
                    <button onclick="editTopic('${newTopicId}')" class="btn-outline-primary">
                        Editar Editar
                    </button>
                    <button onclick="deleteTopic('${newTopicId}')" class="btn-outline-primary text-red-600">
                        Desativar
                    </button>
                </div>
            </div>
            <div class="topic-details">
                <p><strong>ID:</strong> ${newTopicId}</p>
                <p><strong>Descrição:</strong> ${description || 'Sem descrição'}</p>
                <p><strong>Criado em:</strong> ${new Date().toLocaleString('pt-BR')}</p>
                <p><strong>Status:</strong>
                    <span class="status-badge active">Ativo</span>
                </p>
            </div>
        `;

        // Adicionar à lista de tópicos
        const topicsList = document.querySelector('.topics-list');
        if (topicsList) {
            topicsList.appendChild(topicItem);

            // Se não havia tópicos, remover mensagem de "nenhum tópico"
            const noTopics = document.querySelector('.no-topics');
            if (noTopics) {
                noTopics.remove();
            }
        }

        showAlert(`Tópico '${name}' criado com sucesso! (Modo demonstração)`, 'success');
        updateStats();
    }

    closeModal();
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('topicModal');
    const mqttModal = document.getElementById('mqttTestModal');
    const deleteModal = document.getElementById('deleteConfirmModal');
    
    if (event.target === modal && modal.classList.contains('show')) {
        closeModal();
    }
    if (event.target === mqttModal && mqttModal.classList.contains('show')) {
        closeMqttTestModal();
    }
    if (event.target === deleteModal && deleteModal.classList.contains('show')) {
        closeDeleteModal();
    }
}

// Variáveis globais para teste MQTT
let currentTestTopic = null;

// Mostrar modal de teste de comandos MQTT
function showTestCommands(topicName) {
    console.log('🎮 Abrindo teste MQTT para tópico:', topicName);
    
    currentTestTopic = topicName;
    
    // Atualizar informações do modal
    document.getElementById('testTopicName').textContent = topicName;
    document.getElementById('connectionStatus').textContent = 'Verificando...';
    document.getElementById('connectionStatus').className = 'status-checking';
    
    // Limpar resposta anterior
    clearResponse();
    
    // Mostrar modal
    document.getElementById('mqttTestModal').classList.add('show');
    
    // Verificar conexão com o dispositivo
    checkDeviceConnection(topicName);
}

// Fechar modal de teste MQTT
function closeMqttTestModal() {
    document.getElementById('mqttTestModal').classList.remove('show');
    currentTestTopic = null;
}

// Verificar conexão com o dispositivo
async function checkDeviceConnection(topicName) {
    try {
        updateResponse('Verificando conexão com o dispositivo...', 'info');
        
        // Tentar encontrar IP do dispositivo baseado no tópico
        const response = await fetch('/api/topics/test-connection', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ topic: topicName })
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.mqtt_available) {
                document.getElementById('connectionStatus').textContent = 'Conectado (MQTT)';
                document.getElementById('connectionStatus').className = 'status-connected';
                updateResponse('Dispositivo encontrado com suporte MQTT completo!', 'success');
            } else {
                document.getElementById('connectionStatus').textContent = 'Sem MQTT';
                document.getElementById('connectionStatus').className = 'status-warning';
                updateResponse(`Dispositivo encontrado em ${data.device_ip} mas sem suporte MQTT.\n\n${data.suggestion || 'Atualize o WiFi Manager para a versão com MQTT.'}`, 'error');
            }
        } else if (response.status === 206) {
            // Dispositivo encontrado mas sem MQTT
            const data = await response.json();
            document.getElementById('connectionStatus').textContent = 'Sem MQTT';
            document.getElementById('connectionStatus').className = 'status-warning';
            updateResponse(`${data.message}\n\nIP: ${data.device_ip}\n\n${data.suggestion}`, 'error');
        } else {
            document.getElementById('connectionStatus').textContent = 'Desconectado';
            document.getElementById('connectionStatus').className = 'status-error';
            updateResponse('Nenhum dispositivo encontrado na rede. Verifique se o Raspberry Pi está conectado.', 'error');
        }
    } catch (error) {
        document.getElementById('connectionStatus').textContent = 'Erro de conexão';
        document.getElementById('connectionStatus').className = 'status-error';
        updateResponse('Erro ao verificar conexão: ' + error.message, 'error');
    }
}

// Enviar comando MQTT
async function sendMqttCommand(command) {
    if (!currentTestTopic) {
        updateResponse('Nenhum tópico selecionado', 'error');
        return;
    }
    
    // Confirmação para comandos perigosos
    if (command === 'reset') {
        if (!confirm('ATENÇÃO: Isso fará um Factory Reset do dispositivo, apagando todas as configurações. Continuar?')) {
            return;
        }
    }
    
    const payload = command;
    await sendMqttMessage(payload);
}

// Enviar comando personalizado
async function sendCustomCommand() {
    const customInput = document.getElementById('customCommand');
    const customCommand = customInput.value.trim();
    
    if (!customCommand) {
        updateResponse('Digite um comando personalizado', 'error');
        return;
    }
    
    try {
        const payload = JSON.parse(customCommand);
        await sendMqttMessage(payload);
        customInput.value = ''; // Limpar campo após envio
    } catch (error) {
        updateResponse('Comando inválido. Use formato JSON válido: {"command": "valor"}', 'error');
    }
}

// Enviar mensagem MQTT via API - ATUALIZADO v2.0
async function sendMqttMessage(payload) {
    if (!currentTestTopic) {
        updateResponse('Nenhum tópico selecionado', 'error');
        return;
    }
    
    try {
        updateResponse(`📤 Enviando comando: ${typeof payload === 'string' ? payload : JSON.stringify(payload)}`, 'info');
        
        // Desabilitar botões temporariamente
        disableCommandButtons(true);
        
        const response = await fetch('http://{{ request()->getHost() }}:8000/api/mqtt/send-command?v=' + Date.now(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                topic: currentTestTopic,
                command: payload
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            updateResponse(`Comando enviado com sucesso!\n\nDetalhes:\n${JSON.stringify(data, null, 2)}`, 'success');
        } else {
            updateResponse(`Erro ao enviar comando:\n${data.message || 'Erro desconhecido'}`, 'error');
        }
        
    } catch (error) {
        updateResponse(`Erro na comunicação: ${error.message}`, 'error');
    } finally {
        // Reabilitar botões
        setTimeout(() => disableCommandButtons(false), 1000);
    }
}

// Desabilitar/habilitar botões de comando
function disableCommandButtons(disabled) {
    const buttons = document.querySelectorAll('.cmd-btn');
    buttons.forEach(btn => {
        btn.disabled = disabled;
    });
}

// Atualizar área de resposta
function updateResponse(message, type = 'info') {
    const responseArea = document.getElementById('mqttResponse');
    const timestamp = new Date().toLocaleTimeString();
    
    // Adicionar timestamp à mensagem
    const fullMessage = `[${timestamp}] ${message}`;
    
    // Se já há conteúdo, adicionar nova linha
    if (responseArea.textContent && !responseArea.textContent.includes('Nenhum comando')) {
        responseArea.textContent += '\n\n' + fullMessage;
    } else {
        responseArea.textContent = fullMessage;
    }
    
    // Aplicar classe de estilo
    responseArea.className = `response-area response-${type}`;
    
    // Scroll para o final
    responseArea.scrollTop = responseArea.scrollHeight;
}

// Limpar área de resposta
function clearResponse() {
    const responseArea = document.getElementById('mqttResponse');
    responseArea.textContent = 'Nenhum comando enviado ainda...';
    responseArea.className = 'response-area';
}

// Copiar qualquer texto para clipboard (função genérica)
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            // Feedback visual
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = '';
            button.style.background = '#28a745';
            
            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '#3E4A59';
            }, 2000);
            
            // Mostrar notificação
            showAlert('Copiado para área de transferência!', 'success');
        }).catch(() => {
            copyToClipboardFallback(text);
        });
    } else {
        copyToClipboardFallback(text);
    }
}

// Copiar endpoint para clipboard (compatibilidade)
function copyEndpoint(topicName) {
    const endpoint = `POST http://${window.location.hostname}:8000/api/mqtt/${topicName}`;
    copyToClipboard(endpoint);
}

// Fallback para copiar sem clipboard API
function copyToClipboardFallback(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        showAlert('Endpoint copiado!', 'success');
    } catch (err) {
        showAlert('Erro ao copiar. Copie manualmente.', 'error');
    }
    
    document.body.removeChild(textArea);
}
</script>
@endsection
