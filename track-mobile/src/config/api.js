/**
 * Configuração da API Track
 *
 * IMPORTANTE: use aqui a URL do backend Laravel (Track) na sua rede, ex.: http://IP:8000
 * O reconhecimento facial é feito por API externa (Thalamus), igual era com a Luxand:
 * isso fica só no servidor — variáveis THALAMUS_* no .env do Laravel. O app só fala com o Track.
 *
 * Opcional: defina EXPO_PUBLIC_API_BASE_URL no .env na raiz do Expo (reiniciar com -c).
 *
 * Exemplos:
 * - Rede local (servidor Track + Expo nesta máquina): http://10.102.0.103:8000
 * - Emulador Android: http://10.0.2.2:8000
 * - iOS Simulator: http://localhost:8000
 */
const envBase =
  typeof process !== 'undefined' && process.env.EXPO_PUBLIC_API_BASE_URL
    ? String(process.env.EXPO_PUBLIC_API_BASE_URL).replace(/\/$/, '')
    : '';

export const API_CONFIG = {
  BASE_URL: envBase || 'http://10.102.0.103:8000',
};
