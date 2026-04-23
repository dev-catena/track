import React, { useState, useRef, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useAuth } from '../context/AuthContext';
import { api } from '../services/api';
import { colors } from '../theme/colors';

/**
 * Mock de tablet: chama POST /api/auth/tablet-mock (primeiro operador ativo em `operators`),
 * sem usar a câmera nem a Thalamus. Só para teste — exige pelo menos um operador ativo.
 * Produção / validar rosto: false. Para ligar o mock: EXPO_PUBLIC_TABLET_FACE_MOCK=1 no .env do Expo.
 */
const USE_MOCK = String(process.env.EXPO_PUBLIC_TABLET_FACE_MOCK || '') === '1';

export default function FaceCameraScreen({ navigation }) {
  const { login } = useAuth();
  const [permission, requestPermission] = useCameraPermissions();
  const [loading, setLoading] = useState(false);
  const cameraRef = useRef(null);

  useEffect(() => {
    if (!permission?.granted && permission?.canAskAgain) {
      requestPermission();
    }
  }, [permission]);

  const proceedAsOperator = async (token, operator) => {
    await login(token, operator, 'operator');
    // Não usar navigation.reset - ao trocar token/userType, o RootNavigator
    // substitui AuthStack por MainStack, que já inicia em Home
  };

  const handleValidate = async () => {
    if (!permission?.granted && !USE_MOCK) {
      Alert.alert('Câmera', 'Permita o acesso à câmera para continuar.');
      return;
    }
    setLoading(true);
    try {
      if (USE_MOCK) {
        const res = await api.tabletMock();
        if (res.status === 1 && res.data) {
          await proceedAsOperator(res.data.token, res.data.operator);
        } else {
          Alert.alert('Erro', res.message || 'Nenhum operador ativo no sistema.');
        }
        return;
      }
      const photo = await cameraRef.current.takePictureAsync({
        quality: 0.8,
        base64: false,
      });
      if (!photo?.uri) throw new Error('Falha ao capturar imagem');
      const res = await api.faceLogin(photo.uri);
      if (res.status === 1 && res.data) {
        await proceedAsOperator(res.data.token, res.data.operator);
      } else {
        Alert.alert('Rosto não reconhecido', res.message || 'Tente novamente.');
      }
    } catch (e) {
      Alert.alert('Erro', e.message || 'Rosto não reconhecido. Tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  if (!permission) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  if (!permission.granted) {
    return (
      <View style={styles.centered}>
        <Text style={styles.message}>
          {USE_MOCK
            ? 'Modo teste: permita a câmera ou use Validar para prosseguir'
            : 'Permissão de câmera necessária'}
        </Text>
        <TouchableOpacity style={styles.btn} onPress={requestPermission}>
          <Text style={styles.btnText}>Permitir câmera</Text>
        </TouchableOpacity>
        {USE_MOCK && (
          <TouchableOpacity
            style={[styles.btn, styles.btnMock, loading && styles.btnDisabled]}
            onPress={handleValidate}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.btnText}>Validar (prosseguir)</Text>
            )}
          </TouchableOpacity>
        )}
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>Voltar</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <CameraView style={styles.camera} ref={cameraRef} facing="front" />
      <View style={styles.overlay}>
        <Text style={styles.instruction}>
          {USE_MOCK
            ? 'Modo teste: toque em Validar para prosseguir'
            : 'Posicione seu rosto no quadro'}
        </Text>
        <TouchableOpacity
          style={[styles.captureBtn, loading && styles.btnDisabled]}
          onPress={handleValidate}
          disabled={loading}
        >
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.captureText}>
              {USE_MOCK ? 'Validar' : 'Identificar'}
            </Text>
          )}
        </TouchableOpacity>
      </View>
      <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
        <Text style={styles.backText}>Voltar</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000',
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.background,
    padding: 24,
  },
  camera: {
    flex: 1,
  },
  overlay: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    top: 0,
    backgroundColor: 'transparent',
    justifyContent: 'flex-end',
    alignItems: 'center',
    paddingBottom: 48,
  },
  instruction: {
    color: '#fff',
    fontSize: 16,
    marginBottom: 24,
    textAlign: 'center',
  },
  captureBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 14,
    paddingHorizontal: 32,
    borderRadius: 8,
    minWidth: 160,
    alignItems: 'center',
  },
  btnDisabled: {
    opacity: 0.7,
  },
  captureText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  message: {
    fontSize: 16,
    color: colors.textSecondary,
    marginBottom: 24,
    textAlign: 'center',
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 8,
    marginBottom: 12,
  },
  btnMock: {
    backgroundColor: colors.primaryLight,
  },
  btnText: {
    color: '#fff',
    fontSize: 16,
  },
  backBtn: {
    padding: 16,
    alignItems: 'center',
  },
  backText: {
    color: colors.primary,
    fontSize: 14,
  },
});
