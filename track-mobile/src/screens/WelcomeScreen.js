import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Image } from 'react-native';
import { colors } from '../theme/colors';

export default function WelcomeScreen({ navigation }) {
  return (
    <View style={styles.container}>
      <Image
        source={require('../../assets/logo-trac.png')}
        style={styles.logo}
        resizeMode="contain"
      />
      <Text style={styles.subtitle}>Controle de dispositivos</Text>

      <TouchableOpacity
        style={styles.btn}
        onPress={() => navigation.navigate('FaceCamera')}
      >
        <Text style={styles.btnText}>Iniciar</Text>
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.adminLink}
        onPress={() => navigation.navigate('AdminLogin')}
      >
        <Text style={styles.adminLinkText}>Entrar como admin</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.background,
    padding: 24,
  },
  logo: {
    width: 280,
    height: 140,
    marginBottom: 24,
  },
  subtitle: {
    fontSize: 16,
    color: colors.textSecondary,
    marginBottom: 48,
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 14,
    paddingHorizontal: 48,
    borderRadius: 8,
    minWidth: 200,
    alignItems: 'center',
    marginBottom: 24,
  },
  btnText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  adminLink: {
    paddingVertical: 12,
    paddingHorizontal: 24,
  },
  adminLinkText: {
    color: colors.primary,
    fontSize: 14,
  },
});
