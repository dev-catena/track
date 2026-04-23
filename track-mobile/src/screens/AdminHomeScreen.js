import React, { useMemo } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Alert,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import { useDock } from '../context/DockContext';
import { colors } from '../theme/colors';

export default function AdminHomeScreen({ navigation }) {
  const insets = useSafeAreaInsets();
  const {
    user,
    logout,
    organizations = [],
    effectiveOrganizationId,
    setTabletScopeOrganization,
  } = useAuth();
  const { dock } = useDock();

  const currentOrgName = useMemo(() => {
    if (user?.organization_id) {
      return user.organization_name || 'Organização';
    }
    const o = organizations.find((x) => Number(x.id) === Number(effectiveOrganizationId));
    return o?.name || (organizations[0]?.name ?? 'Unidade');
  }, [user, organizations, effectiveOrganizationId]);

  const showOrgPicker = () => {
    if (user?.role !== 'superadmin' || user?.organization_id) return;
    if (organizations.length < 2) return;
    const buttons = organizations.map((o) => ({
      text: o.name,
      onPress: () => setTabletScopeOrganization(o.id),
    }));
    buttons.push({ text: 'Cancelar', style: 'cancel' });
    Alert.alert('Unidade do tablet', 'Qual unidade deseja usar agora?', buttons);
  };

  const handleLogout = () => {
    Alert.alert('Sair', 'Deseja sair?', [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Sair', onPress: logout },
    ]);
  };

  return (
    <ScrollView style={styles.container}>
      <View style={[styles.header, { paddingTop: Math.max(insets.top, 16) + 12 }]}>
        <View style={styles.headerLeft}>
          {!!(user?.organization_id || organizations.length) &&
            (user?.role === 'superadmin' &&
            !user?.organization_id &&
            organizations.length > 1 ? (
              <TouchableOpacity onPress={showOrgPicker}>
                <Text style={styles.companyName} numberOfLines={2}>
                  {currentOrgName} · trocar
                </Text>
              </TouchableOpacity>
            ) : (
              <Text style={styles.companyName} numberOfLines={2}>
                {currentOrgName}
              </Text>
            ))}
          <Text style={styles.greeting}>Admin: {user?.name}</Text>
        </View>
        <TouchableOpacity onPress={handleLogout}>
          <Text style={styles.logout}>Sair</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Configuração do tablet</Text>
        <TouchableOpacity
          style={styles.btn}
          onPress={() => navigation.navigate('SetupDock')}
        >
          <Text style={styles.btnText}>Configurar doca</Text>
        </TouchableOpacity>
        {dock && (
          <Text style={styles.dockInfo}>Doca atual: {dock.name}</Text>
        )}
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Cadastro de rostos</Text>
        <Text style={styles.cardDesc}>
          Grave fotos dos operadores para reconhecimento facial no checkout.
        </Text>
        <TouchableOpacity
          style={styles.btn}
          onPress={() => navigation.navigate('FaceRegister')}
        >
          <Text style={styles.btnText}>Gravar rostos</Text>
        </TouchableOpacity>
      </View>
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
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.primaryDark,
    marginBottom: 8,
  },
  cardDesc: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 16,
  },
  dockInfo: {
    fontSize: 13,
    color: colors.textSecondary,
    marginTop: 8,
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});
