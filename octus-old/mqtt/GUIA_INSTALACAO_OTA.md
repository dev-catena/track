# 🚀 Guia de Instalação do Sistema OTA

## 📋 Pré-requisitos

- Ubuntu/Debian Linux
- Acesso root (sudo)
- Backend Laravel funcionando
- MQTT broker funcionando

## 🔧 Instalação Passo a Passo

### **Opção 1: Instalação Automática (Recomendada)**

```bash
# 1. Navegar para o diretório do projeto
cd /home/darley/octus/mqtt

# 2. Executar instalação completa
sudo ./setup-ota-complete.sh
```

### **Opção 2: Instalação Manual**

#### **Passo 1: Instalar nginx**
```bash
sudo ./install-nginx.sh
```

#### **Passo 2: Configurar nginx OTA**
```bash
sudo ./setup-nginx-ota.sh
```

#### **Passo 3: Criar estrutura de firmware**
```bash
sudo ./create-firmware-structure.sh
```

## 🧪 Verificação da Instalação

### **1. Testar servidor nginx:**
```bash
curl http://firmware.iot.local:8080/api/version
```

**Resposta esperada:**
```json
{
  "server": "nginx",
  "version": "1.0.0",
  "ota": true,
  "timestamp": "2025-09-23T15:30:00Z"
}
```

### **2. Testar estrutura de firmware:**
```bash
curl http://firmware.iot.local:8080/firmware/sensor_de_temperatura/latest/version.json
```

**Resposta esperada:**
```json
{
  "version": "1.0.0",
  "release_date": "2025-09-23",
  "device_type": "sensor_de_temperatura",
  "firmware_url": "http://firmware.iot.local/firmware/sensor_de_temperatura/latest/firmware.bin",
  "checksum_url": "http://firmware.iot.local/firmware/sensor_de_temperatura/latest/checksum.md5",
  "force_update": false,
  "size_bytes": 1048576
}
```

### **3. Verificar logs:**
```bash
# Logs de acesso
tail -f /var/log/nginx/ota-access.log

# Logs de erro
tail -f /var/log/nginx/ota-error.log

# Logs de download de firmware
tail -f /var/log/nginx/firmware-downloads.log
```

## 🔧 Configuração do Firmware ESP32

### **1. Ativar versão com OTA:**
```bash
cd /home/darley/octus/esp32-octus-platformio
./switch_version.sh ota
```

### **2. Compilar firmware:**
```bash
pio run
```

### **3. Upload para ESP32:**
```bash
pio run -t upload
```

## 📡 Testando o Sistema OTA

### **1. Enviar comando OTA via API:**
```bash
curl -X POST http://181.215.135.118:8000/api/mqtt/device-types/1/ota-update \
  -H "Content-Type: application/json" \
  -d '{"force_update": false, "user_id": 1}'
```

### **2. Enviar comando OTA via MQTT:**
```bash
# Usando mosquitto_pub
mosquitto_pub -h 181.215.135.118 -t "iot-c8f09ef17494/ota" -m '{
  "command": "ota_update",
  "ota_id": "123",
  "firmware_version": "1.1.0",
  "firmware_url": "http://firmware.iot.local/firmware/sensor_de_temperatura/latest/firmware.bin",
  "checksum_md5": "a1b2c3d4e5f6...",
  "force_update": false
}'
```

## 🎯 Estrutura Final

```
/var/www/firmware/
├── sensor_de_temperatura/
│   ├── v1.0.0/
│   │   ├── firmware.bin
│   │   ├── version.json
│   │   └── checksum.md5
│   ├── v1.1.0/
│   │   ├── firmware.bin
│   │   ├── version.json
│   │   └── checksum.md5
│   ├── latest -> v1.0.0
│   └── README.md
├── sensor_de_umidade/
│   └── ...
└── index.html
```

## 🔍 Troubleshooting

### **Problema: nginx não inicia**
```bash
# Verificar configuração
sudo nginx -t

# Verificar logs
sudo journalctl -u nginx

# Reiniciar nginx
sudo systemctl restart nginx
```

### **Problema: Arquivo não encontrado**
```bash
# Verificar permissões
sudo chown -R www-data:www-data /var/www/firmware
sudo chmod -R 755 /var/www/firmware
```

### **Problema: DNS não resolve**
```bash
# Adicionar entrada no hosts
echo "127.0.0.1 firmware.iot.local" | sudo tee -a /etc/hosts
```

## 📊 Monitoramento

### **Status do nginx:**
```bash
sudo systemctl status nginx
```

### **Logs em tempo real:**
```bash
# Todos os logs
sudo tail -f /var/log/nginx/*.log

# Apenas OTA
sudo tail -f /var/log/nginx/ota-*.log
```

### **Uso de disco:**
```bash
du -sh /var/www/firmware/
```

## ✅ Checklist de Verificação

- [ ] nginx instalado e funcionando
- [ ] Configuração OTA aplicada
- [ ] Estrutura de firmware criada
- [ ] URLs respondendo corretamente
- [ ] Logs sendo gerados
- [ ] Firmware ESP32 com OTA compilado
- [ ] Comandos OTA funcionando

## 🎉 Próximos Passos

1. **Upload de firmwares reais** para `/var/www/firmware/`
2. **Configurar tipos de dispositivos** no backend
3. **Testar updates OTA** em dispositivos reais
4. **Monitorar logs** e performance
5. **Configurar backup** dos firmwares

---

**Sistema OTA configurado e pronto para uso!** 🚀
