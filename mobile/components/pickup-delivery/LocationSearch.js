import React, { useState, useEffect } from 'react';
import {
  View,
  TextInput,
  FlatList,
  TouchableOpacity,
  Text,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LocationService } from '../../services/locationService';

const LocationSearch = ({
  placeholder = "Search address...",
  onLocationSelect,
  initialValue = "",
  currentLocationButton = true,
}) => {
  const [query, setQuery] = useState(initialValue);
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showResults, setShowResults] = useState(false);

  // Abort controller + stale result guard
  const abortControllerRef = React.useRef(null);
  const searchIdRef = React.useRef(0);

  // Debounced search
  useEffect(() => {
    const timer = setTimeout(() => {
      if (query.trim().length >= 3) {
        // Abort previous request
        if (abortControllerRef.current) {
          abortControllerRef.current.abort();
        }

        const controller = new AbortController();
        abortControllerRef.current = controller;
        const currentSearchId = ++searchIdRef.current;

        performSearch(query, controller.signal, currentSearchId);
      } else {
        // Clear results and cancel any in-flight search
        if (abortControllerRef.current) {
          abortControllerRef.current.abort();
          abortControllerRef.current = null;
        }
        setResults([]);
      }
    }, 400);

    return () => clearTimeout(timer);
  }, [query]);

  const performSearch = async (searchQuery, signal, searchId) => {
    if (!searchQuery.trim()) return;

    setLoading(true);
    try {
      const searchResults = await LocationService.searchLocations(searchQuery, {
        limit: 8,
        signal,
      });

      // Ignore stale results
      if (searchId !== searchIdRef.current) return;

      setResults(searchResults);
      setShowResults(searchResults && searchResults.length > 0);
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error('Search error:', error);
    } finally {
      if (searchId === searchIdRef.current) setLoading(false);
    }
  };

  const handleSelectLocation = (location) => {
    setQuery(location.name);
    setShowResults(false);
    onLocationSelect?.(location);
  };

  const handleUseCurrentLocation = async () => {
    setLoading(true);
    try {
      const location = await LocationService.getCurrentLocation();
      const address = await LocationService.getAddressFromCoordinate(location);
      
      const selectedLocation = {
        id: 'current',
        coordinate: location,
        name: address,
        address: { road: address },
        type: 'current',
      };
      
      // Show current location as first suggestion
      setResults(prev => [selectedLocation, ...(prev || [])]);
      setShowResults(true);
      setQuery(address);
      onLocationSelect?.(selectedLocation);
    } catch (error) {
      console.error('Error getting current location:', error);
    } finally {
      setLoading(false);
    }
  };

  const renderResultItem = ({ item }) => (
    <TouchableOpacity
      style={styles.resultItem}
      onPress={() => handleSelectLocation(item)}
    >
      <Ionicons 
        name="location-outline" 
        size={20} 
        color="#666" 
        style={styles.resultIcon}
      />
      <View style={styles.resultTextContainer}>
        <Text style={styles.resultTitle} numberOfLines={1}>
          {item.address?.road || item.name.split(',')[0]}
        </Text>
        <Text style={styles.resultSubtitle} numberOfLines={2}>
          {item.name}
        </Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <View style={styles.searchContainer}>
        <Ionicons name="search" size={20} color="#666" style={styles.searchIcon} />
        <TextInput
          style={styles.input}
          placeholder={placeholder}
          value={query}
          onChangeText={setQuery}
          onFocus={() => query.length >= 3 && setShowResults(true)}
          placeholderTextColor="#999"
        />
        {loading && (
          <ActivityIndicator size="small" color="#007AFF" style={styles.loading} />
        )}
        {query.length > 0 && (
          <TouchableOpacity onPress={() => setQuery('')}>
            <Ionicons name="close-circle" size={20} color="#999" />
          </TouchableOpacity>
        )}
      </View>

      {currentLocationButton && (
        <TouchableOpacity
          style={styles.currentLocationButton}
          onPress={handleUseCurrentLocation}
          disabled={loading}
        >
          <Ionicons name="locate-outline" size={18} color="#007AFF" />
          <Text style={styles.currentLocationText}>Use Current Location</Text>
        </TouchableOpacity>
      )}

      {showResults && results.length > 0 && (
        <View style={styles.resultsContainer}>
          <FlatList
            data={results}
            renderItem={renderResultItem}
            keyExtractor={(item) => item.id.toString()}
            keyboardShouldPersistTaps="handled"
            style={styles.resultsList}
          />
        </View>
      )}

      {showResults && results.length === 0 && query.length >= 3 && !loading && (
        <View style={styles.noResults}>
          <Text style={styles.noResultsText}>No locations found</Text>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    position: 'relative',
    zIndex: 1000,
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'white',
    borderRadius: 10,
    paddingHorizontal: 15,
    paddingVertical: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  searchIcon: {
    marginRight: 10,
  },
  input: {
    flex: 1,
    fontSize: 16,
    color: '#333',
  },
  loading: {
    marginHorizontal: 10,
  },
  currentLocationButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'white',
    padding: 12,
    borderRadius: 10,
    marginTop: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  currentLocationText: {
    marginLeft: 10,
    fontSize: 14,
    color: '#007AFF',
  },
  resultsContainer: {
    position: 'absolute',
    top: '100%',
    left: 0,
    right: 0,
    backgroundColor: 'white',
    borderRadius: 10,
    marginTop: 5,
    maxHeight: 300,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 5 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 5,
    zIndex: 1001,
  },
  resultsList: {
    borderRadius: 10,
  },
  resultItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 15,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  resultIcon: {
    marginRight: 12,
  },
  resultTextContainer: {
    flex: 1,
  },
  resultTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    marginBottom: 2,
  },
  resultSubtitle: {
    fontSize: 12,
    color: '#666',
    lineHeight: 16,
  },
  noResults: {
    position: 'absolute',
    top: '100%',
    left: 0,
    right: 0,
    backgroundColor: 'white',
    padding: 20,
    borderRadius: 10,
    marginTop: 5,
    alignItems: 'center',
  },
  noResultsText: {
    color: '#666',
    fontSize: 14,
  },
});

export default LocationSearch;