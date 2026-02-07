import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const LocationCard = ({ location, onClose }) => {
  const getLocationTypeIcon = (type) => {
    const icons = {
      pickup: { name: 'location', color: '#4CAF50' },
      delivery: { name: 'home', color: '#FF9800' },
      driver: { name: 'car', color: '#2196F3' },
      user: { name: 'person', color: '#9C27B0' },
    };
    return icons[type] || icons.pickup;
  };

  const icon = getLocationTypeIcon(location?.type);

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View style={styles.iconContainer}>
          <Ionicons name={icon.name} size={20} color="white" />
        </View>
        <View style={styles.titleContainer}>
          <Text style={styles.title}>{location?.title || 'Location'}</Text>
          {location?.description && (
            <Text style={styles.description} numberOfLines={2}>
              {location.description}
            </Text>
          )}
        </View>
        <TouchableOpacity onPress={onClose} style={styles.closeButton}>
          <Ionicons name="close" size={24} color="#666" />
        </TouchableOpacity>
      </View>
      
      {location?.address && (
        <View style={styles.addressContainer}>
          <Text style={styles.addressText}>{location.address}</Text>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: 'white',
    borderRadius: 12,
    padding: 16,
    margin: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 8,
    elevation: 5,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  iconContainer: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#4CAF50',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  titleContainer: {
    flex: 1,
  },
  title: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 2,
  },
  description: {
    fontSize: 14,
    color: '#666',
  },
  closeButton: {
    padding: 4,
  },
  addressContainer: {
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: '#EEE',
  },
  addressText: {
    fontSize: 14,
    color: '#333',
    lineHeight: 20,
  },
});

export default LocationCard;