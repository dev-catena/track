import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  FlatList,
  Alert,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { useDock } from '../context/DockContext';
import { api } from '../services/api';
import { colors } from '../theme/colors';

export default function SetupDockScreen({ navigation }) {
  const { user } = useAuth();
  const { setDock } = useDock();
  const [docks, setDocks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const departmentId = user?.department_id;
  const organizationId = user?.organization_id;

  const loadDocks = async () => {
    if (!departmentId && !organizationId) {
      setLoading(false);
      return;
    }
    try {
      const list = await api.listDocks(departmentId, organizationId);
      setDocks(list || []);
    } catch (e) {
      Alert.alert('Erro', e.message || 'Falha ao carregar docas');
      setDocks([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadDocks();
  }, [departmentId, organizationId]);

  const onRefresh = () => {
    setRefreshing(true);
    loadDocks();
  };

  const selectDock = async (item) => {
    try {
      await setDock({
        id: item.id,
        name: item.name,
        mac_address: item.mac_address,
        pairing_code: item.pairing_code,
        location: item.location,
      });
      Alert.alert('Sucesso', `Doca "${item.name}" configurada. Este tablet está associado a ela.`);
      navigation.goBack();
    } catch (e) {
      Alert.alert('Erro', e.message);
    }
  };

  if (!departmentId && !organizationId) {
    return (
      <View style={styles.centered}>
        <Text style={styles.message}>Seu usuário não tem departamento/empresa associado.</Text>
        <TouchableOpacity style={styles.btn} onPress={() => navigation.goBack()}>
          <Text style={styles.btnText}>Voltar</Text>
        </TouchableOpacity>
      </View>
    );
  }

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Configurar doca</Text>
      <Text style={styles.subtitle}>
        Selecione a doca que corresponde à etiqueta na doca física (MAC ou código)
      </Text>
      <FlatList
        data={docks}
        keyExtractor={(item) => String(item.id)}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        ListEmptyComponent={
          <Text style={styles.empty}>Nenhuma doca ativa encontrada.</Text>
        }
        renderItem={({ item }) => (
          <TouchableOpacity
            style={styles.card}
            onPress={() => selectDock(item)}
            activeOpacity={0.7}
          >
            <Text style={styles.cardName}>{item.name}</Text>
            <Text style={styles.cardMac}>MAC: {item.mac_address}</Text>
            {item.pairing_code && (
              <Text style={styles.cardCode}>Código: {item.pairing_code}</Text>
            )}
            {item.location && (
              <Text style={styles.cardLocation}>{item.location}</Text>
            )}
          </TouchableOpacity>
        )}
      />
      <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
        <Text style={styles.backText}>Voltar</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 24,
    backgroundColor: colors.background,
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
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
    color: colors.textSecondary,
    marginBottom: 24,
    textAlign: 'center',
  },
  card: {
    backgroundColor: colors.surface,
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.primaryLightest,
  },
  cardName: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.primaryDark,
  },
  cardMac: {
    fontSize: 13,
    color: colors.textSecondary,
    marginTop: 4,
    fontFamily: 'monospace',
  },
  cardCode: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 2,
  },
  cardLocation: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 2,
  },
  empty: {
    textAlign: 'center',
    color: colors.textSecondary,
    marginTop: 32,
  },
  message: {
    textAlign: 'center',
    color: colors.textSecondary,
    marginBottom: 24,
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 8,
  },
  btnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  backBtn: {
    alignSelf: 'center',
    paddingVertical: 20,
  },
  backText: {
    color: colors.primary,
    fontSize: 16,
  },
});
