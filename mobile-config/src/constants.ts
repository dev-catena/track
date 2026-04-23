/** Deve ser idêntico ao firmware (ble_provisioning.cpp) */
export const BLE_SERVICE_UUID = 'a1b2c3d4-0001-4000-8000-00805f9b34fb';
export const BLE_CONFIG_CHAR_UUID = 'a1b2c3d4-0002-4000-8000-00805f9b34fb';
export const BLE_STATUS_CHAR_UUID = 'a1b2c3d4-0003-4000-8000-00805f9b34fb';

/** Nome de anúncio no ESP: IOT- + 4 hex finais do MAC (ex: IOT-80BC) */
export const ADV_NAME_PREFIX = 'IOT-';
