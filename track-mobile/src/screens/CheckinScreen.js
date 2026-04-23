import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { useDock } from '../context/DockContext';
import { api } from '../services/api';
import { colors } from '../theme/colors';

export default function CheckinScreen({ navigation, route }) {
  const { dock } = useDock();
  const [loading, setLoading] = useState(false);
  const onSuccess = route.params?.onSuccess;

  const handleCheckin = async () => {
    if (!dock?.mac_address && !dock?.pairing_code) {
      Alert.alert('Erro', 'Doca não configurada.');
      return;
    }
    setLoading(true);
    try {
      await api.closeDock(dock.mac_address, dock.pairing_code, 1);
      onSuccess?.();
      Alert.alert('Sucesso', 'Doca fechada! Dispositivo devolvido.');
      navigation.replace('Home');
    } catch (e) {
      Alert.alert('Erro', e.message || 'Falha ao fechar doca');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Devolver dispositivo</Text>
      <Text style={styles.subtitle}>
        Confirme a devolução do dispositivo. Sua localização será registrada.
      </Text>
      <TouchableOpacity
        style={[styles.btn, loading && styles.btnDisabled]}
        onPress={handleCheckin}
        disabled={loading}
      >
        {loading ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.btnText}>Confirmar devolução</Text>
        )}
      </TouchableOpacity>
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
    justifyContent: 'center',
    padding: 24,
    backgroundColor: colors.background,
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    color: colors.primaryDark,
    marginBottom: 16,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 16,
    color: colors.textSecondary,
    marginBottom: 32,
    textAlign: 'center',
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
    marginTop: 24,
    alignItems: 'center',
  },
  backText: {
    color: colors.primary,
    fontSize: 14,
  },
});
