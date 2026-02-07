
import React, { useEffect, useRef, useState } from 'react';
import { View, StyleSheet } from 'react-native';

// Leaflet is loaded via CDN in the HTML
let L = null;
if (typeof window !== 'undefined') {
  // Use existing Leaflet if already loaded, otherwise set up loading
  if (window.L) {
    L = window.L;
  } else {
    L = require('leaflet');
    require('leaflet/dist/leaflet.css');
    
    // Store globally for consistency
    window.L = L;
    
    // Fix for default marker icons in Leaflet
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
      iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/dist/images/marker-icon-2x.png',
      iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/dist/images/marker-icon.png',
      shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/dist/images/marker-shadow.png',
    });
  }
}

const MapView = React.forwardRef(({
  style,
  initialRegion,
  region,
  onRegionChangeComplete,
  children,
  showsUserLocation = false,
  ...props
}, ref) => {
  const mapRef = useRef(null);
  const mapInstance = useRef(null);
  const markersRef = useRef([]);
  const layersRef = useRef([]);
  const [isMapReady, setIsMapReady] = useState(false);

  // Initialize map
  useEffect(() => {
    if (mapRef.current && L && !mapInstance.current) {
      // Initialize Leaflet map
      const center = initialRegion || region || { latitude: 14.5995, longitude: 120.9842 };
      
      mapInstance.current = L.map(mapRef.current).setView(
        [center.latitude, center.longitude],
        13
      );

      // Store globally for child components with a ready flag
      window.leafletMap = mapInstance.current;
      window.isLeafletMapReady = true;

      // Add OSM tiles (base layer)
      const baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
      }).addTo(mapInstance.current);
      
      layersRef.current.push(baseLayer);

      // Handle map movement
      mapInstance.current.on('moveend', () => {
        if (onRegionChangeComplete && mapInstance.current) {
          const center = mapInstance.current.getCenter();
          const bounds = mapInstance.current.getBounds();
          
          onRegionChangeComplete({
            latitude: center.lat,
            longitude: center.lng,
            latitudeDelta: Math.abs(bounds.getNorth() - bounds.getSouth()),
            longitudeDelta: Math.abs(bounds.getEast() - bounds.getWest()),
          });
        }
      });

      // Mark map as ready
      setTimeout(() => setIsMapReady(true), 100);

      // Clean up on unmount
      return () => {
        markersRef.current.forEach(marker => {
          if (marker && mapInstance.current) {
            mapInstance.current.removeLayer(marker);
          }
        });
        markersRef.current = [];
        
        layersRef.current.forEach(layer => {
          if (layer && mapInstance.current) {
            mapInstance.current.removeLayer(layer);
          }
        });
        layersRef.current = [];
      };
    }

    return () => {
      if (mapInstance.current) {
        mapInstance.current.remove();
        mapInstance.current = null;
        window.leafletMap = null;
        window.isLeafletMapReady = false;
      }
    };
  }, []);

  // Update region
  useEffect(() => {
    if (mapInstance.current && region) {
      mapInstance.current.setView([region.latitude, region.longitude], 13);
    }
  }, [region]);

  // Forward ref
  React.useImperativeHandle(ref, () => ({
    animateToRegion: (region, duration = 500) => {
      if (mapInstance.current) {
        mapInstance.current.setView([region.latitude, region.longitude], 13, {
          animate: true,
          duration: duration / 1000,
        });
      }
    },
    getMap: () => mapInstance.current,
    isReady: () => isMapReady && !!mapInstance.current,
  }));

  // Enhanced child rendering with retry logic
  const renderChildren = () => {
    if (!isMapReady) {
      return null; // Don't render children until map is ready
    }

    return React.Children.map(children, child => {
      if (!child) return null;
      
      return React.cloneElement(child, { 
        mapInstance: mapInstance.current,
        markersRef,
        layersRef,
        isMapReady,
        retryCount: 0 // Add retry capability
      });
    });
  };

  return (
    <View ref={mapRef} style={[styles.container, style]}>
      {!isMapReady && (
        <div style={styles.loadingOverlay}>
          <div style={styles.loadingText}>Loading map...</div>
        </div>
      )}
      {renderChildren()}
    </View>
  );
});

// Enhanced child components with retry logic
const createMapComponent = (Component, setupFn) => {
  return React.forwardRef((props, ref) => {
    const {
      mapInstance,
      markersRef,
      layersRef,
      isMapReady,
      retryCount = 0,
      ...otherProps
    } = props;
    
    const internalRef = useRef(null);
    const [isComponentReady, setIsComponentReady] = useState(false);
    const maxRetries = 10; // Maximum retry attempts

    useEffect(() => {
      if (!isMapReady && retryCount >= maxRetries) return;

      let mounted = true;
      let retryAttempt = 0;

      const initComponent = () => {
        if (!mounted) return;

        // Get map instance from props or global
        const map = mapInstance || window.leafletMap;
        
        if (!map || !L) {
          // Retry if not ready
          if (retryAttempt < maxRetries) {
            retryAttempt++;
            setTimeout(initComponent, 100 * retryAttempt);
          }
          return;
        }

        try {
          // Call the component-specific setup function
          const cleanup = setupFn({
            map,
            L,
            props: otherProps,
            internalRef,
            markersRef,
            layersRef,
          });

          if (mounted) {
            setIsComponentReady(true);
          }

          // Return cleanup function
          return cleanup;
        } catch (error) {
          console.error('Map component initialization error:', error);
          // Retry on error
          if (retryAttempt < maxRetries) {
            retryAttempt++;
            setTimeout(initComponent, 200 * retryAttempt);
          }
        }
      };

      const cleanup = initComponent();

      return () => {
        mounted = false;
        if (cleanup) cleanup();
      };
    }, [isMapReady, retryCount, JSON.stringify(otherProps)]);

    React.useImperativeHandle(ref, () => ({
      isReady: () => isComponentReady,
      getElement: () => internalRef.current,
    }));

    // Render nothing for map overlay components
    return null;
  });
};

// Enhanced Marker component with retry logic
const Marker = createMapComponent('Marker', ({ map, L, props, internalRef, markersRef }) => {
  const { coordinate, title, onPress } = props;
  
  if (!coordinate || !coordinate.latitude || !coordinate.longitude) {
    return null;
  }

  // Remove existing marker
  if (internalRef.current) {
    map.removeLayer(internalRef.current);
    markersRef.current = markersRef.current.filter(m => m !== internalRef.current);
  }

  // Create new marker
  const marker = L.marker([coordinate.latitude, coordinate.longitude])
    .addTo(map)
    .bindPopup(title || 'Location');
  
  if (onPress) {
    marker.on('click', () => onPress({ coordinate }));
  }
  
  internalRef.current = marker;
  markersRef.current.push(marker);

  // Return cleanup function
  return () => {
    if (internalRef.current) {
      map.removeLayer(internalRef.current);
      markersRef.current = markersRef.current.filter(m => m !== internalRef.current);
    }
  };
});

// Enhanced Polyline component
const Polyline = createMapComponent('Polyline', ({ map, L, props, internalRef, layersRef }) => {
  const { coordinates, strokeColor, strokeWidth } = props;
  
  if (!coordinates || coordinates.length === 0) {
    return null;
  }

  // Remove existing polyline
  if (internalRef.current) {
    map.removeLayer(internalRef.current);
    layersRef.current = layersRef.current.filter(l => l !== internalRef.current);
  }

  // Create latLng array
  const latLngs = coordinates.map(coord => [coord.latitude, coord.longitude]);
  
  // Add new polyline
  const polyline = L.polyline(latLngs, {
    color: strokeColor || '#007AFF',
    weight: strokeWidth || 4,
    opacity: 0.7,
  }).addTo(map);
  
  internalRef.current = polyline;
  layersRef.current.push(polyline);

  return () => {
    if (internalRef.current) {
      map.removeLayer(internalRef.current);
      layersRef.current = layersRef.current.filter(l => l !== internalRef.current);
    }
  };
});

// Enhanced Circle component
const Circle = createMapComponent('Circle', ({ map, L, props, internalRef, layersRef }) => {
  const { center, radius, fillColor, strokeColor, strokeWidth } = props;
  
  if (!center) return null;

  // Remove existing circle
  if (internalRef.current) {
    map.removeLayer(internalRef.current);
    layersRef.current = layersRef.current.filter(l => l !== internalRef.current);
  }

  // Add new circle
  const circle = L.circle([center.latitude, center.longitude], {
    radius: radius || 1000,
    fillColor: fillColor || 'rgba(0, 122, 255, 0.1)',
    color: strokeColor || 'rgba(0, 122, 255, 0.5)',
    weight: strokeWidth || 2,
    fillOpacity: 0.2,
  }).addTo(map);
  
  internalRef.current = circle;
  layersRef.current.push(circle);

  return () => {
    if (internalRef.current) {
      map.removeLayer(internalRef.current);
      layersRef.current = layersRef.current.filter(l => l !== internalRef.current);
    }
  };
});

// Enhanced UrlTile component
const UrlTile = createMapComponent('UrlTile', ({ map, L, props, internalRef, layersRef }) => {
  const { urlTemplate, maximumZ } = props;
  
  if (!urlTemplate) return null;

  // Remove existing tile layer
  if (internalRef.current) {
    map.removeLayer(internalRef.current);
    layersRef.current = layersRef.current.filter(l => l !== internalRef.current);
  }

  // Add new tile layer
  const tileLayer = L.tileLayer(urlTemplate, {
    maxZoom: maximumZ || 19,
  }).addTo(map);
  
  internalRef.current = tileLayer;
  layersRef.current.push(tileLayer);

  return () => {
    if (internalRef.current) {
      map.removeLayer(internalRef.current);
      layersRef.current = layersRef.current.filter(l => l !== internalRef.current);
    }
  };
});

const Callout = ({ children }) => null; // Not implemented for web

const PROVIDER_DEFAULT = 'default';

const styles = StyleSheet.create({
  container: {
    width: '100%',
    height: '100%',
    minHeight: 300,
    position: 'relative',
  },
  loadingOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(255, 255, 255, 0.8)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 1000,
  },
  loadingText: {
    fontSize: 16,
    color: '#666',
  },
});

export {
  MapView as default,
  Marker,
  UrlTile,
  Polyline,
  Circle,
  Callout,
  PROVIDER_DEFAULT,
};