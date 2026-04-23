# 🌐 GUIA DE CONFIGURAÇÃO DO DOMÍNIO octus.cloud

## 📋 Pré-requisitos

✅ Sistema já instalado e funcionando  
✅ Domínio **octus.cloud** registrado  
✅ Acesso ao painel DNS do domínio  
✅ Servidor: 145.223.95.178  

---

## 🚀 PASSO 1: Configurar DNS

Acesse o painel de gerenciamento do seu domínio (onde você registrou octus.cloud) e adicione os seguintes registros DNS:

### Registros A (IPv4)

| Nome/Host | Tipo | Valor | TTL |
|-----------|------|-------|-----|
| @ | A | 145.223.95.178 | 3600 |
| www | A | 145.223.95.178 | 3600 |
| api | A | 145.223.95.178 | 3600 |
| app | A | 145.223.95.178 | 3600 |

**Explicação:**
- `@` ou `octus.cloud` → Interface Web principal
- `www.octus.cloud` → Alias da interface web
- `api.octus.cloud` → Backend API
- `app.octus.cloud` → App Mobile

### ⏰ Aguardar Propagação DNS

A propagação DNS pode levar de **5 minutos a 48 horas**. Você pode verificar com:

```bash
# Verificar se DNS está propagado
dig octus.cloud
dig api.octus.cloud
dig app.octus.cloud

# Ou usando nslookup
nslookup octus.cloud
nslookup api.octus.cloud
```

---

## 🔧 PASSO 2: Instalar e Configurar Nginx

No servidor, execute:

```bash
cd /home/darley/octus
bash setup-nginx-domain.sh
```

Este script vai:
- ✅ Instalar Nginx
- ✅ Criar configurações de virtual hosts
- ✅ Configurar reverse proxy para as 3 aplicações
- ✅ Instalar Certbot (para SSL)

---

## 🔐 PASSO 3: Configurar SSL/HTTPS com Let's Encrypt

**IMPORTANTE:** Só execute este passo DEPOIS que o DNS estiver propagado!

```bash
sudo certbot --nginx -d octus.cloud -d www.octus.cloud -d api.octus.cloud -d app.octus.cloud
```

**Durante a instalação, o Certbot vai perguntar:**

1. **Email:** Digite seu email para notificações de renovação
2. **Termos de Serviço:** Digite `Y` para aceitar
3. **Compartilhar email:** Digite `N` (opcional)
4. **Redirect HTTP → HTTPS:** Digite `2` para redirecionar automaticamente

O Certbot vai:
- ✅ Emitir certificados SSL gratuitos
- ✅ Configurar HTTPS automaticamente
- ✅ Configurar renovação automática (certbot renew)

---

## 🔄 PASSO 4: Atualizar Configurações .env

Atualize os arquivos `.env` para usar o domínio:

```bash
cd /home/darley/octus
bash update-env-domain.sh
```

Este script vai atualizar:
- ✅ `mqtt/.env` → APP_URL=https://api.octus.cloud
- ✅ `iot-config-web-laravel/.env` → APP_URL=https://octus.cloud
- ✅ `iot-config-app-laravel/.env` → APP_URL=https://app.octus.cloud

---

## 🚀 PASSO 5: Reiniciar Serviços

```bash
cd /home/darley/octus
./stop-services.sh
./start-services.sh
```

---

## ✅ PASSO 6: Testar o Sistema

Acesse no navegador:

### Interface Web
- https://octus.cloud
- https://www.octus.cloud

### Backend API
- https://api.octus.cloud
- https://api.octus.cloud/api/health

### App Mobile
- https://app.octus.cloud

---

## 🔒 Segurança Adicional (Opcional)

### Configurar Firewall

```bash
# Permitir HTTP e HTTPS
sudo ufw allow 'Nginx Full'

# Permitir SSH
sudo ufw allow OpenSSH

# Ativar firewall
sudo ufw enable

# Verificar status
sudo ufw status
```

### Bloquear Acesso Direto pelas Portas

Se quiser que o acesso seja APENAS pelo domínio (não pelas portas 8000, 8001, 8002):

```bash
# Bloquear acesso externo às portas
sudo ufw deny 8000
sudo ufw deny 8001
sudo ufw deny 8002
```

---

## 📊 Monitoramento

### Verificar Status do Nginx

```bash
sudo systemctl status nginx
```

### Ver Logs do Nginx

```bash
# Logs da API
sudo tail -f /var/log/nginx/octus-api-access.log
sudo tail -f /var/log/nginx/octus-api-error.log

# Logs da Web
sudo tail -f /var/log/nginx/octus-web-access.log

# Logs do App
sudo tail -f /var/log/nginx/octus-app-access.log
```

### Verificar Certificados SSL

```bash
# Ver certificados instalados
sudo certbot certificates

# Testar renovação
sudo certbot renew --dry-run
```

---

## 🔄 Renovação Automática SSL

O Certbot configura automaticamente a renovação. O certificado será renovado automaticamente antes de expirar.

Você pode verificar o timer de renovação:

```bash
sudo systemctl status certbot.timer
```

---

## 🌍 Estrutura Final

```
octus.cloud (443/HTTPS)
├── https://octus.cloud → Interface Web (porta 8001)
├── https://www.octus.cloud → Interface Web (porta 8001)
├── https://api.octus.cloud → Backend API (porta 8000)
└── https://app.octus.cloud → App Mobile (porta 8002)
```

### Fluxo de Requisição

```
Cliente (navegador)
    ↓
HTTPS (porta 443)
    ↓
Nginx (reverse proxy)
    ↓
Laravel (porta 8000/8001/8002)
    ↓
MySQL (porta 3306)
```

---

## 🛠️ Comandos Úteis

### Gerenciar Nginx

```bash
# Iniciar
sudo systemctl start nginx

# Parar
sudo systemctl stop nginx

# Reiniciar
sudo systemctl restart nginx

# Recarregar configuração (sem downtime)
sudo systemctl reload nginx

# Testar configuração
sudo nginx -t
```

### Gerenciar Serviços Laravel

```bash
# Iniciar
./start-services.sh

# Parar
./stop-services.sh

# Ver logs
tail -f /tmp/mqtt-backend.log
tail -f /tmp/iot-web.log
tail -f /tmp/iot-app.log
```

---

## 🚨 Resolução de Problemas

### Problema: "DNS_PROBE_FINISHED_NXDOMAIN"

**Solução:** DNS ainda não propagou. Aguarde e verifique com `dig octus.cloud`

### Problema: "Connection refused"

**Solução:** 
1. Verificar se Nginx está rodando: `sudo systemctl status nginx`
2. Verificar se os serviços Laravel estão rodando: `ps aux | grep php`
3. Verificar logs do Nginx

### Problema: "502 Bad Gateway"

**Solução:**
1. Serviços Laravel não estão rodando
2. Execute: `./start-services.sh`

### Problema: "Certificate verification failed"

**Solução:**
1. DNS não propagou antes de instalar SSL
2. Remover certificados: `sudo certbot delete`
3. Aguardar DNS propagar
4. Reinstalar: `sudo certbot --nginx -d octus.cloud ...`

---

## 📞 Suporte

### Verificar Status Geral

```bash
# DNS
dig octus.cloud

# Nginx
sudo systemctl status nginx

# Laravel Services
ps aux | grep "php artisan serve"

# SSL
sudo certbot certificates

# Portas
sudo netstat -tlnp | grep -E '80|443|8000|8001|8002'
```

---

## ✨ Pronto!

Seu sistema IOT agora está acessível via:

- 🌐 **Interface Web:** https://octus.cloud
- 🔌 **API Backend:** https://api.octus.cloud
- 📱 **App Mobile:** https://app.octus.cloud

Com:
- ✅ SSL/HTTPS configurado
- ✅ Renovação automática de certificados
- ✅ Reverse proxy configurado
- ✅ URLs profissionais

**🎉 Sistema em produção com domínio próprio!**

