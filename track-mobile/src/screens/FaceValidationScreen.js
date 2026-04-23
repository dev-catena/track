import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { useDock } from '../context/DockContext';
import { api } from '../services/api';
import { colors } from '../theme/colors';

export default function FaceValidationScreen({ navigation, route }) {
  const { logout, user } = useAuth();
  const { dock } = useDock();
  const [loading, setLoading] = useState(false);
  const onSuccess = route.params?.onSuccess;

  // Checkout: POST /api/self-service/open → MQTT slot_status → ESP envia POST /api/docks/slot-status
  // → backend escolhe slot livre e publica MQTT open { slot } (LED). operator_id marca o pedido pendente.
  const doCheckout = async () => {
    if (!dock?.mac_address && !dock?.pairing_code) {
      Alert.alert('Erro', 'Doca não configurada. Configure em Configurar doca.');
      return;
    }
    if (user?.id == null) {
      Alert.alert('Erro', 'Sessão inválida. Entre novamente com seu rosto.');
      return;
    }
    setLoading(true);
    try {
      await api.openDock(
        dock.mac_address,
        dock.pairing_code,
        null,
        user?.fcm_token,
        user.id
      );
      onSuccess?.();
      Alert.alert(
        'Sucesso',
        'Solicitação enviada à doca. O slot livre será liberado e o LED correspondente acenderá em seguida.',
        [{ text: 'OK', onPress: () => logout() }]
      );
    } catch (e) {
      Alert.alert('Erro', e.message || 'Falha ao abrir doca');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>Checkout</Text>
        <Text style={styles.subtitle}>
          Identidade confirmada por rosto ao entrar. Confirme para abrir a doca e retirar o
          dispositivo.
        </Text>
        <TouchableOpacity
          style={[styles.btn, loading && styles.btnDisabled]}
          onPress={doCheckout}
          disabled={loading}
        >
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.btnText}>Confirmar checkout</Text>
          )}
        </TouchableOpacity>
      </View>
      <TouchableOpacity
        style={styles.backBtn}
        onPress={() => navigation.goBack()}
      >
        <Text style={styles.backText}>Voltar</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
    padding: 24,
  },
  content: {
    flex: 1,
    justifyContent: 'center',
  },
  title: {
    fontSize: 22,
    fontWeight: '700',
    color: colors.primaryDark,
    marginBottom: 8,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 14,
    color: '#666',
    marginBottom: 24,
    textAlign: 'center',
  },
  input: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.primaryLightest,
    color: colors.textPrimary,
    padding: 14,
    borderRadius: 8,
    fontSize: 16,
    marginBottom: 20,
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 14,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnDisabled: {
    opacity: 0.7,
  },
  btnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  backBtn: {
    alignSelf: 'center',
    paddingVertical: 16,
  },
  backText: {
    color: colors.primary,
    fontSize: 16,
  },
});
