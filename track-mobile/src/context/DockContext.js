import React, { createContext, useContext, useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';

const DOCK_KEY = '@track_dock';

const DockContext = createContext(null);

export function DockProvider({ children }) {
  const [dock, setDockState] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDock();
  }, []);

  const loadDock = async () => {
    try {
      const stored = await AsyncStorage.getItem(DOCK_KEY);
      if (stored) setDockState(JSON.parse(stored));
    } catch (e) {
      console.warn('Erro ao carregar doca:', e);
    } finally {
      setLoading(false);
    }
  };

  const setDock = async (dockData) => {
    if (dockData) {
      await AsyncStorage.setItem(DOCK_KEY, JSON.stringify(dockData));
      setDockState(dockData);
    } else {
      await AsyncStorage.removeItem(DOCK_KEY);
      setDockState(null);
    }
  };

  const clearDock = () => setDock(null);

  return (
    <DockContext.Provider value={{ dock, setDock, clearDock, loading }}>
      {children}
    </DockContext.Provider>
  );
}

export function useDock() {
  const ctx = useContext(DockContext);
  if (!ctx) throw new Error('useDock must be used within DockProvider');
  return ctx;
}
