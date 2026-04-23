import React, { createContext, useContext, useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';

const TOKEN_KEY = '@track_token';
const USER_KEY = '@track_user';
const USER_TYPE_KEY = '@track_user_type'; // 'operator' | 'admin'

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(null);
  const [user, setUser] = useState(null);
  const [userType, setUserType] = useState(null); // 'operator' | 'admin'
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadStoredAuth();
  }, []);

  const loadStoredAuth = async () => {
    try {
      const [storedToken, storedUser, storedType] = await Promise.all([
        AsyncStorage.getItem(TOKEN_KEY),
        AsyncStorage.getItem(USER_KEY),
        AsyncStorage.getItem(USER_TYPE_KEY),
      ]);
      if (storedToken) setToken(storedToken);
      if (storedUser) setUser(JSON.parse(storedUser));
      if (storedType) setUserType(storedType);
    } catch (e) {
      console.warn('Erro ao carregar auth:', e);
    } finally {
      setLoading(false);
    }
  };

  const login = async (tokenValue, userData, type = 'operator') => {
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
    ]);
    setToken(null);
    setUser(null);
    setUserType(null);
  };

  return (
    <AuthContext.Provider value={{ token, user, userType, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
