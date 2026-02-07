import React, { useState, useEffect, useRef } from 'react';
import { View, StyleSheet, Text, TouchableOpacity, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import OSMMap from '../common/OSMMap';
import { LocationService } from '../../services/locationService';
import { RoutingService } from '../../services/routingService';
import LocationCard from './LocatioCard';

const PickupDeliveryMap = ({ 
  pickupLocation,
  deliveryLocation,
  driverLocation,
  showRoute = true,
  onLocationSelect,
  interactive = true,
  style 
}) => {
  const mapRef = useRef(null);
  const [region, setRegion] = useState(null);
  const [route, setRoute] = useState(null);
  const [eta, setEta] = useState(null);
  const [selectedMarker, setSelectedMarker] = useState(null);
  const [userLocation, setUserLocation] = useState(null);

  // Initialize map with locations
  useEffect(() => {
    initializeMap();
  }, [pickupLocation, deliveryLocation]);

  const initializeMap = async () => {
    try {
      // Get user location
      const location = await LocationService.getCurrentLocation();
      setUserLocation(location);
      
      // Determine initial region
      let initialRegion;
      if (pickupLocation && deliveryLocation) {
        // Fit both locations
        const coordinates = [pickupLocation, deliveryLocation, location];
        initialRegion = calculateRegionForCoordinates(coordinates);
      } else if (pickupLocation) {
        // Center on pickup
        initialRegion = {
          ...pickupLocation,
          latitudeDelta: 0.01,
          longitudeDelta: 0.01,
        };
      } else {
        // Center on user
        initialRegion = {
          ...location,
          latitudeDelta: 0.02,
          longitudeDelta: 0.02,
        };
      }
      
      setRegion(initialRegion);
      
      // Calculate route if both locations exist
      if (pickupLocation && deliveryLocation && showRoute) {
        calculateRoute();
      }
    } catch (error) {
      console.error('Error initializing map:', error);
      // Default region
      setRegion({
        latitude: 37.78825,
        longitude: -122.4324,
        latitudeDelta: 0.0922,
        longitudeDelta: 0.0421,
      });
    }
  };

  const calculateRoute = async () => {
    try {
      const points = [
        pickupLocation,
        ...(driverLocation ? [driverLocation] : []),
        deliveryLocation,
      ];
      
      const routeData = await RoutingService.getRoute(points);
      setRoute(routeData);
      
      // Calculate ETA
      const etaData = await RoutingService.getETA(
        driverLocation || pickupLocation,
        deliveryLocation
      );
      setEta(etaData);
    } catch (error) {
      console.error('Error calculating route:', error);
    }
  };

  const calculateRegionForCoordinates = (coordinates) => {
    const latitudes = coordinates.map(c => c.latitude);
    const longitudes = coordinates.map(c => c.longitude);
    
    const minLat = Math.min(...latitudes);
    const maxLat = Math.max(...latitudes);
    const minLng = Math.min(...longitudes);
    const maxLng = Math.max(...longitudes);
    
    const latitudeDelta = (maxLat - minLat) * 1.2;
    const longitudeDelta = (maxLng - minLng) * 1.2;
    
    return {
      latitude: (minLat + maxLat) / 2,
      longitude: (minLng + maxLng) / 2,
      latitudeDelta: Math.max(latitudeDelta, 0.01),
      longitudeDelta: Math.max(longitudeDelta, 0.01),
    };
  };

  const handleMarkerPress = (marker) => {
    setSelectedMarker(marker);
    onLocationSelect?.(marker);
  };

  const handleCenterUser = async () => {
    try {
      const location = await LocationService.getCurrentLocation();
      mapRef.current?.animateToRegion({
        ...location,
        latitudeDelta: 0.01,
        longitudeDelta: 0.01,
      }, 500);
    } catch (error) {
      Alert.alert('Error', 'Unable to get your location');
    }
  };

  const handleFitRoute = () => {
    if (pickupLocation && deliveryLocation) {
      const coordinates = [
        pickupLocation,
        deliveryLocation,
        ...(userLocation ? [userLocation] : []),
      ];
      
      const region = calculateRegionForCoordinates(coordinates);
      mapRef.current?.animateToRegion(region, 500);
    }
  };

  const getMarkers = () => {
    const markers = [];
    
    // User location
    if (userLocation) {
      markers.push({
        coordinate: userLocation,
        title: 'Your Location',
        type: 'user',
        customIcon: true,
        isActive: true,
      });
    }
    
    // Pickup location
    if (pickupLocation) {
      markers.push({
        coordinate: pickupLocation,
        title: 'Pickup Location',
        type: 'pickup',
        customIcon: true,
        isActive: true,
      });
    }
    
    // Delivery location
    if (deliveryLocation) {
      markers.push({
        coordinate: deliveryLocation,
        title: 'Delivery Location',
        type: 'delivery',
        customIcon: true,
        isActive: true,
      });
    }
    
    // Driver location
    if (driverLocation) {
      markers.push({
        coordinate: driverLocation,
        title: 'Driver',
        type: 'driver',
        customIcon: true,
        isActive: true,
      });
    }
    
    return markers;
  };

  const getPolylines = () => {
    const polylines = [];
    
    if (route?.coordinates) {
      polylines.push({
        coordinates: route.coordinates,
        color: '#007AFF',
        width: 4,
      });
    }
    
    if (driverLocation && pickupLocation) {
      polylines.push({
        coordinates: [driverLocation, pickupLocation],
        color: '#FF9800',
        width: 2,
        dashed: true,
      });
    }
    
    return polylines;
  };

  const getCircles = () => {
    const circles = [];
    
    // Delivery radius circle (5km)
    if (pickupLocation) {
      circles.push({
        center: pickupLocation,
        radius: 5000, // 5km in meters
        fillColor: 'rgba(76, 175, 80, 0.05)',
        strokeColor: 'rgba(76, 175, 80, 0.3)',
        strokeWidth: 1,
      });
    }
    
    return circles;
  };

  return (
    <View style={[styles.container, style]}>
      <OSMMap
        ref={mapRef}
        initialRegion={region}
        markers={getMarkers()}
        polylines={getPolylines()}
        circles={getCircles()}
        onMarkerPress={handleMarkerPress}
        onRegionChange={setRegion}
        zoomEnabled={interactive}
        scrollEnabled={interactive}
        style={styles.map}
      />
      
      {/* Controls */}
      <View style={styles.controls}>
        <TouchableOpacity 
          style={styles.controlButton}
          onPress={handleCenterUser}
        >
          <Ionicons name="locate" size={24} color="#007AFF" />
        </TouchableOpacity>
        
        <TouchableOpacity 
          style={styles.controlButton}
          onPress={handleFitRoute}
          disabled={!pickupLocation || !deliveryLocation}
        >
          <Ionicons 
            name="expand" 
            size={24} 
            color={pickupLocation && deliveryLocation ? "#007AFF" : "#CCC"} 
          />
        </TouchableOpacity>
      </View>
      
      {/* ETA Display */}
      {eta && (
        <View style={styles.etaContainer}>
          <Text style={styles.etaText}>
            ETA: {eta.formattedDuration} ({eta.formattedDistance})
          </Text>
          <Text style={styles.etaSubText}>
            Arrival by {eta.arrivalTime.formatted}
          </Text>
        </View>
      )}
      
      {/* Selected Location Card */}
      {selectedMarker && (
        <LocationCard
          location={selectedMarker}
          onClose={() => setSelectedMarker(null)}
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  map: {
    flex: 1,
  },
  controls: {
    position: 'absolute',
    top: 20,
    right: 20,
    gap: 10,
  },
  controlButton: {
    backgroundColor: 'white',
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  etaContainer: {
    position: 'absolute',
    bottom: 20,
    left: 20,
    right: 20,
    backgroundColor: 'white',
    padding: 16,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  etaText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
  etaSubText: {
    fontSize: 14,
    color: '#666',
    marginTop: 4,
  },
});

export default PickupDeliveryMap;