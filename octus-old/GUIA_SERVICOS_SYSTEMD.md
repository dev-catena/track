# 🔄 Guia de Serviços Systemd - Octus IOT

## 📋 Visão Geral

Este sistema agora possui **3 serviços systemd** configurados para reiniciar automaticamente:

| Serviço | Porta | Descrição |
|---------|-------|-----------|
| `octus-api.service` | 8000 | Backend API (MQTT) |
| `octus-web.service` | 8001 | Interface Web |
| `octus-app.service` | 8002 | App Mobile |

---

## 🚀 Instalação

Execute no servidor:

```bash
cd /home/darley/octus
sudo bash create-systemd-services.sh
```

---

## 📊 Comandos de Gerenciamento

### Ver Status de Todos os Serviços

```bash
sudo systemctl status octus-*
```

### Ver Status de um Serviço Específico

```bash
sudo systemctl status octus-api
sudo systemctl status octus-web
sudo systemctl status octus-app
```

### Iniciar Serviços

```bash
# Todos de uma vez
sudo systemctl start octus-api octus-web octus-app

# Ou individualmente
sudo systemctl start octus-api
sudo systemctl start octus-web
sudo systemctl start octus-app
```

### Parar Serviços

```bash
# Todos de uma vez
sudo systemctl stop octus-api octus-web octus-app

# Ou individualmente
sudo systemctl stop octus-api
sudo systemctl stop octus-web
sudo systemctl stop octus-app
```

### Reiniciar Serviços

```bash
# Todos de uma vez
sudo systemctl restart octus-api octus-web octus-app

# Ou individualmente
sudo systemctl restart octus-api
sudo systemctl restart octus-web
sudo systemctl restart octus-app
```

---

## 🔄 Auto-Restart

### Configuração Atual

- **Restart Policy:** `always` (reinicia sempre que cair)
- **Restart Delay:** 5 segundos
- **Boot Startup:** Habilitado (inicia automaticamente no boot)

### Desabilitar Auto-Restart (se necessário)

```bash
sudo systemctl disable octus-api
sudo systemctl disable octus-web
sudo systemctl disable octus-app
```

### Reabilitar Auto-Restart

```bash
sudo systemctl enable octus-api
sudo systemctl enable octus-web
sudo systemctl enable octus-app
```

---

## 📝 Logs

### Ver Logs em Tempo Real (journalctl)

```bash
# Ver logs de um serviço específico
sudo journalctl -u octus-api -f
sudo journalctl -u octus-web -f
sudo journalctl -u octus-app -f

# Ver logs de todos os serviços Octus
sudo journalctl -u "octus-*" -f

# Ver últimas 100 linhas
sudo journalctl -u octus-api -n 100

# Ver logs desde uma hora atrás
sudo journalctl -u octus-api --since "1 hour ago"

# Ver logs de hoje
sudo journalctl -u octus-api --since today
```

### Ver Logs do Sistema (arquivos)

```bash
# Ver logs em tempo real
sudo tail -f /var/log/octus-api.log
sudo tail -f /var/log/octus-web.log
sudo tail -f /var/log/octus-app.log

# Ver erros
sudo tail -f /var/log/octus-api-error.log
sudo tail -f /var/log/octus-web-error.log
sudo tail -f /var/log/octus-app-error.log

# Ver últimas 50 linhas
sudo tail -50 /var/log/octus-api.log
```

### Limpar Logs Antigos

```bash
# Limpar logs do journalctl (manter apenas 7 dias)
sudo journalctl --vacuum-time=7d

# Limpar arquivos de log
sudo truncate -s 0 /var/log/octus-*.log
```

---

## 🔍 Verificar Se Serviços Estão Rodando

### Método 1: systemctl

```bash
sudo systemctl is-active octus-api
sudo systemctl is-active octus-web
sudo systemctl is-active octus-app
```

**Resposta esperada:** `active`

### Método 2: netstat/ss

```bash
# Ver portas em uso
sudo ss -tuln | grep -E ':(8000|8001|8002)'
sudo netstat -tuln | grep -E ':(8000|8001|8002)'
```

**Resposta esperada:**
```
tcp   LISTEN 0   511   0.0.0.0:8000   0.0.0.0:*
tcp   LISTEN 0   511   0.0.0.0:8001   0.0.0.0:*
tcp   LISTEN 0   511   0.0.0.0:8002   0.0.0.0:*
```

### Método 3: curl

```bash
# Testar se APIs respondem
curl -I http://localhost:8000
curl -I http://localhost:8001
curl -I http://localhost:8002
```

---

## 🔧 Editar Configuração de um Serviço

### 1. Editar arquivo do serviço

```bash
sudo nano /etc/systemd/system/octus-api.service
```

### 2. Recarregar configuração

```bash
sudo systemctl daemon-reload
```

### 3. Reiniciar serviço

```bash
sudo systemctl restart octus-api
```

---

## 🚨 Troubleshooting

### Serviço não inicia

```bash
# Ver logs detalhados
sudo journalctl -u octus-api -xe

# Verificar se a porta já está em uso
sudo lsof -i :8000

# Matar processo que está usando a porta
sudo kill -9 $(sudo lsof -t -i:8000)

# Tentar iniciar novamente
sudo systemctl start octus-api
```

### Serviço reinicia constantemente

```bash
# Ver últimos logs
sudo journalctl -u octus-api -n 100

# Verificar erro no Laravel
tail -50 /home/darley/octus/mqtt/storage/logs/laravel.log

# Testar manualmente
cd /home/darley/octus/mqtt
php artisan serve --host=0.0.0.0 --port=8000
```

### Serviço não reinicia automaticamente

```bash
# Verificar se está habilitado
sudo systemctl is-enabled octus-api

# Se mostrar "disabled", habilitar
sudo systemctl enable octus-api

# Verificar configuração
sudo systemctl show octus-api | grep Restart
```

**Deve mostrar:**
```
Restart=always
RestartSec=5s
```

---

## 📊 Monitoramento

### Script de Monitoramento Simples

Crie um arquivo `monitor-services.sh`:

```bash
#!/bin/bash

while true; do
    clear
    echo "=== Status dos Serviços Octus ==="
    echo ""
    
    for service in octus-api octus-web octus-app; do
        if sudo systemctl is-active --quiet $service; then
            echo "✓ $service está RODANDO"
        else
            echo "✗ $service está PARADO"
        fi
    done
    
    echo ""
    echo "=== Portas em Uso ==="
    sudo ss -tuln | grep -E ':(8000|8001|8002)'
    
    echo ""
    echo "Atualizado em: $(date)"
    
    sleep 5
done
```

Execute:

```bash
chmod +x monitor-services.sh
./monitor-services.sh
```

---

## 🔐 Segurança

Os serviços estão configurados com:

- **User/Group:** `darley` (não roda como root)
- **PrivateTmp:** `yes` (diretório temporário isolado)
- **NoNewPrivileges:** `true` (não pode escalar privilégios)

---

## 📁 Localização dos Arquivos

| Arquivo | Localização |
|---------|-------------|
| Serviço API | `/etc/systemd/system/octus-api.service` |
| Serviço Web | `/etc/systemd/system/octus-web.service` |
| Serviço App | `/etc/systemd/system/octus-app.service` |
| Logs journalctl | `sudo journalctl -u octus-*` |
| Logs sistema | `/var/log/octus-*.log` |
| Logs Laravel API | `/home/darley/octus/mqtt/storage/logs/laravel.log` |
| Logs Laravel Web | `/home/darley/octus/iot-config-web-laravel/storage/logs/laravel.log` |
| Logs Laravel App | `/home/darley/octus/iot-config-app-laravel/storage/logs/laravel.log` |

---

## 🎯 Comandos Rápidos

```bash
# Ver status geral
sudo systemctl status octus-* --no-pager

# Reiniciar tudo
sudo systemctl restart octus-api octus-web octus-app

# Ver logs em tempo real (todos)
sudo journalctl -u "octus-*" -f

# Verificar se tudo está rodando
sudo systemctl is-active octus-api octus-web octus-app

# Ver erros recentes
sudo journalctl -u octus-* --since "10 minutes ago" -p err

# Recarregar configurações e reiniciar
sudo systemctl daemon-reload && sudo systemctl restart octus-api octus-web octus-app
```

---

## ✅ Checklist Pós-Instalação

- [ ] Todos os 3 serviços estão rodando: `sudo systemctl status octus-*`
- [ ] Serviços habilitados para boot: `sudo systemctl is-enabled octus-*`
- [ ] Portas 8000, 8001, 8002 estão abertas: `sudo ss -tuln | grep -E ':(8000|8001|8002)'`
- [ ] Sites acessíveis: `curl -I https://api.octus.cloud`, `https://octus.cloud`, `https://app.octus.cloud`
- [ ] Logs não mostram erros: `sudo journalctl -u octus-* --since today -p err`
- [ ] Testar auto-restart: `sudo systemctl stop octus-api` → aguardar 5s → `sudo systemctl status octus-api` (deve estar `active`)

---

## 🆘 Suporte

Se tiver problemas:

1. **Ver logs:** `sudo journalctl -u octus-* -n 100`
2. **Verificar Laravel:** `tail -50 /home/darley/octus/mqtt/storage/logs/laravel.log`
3. **Testar manualmente:** `cd /home/darley/octus/mqtt && php artisan serve`
4. **Recriar serviços:** `sudo bash create-systemd-services.sh`

---

**Sistema configurado para alta disponibilidade! 🚀**

