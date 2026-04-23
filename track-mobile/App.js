import React, { useEffect, useState } from 'react';
import * as SplashScreen from 'expo-splash-screen';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { AuthProvider, useAuth } from './src/context/AuthContext';
import { DockProvider } from './src/context/DockContext';
import CustomSplashScreen from './src/components/CustomSplashScreen';
import WelcomeScreen from './src/screens/WelcomeScreen';
import FaceCameraScreen from './src/screens/FaceCameraScreen';
import AdminLoginScreen from './src/screens/AdminLoginScreen';
import HomeScreen from './src/screens/HomeScreen';
import FaceValidationScreen from './src/screens/FaceValidationScreen';
import SetupDockScreen from './src/screens/SetupDockScreen';
import CheckoutScreen from './src/screens/CheckoutScreen';
import ReportsScreen from './src/screens/ReportsScreen';
import AdminHomeScreen from './src/screens/AdminHomeScreen';
import FaceRegisterScreen from './src/screens/FaceRegisterScreen';
import CompanyNameBadge from './src/components/CompanyNameBadge';

SplashScreen.preventAutoHideAsync();

const Stack = createNativeStackNavigator();

function AuthStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="Welcome" component={WelcomeScreen} />
      <Stack.Screen name="FaceCamera" component={FaceCameraScreen} />
      <Stack.Screen name="AdminLogin" component={AdminLoginScreen} />
    </Stack.Navigator>
  );
}

function MainStack() {
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: '#536173' },
        headerTintColor: '#fff',
        headerRight: () => <CompanyNameBadge />,
      }}
    >
      <Stack.Screen name="Home" component={HomeScreen} options={{ headerShown: false }} />
      <Stack.Screen name="FaceValidation" component={FaceValidationScreen} options={{ title: 'Validação facial' }} />
      <Stack.Screen name="Checkout" component={CheckoutScreen} options={{ title: 'Checkout' }} />
      <Stack.Screen name="Reports" component={ReportsScreen} options={{ title: 'Relatórios' }} />
    </Stack.Navigator>
  );
}

function AdminStack() {
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: '#536173' },
        headerTintColor: '#fff',
        headerRight: () => <CompanyNameBadge />,
      }}
    >
      <Stack.Screen name="AdminHome" component={AdminHomeScreen} options={{ headerShown: false }} />
      <Stack.Screen name="SetupDock" component={SetupDockScreen} options={{ title: 'Configurar doca' }} />
      <Stack.Screen name="FaceRegister" component={FaceRegisterScreen} options={{ title: 'Gravar rostos' }} />
    </Stack.Navigator>
  );
}

const MIN_SPLASH_MS = 2000;

function RootNavigator() {
  const { token, userType, loading } = useAuth();
  const [splashDone, setSplashDone] = useState(false);

  useEffect(() => {
    if (!loading) {
      SplashScreen.hideAsync();
      const timer = setTimeout(() => setSplashDone(true), MIN_SPLASH_MS);
      return () => clearTimeout(timer);
    }
  }, [loading]);

  if (loading || !splashDone) {
    return <CustomSplashScreen />;
  }

  return (
    <NavigationContainer>
      {!token ? (
        <AuthStack />
      ) : userType === 'admin' ? (
        <AdminStack />
      ) : (
        <MainStack />
      )}
    </NavigationContainer>
  );
}

export default function App() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <DockProvider>
          <RootNavigator />
        </DockProvider>
      </AuthProvider>
    </SafeAreaProvider>
  );
}
