import React from 'react';
import { View, StyleSheet, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

// Web version - simple component
const DeliveryMarkerWeb = () => null;

// Native version - full component
const DeliveryMarkerNative = ({ type = 'pickup', isActive = true, size = 40 }) => {
  const getMarkerConfig = () => {
    const configs = {
      pickup: {
        icon: 'location',
        color: isActive ? '#4CAF50' : '#9E9E9E',
        bgColor: isActive ? 'rgba(76, 175, 80, 0.2)' : 'rgba(158, 158, 158, 0.2)',
      },
      delivery: {
        icon: 'home',
        color: isActive ? '#FF9800' : '#9E9E9E',
        bgColor: isActive ? 'rgba(255, 152, 0, 0.2)' : 'rgba(158, 158, 158, 0.2)',
      },
      driver: {
        icon: 'car',
        color: isActive ? '#2196F3' : '#9E9E9E',
        bgColor: isActive ? 'rgba(33, 150, 243, 0.2)' : 'rgba(158, 158, 158, 0.2)',
      },
      user: {
        icon: 'person',
        color: '#9C27B0',
        bgColor: 'rgba(156, 39, 176, 0.2)',
      },
    };

    return configs[type] || configs.pickup;
  };

  const config = getMarkerConfig();

  return (
    <View style={[styles.container, { width: size, height: size }]}>
      <View style={[styles.outerCircle, { 
        backgroundColor: config.bgColor,
        width: size,
        height: size,
        borderRadius: size / 2,
      }]}>
        <View style={[styles.innerCircle, { 
          backgroundColor: 'white',
          width: size * 0.7,
          height: size * 0.7,
          borderRadius: (size * 0.7) / 2,
        }]}>
          <Ionicons 
            name={config.icon} 
            size={size * 0.4} 
            color={config.color} 
          />
        </View>
      </View>
    </View>
  );
};

// Main component that chooses between web and native
const DeliveryMarker = (props) => {
  if (Platform.OS === 'web') {
    return <DeliveryMarkerWeb {...props} />;
  }
  return <DeliveryMarkerNative {...props} />;
};

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  outerCircle: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  innerCircle: {
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: 'white',
  },
});

export default DeliveryMarker;