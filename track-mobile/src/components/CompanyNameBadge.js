import React from 'react';
import { Text } from 'react-native';
import { useAuth } from '../context/AuthContext';

/** Nome da empresa na barra do React Navigation (headerRight). */
export default function CompanyNameBadge({ style }) {
  const { user } = useAuth();
  const name = user?.organization_name;
  if (!name) return null;
  return (
    <Text
      style={[{ color: '#fff', fontSize: 13, fontWeight: '600', marginRight: 14, maxWidth: 200 }, style]}
      numberOfLines={1}
    >
      {name}
    </Text>
  );
}
