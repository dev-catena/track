import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { api } from '../services/api';
import { colors } from '../theme/colors';

export default function ReportsScreen({ navigation }) {
  const { token } = useAuth();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  const loadReports = async () => {
    try {
      const res = await api.reports(token);
      if (res.status === 1 && res.data) setData(res.data);
    } catch (e) {
      console.warn('Reports error:', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadReports();
  }, [token]);

  const reports = data?.reports || [];

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl refreshing={loading} onRefresh={loadReports} />
      }
    >
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>← Voltar</Text>
        </TouchableOpacity>
        <Text style={styles.title}>Relatórios</Text>
      </View>
      {reports.length === 0 ? (
        <Text style={styles.empty}>Nenhum relatório disponível</Text>
      ) : (
        reports.map((r, i) => (
          <View key={i} style={styles.card}>
            <Text style={styles.deviceName}>{r.device_name}</Text>
            <Text style={styles.date}>Retirada: {r.last_pickup_date}</Text>
            <Text style={styles.date}>Devolução: {r.return_date || '-'}</Text>
            <Text style={[styles.status, r.status === 1 && styles.statusOk]}>
              {r.status_message}
            </Text>
          </View>
        ))
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    padding: 20,
    backgroundColor: colors.primary,
  },
  backText: {
    color: '#fff',
    fontSize: 16,
    marginBottom: 8,
  },
  title: {
    fontSize: 20,
    fontWeight: '700',
    color: '#fff',
  },
  empty: {
    padding: 24,
    textAlign: 'center',
    color: colors.textSecondary,
  },
  card: {
    margin: 16,
    padding: 16,
    backgroundColor: colors.surface,
    borderRadius: 8,
    borderLeftWidth: 4,
    borderLeftColor: colors.primary,
  },
  deviceName: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.primaryDark,
  },
  date: {
    fontSize: 14,
    color: colors.textSecondary,
    marginTop: 4,
  },
  status: {
    marginTop: 8,
    fontSize: 14,
    color: colors.error,
    fontWeight: '500',
  },
  statusOk: {
    color: colors.success,
  },
});
