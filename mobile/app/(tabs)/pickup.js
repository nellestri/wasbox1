import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  TextInput,
  Alert,
  ActivityIndicator,
  Platform,
  Modal,
  Dimensions,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Picker } from '@react-native-picker/picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';

// Import your map components
import PickupDeliveryMap from '../../components/pickup-delivery/PickupDelivery';
import LocationSearch from '../../components/pickup-delivery/LocationSearch';
import { LocationService } from '../../services/locationService';
import { useLocationStore } from '../../store/locationStore';

const { width } = Dimensions.get('window');

const COLORS = {
  background: '#0A1128',
  cardDark: '#1A2847',
  cardBlue: '#0EA5E9',
  primary: '#0EA5E9',
  textPrimary: '#FFFFFF',
  textSecondary: '#94A3B8',
  success: '#10B981',
};

export default function PickupRequestScreen() {
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  // Map & Location State
  const [showMapModal, setShowMapModal] = useState(false);
  const [locationMode, setLocationMode] = useState('pickup'); // 'pickup' or 'delivery'
  const [isLoadingLocation, setIsLoadingLocation] = useState(false);
  const [selectedLocation, setSelectedLocation] = useState(null);
  const [mapRegion, setMapRegion] = useState(null);
  const [showLocationSearch, setShowLocationSearch] = useState(false);

  // Form data
  const [branches, setBranches] = useState([]);
  const [selectedBranch, setSelectedBranch] = useState('');
  const [pickupAddress, setPickupAddress] = useState('');
  const [pickupCoordinates, setPickupCoordinates] = useState(null); // New: store coordinates
  const [deliveryAddress, setDeliveryAddress] = useState(''); // New: separate delivery address
  const [deliveryCoordinates, setDeliveryCoordinates] = useState(null); // New: delivery coordinates
  const [pickupDate, setPickupDate] = useState(new Date());
  const [pickupTime, setPickupTime] = useState('09:00');
  const [phoneNumber, setPhoneNumber] = useState('');
  const [notes, setNotes] = useState('');

  // Date/Time picker state
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [showTimePicker, setShowTimePicker] = useState(false);

  // Get from store
  const locationStore = useLocationStore();

  useEffect(() => {
    fetchInitialData();
  }, []);

  const fetchInitialData = async () => {
    try {
      setLoading(true);

      // Initialize location first
      await initializeLocation();

      // Then fetch branches and customer data
      await Promise.all([
        fetchBranches(),
        fetchCustomerData(),
      ]);
    } catch (error) {
      console.error('Error fetching initial data:', error);
      Alert.alert('Error', 'Failed to load data. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const initializeLocation = async () => {
    try {
      setIsLoadingLocation(true);
      const location = await LocationService.getCurrentLocation();
      
      setMapRegion({
        ...location,
        latitudeDelta: 0.05,
        longitudeDelta: 0.05,
      });

      // Store in location store
      locationStore.setUserLocation(location);
      
    } catch (error) {
      console.error('Error initializing location:', error);
      // Default to Manila
      setMapRegion({
        latitude: 14.5995,
        longitude: 120.9842,
        latitudeDelta: 0.1,
        longitudeDelta: 0.1,
      });
    } finally {
      setIsLoadingLocation(false);
    }
  };

  const fetchBranches = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/v1/branches`, {
        headers: { 'Accept': 'application/json' },
      });

      if (response.ok) {
        const data = await response.json();
        console.log('Branches response:', data);

        let branchesArray = [];
        if (data.success && data.data && data.data.branches) {
          branchesArray = data.data.branches;
        } else if (Array.isArray(data.data)) {
          branchesArray = data.data;
        } else if (Array.isArray(data)) {
          branchesArray = data;
        }

        setBranches(branchesArray);

        if (branchesArray.length > 0) {
          setSelectedBranch(branchesArray[0].id.toString());
        }
      }
    } catch (error) {
      console.error('Error fetching branches:', error);
      setBranches([]);
    }
  };

  const fetchCustomerData = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/user`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.customer) {
          const customer = data.data.customer;

          // Pre-fill customer data
          if (customer.address) {
            setPickupAddress(customer.address);
            // Optionally geocode address to get coordinates
          }
          if (customer.phone) {
            setPhoneNumber(customer.phone);
          }
        }
      }
    } catch (error) {
      console.error('Error fetching customer data:', error);
    }
  };

  // Map Functions
  const openMapForLocation = (mode) => {
    setLocationMode(mode);
    setShowMapModal(true);
  };

  const handleLocationSelect = async (location) => {
    try {
      if (!location || !location.coordinate) return;

      const address = await LocationService.getAddressFromCoordinate(location.coordinate);
      
      if (locationMode === 'pickup') {
        setPickupAddress(address);
        setPickupCoordinates(location.coordinate);
      } else {
        setDeliveryAddress(address);
        setDeliveryCoordinates(location.coordinate);
      }

      setSelectedLocation({
        ...location,
        address: address,
      });

      setShowMapModal(false);
      setShowLocationSearch(false);
      
      Alert.alert(
        'Location Selected',
        `Address: ${address}\n\nCoordinates: ${location.coordinate.latitude.toFixed(6)}, ${location.coordinate.longitude.toFixed(6)}`,
        [{ text: 'OK' }]
      );

    } catch (error) {
      console.error('Error getting address:', error);
      Alert.alert('Error', 'Could not get address for selected location');
    }
  };

  const handleUseCurrentLocation = async (mode) => {
    try {
      setIsLoadingLocation(true);
      const location = await LocationService.getCurrentLocation();
      const address = await LocationService.getAddressFromCoordinate(location);
      
      const locationData = {
        coordinate: location,
        name: 'Current Location',
        address: address,
      };
      
      handleLocationSelect(locationData);
      
    } catch (error) {
      Alert.alert('Error', 'Unable to get your current location');
    } finally {
      setIsLoadingLocation(false);
    }
  };

  const calculateDistance = () => {
    if (!pickupCoordinates || !deliveryCoordinates) return null;
    
    const distance = LocationService.calculateDistance(pickupCoordinates, deliveryCoordinates);
    return LocationService.formatDistance(distance);
  };

  const validateForm = () => {
    if (!selectedBranch) {
      Alert.alert('Validation Error', 'Please select a branch');
      return false;
    }
    if (!pickupAddress.trim()) {
      Alert.alert('Validation Error', 'Please select a pickup location');
      return false;
    }
    if (!deliveryAddress.trim()) {
      Alert.alert('Validation Error', 'Please select a delivery location');
      return false;
    }
    if (!phoneNumber.trim()) {
      Alert.alert('Validation Error', 'Please enter your phone number');
      return false;
    }
    
    // Check distance between pickup and delivery
    if (pickupCoordinates && deliveryCoordinates) {
      const distance = LocationService.calculateDistance(pickupCoordinates, deliveryCoordinates);
      if (distance > 50000) { // 50km max
        Alert.alert(
          'Distance Warning',
          `The distance between pickup and delivery is ${LocationService.formatDistance(distance)}. Maximum allowed distance is 50km.`,
          [{ text: 'OK' }]
        );
        return false;
      }
    }
    
    return true;
  };

  const handleSubmit = async () => {
    if (!validateForm()) return;

    try {
      setSubmitting(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);

      if (!token) {
        Alert.alert('Error', 'Please login to continue');
        router.replace('/(auth)/login');
        return;
      }

      const response = await fetch(`${API_BASE_URL}/v1/pickups`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          branch_id: parseInt(selectedBranch),
          pickup_address: pickupAddress,
          delivery_address: deliveryAddress, // New field
          latitude: pickupCoordinates?.latitude || 0,
          longitude: pickupCoordinates?.longitude || 0,
          delivery_latitude: deliveryCoordinates?.latitude || 0, // New field
          delivery_longitude: deliveryCoordinates?.longitude || 0, // New field
          preferred_date: pickupDate.toISOString().split('T')[0],
          preferred_time: pickupTime,
          phone_number: phoneNumber,
          notes: notes,
          estimated_weight: null,
          service_id: null,
          distance: calculateDistance(), // Optional
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        Alert.alert(
          'Success',
          'Pickup request submitted successfully! We will confirm your request shortly.',
          [
            {
              text: 'OK',
              onPress: () => {
                // Reset form
                setPickupAddress('');
                setDeliveryAddress('');
                setPickupCoordinates(null);
                setDeliveryCoordinates(null);
                setNotes('');
                router.push('/(tabs)/');
              },
            },
          ]
        );
      } else {
        Alert.alert('Error', data.message || 'Failed to submit pickup request');
      }
    } catch (error) {
      console.error('Error submitting pickup request:', error);
      Alert.alert('Error', 'Failed to submit pickup request. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
          <Ionicons name="arrow-back" size={24} color={COLORS.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Pickup Request</Text>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        {/* Info Card */}
        <View style={styles.infoCard}>
          <Ionicons name="car-outline" size={32} color={COLORS.primary} />
          <Text style={styles.infoTitle}>Free Pickup & Delivery</Text>
          <Text style={styles.infoText}>
            Select pickup and delivery locations on the map for accurate service!
          </Text>
        </View>

        {/* Location Selection Card */}
        <View style={styles.locationCard}>
          <Text style={styles.sectionTitle}>Select Locations</Text>
          
          {/* Pickup Location */}
          <View style={styles.locationInputGroup}>
            <Text style={styles.label}>
              Pickup Location *
              {pickupCoordinates && (
                <Text style={styles.coordinateText}>
                  {` (${pickupCoordinates.latitude.toFixed(4)}, ${pickupCoordinates.longitude.toFixed(4)})`}
                </Text>
              )}
            </Text>
            <TouchableOpacity
              style={styles.locationButton}
              onPress={() => openMapForLocation('pickup')}
            >
              <Ionicons name="location-outline" size={20} color={COLORS.primary} />
              <View style={styles.locationButtonTextContainer}>
                {pickupAddress ? (
                  <Text style={styles.locationSelectedText} numberOfLines={1}>
                    {pickupAddress}
                  </Text>
                ) : (
                  <Text style={styles.locationPlaceholder}>
                    Tap to select pickup location
                  </Text>
                )}
              </View>
              <Ionicons name="chevron-forward" size={20} color={COLORS.textSecondary} />
            </TouchableOpacity>
          </View>

          {/* Delivery Location */}
          <View style={styles.locationInputGroup}>
            <Text style={styles.label}>
              Delivery Location *
              {deliveryCoordinates && (
                <Text style={styles.coordinateText}>
                  {` (${deliveryCoordinates.latitude.toFixed(4)}, ${deliveryCoordinates.longitude.toFixed(4)})`}
                </Text>
              )}
            </Text>
            <TouchableOpacity
              style={styles.locationButton}
              onPress={() => openMapForLocation('delivery')}
            >
              <Ionicons name="home-outline" size={20} color={COLORS.primary} />
              <View style={styles.locationButtonTextContainer}>
                {deliveryAddress ? (
                  <Text style={styles.locationSelectedText} numberOfLines={1}>
                    {deliveryAddress}
                  </Text>
                ) : (
                  <Text style={styles.locationPlaceholder}>
                    Tap to select delivery location
                  </Text>
                )}
              </View>
              <Ionicons name="chevron-forward" size={20} color={COLORS.textSecondary} />
            </TouchableOpacity>
          </View>

          {/* Distance Display */}
          {pickupCoordinates && deliveryCoordinates && (
            <View style={styles.distanceContainer}>
              <Ionicons name="map-outline" size={16} color={COLORS.success} />
              <Text style={styles.distanceText}>
                Distance: {calculateDistance()}
              </Text>
            </View>
          )}
        </View>

        {/* Form Section */}
        <View style={styles.formSection}>
          {/* Branch Selection */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Select Branch</Text>
            <View style={styles.pickerContainer}>
              <Picker
                selectedValue={selectedBranch}
                onValueChange={(value) => setSelectedBranch(value)}
                style={styles.picker}
                dropdownIconColor={COLORS.primary}
              >
                {branches && branches.length > 0 ? (
                  branches.map((branch) => (
                    <Picker.Item
                      key={branch.id}
                      label={`${branch.name} - ${branch.city || ''}`}
                      value={branch.id.toString()}
                      color="#fff"
                    />
                  ))
                ) : (
                  <Picker.Item label="No branches available" value="" color="#fff" />
                )}
              </Picker>
            </View>
          </View>

          {/* Phone Number */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Phone Number *</Text>
            <TextInput
              style={styles.input}
              placeholder="e.g., 0917-123-4567"
              placeholderTextColor={COLORS.textSecondary}
              value={phoneNumber}
              onChangeText={setPhoneNumber}
              keyboardType="phone-pad"
            />
          </View>

          {/* Pickup Date */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Pickup Date</Text>
            <TouchableOpacity
              style={styles.dateButton}
              onPress={() => setShowDatePicker(true)}
            >
              <Ionicons name="calendar-outline" size={20} color={COLORS.primary} />
              <Text style={styles.dateButtonText}>
                {pickupDate.toLocaleDateString('en-US', {
                  weekday: 'short',
                  month: 'short',
                  day: 'numeric',
                  year: 'numeric',
                })}
              </Text>
            </TouchableOpacity>
          </View>

          {/* Pickup Time */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Preferred Time</Text>
            <TouchableOpacity
              style={styles.dateButton}
              onPress={() => setShowTimePicker(true)}
            >
              <Ionicons name="time-outline" size={20} color={COLORS.primary} />
              <Text style={styles.dateButtonText}>{pickupTime}</Text>
            </TouchableOpacity>
          </View>

          {/* Notes */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Additional Notes (Optional)</Text>
            <TextInput
              style={styles.textInput}
              placeholder="Any special instructions..."
              placeholderTextColor={COLORS.textSecondary}
              value={notes}
              onChangeText={setNotes}
              multiline
              numberOfLines={3}
            />
          </View>
        </View>

        {/* Submit Button */}
        <View style={styles.buttonSection}>
          <TouchableOpacity
            style={[styles.submitButton, submitting && styles.submitButtonDisabled]}
            onPress={handleSubmit}
            disabled={submitting}
          >
            {submitting ? (
              <ActivityIndicator size="small" color="#FFF" />
            ) : (
              <>
                <Ionicons name="checkmark-circle-outline" size={20} color="#FFF" />
                <Text style={styles.submitButtonText}>Request Pickup</Text>
              </>
            )}
          </TouchableOpacity>
        </View>

        <View style={{ height: 100 }} />
      </ScrollView>

      {/* Map Modal */}
      <Modal
        visible={showMapModal}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setShowMapModal(false)}
      >
        <View style={styles.modalContainer}>
          {/* Modal Header */}
          <View style={styles.modalHeader}>
            <TouchableOpacity
              onPress={() => setShowMapModal(false)}
              style={styles.modalCloseButton}
            >
              <Ionicons name="close" size={24} color={COLORS.textPrimary} />
            </TouchableOpacity>
            <Text style={styles.modalTitle}>
              Select {locationMode === 'pickup' ? 'Pickup' : 'Delivery'} Location
            </Text>
            <TouchableOpacity
              style={styles.useCurrentButton}
              onPress={() => handleUseCurrentLocation(locationMode)}
              disabled={isLoadingLocation}
            >
              <Ionicons name="locate-outline" size={18} color={COLORS.primary} />
              <Text style={styles.useCurrentText}>Current</Text>
            </TouchableOpacity>
          </View>

          {/* Search Bar */}
          <View style={styles.searchContainer}>
            <LocationSearch
              placeholder={`Search ${locationMode} address...`}
              onLocationSelect={handleLocationSelect}
              currentLocationButton={false}
            />
          </View>

          {/* Map */}
          {mapRegion && (
            <PickupDeliveryMap
              pickupLocation={locationMode === 'pickup' ? null : pickupCoordinates}
              deliveryLocation={locationMode === 'delivery' ? null : deliveryCoordinates}
              onLocationSelect={(marker) => {
                if (marker.coordinate) {
                  handleLocationSelect({
                    coordinate: marker.coordinate,
                    name: marker.title,
                  });
                }
              }}
              style={styles.map}
            />
          )}

          {/* Confirm Button */}
          <View style={styles.modalFooter}>
            <TouchableOpacity
              style={styles.confirmLocationButton}
              onPress={() => {
                if (selectedLocation) {
                  handleLocationSelect(selectedLocation);
                } else {
                  Alert.alert('Select Location', 'Please select a location on the map');
                }
              }}
            >
              <Text style={styles.confirmLocationText}>
                Confirm {locationMode === 'pickup' ? 'Pickup' : 'Delivery'} Location
              </Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* Date Picker */}
      {showDatePicker && (
        <DateTimePicker
          value={pickupDate}
          mode="date"
          display={Platform.OS === 'ios' ? 'spinner' : 'default'}
          onChange={handleDateChange}
          minimumDate={new Date()}
        />
      )}

      {/* Time Picker */}
      {showTimePicker && (
        <DateTimePicker
          value={new Date(`2000-01-01T${pickupTime}`)}
          mode="time"
          display={Platform.OS === 'ios' ? 'spinner' : 'default'}
          onChange={handleTimeChange}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  centerContent: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: COLORS.textSecondary,
    marginTop: 12,
    fontSize: 14,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 20,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: COLORS.cardDark,
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
  },
  scrollView: {
    flex: 1,
  },
  infoCard: {
    backgroundColor: COLORS.cardBlue,
    marginHorizontal: 20,
    marginBottom: 24,
    borderRadius: 20,
    padding: 24,
    alignItems: 'center',
  },
  infoTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFF',
    marginTop: 12,
    marginBottom: 8,
  },
  infoText: {
    fontSize: 14,
    color: '#FFF',
    textAlign: 'center',
    opacity: 0.9,
  },
  locationCard: {
    backgroundColor: COLORS.cardDark,
    marginHorizontal: 20,
    marginBottom: 24,
    borderRadius: 20,
    padding: 20,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
    marginBottom: 16,
  },
  locationInputGroup: {
    marginBottom: 16,
  },
  locationButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(14, 165, 233, 0.1)',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.primary,
    gap: 12,
  },
  locationButtonTextContainer: {
    flex: 1,
  },
  locationSelectedText: {
    color: COLORS.textPrimary,
    fontSize: 14,
    fontWeight: '500',
  },
  locationPlaceholder: {
    color: COLORS.textSecondary,
    fontSize: 14,
  },
  coordinateText: {
    color: COLORS.success,
    fontSize: 12,
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
  },
  distanceContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255, 255, 255, 0.1)',
    gap: 8,
  },
  distanceText: {
    color: COLORS.success,
    fontSize: 14,
    fontWeight: '500',
  },
  formSection: {
    paddingHorizontal: 20,
  },
  inputGroup: {
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.textPrimary,
    marginBottom: 8,
  },
  input: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 12,
    padding: 16,
    color: COLORS.textPrimary,
    fontSize: 14,
  },
  textInput: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 12,
    padding: 16,
    color: COLORS.textPrimary,
    fontSize: 14,
    minHeight: 80,
    textAlignVertical: 'top',
  },
  pickerContainer: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 12,
    overflow: 'hidden',
  },
  picker: {
    color: COLORS.textPrimary,
    backgroundColor: COLORS.cardDark,
  },
  dateButton: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 12,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  dateButtonText: {
    color: COLORS.textPrimary,
    fontSize: 14,
    fontWeight: '500',
  },
  buttonSection: {
    paddingHorizontal: 20,
    marginTop: 24,
  },
  submitButton: {
    backgroundColor: COLORS.primary,
    borderRadius: 12,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  submitButtonDisabled: {
    opacity: 0.6,
  },
  submitButtonText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  // Modal Styles
  modalContainer: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255, 255, 255, 0.1)',
  },
  modalCloseButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: COLORS.cardDark,
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
    flex: 1,
    textAlign: 'center',
    marginHorizontal: 12,
  },
  useCurrentButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: 'rgba(14, 165, 233, 0.1)',
    borderRadius: 8,
    gap: 4,
  },
  useCurrentText: {
    color: COLORS.primary,
    fontSize: 14,
    fontWeight: '500',
  },
  searchContainer: {
    paddingHorizontal: 20,
    paddingVertical: 16,
    backgroundColor: COLORS.background,
  },
  map: {
    flex: 1,
  },
  modalFooter: {
    padding: 20,
    backgroundColor: COLORS.background,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255, 255, 255, 0.1)',
  },
  confirmLocationButton: {
    backgroundColor: COLORS.primary,
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
  },
  confirmLocationText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
});