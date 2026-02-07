import * as Location from 'expo-location';
import { Platform, Alert, Linking } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Only import TaskManager on native platforms
let TaskManager;
if (Platform.OS !== 'web') {
  try {
    TaskManager = require('expo-task-manager');
  } catch (error) {
    console.warn('expo-task-manager not available:', error.message);
  }
}

const LAST_KNOWN_LOCATION_KEY = 'washbox-last-known-location';

// Define background task only on native platforms
if (Platform.OS !== 'web' && TaskManager) {
  const LOCATION_TASK_NAME = 'washbox-location-tracking';
  
  TaskManager.defineTask(LOCATION_TASK_NAME, async ({ data, error }) => {
    if (error) {
      console.error('Location tracking error:', error);
      return;
    }
    
    if (data && data.locations && data.locations.length > 0) {
      const location = data.locations[0];
      const locationData = {
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
        accuracy: location.coords.accuracy,
        timestamp: location.timestamp,
      };
      
      await AsyncStorage.setItem(LAST_KNOWN_LOCATION_KEY, JSON.stringify(locationData));
      console.log('Background location update:', locationData);
    }
  });
}

// Web geolocation API wrapper
const getWebLocation = () => {
  return new Promise((resolve, reject) => {
    if (typeof navigator === 'undefined' || !navigator.geolocation) {
      reject(new Error('Geolocation not supported'));
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
          altitude: position.coords.altitude,
          speed: position.coords.speed,
          heading: position.coords.heading,
          timestamp: position.timestamp,
          source: 'web',
        });
      },
      (error) => {
        reject(error);
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 30000,
      }
    );
  });
};

export const LocationService = {
  // ========== PERMISSIONS ==========
  async requestPermissions() {
    try {
      // Web permissions
      if (Platform.OS === 'web') {
        return new Promise((resolve) => {
          // Web browsers handle permissions via browser prompt
          // We'll assume granted if no error occurs
          resolve({ 
            foreground: 'granted', 
            background: 'undetermined' 
          });
        });
      }

      // Native permissions
      const { status: foregroundStatus } = await Location.requestForegroundPermissionsAsync();
      
      if (foregroundStatus !== 'granted') {
        throw new Error('Location permission denied');
      }

      let backgroundStatus = 'undetermined';
      try {
        const { status } = await Location.requestBackgroundPermissionsAsync();
        backgroundStatus = status;
      } catch (error) {
        console.warn('Background permission not available:', error.message);
      }

      return { 
        foreground: foregroundStatus, 
        background: backgroundStatus 
      };
    } catch (error) {
      console.error('Permission error:', error);
      throw error;
    }
  },

  // ========== LOCATION GETTERS ==========
  async getCurrentLocation(options = {}) {
    try {
      // Web implementation
      if (Platform.OS === 'web') {
        return await getWebLocation();
      }

      // Native implementation
      const { status } = await Location.getForegroundPermissionsAsync();
      
      if (status !== 'granted') {
        await this.requestPermissions();
      }

      const location = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.Balanced,
        timeout: 15000,
        maximumAge: 30000,
        ...options,
      });

      const locationData = {
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
        accuracy: location.coords.accuracy,
        altitude: location.coords.altitude,
        speed: location.coords.speed,
        heading: location.coords.heading,
        timestamp: location.timestamp,
        source: 'native',
      };

      await this.storeLocation(locationData);
      return locationData;
      
    } catch (error) {
      console.error('Location error:', error.message);
      
      // Try to get last known location
      const lastLocation = await this.getLastKnownLocation();
      if (lastLocation) {
        return { ...lastLocation, source: 'cached', isFallback: true };
      }
      
      // Return default location
      return await this.getDefaultLocation();
    }
  },

  async getLastKnownLocation() {
    try {
      const stored = await AsyncStorage.getItem(LAST_KNOWN_LOCATION_KEY);
      return stored ? JSON.parse(stored) : null;
    } catch (_error) {
      // ignore parse/storage errors
      return null;
    }
  },

  async storeLocation(location) {
    try {
      await AsyncStorage.setItem(LAST_KNOWN_LOCATION_KEY, JSON.stringify(location));
    } catch (error) {
      console.error('Storage error:', error);
    }
  },

  async getDefaultLocation() {
    return {
      latitude: 14.5995, // Manila, Philippines
      longitude: 120.9842,
      accuracy: 10000,
      timestamp: Date.now(),
      source: 'default',
      isFallback: true,
    };
  },

  // ========== LOCATION TRACKING (Native only) ==========
  async startLocationTracking(orderId) {
    if (Platform.OS === 'web' || !TaskManager) {
      console.warn('Background location tracking not available on web');
      return false;
    }

    try {
      await Location.startLocationUpdatesAsync('washbox-location-tracking', {
        accuracy: Location.Accuracy.High,
        timeInterval: 10000,
        distanceInterval: 10,
        foregroundService: {
          notificationTitle: 'WASHBOX Delivery Tracking',
          notificationBody: 'Your delivery is being tracked',
          notificationColor: '#007AFF',
        },
        pausesUpdatesAutomatically: false,
      });
      return true;
    } catch (error) {
      console.error('Tracking error:', error);
      return false;
    }
  },

  async stopLocationTracking() {
    if (Platform.OS === 'web' || !TaskManager) return false;
    
    try {
      await Location.stopLocationUpdatesAsync('washbox-location-tracking');
      return true;
    } catch (error) {
      console.error('Stop tracking error:', error);
      return false;
    }
  },

  // ========== GEOCODING & SEARCH ==========
  async searchLocations(query, options = {}) {
    if (!query || query.trim().length < 3) {
      return [];
    }

    // Simple abbreviation expansion map
    const expansions = {
      'dr': 'doctor',
      'drv': 'drive',
      'st': 'street',
      'st.': 'street',
      'rd': 'road',
      'ave': 'avenue',
      'blvd': 'boulevard',
      'hwy': 'highway',
      'bldg': 'building',
      'pl': 'place',
      'mt': 'mount',
      'brgy': 'barangay',
    };

    const normalizeAndExpand = (s) => {
      // Remove common punctuation (but keep internal hyphens), collapse whitespace
      const cleaned = s.trim().replace(/[.,]/g, '').replace(/\s+/g, ' ');
      return cleaned.split(' ').map(tok => expansions[tok.toLowerCase()] || tok).join(' ');
    };

    const dropHouseNumber = (s) => s.replace(/^\d+\s*/,'').trim();
    const removeTrailingCityToken = (s) => s.replace(/\bcity\.?$/i, '').trim();

    const normalized = normalizeAndExpand(query);

    // Build viewbox bias if center provided or last-known location exists
    let viewboxParams = '';
    const center = options.center || (await this.getLastKnownLocation());
    if (center && center.latitude && center.longitude) {
      const delta = options.viewboxDelta || 0.08; // ~9km
      const minLat = center.latitude - delta;
      const minLon = center.longitude - delta;
      const maxLat = center.latitude + delta;
      const maxLon = center.longitude + delta;
      viewboxParams = `&viewbox=${minLon},${minLat},${maxLon},${maxLat}&bounded=1`;
    }

    const limit = options.limit || 10;

    const attemptSearch = async (q, extraParams = '') => {
      const params = new URLSearchParams({
        format: 'json',
        q: q,
        limit,
        addressdetails: 1,
        'accept-language': 'en',
        countrycodes: options.countryCodes || 'ph',
      });

      const url = `https://nominatim.openstreetmap.org/search?${params.toString()}${extraParams || viewboxParams}`;
      console.debug('Nominatim search URL:', url);

      try {
        const resp = await fetch(url, {
          headers: { 'User-Agent': 'WASHBOX Mobile App/1.0.0' },
          signal: options.signal,
        });

        if (!resp.ok) {
          console.debug('Nominatim responded with status:', resp.status);
          return [];
        }

        const data = await resp.json();
        if (!data || !Array.isArray(data) || data.length === 0) {
          console.debug('Nominatim empty response for query:', q, data);
          return [];
        }

        return data;
      } catch (err) {
        if (err.name === 'AbortError') {
          // Request was cancelled
          console.debug('Nominatim request aborted for query:', q);
          throw err;
        }
        console.error('Nominatim fetch error:', err);
        return [];
      }
    };

    // Progressive fallback queries
    const attempts = [
      normalized,
      dropHouseNumber(normalized),
      removeTrailingCityToken(dropHouseNumber(normalized)),
    ];

    let results = [];
    for (const q of attempts) {
      if (!q || q.length < 2) continue;
      results = await attemptSearch(q);
      if (results.length) break;
    }

    // Additional fallbacks
    if (!results.length) {
      // Try last 2 tokens (likely street + city)
      const tokens = normalized.split(' ');
      if (tokens.length >= 2) {
        const lastTwo = tokens.slice(-2).join(' ');
        results = await attemptSearch(lastTwo);
      }
    }

    if (!results.length) {
      // As a last resort, try without country code and without bounding box (wider search)
      const params = new URLSearchParams({
        format: 'json',
        q: dropHouseNumber(query.trim()),
        limit,
        addressdetails: 1,
        'accept-language': 'en',
      });
      const url = `https://nominatim.openstreetmap.org/search?${params.toString()}`;
      console.debug('Nominatim fallback search URL:', url);

      try {
        const resp = await fetch(url, { headers: { 'User-Agent': 'WASHBOX Mobile App/1.0.0' }, signal: options.signal });
        if (resp.ok) {
          const data = await resp.json();
          if (data && Array.isArray(data) && data.length) {
            results = data;
          } else {
            console.debug('Fallback empty response:', data);
          }
        }
      } catch (err) {
        if (err.name === 'AbortError') {
          console.debug('Nominatim fallback request aborted');
          throw err;
        }
        console.error('Nominatim fallback fetch error:', err);
      }
    }

    // If still empty, log and return empty
    if (!results || results.length === 0) {
      console.debug('All Nominatim attempts returned no results for original query:', query);

      // Photon fallback
      try {
        const photonParams = new URLSearchParams({
          q: query.trim(),
          limit,
          lang: 'en',
        });
        if (center && center.latitude && center.longitude) {
          photonParams.set('lat', center.latitude);
          photonParams.set('lon', center.longitude);
        }
        const photonUrl = `https://photon.komoot.io/api/?${photonParams.toString()}`;
        console.debug('Photon fallback URL:', photonUrl);

        const photonResp = await fetch(photonUrl, { headers: { 'User-Agent': 'WASHBOX Mobile App/1.0.0' }, signal: options.signal });
        if (photonResp.ok) {
          const photonData = await photonResp.json();
          if (photonData && Array.isArray(photonData.features) && photonData.features.length) {
            results = photonData.features.map(f => ({
              place_id: f.properties.osm_id || (f.properties.osm_key + '_' + f.properties.osm_id),
              display_name: [f.properties.name, f.properties.street, f.properties.city, f.properties.state, f.properties.country].filter(Boolean).join(', '),
              lat: f.geometry.coordinates[1],
              lon: f.geometry.coordinates[0],
              address: {
                house_number: f.properties.housenumber,
                road: f.properties.street,
                city: f.properties.city || f.properties.town || f.properties.village,
                state: f.properties.state,
                country: f.properties.country,
              },
              type: f.properties.osm_value || f.properties.type || 'place',
            }));
          } else {
            console.debug('Photon returned no features for query:', query, photonData);
          }
        } else {
          console.debug('Photon responded with status:', photonResp.status);
        }
      } catch (err) {
        console.error('Photon fetch error:', err);
      }
    }

    if (!results || results.length === 0) {
      console.debug('All geocoding attempts (Nominatim + Photon) returned no results for original query:', query);
      return [];
    }

    // Map results to expected format
    return results.map(item => ({
        id: item.place_id,
        name: item.display_name,
        coordinate: {
          latitude: parseFloat(item.lat),
          longitude: parseFloat(item.lon),
        },
        address: {
          house_number: item.address?.house_number,
          road: item.address?.road,
          city: item.address?.city || item.address?.town || item.address?.village,
          state: item.address?.state,
          country: item.address?.country,
        },
        type: item.type,
      }));
  },

  async reverseGeocode(coordinate) {
    const params = new URLSearchParams({
      format: 'json',
      lat: coordinate.latitude,
      lon: coordinate.longitude,
      zoom: 18,
      addressdetails: 1,
      'accept-language': 'en',
    });

    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?${params.toString()}`,
        {
          headers: {
            'User-Agent': 'WASHBOX Mobile App/1.0.0',
          },
        }
      );

      if (!response.ok) throw new Error('Reverse geocode failed');

      const data = await response.json();
      
      return {
        address: data.display_name,
        components: data.address,
        coordinate: {
          latitude: parseFloat(data.lat),
          longitude: parseFloat(data.lon),
        },
      };
    } catch (error) {
      console.error('Reverse geocode error:', error);
      throw error;
    }
  },

  async getAddressFromCoordinate(coordinate) {
    try {
      const result = await this.reverseGeocode(coordinate);
      return result.address;
    } catch (error) {
      console.error('Address error:', error);
      return `${coordinate.latitude.toFixed(4)}, ${coordinate.longitude.toFixed(4)}`;
    }
  },

  // ========== DISTANCE CALCULATIONS ==========
  calculateDistance(coord1, coord2) {
    const R = 6371e3; // Earth radius in meters
    const φ1 = coord1.latitude * Math.PI / 180;
    const φ2 = coord2.latitude * Math.PI / 180;
    const Δφ = (coord2.latitude - coord1.latitude) * Math.PI / 180;
    const Δλ = (coord2.longitude - coord1.longitude) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
  },

  formatDistance(meters) {
    if (meters < 1000) {
      return `${Math.round(meters)} m`;
    }
    return `${(meters / 1000).toFixed(1)} km`;
  },

  isWithinDeliveryRadius(userLocation, pickupLocation, radiusMeters = 5000) {
    const distance = this.calculateDistance(userLocation, pickupLocation);
    return {
      isWithinRadius: distance <= radiusMeters,
      distance,
      formattedDistance: this.formatDistance(distance),
    };
  },

  // ========== MISC UTILITIES ==========
  async openLocationSettings() {
    if (Platform.OS === 'ios') {
      await Linking.openURL('app-settings:');
    } else if (Platform.OS === 'android') {
      await Linking.openSettings();
    } else {
      // Web - show instructions
      Alert.alert(
        'Location Settings',
        'Please enable location permissions in your browser settings.',
        [{ text: 'OK' }]
      );
    }
  },
};