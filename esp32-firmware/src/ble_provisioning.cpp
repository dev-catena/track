/**
 * Provisionamento de rede via BLE (NimBLE) — opção além do captive portal / AP.
 * Serviço 128-bit + escrita de JSON (mesmo formato de POST /configure).
 */
#include "ble_provisioning.h"
#include "NimBLEDevice.h"
#include "NimBLEServer.h"
#include "NimBLEService.h"
#include "NimBLECharacteristic.h"
#include "NimBLEAdvertising.h"
#include "NimBLEUtils.h"
#include <string>
// UUIDs fixos (app mobile-config e firmware devem coincidir)
static const char* BLE_SVC  = "a1b2c3d4-0001-4000-8000-00805f9b34fb";
static const char* BLE_CFG  = "a1b2c3d4-0002-4000-8000-00805f9b34fb";
static const char* BLE_ST   = "a1b2c3d4-0003-4000-8000-00805f9b34fb";

static void (*g_onJson)(const char*) = nullptr;
static String g_pending;
static bool g_hasPending = false;
static NimBLECharacteristic* g_status = nullptr;
static char g_statusCstr[512];
static char g_nameCopy[32];

class BleCfgCallback : public NimBLECharacteristicCallbacks {
  void onWrite(NimBLECharacteristic* p) override {
    std::string v = p->getValue();
    if (v.empty()) {
      return;
    }
    g_hasPending = true;
    g_pending = String(v.c_str());
  }
};

void bleProvisioningSetStatus(const char* json) {
  if (g_status == nullptr || json == nullptr) {
    return;
  }
  strncpy(g_statusCstr, json, sizeof(g_statusCstr) - 1);
  g_statusCstr[sizeof(g_statusCstr) - 1] = 0;
  g_status->setValue((const uint8_t*)g_statusCstr, (size_t)strlen(g_statusCstr));
  g_status->notify();
}

void bleProvisioningInit(const char* advertisingName, void (*onJson)(const char*)) {
  g_onJson = onJson;
  strncpy(g_nameCopy, advertisingName, sizeof(g_nameCopy) - 1);
  g_nameCopy[sizeof(g_nameCopy) - 1] = 0;

  NimBLEDevice::init(g_nameCopy);
  NimBLEDevice::setMTU(500);
  NimBLEServer* s = NimBLEDevice::createServer();
  NimBLEService* svc = s->createService(BLE_SVC);
  if (svc == nullptr) {
    return;
  }

  NimBLECharacteristic* cfg = svc->createCharacteristic(
      BLE_CFG, NIMBLE_PROPERTY::WRITE | NIMBLE_PROPERTY::WRITE_NR);
  if (cfg) {
    cfg->setCallbacks(new BleCfgCallback());
  }

  g_status = svc->createCharacteristic(
      BLE_ST, NIMBLE_PROPERTY::READ | NIMBLE_PROPERTY::NOTIFY);
  if (g_status) {
    strncpy(g_statusCstr, R"({"ready":true,"message":"aguardando"})", sizeof(g_statusCstr) - 1);
    g_statusCstr[sizeof(g_statusCstr) - 1] = 0;
    g_status->setValue((const uint8_t*)g_statusCstr, (size_t)strlen(g_statusCstr));
  }

  svc->start();
  NimBLEAdvertising* a = NimBLEDevice::getAdvertising();
  a->addServiceUUID(BLE_SVC);
  a->setName(g_nameCopy);
  a->start();
}

void bleProvisioningPoll() {
  if (!g_hasPending || g_onJson == nullptr) {
    return;
  }
  g_hasPending = false;
  String copy = g_pending;
  g_pending = "";
  g_onJson(copy.c_str());
}
