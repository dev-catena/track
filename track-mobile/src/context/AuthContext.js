import React, { createContext, useContext, useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';

const TOKEN_KEY = '@track_token';
const USER_KEY = '@track_user';
const USER_TYPE_KEY = '@track_user_type'; // 'operator' | 'admin'
const TABLET_ORG_KEY = '@track_tablet_org_id'; // superadmin no app: unidade usada no tablet

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(null);
  const [user, setUser] = useState(null);
  const [userType, setUserType] = useState(null); // 'operator' | 'admin'
  const [tabletScopeOrgId, setTabletScopeOrgId] = useState(null); // quando superadmin sem organization_id
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadStoredAuth();
  }, []);

  const loadStoredAuth = async () => {
    try {
      const [storedToken, storedUser, storedType, storedTabletOrg] = await Promise.all([
        AsyncStorage.getItem(TOKEN_KEY),
        AsyncStorage.getItem(USER_KEY),
        AsyncStorage.getItem(USER_TYPE_KEY),
        AsyncStorage.getItem(TABLET_ORG_KEY),
      ]);
      if (storedToken) setToken(storedToken);
      if (storedUser) setUser(JSON.parse(storedUser));
      if (storedType) setUserType(storedType);
      if (storedTabletOrg) {
        const n = parseInt(storedTabletOrg, 10);
        if (Number.isFinite(n)) setTabletScopeOrgId(n);
      }
    } catch (e) {
      console.warn('Erro ao carregar auth:', e);
    } finally {
      setLoading(false);
    }
  };

  const login = async (tokenValue, userData, type = 'operator', initialTabletOrgId = null) => {
    // Superadmin sem unidade: escopo do tablet (primeira unidade da lista ou escolha depois)
    const needsScope =
      type === 'admin' && userData?.role === 'superadmin' && !userData?.organization_id;
    const scope =
      initialTabletOrgId ??
      (userData?.organizations?.[0]?.id != null
        ? Number(userData.organizations[0].id)
        : null);
    if (needsScope && scope) {
      await AsyncStorage.setItem(TABLET_ORG_KEY, String(scope));
      setTabletScopeOrgId(scope);
    } else {
      await AsyncStorage.removeItem(TABLET_ORG_KEY);
      setTabletScopeOrgId(null);
    }

    await Promise.all([
      AsyncStorage.setItem(TOKEN_KEY, tokenValue),
      AsyncStorage.setItem(USER_KEY, JSON.stringify(userData)),
      AsyncStorage.setItem(USER_TYPE_KEY, type),
    ]);
    setToken(tokenValue);
    setUser(userData);
    setUserType(type);
  };

  const logout = async () => {
    await Promise.all([
      AsyncStorage.removeItem(TOKEN_KEY),
      AsyncStorage.removeItem(USER_KEY),
      AsyncStorage.removeItem(USER_TYPE_KEY),
      AsyncStorage.removeItem(TABLET_ORG_KEY),
    ]);
    setToken(null);
    setUser(null);
    setUserType(null);
    setTabletScopeOrgId(null);
  };

  /** Unidade usada no tablet: admin/gerente têm no usuário; superadmin usa o escolhido. */
  const effectiveOrganizationId =
    user?.organization_id != null ? user.organization_id : tabletScopeOrgId;
  const organizations = user?.organizations || [];

  const setTabletScopeOrganization = async (orgId) => {
    const n = parseInt(String(orgId), 10);
    if (!Number.isFinite(n)) return;
    setTabletScopeOrgId(n);
    await AsyncStorage.setItem(TABLET_ORG_KEY, String(n));
  };

  return (
    <AuthContext.Provider
      value={{
        token,
        user,
        userType,
        tabletScopeOrgId,
        effectiveOrganizationId,
        organizations,
        loading,
        login,
        logout,
        setTabletScopeOrganization,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
