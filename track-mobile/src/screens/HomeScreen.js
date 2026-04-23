import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  RefreshControl,
  Alert,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useAuth } from '../context/AuthContext';
import { useDock } from '../context/DockContext';
import { api } from '../services/api';
import { colors } from '../theme/colors';

const CHECKED_IN_KEY = '@track_tablet_checked_in';

export default function HomeScreen({ navigation }) {
  const insets = useSafeAreaInsets();
  const { user, token, logout } = useAuth();
  const { dock } = useDock();
  const [dashboard, setDashboard] = useState(null);
  const [loading, setLoading] = useState(true);
  const [tabletCheckedIn, setTabletCheckedIn] = useState(false);

  const loadDashboard = async () => {
    try {
      const res = await api.dashboard(token);
      if (res.status === 1 && res.data) {
        setDashboard(res.data);
      }
    } catch (e) {
      console.warn('Dashboard error:', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDashboard();
    const interval = setInterval(loadDashboard, 10000);
    return () => clearInterval(interval);
  }, [token]);

  useEffect(() => {
    const check = async () => {
      try {
        const v = await AsyncStorage.getItem(CHECKED_IN_KEY);
        setTabletCheckedIn(v === '1');
      } catch (_) {}
    };
    check();
  }, []);

  // Checkin é automático (doca detecta dispositivo no slot) - limpa estado local quando dashboard indica devolução
  useEffect(() => {
    if (dashboard?.checked_in === 0) {
      setTabletCheckedIn(false);
      AsyncStorage.removeItem(CHECKED_IN_KEY);
    }
  }, [dashboard?.checked_in]);

  const handleCheckout = () => {
    if (!dock) {
      Alert.alert('Doca não configurada', 'A doca deste tablet ainda não foi configurada. Entre em contato com o administrador.');
      return;
    }
    navigation.navigate('FaceValidation', { onSuccess: onCheckoutSuccess });
  };

  const onCheckoutSuccess = async () => {
    setTabletCheckedIn(true);
    await AsyncStorage.setItem(CHECKED_IN_KEY, '1');
  };

  const handleLogout = () => {
    Alert.alert('Sair', 'Deseja sair?', [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Sair', onPress: logout },
    ]);
  };

  const checkedIn = dock ? tabletCheckedIn : (dashboard?.checked_in === 1);
  const device = dashboard?.device;
  const companyName = user?.organization_name || dashboard?.user?.organization_name || '';

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl refreshing={loading} onRefresh={loadDashboard} />
      }
    >
      <View style={[styles.header, { paddingTop: Math.max(insets.top, 16) + 12 }]}>
        <View style={styles.headerLeft}>
          {!!companyName && <Text style={styles.companyName}>{companyName}</Text>}
          <Text style={styles.greeting}>Olá, {user?.name || 'Operador'}</Text>
        </View>
        <View style={styles.headerRight}>
          {dock && (
            <Text style={styles.configText}>Doca: {dock.name}</Text>
          )}
          <TouchableOpacity onPress={handleLogout}>
            <Text style={styles.logout}>Sair</Text>
          </TouchableOpacity>
        </View>
      </View>
      {!dock && (
        <View style={styles.noDockMsg}>
          <Text style={styles.noDockText}>A doca ainda não foi configurada. Entre em contato com o administrador.</Text>
        </View>
      )}

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Status</Text>
        <Text style={styles.status}>
          {checkedIn ? 'Dispositivo em uso' : 'Disponível para checkout'}
        </Text>
        {device?.name && (
          <Text style={styles.deviceName}>Dispositivo: {device.name}</Text>
        )}
        {checkedIn && (
          <Text style={styles.hint}>Devolva o dispositivo no slot – o checkin é automático.</Text>
        )}
      </View>

      <TouchableOpacity
        style={[styles.btn, styles.btnPrimary, checkedIn && styles.btnDisabled]}
        onPress={handleCheckout}
        disabled={checkedIn}
      >
        <Text style={styles.btnText}>Checkout (Pegar dispositivo)</Text>
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.linkBtn}
        onPress={() => navigation.navigate('Reports')}
      >
        <Text style={styles.linkText}>Relatórios</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingBottom: 16,
    backgroundColor: colors.primary,
  },
  headerLeft: {
    flex: 1,
    marginRight: 8,
  },
  companyName: {
    color: 'rgba(255,255,255,0.98)',
    fontSize: 14,
    fontWeight: '700',
    marginBottom: 4,
  },
  headerRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
  },
  configText: {
    color: 'rgba(255,255,255,0.9)',
    fontSize: 12,
  },
  noDockMsg: {
    margin: 16,
    padding: 12,
    backgroundColor: colors.primaryLightest,
    borderRadius: 8,
  },
  noDockText: {
    color: colors.textSecondary,
    fontSize: 14,
    textAlign: 'center',
  },
  greeting: {
    fontSize: 18,
    fontWeight: '600',
    color: '#fff',
  },
  logout: {
    color: '#fff',
    fontSize: 14,
  },
  card: {
    margin: 16,
    padding: 20,
    backgroundColor: colors.surface,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 4,
    elevation: 2,
  },
  cardTitle: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 4,
  },
  status: {
    fontSize: 18,
    fontWeight: '600',
    color: colors.primaryDark,
  },
  deviceName: {
    marginTop: 8,
    fontSize: 14,
    color: colors.textSecondary,
  },
  hint: {
    marginTop: 12,
    fontSize: 13,
    color: colors.textSecondary,
    fontStyle: 'italic',
  },
  btn: {
    marginHorizontal: 16,
    marginTop: 12,
    paddingVertical: 14,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnPrimary: {
    backgroundColor: colors.primary,
  },
  btnSecondary: {
    backgroundColor: colors.primaryLight,
  },
  btnDisabled: {
    opacity: 0.5,
  },
  btnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  linkBtn: {
    margin: 24,
    alignItems: 'center',
  },
  linkText: {
    color: colors.primary,
    fontSize: 14,
  },
});
