import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Alert,
  Animated,
  Dimensions,
  StatusBar,
  Platform,
  Linking,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';
import FloatingBubbles from '../../components/FloatingBubbles';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

const COLORS = {
  background: '#0A0E27',
  backgroundLight: '#131937',
  cardDark: '#1C2340',
  cardLight: '#252D4C',
  primary: '#0EA5E9',
  primaryDark: '#0284C7',
  primaryLight: '#38BDF8',
  secondary: '#8B5CF6',
  accent: '#F59E0B',
  textPrimary: '#FFFFFF',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  success: '#10B981',
  info: '#3B82F6',
  warning: '#F59E0B',
  danger: '#EF4444',
  purple: '#8B5CF6',
  pink: '#EC4899',
  cyan: '#06B6D4',
  border: '#1E293B',
  gradientPrimary: ['#0EA5E9', '#3B82F6'],
  gradientSecondary: ['#8B5CF6', '#EC4899'],
  gradientSuccess: ['#10B981', '#059669'],
  gradientWarning: ['#F59E0B', '#EF4444'],
};

export default function HomeScreen() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [fadeAnim] = useState(new Animated.Value(0));
  const [slideAnim] = useState(new Animated.Value(50));
  
  const [promotions, setPromotions] = useState([]);
  const [activeOrders, setActiveOrders] = useState([]);
  const [branches, setBranches] = useState([]);
  const [customer, setCustomer] = useState(null);
  const [unreadCount, setUnreadCount] = useState(0);
  const [stats, setStats] = useState({
    totalOrders: 0,
    activeOrders: 0,
    totalSpent: 0,
  });

  useEffect(() => {
    fetchData();
    
    const notificationInterval = setInterval(() => {
      fetchUnreadCount();
    }, 30000);

    return () => clearInterval(notificationInterval);
  }, []);

  useEffect(() => {
    if (!loading) {
      Animated.parallel([
        Animated.timing(fadeAnim, {
          toValue: 1,
          duration: 600,
          useNativeDriver: true,
        }),
        Animated.spring(slideAnim, {
          toValue: 0,
          tension: 50,
          friction: 7,
          useNativeDriver: true,
        }),
      ]).start();
    }
  }, [loading]);

  const fetchData = async () => {
    try {
      setLoading(true);

      const customerData = await AsyncStorage.getItem(STORAGE_KEYS.CUSTOMER);
      if (customerData) {
        setCustomer(JSON.parse(customerData));
      }

      await Promise.all([
        fetchPromotions(),
        fetchActiveOrders(),
        fetchBranches(),
        fetchUnreadCount(),
        fetchCustomerStats(),
      ]);
    } catch (error) {
      console.error('Error fetching data:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  const fetchUnreadCount = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) {
        setUnreadCount(0);
        return;
      }

      const response = await fetch(`${API_BASE_URL}/v1/notifications/unread-count`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data) {
          setUnreadCount(data.data.unread_count || 0);
        }
      }
    } catch (error) {
      console.error('Error fetching unread count:', error);
    }
  };

  const fetchCustomerStats = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/customer/stats`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.stats) {
          setStats(data.data.stats);
        }
      }
    } catch (error) {
      setStats({ totalOrders: 0, activeOrders: 0, totalSpent: 0 });
    }
  };

  const fetchPromotions = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/v1/promotions/featured`, {
        headers: { 'Accept': 'application/json' },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.promotions) {
          setPromotions(data.data.promotions);
        }
      }
    } catch (error) {
      console.error('Error fetching promotions:', error);
      setPromotions([]);
    }
  };

  const fetchActiveOrders = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) {
        setActiveOrders([]);
        return;
      }

      const response = await fetch(`${API_BASE_URL}/v1/customer/active-orders`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.orders) {
          setActiveOrders(data.data.orders);
        }
      } else if (response.status === 401) {
        await AsyncStorage.removeItem(STORAGE_KEYS.TOKEN);
        await AsyncStorage.removeItem(STORAGE_KEYS.CUSTOMER);
        setCustomer(null);
        setActiveOrders([]);
      }
    } catch (error) {
      console.error('Error fetching orders:', error);
      setActiveOrders([]);
    }
  };

  const fetchBranches = async () => {
    try {
      console.log('Fetching branches from:', `${API_BASE_URL}/v1/branches`);
      
      const response = await fetch(`${API_BASE_URL}/v1/branches`, {
        headers: { 
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
      });

      console.log('Response status:', response.status);
      
      if (response.ok) {
        const data = await response.json();
        console.log('Branches API Response:', JSON.stringify(data, null, 2));
        
        if (data.success && data.data && Array.isArray(data.data.branches)) {
          console.log('Found branches:', data.data.branches.length);
          // Process branches - parse operating_hours if needed
          const processedBranches = data.data.branches.map(branch => {
            let operatingHours = branch.operating_hours;
            
            // Parse operating_hours if it's a string
            if (typeof operatingHours === 'string') {
              try {
                operatingHours = JSON.parse(operatingHours);
              } catch (e) {
                console.warn('Failed to parse operating_hours for branch:', branch.id);
                operatingHours = {};
              }
            }
            
            return {
              ...branch,
              province: branch.province || 'Negros Oriental',
              operating_hours: operatingHours,
              // Add a formatted address if not present
              formatted_address: branch.address 
                ? `${branch.address}, ${branch.city}, ${branch.province || 'Negros Oriental'}`
                : `${branch.city}, ${branch.province || 'Negros Oriental'}`
            };
          });
          
          setBranches(processedBranches);
        } else {
          console.error('Unexpected API response structure:', data);
          setBranches([]);
        }
      } else {
        console.error('Branches API error status:', response.status);
        setBranches([]);
      }
    } catch (error) {
      console.error('Error fetching branches:', error);
      setBranches([]);
    }
  };

  // Helper function to get mock branches for testing
  const getMockBranches = () => {
    return [
      {
        id: 1,
        name: 'WashBox Sibulan',
        code: 'SBL',
        address: 'National Highway, Poblacion',
        city: 'Sibulan',
        province: 'Negros Oriental',
        phone: '09171234567',
        email: 'sibulan@washbox.com',
        latitude: 9.35000000,
        longitude: 123.28330000,
        operating_hours: {
          Monday: { open: "08:00", close: "18:00" },
          Tuesday: { open: "08:00", close: "18:00" },
          Wednesday: { open: "08:00", close: "18:00" },
          Thursday: { open: "08:00", close: "18:00" },
          Friday: { open: "08:00", close: "18:00" },
          Saturday: { open: "08:00", close: "17:00" },
          Sunday: { open: "09:00", close: "17:00" }
        },
        is_open: true,
        is_active: true,
        formatted_address: 'National Highway, Poblacion, Sibulan, Negros Oriental'
      },
      {
        id: 2,
        name: 'WashBox Dumaguete',
        code: 'DGT',
        address: 'Calindagan Road',
        city: 'Dumaguete',
        province: 'Negros Oriental',
        phone: '09172345678',
        email: 'dumaguete@washbox.com',
        latitude: 9.30500000,
        longitude: 123.30600000,
        operating_hours: {
          Monday: { open: "07:00", close: "20:00" },
          Tuesday: { open: "07:00", close: "20:00" },
          Wednesday: { open: "07:00", close: "20:00" },
          Thursday: { open: "07:00", close: "20:00" },
          Friday: { open: "07:00", close: "20:00" },
          Saturday: { open: "08:00", close: "19:00" },
          Sunday: { open: "09:00", close: "18:00" }
        },
        is_open: true,
        is_active: true,
        formatted_address: 'Calindagan Road, Dumaguete, Negros Oriental'
      },
      {
        id: 3,
        name: 'WashBox Bais',
        code: 'BAI',
        address: 'Bais City Plaza',
        city: 'Bais',
        province: 'Negros Oriental',
        phone: '09173456789',
        email: 'bais@washbox.com',
        latitude: 9.59000000,
        longitude: 123.12000000,
        operating_hours: {
          Monday: { open: "09:00", close: "17:00" },
          Tuesday: { open: "09:00", close: "17:00" },
          Wednesday: { open: "09:00", close: "17:00" },
          Thursday: { open: "09:00", close: "17:00" },
          Friday: { open: "09:00", close: "17:00" },
          Saturday: { open: "09:00", close: "16:00" },
          Sunday: { closed: true }
        },
        is_open: false,
        is_active: true,
        formatted_address: 'Bais City Plaza, Bais, Negros Oriental'
      }
    ];
  };

  const getStatusColor = (status) => {
    const statusColors = {
      'received': COLORS.info,
      'processing': COLORS.warning,
      'ready': COLORS.success,
      'paid': COLORS.success,
      'completed': COLORS.success,
    };
    return statusColors[status?.toLowerCase()] || COLORS.info;
  };

  const getStatusGradient = (status) => {
    const gradients = {
      'received': COLORS.gradientPrimary,
      'processing': COLORS.gradientWarning,
      'ready': COLORS.gradientSuccess,
      'paid': COLORS.gradientSuccess,
      'completed': COLORS.gradientSuccess,
    };
    return gradients[status?.toLowerCase()] || COLORS.gradientPrimary;
  };

  const formatPrice = (price) => {
    return `₱${parseFloat(price).toLocaleString('en-PH', { 
      minimumFractionDigits: 0,
      maximumFractionDigits: 0 
    })}`;
  };

  const handleBranchPress = (branch) => {
    // Format operating hours
    let hoursText = '';
    if (branch.operating_hours && typeof branch.operating_hours === 'object') {
      hoursText = 'Operating Hours:\n';
      const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
      days.forEach(day => {
        if (branch.operating_hours[day]) {
          const dayHours = branch.operating_hours[day];
          if (dayHours.closed) {
            hoursText += `${day}: Closed\n`;
          } else {
            hoursText += `${day}: ${dayHours.open || '08:00'} - ${dayHours.close || '18:00'}\n`;
          }
        } else {
          hoursText += `${day}: 08:00 - 18:00\n`;
        }
      });
    } else {
      hoursText = 'Operating Hours: Standard Business Hours\n';
    }

    Alert.alert(
      `${branch.name} (${branch.code})`,
      `📍 ${branch.address || branch.city}\n${branch.city}, ${branch.province || 'Negros Oriental'}\n\n` +
      `📞 ${branch.phone || 'N/A'}\n` +
      `✉️ ${branch.email || 'N/A'}\n\n` +
      `${hoursText}\n` +
      `Status: ${branch.is_active ? (branch.is_open ? '🟢 Open Now' : '🔴 Closed') : '⛔ Inactive'}`,
      [
        { text: 'Close', style: 'cancel' },
        branch.phone ? { 
          text: 'Call Branch', 
          onPress: () => {
            const phoneNumber = branch.phone.replace(/\D/g, '');
            Linking.openURL(`tel:${phoneNumber}`);
          }
        } : null,
        branch.email ? {
          text: 'Email',
          onPress: () => Linking.openURL(`mailto:${branch.email}`)
        } : null,
        branch.latitude && branch.longitude ? {
          text: 'Open in Maps',
          onPress: () => Linking.openURL(`https://www.google.com/maps?q=${branch.latitude},${branch.longitude}`)
        } : null,
      ].filter(Boolean)
    );
  };

  // Quick Service Categories
  const serviceCategories = [
    { 
      icon: 'water', 
      label: 'Wash & Fold', 
      color: COLORS.primary,
      gradient: COLORS.gradientPrimary,
      action: () => router.push('/services/wash-fold')
    },
    { 
      icon: 'shirt', 
      label: 'Dry Clean', 
      color: COLORS.purple,
      gradient: COLORS.gradientSecondary,
      action: () => router.push('/services/dry-clean')
    },
    { 
      icon: 'flash', 
      label: 'Express', 
      color: COLORS.warning,
      gradient: COLORS.gradientWarning,
      action: () => router.push('/services/express')
    },
    { 
      icon: 'car', 
      label: 'Pickup', 
      color: COLORS.success,
      gradient: COLORS.gradientSuccess,
      action: () => router.push('/(tabs)/pickup')
    },
  ];

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />

      {/* 🫧 Floating Bubbles Background */}
      <FloatingBubbles count={12} />

      {/* Floating Header */}
      <View style={styles.floatingHeader}>
        <View style={styles.headerLeft}>
          <LinearGradient
            colors={COLORS.gradientPrimary}
            style={styles.logoContainer}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          >
            <Ionicons name="water" size={24} color={COLORS.textPrimary} />
          </LinearGradient>
          <View>
            <Text style={styles.logoText}>WASHBOX</Text>
            {customer && (
              <Text style={styles.welcomeText}>
                Hi, {customer.name?.split(' ')[0]}! 👋
              </Text>
            )}
          </View>
        </View>
        
        <TouchableOpacity 
          style={styles.notificationButton}
          onPress={() => router.push('/notifications')}
        >
          <View style={styles.notificationIconContainer}>
            <Ionicons name="notifications" size={22} color={COLORS.textPrimary} />
            {unreadCount > 0 && (
              <View style={styles.notificationBadge}>
                <Text style={styles.notificationBadgeText}>
                  {unreadCount > 9 ? '9+' : unreadCount}
                </Text>
              </View>
            )}
          </View>
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scrollView}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl 
            refreshing={refreshing} 
            onRefresh={onRefresh} 
            tintColor={COLORS.primary}
            colors={[COLORS.primary]}
          />
        }
        contentContainerStyle={styles.scrollContent}
      >
        <Animated.View style={{ 
          opacity: fadeAnim,
          transform: [{ translateY: slideAnim }]
        }}>
          {/* Hero Section with Stats */}
          <LinearGradient
            colors={['rgba(14, 165, 233, 0.15)', 'rgba(59, 130, 246, 0.05)']}
            style={styles.heroSection}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          >
            <View style={styles.heroContent}>
              <Text style={styles.heroTitle}>
                Freshly Cleaned,{'\n'}
                <Text style={styles.heroTitleHighlight}>Done Right</Text>
              </Text>
              <Text style={styles.heroSubtitle}>
                Professional laundry service at your doorstep
              </Text>

              {/* Quick Stats */}
              <View style={styles.statsContainer}>
                <View style={styles.statCard}>
                  <Text style={styles.statValue}>{stats.totalOrders}</Text>
                  <Text style={styles.statLabel}>Total Orders</Text>
                </View>
                <View style={styles.statDivider} />
                <View style={styles.statCard}>
                  <Text style={styles.statValue}>{stats.activeOrders}</Text>
                  <Text style={styles.statLabel}>Active</Text>
                </View>
                <View style={styles.statDivider} />
                <View style={styles.statCard}>
                  <Text style={styles.statValue}>{formatPrice(stats.totalSpent)}</Text>
                  <Text style={styles.statLabel}>Total Spent</Text>
                </View>
              </View>
            </View>
          </LinearGradient>

          {/* Service Categories */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Our Services</Text>
            <View style={styles.categoriesGrid}>
              {serviceCategories.map((category, index) => (
                <TouchableOpacity
                  key={index}
                  style={styles.categoryCard}
                  onPress={category.action}
                  activeOpacity={0.8}
                >
                  <LinearGradient
                    colors={category.gradient}
                    style={styles.categoryGradient}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 1 }}
                  >
                    <Ionicons name={category.icon} size={24} color={COLORS.textPrimary} />
                  </LinearGradient>
                  <Text style={styles.categoryLabel}>{category.label}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

         {promotions.length > 0 && (
  <View style={styles.section}>
    <View style={styles.sectionHeader}>
      <Text style={styles.sectionTitle}>Special Offers</Text>
      <TouchableOpacity onPress={() => router.push('/promotions')}>
        <Text style={styles.sectionLink}>View All →</Text>
      </TouchableOpacity>
    </View>
              
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.promoScrollContent}
                snapToInterval={SCREEN_WIDTH - 60}
                decelerationRate="fast"
              >
                {promotions.map((promo, index) => (
                  <TouchableOpacity 
                    key={promo.id} 
                    style={[
                      styles.promoCard,
                      index === 0 && styles.promoCardFirst,
                    ]}
                    activeOpacity={0.9}
                  >
                    <LinearGradient
                      colors={index % 2 === 0 ? COLORS.gradientPrimary : COLORS.gradientSecondary}
                      style={styles.promoGradient}
                      start={{ x: 0, y: 0 }}
                      end={{ x: 1, y: 1 }}
                    >
                      {/* Promo Badge */}
                      <View style={styles.promoBadge}>
                        <Ionicons name="star" size={12} color={COLORS.warning} />
                        <Text style={styles.promoBadgeText}>
                          {promo.is_active ? 'ACTIVE' : 'LIMITED'}
                        </Text>
                      </View>

                      {/* Promo Content */}
                      <View style={styles.promoContent}>
                        <Text style={styles.promoTitle}>
                          {promo.poster_title || promo.name}
                        </Text>
                        <Text style={styles.promoSubtitle} numberOfLines={2}>
                          {promo.poster_subtitle || promo.description}
                        </Text>

                        {promo.display_price && (
                          <View style={styles.promoPriceContainer}>
                            <Text style={styles.promoPrice}>₱{promo.display_price}</Text>
                            {promo.original_price && (
                              <Text style={styles.promoOriginalPrice}>
                                ₱{promo.original_price}
                              </Text>
                            )}
                          </View>
                        )}

                        {promo.poster_features && promo.poster_features.length > 0 && (
                          <View style={styles.promoFeatures}>
                            {promo.poster_features.slice(0, 2).map((feature, idx) => (
                              <View key={idx} style={styles.featureRow}>
                                <Ionicons name="checkmark-circle" size={14} color="rgba(255,255,255,0.9)" />
                                <Text style={styles.featureText}>{feature}</Text>
                              </View>
                            ))}
                          </View>
                        )}
                      </View>

                      {/* Arrow */}
                      <View style={styles.promoArrow}>
                        <Ionicons name="arrow-forward" size={20} color="rgba(255,255,255,0.8)" />
                      </View>
                    </LinearGradient>
                  </TouchableOpacity>
                ))}
              </ScrollView>
            </View>
          )}

          {/* Active Orders */}
          {activeOrders.length > 0 && (
            <View style={styles.section}>
              <View style={styles.sectionHeader}>
                <Text style={styles.sectionTitle}>Active Orders</Text>
                <TouchableOpacity onPress={() => router.push('/(tabs)/order')}>
                  <Text style={styles.sectionLink}>View All →</Text>
                </TouchableOpacity>
              </View>

              {activeOrders.slice(0, 3).map((order) => (
                <TouchableOpacity
                  key={order.id}
                  style={styles.orderCard}
                  onPress={() => router.push(`/orders/${order.tracking_number}`)}
                  activeOpacity={0.8}
                >
                  <LinearGradient
                    colors={['rgba(28, 35, 64, 0.8)', 'rgba(28, 35, 64, 0.4)']}
                    style={styles.orderGradient}
                  >
                    {/* Status Indicator */}
                    <View style={styles.orderStatusBar}>
                      <LinearGradient
                        colors={getStatusGradient(order.status)}
                        style={styles.orderStatusGradient}
                        start={{ x: 0, y: 0 }}
                        end={{ x: 0, y: 1 }}
                      />
                    </View>

                    <View style={styles.orderContent}>
                      {/* Header */}
                      <View style={styles.orderHeader}>
                        <View style={styles.orderIcon}>
                          <LinearGradient
                            colors={getStatusGradient(order.status)}
                            style={styles.orderIconGradient}
                          >
                            <Ionicons name="receipt" size={20} color={COLORS.textPrimary} />
                          </LinearGradient>
                        </View>
                        
                        <View style={styles.orderHeaderContent}>
                          <Text style={styles.orderNumber}>{order.tracking_number}</Text>
                          <Text style={styles.orderService} numberOfLines={1}>
                            {order.service_name} • {order.branch_name}
                          </Text>
                        </View>

                        <View style={[styles.orderStatusBadge, { backgroundColor: getStatusColor(order.status) + '20' }]}>
                          <Text style={[styles.orderStatusText, { color: getStatusColor(order.status) }]}>
                            {order.status?.toUpperCase()}
                          </Text>
                        </View>
                      </View>

                      {/* Footer */}
                      <View style={styles.orderFooter}>
                        <View style={styles.orderTimeContainer}>
                          <Ionicons name="time-outline" size={14} color={COLORS.textMuted} />
                          <Text style={styles.orderTime}>
                            {order.estimated_completion || 'Processing'}
                          </Text>
                        </View>
                        <View style={styles.orderPriceContainer}>
                          <Text style={styles.orderPrice}>{formatPrice(order.total_amount || 0)}</Text>
                          <Ionicons name="chevron-forward" size={18} color={COLORS.textMuted} />
                        </View>
                      </View>
                    </View>
                  </LinearGradient>
                </TouchableOpacity>
              ))}
            </View>
          )}

          {/* Empty State for Orders */}
          {activeOrders.length === 0 && customer && (
            <View style={styles.section}>
              <TouchableOpacity
                style={styles.emptyStateCard}
                onPress={() => router.push('/(tabs)/pickup')}
                activeOpacity={0.9}
              >
                <LinearGradient
                  colors={['rgba(14, 165, 233, 0.1)', 'rgba(59, 130, 246, 0.05)']}
                  style={styles.emptyStateGradient}
                >
                  <View style={styles.emptyStateIcon}>
                    <Ionicons name="basket-outline" size={40} color={COLORS.primary} />
                  </View>
                  <Text style={styles.emptyStateTitle}>No Active Orders</Text>
                  <Text style={styles.emptyStateText}>
                    Start your first order and experience our premium service
                  </Text>
                  <View style={styles.emptyStateButton}>
                    <LinearGradient
                      colors={COLORS.gradientPrimary}
                      style={styles.emptyStateButtonGradient}
                      start={{ x: 0, y: 0 }}
                      end={{ x: 1, y: 0 }}
                    >
                      <Ionicons name="add-circle" size={20} color={COLORS.textPrimary} />
                      <Text style={styles.emptyStateButtonText}>Request Pickup</Text>
                    </LinearGradient>
                  </View>
                </LinearGradient>
              </TouchableOpacity>
            </View>
          )}

          {/* Branches */}
          {branches.length > 0 && (
            <View style={styles.section}>
              <View style={styles.sectionHeader}>
                <Text style={styles.sectionTitle}>Our Locations</Text>
                <TouchableOpacity>
                  <Text style={styles.sectionLink}>View Map →</Text>
                </TouchableOpacity>
              </View>
              
              <ScrollView 
                horizontal 
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.branchScrollContent}
              >
                {branches.map((branch, index) => (
                  <TouchableOpacity 
                    key={branch.id} 
                    style={[
                      styles.branchCard,
                      index === 0 && styles.branchCardFirst,
                    ]}
                    onPress={() => handleBranchPress(branch)}
                    activeOpacity={0.8}
                  >
                    <LinearGradient
                      colors={branch.is_active ? 
                        (branch.is_open ? ['rgba(16, 185, 129, 0.6)', 'rgba(5, 150, 105, 0.3)'] : ['rgba(28, 35, 64, 0.6)', 'rgba(28, 35, 64, 0.3)']) :
                        ['rgba(239, 68, 68, 0.2)', 'rgba(239, 68, 68, 0.1)']}
                      style={styles.branchGradient}
                    >
                      {/* Branch Icon */}
                      <View style={styles.branchIconContainer}>
                        <LinearGradient
                          colors={branch.is_active ? 
                            (branch.is_open ? COLORS.gradientSuccess : COLORS.gradientPrimary) :
                            COLORS.gradientWarning}
                          style={styles.branchIconGradient}
                        >
                          <Ionicons 
                            name={branch.is_active ? (branch.is_open ? "business" : "time") : "close-circle"} 
                            size={24} 
                            color={COLORS.textPrimary} 
                          />
                        </LinearGradient>
                      </View>
                      
                      {/* Branch Code */}
                      {branch.code && (
                        <View style={[styles.branchCodeBadge, 
                          !branch.is_active && { backgroundColor: COLORS.danger + '30' }
                        ]}>
                          <Text style={[styles.branchCodeText,
                            !branch.is_active && { color: COLORS.danger }
                          ]}>
                            {branch.code}
                          </Text>
                        </View>
                      )}
                      
                      {/* Branch Info */}
                      <Text style={styles.branchName} numberOfLines={1}>
                        {branch.name}
                      </Text>
                      <Text style={styles.branchCity} numberOfLines={1}>
                        📍 {branch.city}
                      </Text>
                      
                      {/* Status */}
                      <View style={styles.branchStatus}>
                        <View style={[
                          styles.branchStatusDot,
                          { 
                            backgroundColor: branch.is_active ? 
                              (branch.is_open ? COLORS.success : COLORS.warning) : 
                              COLORS.danger 
                          }
                        ]} />
                        <Text style={[
                          styles.branchStatusText,
                          { 
                            color: branch.is_active ? 
                              (branch.is_open ? COLORS.success : COLORS.warning) : 
                              COLORS.danger 
                          }
                        ]}>
                          {branch.is_active ? (branch.is_open ? 'Open Now' : 'Closed') : 'Inactive'}
                        </Text>
                      </View>
                    </LinearGradient>
                  </TouchableOpacity>
                ))}
              </ScrollView>
            </View>
          )}

          {/* Why Choose Us */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Why Choose WashBox?</Text>
            <View style={styles.featuresGrid}>
              <View style={styles.featureCard}>
                <View style={[styles.featureIcon, { backgroundColor: COLORS.primary + '20' }]}>
                  <Ionicons name="flash" size={24} color={COLORS.primary} />
                </View>
                <Text style={styles.featureCardTitle}>Fast Service</Text>
                <Text style={styles.featureCardText}>24-hour turnaround time</Text>
              </View>

              <View style={styles.featureCard}>
                <View style={[styles.featureIcon, { backgroundColor: COLORS.success + '20' }]}>
                  <Ionicons name="shield-checkmark" size={24} color={COLORS.success} />
                </View>
                <Text style={styles.featureCardTitle}>Quality Guaranteed</Text>
                <Text style={styles.featureCardText}>Professional care always</Text>
              </View>

              <View style={styles.featureCard}>
                <View style={[styles.featureIcon, { backgroundColor: COLORS.purple + '20' }]}>
                  <Ionicons name="pricetag" size={24} color={COLORS.purple} />
                </View>
                <Text style={styles.featureCardTitle}>Best Prices</Text>
                <Text style={styles.featureCardText}>Affordable & transparent</Text>
              </View>

              <View style={styles.featureCard}>
                <View style={[styles.featureIcon, { backgroundColor: COLORS.warning + '20' }]}>
                  <Ionicons name="people" size={24} color={COLORS.warning} />
                </View>
                <Text style={styles.featureCardTitle}>Trusted by 1000+</Text>
                <Text style={styles.featureCardText}>Satisfied customers</Text>
              </View>
            </View>
          </View>

          {/* Bottom Spacing */}
          <View style={{ height: 100 }} />
        </Animated.View>
      </ScrollView>
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
    marginTop: 16,
    fontSize: 14,
    fontWeight: '600',
  },

  // Floating Header
  floatingHeader: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'ios' ? 60 : 50,
    paddingBottom: 20,
    zIndex: 1000,
    backgroundColor: COLORS.background + 'E6',
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  logoContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
    elevation: 4,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
  },
  logoText: {
    color: COLORS.textPrimary,
    fontSize: 20,
    fontWeight: '800',
    letterSpacing: 1,
  },
  welcomeText: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '500',
    marginTop: 2,
  },
  notificationButton: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: COLORS.cardDark,
    justifyContent: 'center',
    alignItems: 'center',
  },
  notificationIconContainer: {
    position: 'relative',
  },
  notificationBadge: {
    position: 'absolute',
    top: -6,
    right: -6,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: COLORS.danger,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 4,
    borderWidth: 2,
    borderColor: COLORS.cardDark,
  },
  notificationBadgeText: {
    color: COLORS.textPrimary,
    fontSize: 9,
    fontWeight: '700',
  },

  // Content
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingTop: Platform.OS === 'ios' ? 120 : 110,
  },

  // Hero Section
  heroSection: {
    marginHorizontal: 20,
    marginBottom: 24,
    borderRadius: 24,
    overflow: 'hidden',
    elevation: 4,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
  },
  heroContent: {
    padding: 24,
  },
  heroTitle: {
    fontSize: 32,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 8,
    lineHeight: 40,
  },
  heroTitleHighlight: {
    color: COLORS.primary,
  },
  heroSubtitle: {
    fontSize: 15,
    color: COLORS.textSecondary,
    lineHeight: 22,
    marginBottom: 24,
  },
  statsContainer: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  statCard: {
    flex: 1,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 11,
    color: COLORS.textSecondary,
    fontWeight: '600',
  },
  statDivider: {
    width: 1,
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    marginHorizontal: 8,
  },

  // Section
  section: {
    marginBottom: 32,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.textPrimary,
  },
  sectionLink: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.primary,
  },

  // Service Categories
  categoriesGrid: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    gap: 12,
  },
  categoryCard: {
    flex: 1,
    alignItems: 'center',
  },
  categoryGradient: {
    width: 60,
    height: 60,
    borderRadius: 30,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
    elevation: 4,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
  },
  categoryLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: COLORS.textSecondary,
    textAlign: 'center',
  },

  // Promotions
  promoScrollContent: {
    paddingLeft: 20,
    gap: 16,
    paddingRight: 20,
  },
  promoCard: {
    width: SCREEN_WIDTH - 80,
    borderRadius: 20,
    overflow: 'hidden',
    elevation: 6,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 12,
  },
  promoCardFirst: {
    marginLeft: 0,
  },
  promoGradient: {
    padding: 20,
    minHeight: 180,
    justifyContent: 'space-between',
  },
  promoBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 12,
    gap: 6,
  },
  promoBadgeText: {
    color: COLORS.textPrimary,
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 1,
  },
  promoContent: {
    flex: 1,
    justifyContent: 'center',
  },
  promoTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 6,
    lineHeight: 30,
  },
  promoSubtitle: {
    fontSize: 14,
    color: 'rgba(255, 255, 255, 0.9)',
    marginBottom: 16,
    lineHeight: 20,
  },
  promoPriceContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 12,
  },
  promoPrice: {
    fontSize: 32,
    fontWeight: '800',
    color: COLORS.textPrimary,
  },
  promoOriginalPrice: {
    fontSize: 18,
    color: 'rgba(255, 255, 255, 0.6)',
    textDecorationLine: 'line-through',
  },
  promoFeatures: {
    gap: 8,
  },
  featureRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  featureText: {
    color: 'rgba(255, 255, 255, 0.9)',
    fontSize: 13,
    fontWeight: '500',
  },
  promoArrow: {
    alignSelf: 'flex-end',
  },

  // Active Orders
  orderCard: {
    marginHorizontal: 20,
    marginBottom: 12,
    borderRadius: 20,
    overflow: 'hidden',
    elevation: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
  },
  orderGradient: {
    flexDirection: 'row',
    padding: 16,
    position: 'relative',
  },
  orderStatusBar: {
    position: 'absolute',
    left: 0,
    top: 0,
    bottom: 0,
    width: 4,
    overflow: 'hidden',
  },
  orderStatusGradient: {
    flex: 1,
  },
  orderContent: {
    flex: 1,
    paddingLeft: 12,
  },
  orderHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
    gap: 12,
  },
  orderIcon: {
    width: 44,
    height: 44,
  },
  orderIconGradient: {
    width: '100%',
    height: '100%',
    borderRadius: 22,
    justifyContent: 'center',
    alignItems: 'center',
  },
  orderHeaderContent: {
    flex: 1,
  },
  orderNumber: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
    fontFamily: 'monospace',
    marginBottom: 2,
  },
  orderService: {
    fontSize: 12,
    color: COLORS.textSecondary,
  },
  orderStatusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  orderStatusText: {
    fontSize: 9,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
  orderFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  orderTimeContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  orderTime: {
    fontSize: 12,
    color: COLORS.textMuted,
    fontWeight: '500',
  },
  orderPriceContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  orderPrice: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.primary,
  },

  // Empty State
  emptyStateCard: {
    marginHorizontal: 20,
    borderRadius: 20,
    overflow: 'hidden',
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
  },
  emptyStateGradient: {
    padding: 32,
    alignItems: 'center',
  },
  emptyStateIcon: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(14, 165, 233, 0.15)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 20,
  },
  emptyStateTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 8,
  },
  emptyStateText: {
    fontSize: 14,
    color: COLORS.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: 24,
  },
  emptyStateButton: {
    borderRadius: 12,
    overflow: 'hidden',
  },
  emptyStateButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingVertical: 14,
    gap: 8,
  },
  emptyStateButtonText: {
    color: COLORS.textPrimary,
    fontSize: 14,
    fontWeight: '700',
  },

  // Branches
  branchScrollContent: {
    paddingLeft: 20,
    gap: 12,
    paddingRight: 20,
  },
  branchCard: {
    width: 160,
    borderRadius: 20,
    overflow: 'hidden',
    elevation: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
  },
  branchCardFirst: {
    marginLeft: 0,
  },
  branchGradient: {
    padding: 20,
    alignItems: 'center',
    minHeight: 200,
    justifyContent: 'space-between',
  },
  branchIconContainer: {
    width: 64,
    height: 64,
  },
  branchIconGradient: {
    width: '100%',
    height: '100%',
    borderRadius: 32,
    justifyContent: 'center',
    alignItems: 'center',
    elevation: 4,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
  },
  branchCodeBadge: {
    position: 'absolute',
    top: 16,
    right: 16,
    backgroundColor: COLORS.primary + '30',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
  },
  branchCodeText: {
    fontSize: 10,
    fontWeight: '800',
    color: COLORS.primary,
    letterSpacing: 0.5,
  },
  branchName: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginTop: 16,
    marginBottom: 4,
    textAlign: 'center',
  },
  branchCity: {
    fontSize: 12,
    color: COLORS.textSecondary,
    marginBottom: 12,
    textAlign: 'center',
  },
  branchStatus: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 12,
  },
  branchStatusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  branchStatusText: {
    fontSize: 11,
    fontWeight: '600',
  },

  // Features Grid
  featuresGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: 20,
    gap: 12,
  },
  featureCard: {
    flex: 1,
    minWidth: (SCREEN_WIDTH - 56) / 2,
    backgroundColor: COLORS.cardDark,
    borderRadius: 16,
    padding: 20,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  featureIcon: {
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 12,
  },
  featureCardTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 4,
    textAlign: 'center',
  },
  featureCardText: {
    fontSize: 12,
    color: COLORS.textSecondary,
    textAlign: 'center',
  },
});