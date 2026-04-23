import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  FlatList,
  Alert,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useAuth } from '../context/AuthContext';
import { api } from '../services/api';
import { colors } from '../theme/colors';

export default function FaceRegisterScreen({ navigation }) {
  const { token, user, effectiveOrganizationId } = useAuth();
  const [operators, setOperators] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedOperator, setSelectedOperator] = useState(null);
  const [capturing, setCapturing] = useState(false);
  const [permission, requestPermission] = useCameraPermissions();
  const cameraRef = useRef(null);

  useEffect(() => {
    loadOperators();
  }, [token, effectiveOrganizationId]);

  const loadOperators = async () => {
    try {
      if (!effectiveOrganizationId) {
        setOperators([]);
        return;
      }
      const list = await api.listOperators(token, effectiveOrganizationId);
      setOperators(list || []);
    } catch (e) {
      Alert.alert('Erro', e.message || 'Falha ao carregar usuários');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadOperators();
  };

  const selectOperator = (op) => {
    setSelectedOperator(op);
  };

  const captureAndRegister = async () => {
    if (!selectedOperator || !cameraRef.current || !permission?.granted) {
      Alert.alert('Erro', 'Selecione um operador e permita a câmera.');
      return;
    }
    setCapturing(true);
    try {
      const photo = await cameraRef.current.takePictureAsync({
        quality: 0.8,
        base64: false,
      });
      if (!photo?.uri) throw new Error('Falha ao capturar imagem');

      const isUser = selectedOperator.type === 'user'; // type: 'operator' | 'user'
      if (isUser) {
        await api.registerUserFace(token, selectedOperator.id, photo.uri);
      } else {
        const scope =
          !user?.organization_id && user?.role === 'superadmin'
            ? effectiveOrganizationId
            : null;
        await api.registerOperatorFace(
          token,
          selectedOperator.id,
          photo.uri,
          scope,
        );
      }
      Alert.alert('Sucesso', `Rosto de ${selectedOperator.name} registrado!`);
      setSelectedOperator(null);
      loadOperators();
    } catch (e) {
      Alert.alert('Erro', e.message || 'Falha ao registrar rosto');
    } finally {
      setCapturing(false);
    }
  };

  const backToSelect = () => {
    setSelectedOperator(null);
  };

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  if (!effectiveOrganizationId) {
    return (
      <View style={styles.centered}>
        <Text style={styles.message}>
          Nenhuma unidade ativa. Superadmin: escolha a unidade na tela inicial. Outros: o usuário precisa estar
          vinculado a uma organização.
        </Text>
        <TouchableOpacity style={styles.btn} onPress={() => navigation.goBack()}>
          <Text style={styles.btnText}>Voltar</Text>
        </TouchableOpacity>
      </View>
    );
  }

  if (selectedOperator) {
    if (!permission?.granted) {
      return (
        <View style={styles.centered}>
          <Text style={styles.message}>Permissão de câmera necessária</Text>
          <TouchableOpacity style={styles.btn} onPress={requestPermission}>
            <Text style={styles.btnText}>Permitir câmera</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.backBtn} onPress={backToSelect}>
            <Text style={styles.backText}>Voltar</Text>
          </TouchableOpacity>
        </View>
      );
    }

    return (
      <View style={styles.container}>
        <Text style={styles.captureTitle}>Rosto de {selectedOperator.name}</Text>
        <CameraView style={styles.camera} ref={cameraRef} facing="front">
          <View style={styles.overlay}>
            <TouchableOpacity
              style={[styles.captureBtn, capturing && styles.btnDisabled]}
              onPress={captureAndRegister}
              disabled={capturing}
            >
              {capturing ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.captureText}>Capturar e registrar</Text>
              )}
            </TouchableOpacity>
          </View>
        </CameraView>
        <TouchableOpacity style={styles.backBtn} onPress={backToSelect}>
          <Text style={styles.backText}>Voltar</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.listContainer}>
      <Text style={styles.title}>Gravar rostos</Text>
      <Text style={styles.subtitle}>
        Selecione o usuário da empresa para gravar o rosto. Toque no nome.
      </Text>
      <FlatList
        data={operators}
        keyExtractor={(item) => String(item.id)}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        renderItem={({ item }) => (
          <TouchableOpacity
            style={styles.card}
            onPress={() => selectOperator(item)}
            activeOpacity={0.7}
          >
            <Text style={styles.cardName}>{item.name}</Text>
            {(item.username || item.email) && (
              <Text style={styles.cardMeta}>
                {item.username ? `@${item.username}` : item.email}
              </Text>
            )}
            {item.face_id ? (
              <Text style={styles.faceOk}>✓ Rosto cadastrado</Text>
            ) : (
              <Text style={styles.faceMissing}>Rosto não cadastrado</Text>
            )}
          </TouchableOpacity>
        )}
        ListEmptyComponent={
          <Text style={styles.empty}>
            Nenhum usuário cadastrado na empresa. Cadastre operadores no Track primeiro.
          </Text>
        }
      />
      <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
        <Text style={styles.backText}>Voltar</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000',
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  listContainer: {
    flex: 1,
    padding: 24,
    backgroundColor: colors.background,
  },
  title: {
    fontSize: 20,
    fontWeight: '700',
    color: colors.primaryDark,
    marginBottom: 4,
  },
  subtitle: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 20,
  },
  card: {
    backgroundColor: colors.surface,
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.primaryLightest,
  },
  cardName: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.primaryDark,
  },
  cardMeta: {
    fontSize: 13,
    color: colors.textSecondary,
    marginTop: 4,
  },
  faceOk: {
    fontSize: 12,
    color: '#22c55e',
    marginTop: 6,
  },
  faceMissing: {
    fontSize: 12,
    color: '#f59e0b',
    marginTop: 6,
  },
  empty: {
    textAlign: 'center',
    color: colors.textSecondary,
    marginTop: 32,
  },
  camera: {
    flex: 1,
    minHeight: 400,
  },
  overlay: {
    flex: 1,
    backgroundColor: 'transparent',
    justifyContent: 'flex-end',
    alignItems: 'center',
    paddingBottom: 48,
  },
  captureTitle: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
    textAlign: 'center',
    padding: 16,
    backgroundColor: 'rgba(0,0,0,0.5)',
  },
  captureBtn: {
    backgroundColor: colors.primary,
    paddingVertical: 14,
    paddingHorizontal: 32,
    borderRadius: 8,
    minWidth: 200,
    alignItems: 'center',
  },
  btnDisabled: {
    opacity: 0.7,
  },
  captureText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  message: {
    fontSize: 16,
    color: colors.textSecondary,
    marginBottom: 24,
    textAlign: 'center',
  },
  btn: {
    backgroundColor: colors.primary,
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 8,
  },
  btnText: {
    color: '#fff',
    fontSize: 16,
  },
  backBtn: {
    padding: 16,
    alignItems: 'center',
  },
  backText: {
    color: colors.primary,
    fontSize: 14,
  },
});
