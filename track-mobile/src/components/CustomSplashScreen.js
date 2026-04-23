import React from 'react';
import { View, Image, StyleSheet } from 'react-native';

const SPLASH_BG = '#F8F8F8';

export default function CustomSplashScreen() {
  return (
    <View style={styles.container}>
      <Image
        source={require('../../assets/splash.png')}
        style={styles.logo}
        resizeMode="contain"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: SPLASH_BG,
  },
  logo: {
    width: 300,
    height: 220,
  },
});
