@extends('layouts.app')

@section('title', 'Configurar Dispositivo')

@section('content')
<div class="device-config-container">
    @if(session('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-lg font-medium">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="device-card">
        <h2>📱 Configuração de Dispositivo IoT</h2>
        <p>Complete a configuração do seu dispositivo IoT</p>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Verificar se MAC existe no localStorage -->
        <div id="mac-check" class="alert alert-info">
            <span class="spinner"></span> Verificando dispositivo...
            </div>

        <!-- Botões de teste para debug -->
        <div class="debug-section" style="margin-bottom: 1rem;">
            <h4>🧪 Testes de Debug</h4>
            <button onclick="testLocalStorage()" class="btn btn-primary" style="margin: 0.25rem;">
                🔍 Verificar localStorage
            </button>
            <button onclick="clearLocalStorage()" class="btn btn-secondary" style="margin: 0.25rem;">
                🗑️ Limpar localStorage
            </button>
            <button onclick="setTestMAC()" class="btn btn-warning" style="margin: 0.25rem;">
                🧪 Definir MAC teste
            </button>
            <button onclick="debugMACNow()" class="btn btn-info" style="margin: 0.25rem;">
                🔍 Debug imediato
            </button>
        </div>

        <form method="POST" action="{{ route('device.save') }}" class="device-form" id="device-config-form" style="display: none;">
            @csrf
            
            <!-- Campo hidden para MAC address -->
            <input type="hidden" id="mac_address" name="mac_address" value="">
            
            <div class="device-info-section">
                <h3>📟 Dispositivo ESP32 Detectado Automaticamente</h3>
                <div class="detected-device">
                    <div class="device-info-item">
                        <strong>MAC Address:</strong>
                        <span id="display-mac" class="mono mac-display">-</span>
                    </div>
                    <div class="device-status">
                        <span class="status-auto">✅ Detectado automaticamente</span>
                    </div>
                </div>
                

            </div>

            <div class="form-group">
                <label for="device_name">🏷️ Nome do Dispositivo</label>
                <input
                    type="text"
                    id="device_name"
                    name="device_name"
                    value="{{ old('device_name') }}"
                    required
                    class="form-input"
                    placeholder="Ex: Sensor Temperatura Sala A1"
                />
                <small class="text-gray-300 text-sm">Nome identificador do dispositivo</small>
            </div>

            <div class="form-group">
                <label for="device_type">⚙️ Tipo do Dispositivo</label>
                <select id="device_type" name="device_type" required class="form-input">
                    <option value="">Selecione o tipo</option>
                    <!-- Opções carregadas dinamicamente via JavaScript -->
                </select>
                <small class="text-gray-300 text-sm">Tipo funcional do dispositivo IoT</small>
            </div>

            <div class="form-group">
                <label for="department">🏢 Departamento</label>
                <select id="department" name="department" required class="form-input">
                    <option value="">Selecione o departamento</option>
                    <!-- Opções carregadas dinamicamente via JavaScript -->
                </select>
                <small class="text-gray-300 text-sm">Departamento onde o dispositivo será instalado</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-button" id="submitBtn">
                    <span id="submitText">📡 Criar Tópico MQTT e Configurar Dispositivo</span>
                </button>
                <button type="button" onclick="resetForm()" class="reset-button">
                    🔄 Limpar Formulário
                </button>
            </div>
        </form>

        <!-- Mensagem de erro se não encontrar MAC -->
        <div id="no-mac-error" class="alert alert-danger" style="display: none;">
            <h4>❌ Dispositivo não encontrado</h4>
            <p>Não foi possível encontrar o MAC address do dispositivo ESP32.</p>
            <div class="troubleshooting">
                <h5>🔍 Possíveis soluções:</h5>
                <ul>
                    <li>Primeiro execute o processo de conexão WiFi no captive portal</li>
                    <li>Verifique se você acessou o ESP32 em <strong>192.168.4.1:5000</strong></li>
                    <li>Certifique-se de que o dispositivo se conectou com sucesso</li>
                    <li>Tente fazer a configuração WiFi novamente</li>
                </ul>
                <button onclick="window.location.href='http://192.168.4.1:5000'" class="btn btn-primary">
                    🔧 Ir para Captive Portal
                </button>
                    </div>
                </div>
            </div>

    <!-- Resultado da configuração -->
    <div id="config-result" style="display: none;"></div>
</div>

<script>
// Verificar MAC address no localStorage quando página carrega
document.addEventListener('DOMContentLoaded', function() {
    checkDeviceMAC();
    loadDeviceTypes();
    loadDepartments();
});

// Funções de debug para localStorage
function testLocalStorage() {
    const macAddress = localStorage.getItem('esp32_mac_address');
    const allItems = Object.keys(localStorage).map(key => `${key}: ${localStorage.getItem(key)}`).join('\n');
    
    alert(`🔍 Debug localStorage:\n\nMAC Address: ${macAddress || 'não encontrado'}\nTipo: ${typeof macAddress}\nComprimento: ${macAddress ? macAddress.length : 'null'}\n\nTodos os itens:\n${allItems || 'localStorage vazio'}`);
    
    console.log('🔍 Debug localStorage completo:', localStorage);
    console.log('MAC específico:', macAddress);
}

function clearLocalStorage() {
    if (confirm('⚠️ Isso vai limpar TODOS os dados do localStorage. Continuar?')) {
        localStorage.clear();
        alert('🗑️ localStorage limpo!');
        location.reload();
    }
}

function setTestMAC() {
    const testMAC = 'AA:BB:CC:DD:EE:FF';
    console.log('🧪 Definindo MAC de teste:', testMAC);
    localStorage.setItem('esp32_mac_address', testMAC);
    
    // Verificar se foi salvo
    const saved = localStorage.getItem('esp32_mac_address');
    console.log('🧪 MAC salvo:', saved);
    
    alert(`🧪 MAC de teste definido: ${testMAC}\n\nRecarregando página para testar...`);
    location.reload();
}

// Função adicional para debug imediato
function debugMACNow() {
    console.log('🔍 Debug imediato do localStorage:');
    console.log('- localStorage completo:', localStorage);
    console.log('- MAC atual:', localStorage.getItem('esp32_mac_address'));
    console.log('- Todas as chaves:', Object.keys(localStorage));
    
    // Testar acesso aos elementos
    const macField = document.getElementById('mac_address');
    const displayField = document.getElementById('display-mac');
    const deviceForm = document.getElementById('device-config-form');
    
    console.log('- Elementos DOM:');
    console.log('  - Campo MAC hidden:', macField);
    console.log('  - Display MAC span:', displayField);
    console.log('  - Formulário:', deviceForm);
    console.log('  - Formulário visível:', deviceForm?.style.display);
    
    alert('Verifique o console para detalhes completos do debug');
}

// Carregar tipos de dispositivos via API
async function loadDeviceTypes() {
    try {
        console.log('📡 Carregando tipos de dispositivos...');
        const response = await fetch('/api/device-types');
        const data = await response.json();
        
        if (data.success && data.data) {
            const select = document.getElementById('device_type');
            
            // Limpar opções existentes (manter apenas a primeira)
            const firstOption = select.firstElementChild;
            select.innerHTML = '';
            select.appendChild(firstOption);
            
            // Adicionar opções dinâmicas
            data.data.forEach(type => {
                const option = document.createElement('option');
                option.value = type.value || type.name;
                option.textContent = type.label || `📱 ${type.name}`;
                select.appendChild(option);
            });
            
            console.log('✅ Tipos de dispositivos carregados:', data.data.length);
        } else {
            console.log('⚠️ Falha ao carregar tipos de dispositivos, mantendo opções padrão');
        }
    } catch (error) {
        console.error('❌ Erro ao carregar tipos de dispositivos:', error);
    }
}

// Carregar departamentos via API
async function loadDepartments() {
    try {
        console.log('📡 Carregando departamentos...');
        const response = await fetch('/api/departments');
        const data = await response.json();
        
        if (data.success && data.data) {
            const select = document.getElementById('department');
            
            // Limpar opções existentes (manter apenas a primeira)
            const firstOption = select.firstElementChild;
            select.innerHTML = '';
            select.appendChild(firstOption);
            
            // Adicionar opções dinâmicas
            data.data.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.value || dept.name.toLowerCase().replace(/\s+/g, '_');
                option.textContent = dept.label || `🏢 ${dept.name}`;
                select.appendChild(option);
            });
            
            console.log('✅ Departamentos carregados:', data.data.length);
        } else {
            console.log('⚠️ Falha ao carregar departamentos, mantendo opções padrão');
        }
    } catch (error) {
        console.error('❌ Erro ao carregar departamentos:', error);
    }
}

function checkDeviceMAC() {
    console.log('🚀 Iniciando checkDeviceMAC...');
    
    const macCheckDiv = document.getElementById('mac-check');
    const deviceForm = document.getElementById('device-config-form');
    const noMacError = document.getElementById('no-mac-error');
    
    // Verificar se elementos existem
    console.log('Elementos encontrados:', {
        macCheckDiv: !!macCheckDiv,
        deviceForm: !!deviceForm,
        noMacError: !!noMacError
    });
    
    // Primeiro: verificar URL
    console.log('🌐 Verificando MAC na URL...');
    const urlParams = new URLSearchParams(window.location.search);
    const urlMac = urlParams.get('mac');
    console.log('🔗 MAC encontrado na URL:', urlMac);
    
    // Segundo: verificar localStorage
    console.log('🔍 Verificando localStorage...');
    console.log('localStorage completo:', localStorage);
    console.log('Chaves no localStorage:', Object.keys(localStorage));
    
    const storedMac = localStorage.getItem('esp32_mac_address');
    console.log('📍 MAC obtido do localStorage:', storedMac);
    
    // Prioridade: URL > localStorage
    const macAddress = urlMac || storedMac;
    console.log('🎯 MAC final selecionado:', macAddress, urlMac ? '(da URL)' : '(do localStorage)');
    console.log('📍 Tipo do MAC:', typeof macAddress);
    console.log('📍 Comprimento do MAC:', macAddress ? macAddress.length : 'null');
    console.log('📍 MAC é válido?', macAddress && macAddress !== 'UNKNOWN' && macAddress !== 'null' && macAddress.length > 10);
    
    // Forçar um pequeno delay para garantir que a página está carregada
    setTimeout(() => {
        if (macAddress && macAddress !== 'UNKNOWN' && macAddress !== 'null' && macAddress.length > 10) {
            // Se MAC veio da URL, salvar no localStorage para futuras visitas
            if (urlMac && urlMac !== storedMac) {
                localStorage.setItem('esp32_mac_address', urlMac);
                console.log('💾 MAC da URL salvo no localStorage para futuras visitas');
            }
            
            // MAC encontrado - mostrar formulário
            console.log('✅ MAC Address válido encontrado:', macAddress);
            
            // Preencher campos (hidden e display informativo)
            const macField = document.getElementById('mac_address');
            const displayField = document.getElementById('display-mac');
            
            if (macField) {
                macField.value = macAddress;
                console.log('✅ Campo hidden preenchido:', macField.value);
            } else {
                console.error('❌ Campo mac_address não encontrado!');
            }
            
            if (displayField) {
                displayField.textContent = macAddress;
                console.log('✅ Display MAC atualizado:', displayField.textContent);
            } else {
                console.error('❌ Campo display-mac não encontrado!');
            }
            
            // Mostrar/ocultar elementos
            if (macCheckDiv) macCheckDiv.style.display = 'none';
            if (deviceForm) deviceForm.style.display = 'block';
            if (noMacError) noMacError.style.display = 'none';
            
            // Limpar URL para deixar mais limpa (opcional)
            if (urlMac) {
                const cleanUrl = window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
                console.log('🧹 URL limpa após carregar MAC');
            }
            
            console.log('✅ Formulário exibido com MAC:', macAddress);
            
            // Mostrar confirmação visual após um pequeno delay
            setTimeout(() => {
                alert(`✅ Dispositivo ESP32 detectado automaticamente!\n\nMAC: ${macAddress}\n\nO formulário está pronto para preenchimento.`);
            }, 800);
            
        } else {
            // MAC não encontrado - mostrar erro detalhado
            console.log('❌ MAC Address não encontrado ou inválido');
            console.log('❌ Valor recebido:', macAddress);
            console.log('❌ Chaves disponíveis:', Object.keys(localStorage));
            
            if (macCheckDiv) macCheckDiv.style.display = 'none';
            if (deviceForm) deviceForm.style.display = 'none';
            if (noMacError) noMacError.style.display = 'block';
            
            // Debug mais detalhado no erro
            const debugInfo = document.createElement('div');
            debugInfo.style.marginTop = '1rem';
            debugInfo.style.padding = '1rem';
            debugInfo.style.background = '#f8f9fa';
            debugInfo.style.borderRadius = '8px';
            debugInfo.innerHTML = `
                <h4>🔍 Debug localStorage:</h4>
                <p><strong>MAC obtido:</strong> ${macAddress || 'null'}</p>
                <p><strong>Tipo:</strong> ${typeof macAddress}</p>
                <p><strong>Todas as chaves:</strong> ${Object.keys(localStorage).join(', ') || 'nenhuma'}</p>
                <p><strong>Conteúdo completo:</strong></p>
                <pre style="background: #fff; padding: 0.5rem; border-radius: 4px; overflow-x: auto;">${JSON.stringify(localStorage, null, 2)}</pre>
                <button onclick="localStorage.setItem('esp32_mac_address', 'AA:BB:CC:DD:EE:FF'); location.reload();" style="margin-top: 0.5rem; padding: 0.5rem; background: #007bff; color: white; border: none; border-radius: 4px;">
                    🧪 Testar com MAC fictício
                </button>
                <button onclick="console.log('localStorage atual:', localStorage); alert('Verifique o console para detalhes');" style="margin-top: 0.5rem; margin-left: 0.5rem; padding: 0.5rem; background: #28a745; color: white; border: none; border-radius: 4px;">
                    🔍 Debug console
                </button>
            `;
            
            // Adicionar debug info se não existe
            if (!document.getElementById('debug-info') && noMacError) {
                debugInfo.id = 'debug-info';
                noMacError.appendChild(debugInfo);
            }
            
            console.log('❌ Exibindo tela de erro - MAC não encontrado');
        }
    }, 300);
}

function resetForm() {
    if (confirm('Tem certeza que deseja limpar o formulário?')) {
        document.querySelector('.device-form').reset();
    }
}

// Submissão do formulário
document.getElementById('device-config-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Verificar se MAC está preenchido antes de submeter
    const macAddress = document.getElementById('mac_address').value;
    if (!macAddress || macAddress.length < 10) {
        alert('❌ Erro: MAC Address não detectado!\n\nExecute primeiro o processo de configuração WiFi no captive portal.');
        return false;
    }
    
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const form = e.target;
    const resultDiv = document.getElementById('config-result');
    
    // Obter dados do formulário
    const formData = new FormData(form);
    const macAddress = formData.get('mac_address');
    const deviceName = formData.get('device_name');
    const deviceType = formData.get('device_type');
    const department = formData.get('department');
    
    // Validação
    if (!macAddress || !deviceName || !deviceType || !department) {
        alert('Por favor, preencha todos os campos obrigatórios.');
        return;
    }
    
    // Mostrar loading
    submitBtn.disabled = true;
    submitText.textContent = '📡 Criando tópico MQTT...';
    
    resultDiv.innerHTML = '<div class="alert alert-info">📡 Criando tópico MQTT no backend...</div>';
    resultDiv.style.display = 'block';
    
    try {
        // Enviar para o backend Laravel
        const response = await fetch('{{ route("device.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                mac_address: macAddress,
                device_name: deviceName,
                device_type: deviceType,
                department: department
                })
            });
            
        if (!response.ok) {
            throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
            }
            
        const data = await response.json();
        console.log('Resposta do backend:', data);
            
        if (data.success) {
            // Mostrar sucesso
                resultDiv.innerHTML = `
                    <div class="device-card success-card">
                    <h2>🎉 Dispositivo Configurado com Sucesso!</h2>
                        
                        <div class="result-section">
                            <h3>📱 Informações do Dispositivo</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong>Nome:</strong>
                                <span>${deviceName}</span>
                                </div>
                                <div class="info-item">
                                <strong>MAC Address:</strong>
                                <span class="mono">${macAddress}</span>
                                </div>
                                <div class="info-item">
                                    <strong>Tipo:</strong>
                                <span>${deviceType}</span>
                                </div>
                                <div class="info-item">
                                    <strong>Departamento:</strong>
                                    <span>${department}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="result-section">
                            <h3>📡 Tópico MQTT Criado</h3>
                            <div class="topic-info">
                                <div class="topic-item">
                                    <strong>Nome do Tópico:</strong>
                                <div class="topic-name">${data.mqtt_info.topic}</div>
                                </div>
                                <div class="topic-item">
                                <strong>Broker MQTT:</strong>
                                <div class="topic-name">${data.mqtt_info.broker}</div>
                                </div>
                                <div class="topic-item">
                                <strong>Porta:</strong>
                                <div class="topic-id">${data.mqtt_info.port}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="result-section">
                        <h3>🚀 Configurando ESP32...</h3>
                        <div id="esp32-config-status" class="next-steps">
                            <p id="esp32-status">🔄 Enviando configuração para o ESP32...</p>
                            </div>
                        </div>
                    </div>
                `;
                
            submitText.textContent = '🔧 Configurando ESP32...';
            
            // Agora configurar o ESP32 com os dados do tópico MQTT
            await configureESP32(data.mqtt_info);
            
            } else {
            throw new Error(data.message || 'Erro ao criar tópico MQTT');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h4>❌ Erro na Configuração</h4>
                <p><strong>Detalhes:</strong> ${error.message}</p>
                <div class="troubleshooting">
                    <h5>🔍 Possíveis Soluções:</h5>
                    <ul>
                        <li>Verifique sua conexão com a internet</li>
                        <li>Certifique-se de que o backend está funcionando</li>
                        <li>Tente novamente em alguns segundos</li>
                    </ul>
                </div>
            </div>
        `;
    } finally {
        submitBtn.disabled = false;
        submitText.textContent = '📡 Criar Tópico MQTT e Configurar Dispositivo';
    }
});

// Configurar ESP32 com dados do MQTT
async function configureESP32(mqttInfo) {
    const statusElement = document.getElementById('esp32-status');
    
    try {
        statusElement.innerHTML = '🔧 Conectando ao ESP32...';
        
        // Tentar várias possibilidades de IP do ESP32
        const possibleIPs = [
            '192.168.0.106', // IP conhecido na rede
            '192.168.1.100', // Faixa comum de DHCP
            '192.168.1.101',
            '192.168.0.100',
            '192.168.0.101'
        ];
        
        let configured = false;
        
        for (const ip of possibleIPs) {
            try {
                statusElement.innerHTML = `🔧 Tentando configurar ESP32 em ${ip}:5000...`;
                
                const response = await fetch(`http://${ip}:5000/api/mqtt/config`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        broker: mqttInfo.broker,
                        port: mqttInfo.port,
                        topic: mqttInfo.topic
                    }),
                    timeout: 5000
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        statusElement.innerHTML = `✅ ESP32 configurado com sucesso em ${ip}!`;
                        configured = true;
                        
                        // Mostrar próximos passos
        setTimeout(() => {
                            statusElement.innerHTML = `
                                ✅ ESP32 configurado com sucesso!<br>
                                📡 Conectado ao MQTT: ${mqttInfo.broker}<br>
                                📝 Inscrito no tópico: ${mqttInfo.topic}<br>
                                🚀 Dispositivo pronto para receber comandos!
                            `;
                        }, 1000);
                        
                        break;
                    }
                }
            } catch (err) {
                console.log(`Tentativa em ${ip} falhou:`, err);
                continue;
            }
        }
        
        if (!configured) {
            statusElement.innerHTML = `
                ⚠️ Não foi possível configurar o ESP32 automaticamente.<br>
                📋 <strong>Configuração Manual:</strong><br>
                🔧 Broker: ${mqttInfo.broker}<br>
                📊 Porta: ${mqttInfo.port}<br>
                📝 Tópico: ${mqttInfo.topic}<br>
                💡 Configure manualmente no ESP32 se necessário.
            `;
        }
            
        } catch (error) {
        console.error('Erro ao configurar ESP32:', error);
    statusElement.innerHTML = `
            ❌ Erro ao configurar ESP32: ${error.message}<br>
            📋 Use a configuração manual se necessário.
        `;
    }
}
</script>

<style>
.device-info-section {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.device-info-section h3 {
    color: #1e40af;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.detected-device {
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.75rem;
}

.device-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.device-info-item strong {
    color: #374151;
}

.device-status {
    margin-top: 0.5rem;
    text-align: center;
}

.status-auto {
    background: #d4edda;
    color: #155724;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-block;
}



.mac-display {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #155724;
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid #28a745;
}

.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.success-card {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: 2px solid #34d399;
}

.result-section {
    margin: 1.5rem 0;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.result-section h3 {
    font-size: 1.3rem;
    color: #ffffff;
    margin-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    padding-bottom: 0.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
}

@media (min-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.info-item {
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
}

.info-item strong {
    color: #d1fae5;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.info-item span {
    color: #ffffff;
    font-size: 1rem;
}

.mono {
    font-family: 'Courier New', monospace;
    background: rgba(0, 0, 0, 0.3);
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
}

.topic-item {
    margin-bottom: 1rem;
}

.topic-item strong {
    display: block;
    color: #d1fae5;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.topic-name, .topic-id {
    font-family: 'Courier New', monospace;
    font-size: 0.95rem;
    color: #ffffff;
    background: rgba(0, 0, 0, 0.3);
    padding: 0.75rem;
    border-radius: 4px;
    word-break: break-all;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.next-steps {
    color: #ffffff;
}

.next-steps p {
    margin: 0.5rem 0;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    border-left: 3px solid #34d399;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
    border: 1px solid transparent;
}

.alert-info {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    border-color: #38bdf8;
    color: white;
}

.alert-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-color: #34d399;
    color: white;
}

.alert-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border-color: #f87171;
    color: white;
}

.troubleshooting {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
}

.troubleshooting h5 {
    margin: 0 0 0.5rem 0;
    color: #fef3c7;
}

.troubleshooting ul {
    margin: 0;
    padding-left: 1.5rem;
}

.troubleshooting li {
    margin: 0.5rem 0;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    margin: 0.5rem 0.25rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

/* Outros estilos mantidos do arquivo original... */
</style>
@endsection

