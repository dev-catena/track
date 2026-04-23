/**
 * Configuração opcional da doca Zontec via Bluetooth LE (além do portal 192.168.4.1).
 */
import React, {useCallback, useEffect, useState} from 'react';
import {
  SafeAreaView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  View,
  FlatList,
  Pressable,
  Platform,
  PermissionsAndroid,
  Alert,
  ActivityIndicator,
  useColorScheme,
} from 'react-native';
import {BleManager, Device, Characteristic, State} from 'react-native-ble-plx';
import NetInfo from '@react-native-community/netinfo';
import {Buffer} from 'buffer';
import {
  ADV_NAME_PREFIX,
  BLE_CONFIG_CHAR_UUID,
  BLE_SERVICE_UUID,
  BLE_STATUS_CHAR_UUID,
} from './src/constants';

let wifiGetSSID: null | (() => Promise<string>) = null;
try {
  const Wifi = require('react-native-wifi-reborn');
  wifiGetSSID = async () => {
    if (Platform.OS === 'ios') {
      return '';
    }
    try {
      return (await Wifi.getCurrentWifiSSID()) || '';
    } catch {
      return '';
    }
  };
} catch {
  wifiGetSSID = async () => '';
}

const manager = new BleManager();

function App(): React.JSX.Element {
  const isDark = useColorScheme() === 'dark';
  const [btOn, setBtOn] = useState(false);
  const [scanning, setScanning] = useState(false);
  const [found, setFound] = useState<Device[]>([]);
  const [connecting, setConnecting] = useState(false);
  const [dev, setDev] = useState<Device | null>(null);
  const [ssid, setSsid] = useState('');
  const [password, setPassword] = useState('');
  const [serverIp, setServerIp] = useState('10.102.0.103');
  const [sending, setSending] = useState(false);
  const [lastStatus, setLastStatus] = useState('');

  useEffect(() => {
    const sub = manager.onStateChange(s => {
      if (s === State.PoweredOn) {
        setBtOn(true);
      } else {
        setBtOn(false);
      }
    }, true);
    (async () => {
      if (Platform.OS === 'android') {
        const api = Number(Platform.Version);
        if (api >= 31) {
          await PermissionsAndroid.requestMultiple([
            'android.permission.BLUETOOTH_SCAN' as any,
            'android.permission.BLUETOOTH_CONNECT' as any,
            'android.permission.ACCESS_FINE_LOCATION' as any,
          ]);
        } else {
          await PermissionsAndroid.request(
            PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION,
          );
        }
      }
    })();
    (async () => {
      const n = await NetInfo.fetch();
      if (n.type === 'wifi' && n.details && 'ssid' in n.details) {
        const s = n.details as {ssid?: string};
        if (s.ssid) {
          setSsid(s.ssid.replace(/"/g, ''));
        }
      }
      if (wifiGetSSID) {
        const w = await wifiGetSSID();
        if (w) {
          setSsid(w);
        }
      }
    })();
    return () => {
      sub.remove();
    };
  }, []);

  const startParear = useCallback(() => {
    if (!btOn) {
      Alert.alert('Bluetooth', 'Ative o Bluetooth e tente de novo.');
      return;
    }
    setFound([]);
    setDev(null);
    setLastStatus('');
    setScanning(true);
    const seen = new Set<string>();
    manager.startDeviceScan(null, {allowDuplicates: false}, (err, d) => {
      if (err) {
        setScanning(false);
        Alert.alert('Scan', err.message);
        return;
      }
      if (!d) {
        return;
      }
      const name = d.name || '';
      if (name.length > 0 && name.indexOf(ADV_NAME_PREFIX) === 0) {
        if (!seen.has(d.id)) {
          seen.add(d.id);
          setFound(prev => (prev.find(x => x.id === d.id) ? prev : [...prev, d]));
        }
      }
    });
    setTimeout(() => {
      manager.stopDeviceScan();
      setScanning(false);
    }, 10000);
  }, [btOn]);

  const onSelect = async (d: Device) => {
    try {
      setConnecting(true);
      manager.stopDeviceScan();
      setScanning(false);
      const connected = await manager.connectToDevice(d.id, {timeout: 10000});
      await connected.requestMTU(500);
      await connected.discoverAllServicesAndCharacteristics();
      setDev(connected);
    } catch (e: any) {
      Alert.alert('Conexão', e?.message || String(e));
    } finally {
      setConnecting(false);
    }
  };

  const enviarConfig = async () => {
    if (!dev) {
      return;
    }
    if (!ssid.trim() || !serverIp.trim()) {
      Alert.alert('Dados', 'Preencha SSID e IP do backend.');
      return;
    }
    setSending(true);
    setLastStatus('');
    try {
      const body = JSON.stringify({
        ssid: ssid.trim(),
        password: password,
        server_ip: serverIp.trim(),
      });
      const b64 = Buffer.from(body, 'utf8').toString('base64');
      await dev.writeCharacteristicWithResponseForService(
        BLE_SERVICE_UUID,
        BLE_CONFIG_CHAR_UUID,
        b64,
      );
      // ESP testa WiFi (até ~10s) e backend; o status final só após isso
      await new Promise(r => setTimeout(r, 20000));
      const st: Characteristic | null = await dev.readCharacteristicForService(
        BLE_SERVICE_UUID,
        BLE_STATUS_CHAR_UUID,
      );
      if (st?.value) {
        const dec = Buffer.from(st.value, 'base64').toString('utf8');
        setLastStatus(dec);
        let alertMsg = dec;
        try {
          const j = JSON.parse(dec) as {message?: string};
          if (j && j.message) {
            alertMsg = j.message;
          }
        } catch {
          // texto puro
        }
        Alert.alert('Resposta do ESP32', alertMsg);
      } else {
        setLastStatus('(sem leitura de status)');
      }
    } catch (e: any) {
      Alert.alert('Envio', e?.message || String(e));
    } finally {
      setSending(false);
    }
  };

  const bg = isDark ? '#1a1a1e' : '#f0f2f5';
  const titleC = isDark ? '#fff' : '#111';
  return (
    <SafeAreaView style={[styles.safe, {backgroundColor: bg}]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />
      <View style={styles.header}>
        <Text style={[styles.h1, {color: titleC}]}>
          Zontec — Config rede
        </Text>
        <Text style={styles.hint}>
          Método opcional via BLE. O portal em 192.168.4.1 continua igual.
        </Text>
      </View>

      <Pressable
        style={[styles.btn, !btOn && styles.btnOff]}
        onPress={startParear}
        disabled={scanning || !btOn}>
        {scanning ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.btnTxt}>
            {btOn ? 'Parear: buscar IOT-…' : 'Bluetooth desligado'}
          </Text>
        )}
      </Pressable>
      {scanning && <Text style={styles.scanTxt}>Buscando ~10s…</Text>}

      <Text style={styles.sub}>Dispositivos (nome começa com {ADV_NAME_PREFIX})</Text>
      <FlatList
        data={found}
        keyExtractor={item => item.id}
        style={styles.list}
        renderItem={({item}) => (
          <Pressable
            style={styles.item}
            onPress={() => onSelect(item)}
            android_ripple={{color: '#ccc'}}>
            <Text style={styles.itemTitle}>{item.name || item.id}</Text>
            <Text style={styles.itemId}>{item.id}</Text>
          </Pressable>
        )}
        ListEmptyComponent={
          <Text style={styles.empty}>
            {scanning
              ? 'Aproxime a doca e aguarde…'
              : 'Nada ainda — toque em Parear.'}
          </Text>
        }
      />

      {connecting && <Text style={styles.info}>Conectando…</Text>}

      {dev && !connecting && (
        <View style={styles.form}>
          <Text style={styles.sub}>Ajuste a rede (mesmos campos do portal)</Text>
          <Text style={styles.lab}>IP do backend (MQTT/HTTP)</Text>
          <TextInput
            style={styles.inp}
            value={serverIp}
            onChangeText={setServerIp}
            placeholder="10.102.0.103"
            autoCapitalize="none"
            keyboardType="decimal-pad"
          />
          <Text style={styles.lab}>SSID WiFi</Text>
          <TextInput
            style={styles.inp}
            value={ssid}
            onChangeText={setSsid}
            placeholder="nome da rede"
            autoCapitalize="none"
          />
          <Text style={styles.lab}>Senha WiFi</Text>
          <TextInput
            style={styles.inp}
            value={password}
            onChangeText={setPassword}
            secureTextEntry
            placeholder="senha (conforme a rede)"
          />
          {sending && (
            <Text style={styles.wait}>
              Não saia desta tela. O ESP está testando WiFi e o backend (até ~30s)…
            </Text>
          )}
          <Pressable
            style={[styles.btn, styles.btnOk]}
            onPress={enviarConfig}
            disabled={sending}>
            {sending ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.btnTxt}>Enviar para gravar no ESP32</Text>
            )}
          </Pressable>
          {lastStatus ? (
            <Text style={styles.status}>Última resposta: {lastStatus}</Text>
          ) : null}
        </View>
      )}

    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {flex: 1, padding: 16},
  header: {marginBottom: 12},
  h1: {fontSize: 22, fontWeight: '700', marginBottom: 6},
  hint: {fontSize: 13, color: '#666'},
  sub: {marginTop: 8, fontWeight: '600', color: '#333'},
  btn: {
    backgroundColor: '#0c5389',
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
    marginVertical: 6,
  },
  btnOff: {opacity: 0.5},
  btnOk: {backgroundColor: '#2d6a4f', marginTop: 12},
  btnTxt: {color: '#fff', fontSize: 16, fontWeight: '600'},
  scanTxt: {textAlign: 'center', color: '#888', marginBottom: 8},
  list: {maxHeight: 160, marginTop: 6},
  item: {
    padding: 10,
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    marginBottom: 6,
    backgroundColor: '#fff',
  },
  itemTitle: {fontWeight: '600', color: '#111'},
  itemId: {fontSize: 12, color: '#888', marginTop: 4},
  empty: {color: '#999', textAlign: 'center', marginTop: 8},
  info: {textAlign: 'center', color: '#0c5389', marginVertical: 4},
  form: {
    marginTop: 8,
    padding: 8,
    borderTopWidth: 1,
    borderColor: '#ddd',
  },
  lab: {marginTop: 6, fontSize: 12, color: '#555'},
  inp: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 6,
    padding: 8,
    marginTop: 4,
    color: '#111',
    backgroundColor: '#fff',
  },
  status: {marginTop: 8, fontSize: 12, color: '#444'},
  wait: {fontSize: 12, color: '#a63', marginBottom: 8, textAlign: 'center'},
});

export default App;
