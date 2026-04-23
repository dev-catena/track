import { API_CONFIG } from '../config/api';

const BASE_URL = API_CONFIG.BASE_URL;

const getHeaders = (token) => ({
  Accept: 'application/json',
  ...(token && { Authorization: `Bearer ${token}` }),
});

const TIMEOUT_LIST_MS = 45000;
const TIMEOUT_FACE_MS = 180000;

async function fetchWithTimeout(url, options = {}, timeoutMs = 30000) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    return await fetch(url, { ...options, signal: controller.signal });
  } catch (e) {
    if (e?.name === 'AbortError') {
      throw new Error(
        'Tempo esgotado ao falar com o servidor Track. Confira: (1) em config/api.js a URL é do Laravel (IP:porta), não face.thalamus.ind.br; (2) celular e PC na mesma rede; (3) servidor rodando (ex.: php artisan serve).'
      );
    }
    throw e;
  } finally {
    clearTimeout(timer);
  }
}

export const api = {
  async login(loginValue, password, fcmToken = '', type = 'username') {
    const formData = new FormData();
    formData.append(type, loginValue);
    formData.append('password', password);
    formData.append('fcm_token', fcmToken || '');
    formData.append('type', type);

    const res = await fetch(`${BASE_URL}/api/auth/v2/login`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: formData,
    });
    const data = await res.json();
    if (data.status === 0) throw new Error(data.message || 'Login falhou');
    return data;
  },

  async tabletMock() {
    const res = await fetch(`${BASE_URL}/api/auth/tablet-mock`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
    });
    const data = await res.json();
    if (data.status === 0) throw new Error(data.message || 'Erro ao validar');
    return data;
  },

  async faceLogin(imageUri) {
    const formData = new FormData();
    formData.append('type', 'face_login');
    formData.append('image', {
      uri: imageUri,
      type: 'image/jpeg',
      name: 'image.jpg',
    });

    const res = await fetch(`${BASE_URL}/api/auth/v2/login`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: formData,
    });
    const data = await res.json();
    if (data.status === 0) throw new Error(data.message || 'Rosto não reconhecido');
    return data;
  },

  async adminLogin(loginValue, password) {
    const isEmail = loginValue.includes('@');
    const formData = new FormData();
    if (isEmail) {
      formData.append('email', loginValue.trim());
    } else {
      formData.append('username', loginValue.trim());
    }
    formData.append('password', password);

    const res = await fetch(`${BASE_URL}/api/auth/admin/login`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: formData,
    });
    const data = await res.json();
    if (data.status === 0) throw new Error(data.message || 'Login falhou');
    return data;
  },

  async dashboard(token) {
    const res = await fetch(`${BASE_URL}/api/dashboard`, {
      headers: getHeaders(token),
    });
    const data = await res.json();
    if (data.status === 0) throw new Error(data.message || 'Erro ao carregar');
    return data;
  },

  async listDocks(departmentId, organizationId) {
    const param = organizationId ? `organization_id=${organizationId}` : `department_id=${departmentId}`;
    const res = await fetch(`${BASE_URL}/api/self-service/docks?${param}`);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Erro ao listar docas');
    return data.data;
  },

  async openDock(macAddress, pairingCode, slot, fcmToken, operatorId) {
    const body = macAddress ? { mac_address: macAddress } : { pairing_code: pairingCode };
    if (slot) body.slot = slot;
    if (fcmToken) body.fcm_token = fcmToken;
    if (operatorId != null && operatorId !== '') {
      const n = typeof operatorId === 'number' ? operatorId : parseInt(String(operatorId), 10);
      if (Number.isFinite(n)) body.operator_id = n;
    }
    if (!body.mac_address && !body.pairing_code) throw new Error('Informe mac_address ou pairing_code');
    const res = await fetch(`${BASE_URL}/api/self-service/open`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Falha ao abrir doca');
    return data;
  },

  async closeDock(macAddress, pairingCode, slot) {
    const body = macAddress ? { mac_address: macAddress } : { pairing_code: pairingCode };
    if (slot) body.slot = slot;
    const res = await fetch(`${BASE_URL}/api/self-service/close`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Falha ao fechar doca');
    return data;
  },

  async validateUser(token, imageUri, latitude, longitude, deviceSerialNumber) {
    const formData = new FormData();
    formData.append('image', {
      uri: imageUri,
      type: 'image/jpeg',
      name: 'image.jpg',
    });
    formData.append('latitude', String(latitude));
    formData.append('longitude', String(longitude));
    formData.append('device_serial_number', deviceSerialNumber || '');

    const res = await fetch(`${BASE_URL}/api/user/validate`, {
      method: 'POST',
      headers: getHeaders(token),
      body: formData,
    });
    const data = await res.json();
    if (data.status === 0) throw new Error(data.message || 'Validação falhou');
    return data;
  },

  async deviceCheckin(token, deviceId, latitude, longitude) {
    const res = await fetch(`${BASE_URL}/api/device/checkin`, {
      method: 'POST',
      headers: { ...getHeaders(token), 'Content-Type': 'application/json' },
      body: JSON.stringify({
        device_id: deviceId,
        latitude: String(latitude),
        longitude: String(longitude),
      }),
    });
    const data = await res.json();
    if (data.status === 0) throw new Error(data.message || 'Checkin falhou');
    return data;
  },

  async listOperators(token) {
    const res = await fetchWithTimeout(
      `${BASE_URL}/api/admin/operators`,
      { headers: getHeaders(token) },
      TIMEOUT_LIST_MS
    );
    let data;
    try {
      data = await res.json();
    } catch {
      throw new Error(`Resposta inválida do servidor (${res.status}). Verifique a URL em config/api.js.`);
    }
    if (data.status === 0) throw new Error(data.message || 'Erro ao listar operadores');
    return data.data?.operators || [];
  },

  async registerOperatorFace(token, operatorId, imageUri) {
    const formData = new FormData();
    formData.append('image', {
      uri: imageUri,
      type: 'image/jpeg',
      name: 'image.jpg',
    });

    const res = await fetchWithTimeout(
      `${BASE_URL}/api/admin/operators/${operatorId}/face-register`,
      {
        method: 'POST',
        headers: getHeaders(token),
        body: formData,
      },
      TIMEOUT_FACE_MS
    );
    let data;
    try {
      data = await res.json();
    } catch {
      throw new Error(
        `Falha ao registrar rosto (HTTP ${res.status}). O servidor Track pode estar lento ou a Thalamus não respondeu — veja os logs do Laravel.`
      );
    }
    if (data.status === 0) throw new Error(data.message || 'Falha ao registrar rosto');
    return data;
  },

  async registerUserFace(token, userId, imageUri) {
    const formData = new FormData();
    formData.append('image', {
      uri: imageUri,
      type: 'image/jpeg',
      name: 'image.jpg',
    });

    const res = await fetchWithTimeout(
      `${BASE_URL}/api/admin/users/${userId}/face-register`,
      {
        method: 'POST',
        headers: getHeaders(token),
        body: formData,
      },
      TIMEOUT_FACE_MS
    );
    let data;
    try {
      data = await res.json();
    } catch {
      throw new Error(
        `Falha ao registrar rosto (HTTP ${res.status}). O servidor Track pode estar lento ou a Thalamus não respondeu — veja os logs do Laravel.`
      );
    }
    if (data.status === 0) throw new Error(data.message || 'Falha ao registrar rosto');
    return data;
  },

  async reports(token) {
    const res = await fetch(`${BASE_URL}/api/reports`, {
      headers: getHeaders(token),
    });
    const data = await res.json();
    if (data.status === 0) throw new Error(data.message || 'Erro ao carregar');
    return data;
  },
};
