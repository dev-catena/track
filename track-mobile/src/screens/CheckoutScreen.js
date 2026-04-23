import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Alert } from 'react-native';
import { colors } from '../theme/colors';

export default function CheckoutScreen({ navigation }) {
  return (
    <View style={styles.container}>
      <Text style={styles.title}>Checkout</Text>
      <Text style={styles.subtitle}>
        Após validação facial, o checkout é feito automaticamente. Se você chegou aqui sem validar, volte e valide primeiro.
      </Text>
      <TouchableOpacity
        style={styles.btn}
        onPress={() => navigation.navigate('FaceValidation')}
      >
        <Text style={styles.btnText}>Validar rosto novamente</Text>
      </TouchableOpacity>
      <TouchableOpacity
        style={styles.backBtn}
        onPress={() => navigation.goBack()}
      >
        <Text style={styles.backText}>Voltar ao início</Text>
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
