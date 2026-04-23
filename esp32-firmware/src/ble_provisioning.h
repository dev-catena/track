#pragma once

#include <Arduino.h>

/** Inicializa anúncio GATT (complemento opcional ao portal em 192.168.4.1). */
void bleProvisioningInit(const char* advertisingName, void (*onJson)(const char*));
/** Chamar a partir do loop(); processa JSON recebido no characteristic de escrita. */
void bleProvisioningPoll();
/** Atualiza o characteristic de status (e notify) após processar. */
void bleProvisioningSetStatus(const char* json);
