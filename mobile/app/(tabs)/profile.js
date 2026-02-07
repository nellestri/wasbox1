import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
  RefreshControl,
  Animated,
  Dimensions,
  StatusBar,
  Platform,
} from 'react-native';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';
import { useAuth } from '../../context/AuthContext';
import TermsModal from '../../components/TermsModal';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

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

export default function ProfileScreen() {
  const { logout } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [customer, setCustomer] = useState(null);
  const [showTermsModal, setShowTermsModal] = useState(false);
  const [stats, setStats] = useState({
    totalOrders: 0,
    totalSpent: 0,
    rating: 0,
    completionRate: 0,
    pendingOrders: 0,
    activePickups: 0,
  });
  
  // Animations
  const [fadeAnim] = useState(new Animated.Value(0));
  const [slideAnim] = useState(new Animated.Value(50));
  const [scaleAnim] = useState(new Animated.Value(0.9));

  useEffect(() => {
    fetchProfileData();
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
        Animated.spring(scaleAnim, {
          toValue: 1,
          tension: 50,
          friction: 7,
          useNativeDriver: true,
        }),
      ]).start();
    }
  }, [loading]);

  const fetchProfileData = async () => {
    try {
      setLoading(true);
      await Promise.all([
        fetchCustomerProfile(),
        fetchCustomerStats(),
      ]);
    } catch (error) {
      console.error('Error fetching profile:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchProfileData();
    setRefreshing(false);
  };

  const fetchCustomerProfile = async () => {
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
          setCustomer(data.data.customer);
          await AsyncStorage.setItem(STORAGE_KEYS.CUSTOMER, JSON.stringify(data.data.customer));
        }
      } else if (response.status === 401) {
        await logout();
      }
    } catch (error) {
      const cachedCustomer = await AsyncStorage.getItem(STORAGE_KEYS.CUSTOMER);
      if (cachedCustomer) setCustomer(JSON.parse(cachedCustomer));
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
      setStats({ 
        totalOrders: 24, 
        totalSpent: 15420, 
        rating: 4.8,
        completionRate: 95,
        pendingOrders: 3,
        activePickups: 1,
      });
    }
  };

  const performLogout = async () => {
    try {
      setLoggingOut(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);

      if (token) {
        await fetch(`${API_BASE_URL}/v1/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        });
      }
      await logout();
    } catch (error) {
      console.error('Logout error:', error);
      await logout();
    } finally {
      setLoggingOut(false);
    }
  };

  const handleLogout = () => {
    Alert.alert(
      'Log Out',
      'Are you sure you want to log out?',
      [
        { text: 'Cancel', style: 'cancel' },
        { 
          text: 'Log Out', 
          style: 'destructive', 
          onPress: performLogout 
        },
      ]
    );
  };

  const getInitials = (name) => {
    if (!name) return '??';
    return name.split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2);
  };

  const formatCurrency = (amount) => {
    return `₱${parseFloat(amount).toLocaleString('en-PH', { 
      minimumFractionDigits: 0,
      maximumFractionDigits: 0 
    })}`;
  };

  const getProfileCompletion = () => {
    let score = 0;
    if (customer?.name) score += 25;
    if (customer?.email) score += 25;
    if (customer?.phone) score += 25;
    if (customer?.address) score += 25;
    return score;
  };

  // Quick Actions
  const quickActions = [
    { 
      icon: 'add-circle', 
      label: 'New Order', 
      color: COLORS.primary,
      gradient: COLORS.gradientPrimary,
      action: () => router.push('/order/new')
    },
    { 
      icon: 'location', 
      label: 'Pickup', 
      color: COLORS.success,
      gradient: COLORS.gradientSuccess,
      action: () => router.push('/pickup')
    },
    { 
      icon: 'time', 
      label: 'History', 
      color: COLORS.purple,
      gradient: COLORS.gradientSecondary,
      action: () => router.push('/order')
    },
    { 
      icon: 'chatbubbles', 
      label: 'Support', 
      color: COLORS.accent,
      gradient: COLORS.gradientWarning,
      action: () => Alert.alert('Support', '📞 (035) 123-4567')
    },
  ];

  // Menu Sections
  const menuSections = [
    {
      title: 'Account',
      items: [
        { 
          icon: 'person-outline', 
          label: 'Edit Profile', 
          description: 'Update your information',
          color: COLORS.primary,
          action: () => router.push('/profile/edit'),
        },
        { 
          icon: 'card-outline', 
          label: 'Payment Methods', 
          description: 'Manage payment options',
          color: COLORS.success,
          action: () => Alert.alert('Coming Soon'),
        },
        { 
          icon: 'location-outline', 
          label: 'Saved Addresses', 
          description: 'Manage delivery locations',
          color: COLORS.cyan,
          action: () => Alert.alert('Coming Soon'),
        },
      ],
    },
    {
      title: 'Preferences',
      items: [
        { 
          icon: 'notifications-outline', 
          label: 'Notifications', 
          description: 'Push notifications & alerts',
          color: COLORS.warning,
          action: () => router.push('/notifications'),
        },
        { 
          icon: 'shield-checkmark-outline', 
          label: 'Privacy & Security', 
          description: 'Password & account security',
          color: COLORS.purple,
          action: () => Alert.alert('Coming Soon'),
        },
      ],
    },
    {
      title: 'Support',
      items: [
        { 
          icon: 'help-circle-outline', 
          label: 'Help Center', 
          description: 'FAQs and support',
          color: COLORS.primary,
          action: () => Alert.alert('Support', '📞 (035) 123-4567\n✉️ support@washbox.com'),
        },
        { 
          icon: 'document-text-outline', 
          label: 'Terms & Privacy', 
          description: 'Legal information',
          color: COLORS.textSecondary,
          action: () => setShowTermsModal(true),
        },
      ],
    },
  ];

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading profile...</Text>
      </View>
    );
  }

  const profileCompletion = getProfileCompletion();

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />

      <ScrollView 
        style={styles.content}
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
          {/* Hero Profile Card */}
          <View style={styles.heroCard}>
            <LinearGradient
              colors={['#1C2340', '#252D4C']}
              style={styles.heroGradient}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
            >
              {/* Background Pattern */}
              <View style={styles.patternContainer}>
                <View style={[styles.patternCircle, { top: -50, right: -50 }]} />
                <View style={[styles.patternCircle, { bottom: -30, left: -30, opacity: 0.3 }]} />
              </View>

              {/* Profile Header */}
              <View style={styles.profileSection}>
                <View style={styles.avatarWrapper}>
                  <LinearGradient
                    colors={COLORS.gradientPrimary}
                    style={styles.avatarGradient}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 1 }}
                  >
                    <Text style={styles.avatarText}>{getInitials(customer?.name)}</Text>
                  </LinearGradient>
                  <View style={styles.statusBadge}>
                    <Ionicons name="checkmark-circle" size={24} color={COLORS.success} />
                  </View>
                  <TouchableOpacity style={styles.editAvatarButton}>
                    <Ionicons name="camera" size={16} color={COLORS.textPrimary} />
                  </TouchableOpacity>
                </View>

                <View style={styles.profileDetails}>
                  <Text style={styles.userName}>{customer?.name || 'User'}</Text>
                  <Text style={styles.userEmail}>{customer?.email}</Text>
                  
                  {customer?.phone && (
                    <View style={styles.phoneContainer}>
                      <Ionicons name="call" size={14} color={COLORS.primaryLight} />
                      <Text style={styles.phoneText}>{customer.phone}</Text>
                    </View>
                  )}

                  {/* Profile Completion */}
                  <View style={styles.completionCard}>
                    <View style={styles.completionHeader}>
                      <Text style={styles.completionText}>Profile Completion</Text>
                      <Text style={styles.completionPercentage}>{profileCompletion}%</Text>
                    </View>
                    <View style={styles.progressBarContainer}>
                      <View style={styles.progressBarBackground}>
                        <LinearGradient
                          colors={COLORS.gradientPrimary}
                          style={[styles.progressBarFill, { width: `${profileCompletion}%` }]}
                          start={{ x: 0, y: 0 }}
                          end={{ x: 1, y: 0 }}
                        />
                      </View>
                    </View>
                  </View>
                </View>
              </View>

              {/* Stats Row */}
              <View style={styles.statsRow}>
                <View style={styles.statItem}>
                  <Text style={styles.statValue}>{stats.totalOrders}</Text>
                  <Text style={styles.statLabel}>Orders</Text>
                </View>
                <View style={styles.statDivider} />
                <View style={styles.statItem}>
                  <Text style={styles.statValue}>{formatCurrency(stats.totalSpent)}</Text>
                  <Text style={styles.statLabel}>Spent</Text>
                </View>
                <View style={styles.statDivider} />
                <View style={styles.statItem}>
                  <View style={styles.ratingContainer}>
                    <Ionicons name="star" size={16} color={COLORS.warning} />
                    <Text style={styles.statValue}>{stats.rating.toFixed(1)}</Text>
                  </View>
                  <Text style={styles.statLabel}>Rating</Text>
                </View>
              </View>
            </LinearGradient>
          </View>

          {/* Quick Actions */}
          <View style={styles.quickActionsSection}>
            <Text style={styles.sectionTitle}>Quick Actions</Text>
            <View style={styles.quickActionsGrid}>
              {quickActions.map((action, index) => (
                <TouchableOpacity
                  key={index}
                  style={styles.quickActionCard}
                  onPress={action.action}
                  activeOpacity={0.8}
                >
                  <LinearGradient
                    colors={action.gradient}
                    style={styles.quickActionGradient}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 1 }}
                  >
                    <Ionicons name={action.icon} size={24} color={COLORS.textPrimary} />
                  </LinearGradient>
                  <Text style={styles.quickActionLabel}>{action.label}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          {/* Active Orders Card */}
          {(stats.pendingOrders > 0 || stats.activePickups > 0) && (
            <View style={styles.sectionContainer}>
              <Text style={styles.sectionTitle}>Active</Text>
              <View style={styles.activeCard}>
                <LinearGradient
                  colors={['#1C2340', '#252D4C']}
                  style={styles.activeGradient}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                >
                  {stats.pendingOrders > 0 && (
                    <TouchableOpacity 
                      style={styles.activeItem}
                      onPress={() => router.push('/order')}
                    >
                      <View style={styles.activeIconContainer}>
                        <LinearGradient
                          colors={COLORS.gradientWarning}
                          style={styles.activeIconGradient}
                        >
                          <Ionicons name="time" size={20} color={COLORS.textPrimary} />
                        </LinearGradient>
                      </View>
                      <View style={styles.activeContent}>
                        <Text style={styles.activeTitle}>{stats.pendingOrders} Pending Orders</Text>
                        <Text style={styles.activeSubtitle}>Track your orders</Text>
                      </View>
                      <Ionicons name="chevron-forward" size={20} color={COLORS.textMuted} />
                    </TouchableOpacity>
                  )}

                  {stats.activePickups > 0 && (
                    <TouchableOpacity 
                      style={styles.activeItem}
                      onPress={() => router.push('/pickup')}
                    >
                      <View style={styles.activeIconContainer}>
                        <LinearGradient
                          colors={COLORS.gradientSuccess}
                          style={styles.activeIconGradient}
                        >
                          <Ionicons name="location" size={20} color={COLORS.textPrimary} />
                        </LinearGradient>
                      </View>
                      <View style={styles.activeContent}>
                        <Text style={styles.activeTitle}>{stats.activePickups} Active Pickups</Text>
                        <Text style={styles.activeSubtitle}>View pickup status</Text>
                      </View>
                      <Ionicons name="chevron-forward" size={20} color={COLORS.textMuted} />
                    </TouchableOpacity>
                  )}
                </LinearGradient>
              </View>
            </View>
          )}

          {/* Membership Card */}
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Membership</Text>
            <TouchableOpacity 
              style={styles.membershipCard}
              activeOpacity={0.9}
            >
              <LinearGradient
                colors={['#2D1B69', '#8B5CF6']}
                style={styles.membershipGradient}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
              >
                <View style={styles.membershipContent}>
                  <View style={styles.membershipBadge}>
                    <Ionicons name="diamond" size={32} color={COLORS.warning} />
                  </View>
                  <View style={styles.membershipInfo}>
                    <Text style={styles.membershipTier}>Silver Member</Text>
                    <Text style={styles.membershipProgress}>5 orders to Gold • {stats.completionRate}% completion</Text>
                    <View style={styles.membershipProgressBar}>
                      <View style={[styles.membershipProgressFill, { width: '60%' }]} />
                    </View>
                  </View>
                  <Ionicons name="chevron-forward" size={24} color="rgba(255,255,255,0.6)" />
                </View>
              </LinearGradient>
            </TouchableOpacity>
          </View>

          {/* Menu Sections */}
          {menuSections.map((section, sectionIndex) => (
            <View key={sectionIndex} style={styles.sectionContainer}>
              <Text style={styles.sectionTitle}>{section.title}</Text>
              <View style={styles.menuCard}>
                {section.items.map((item, itemIndex) => (
                  <React.Fragment key={itemIndex}>
                    <TouchableOpacity 
                      style={styles.menuItem}
                      onPress={item.action}
                      activeOpacity={0.7}
                    >
                      <View style={[styles.menuIconContainer, { backgroundColor: item.color + '20' }]}>
                        <Ionicons name={item.icon} size={22} color={item.color} />
                      </View>
                      <View style={styles.menuContent}>
                        <Text style={styles.menuLabel}>{item.label}</Text>
                        <Text style={styles.menuDescription}>{item.description}</Text>
                      </View>
                      <Ionicons name="chevron-forward" size={20} color={COLORS.textMuted} />
                    </TouchableOpacity>
                    {itemIndex < section.items.length - 1 && (
                      <View style={styles.menuDivider} />
                    )}
                  </React.Fragment>
                ))}
              </View>
            </View>
          ))}

          {/* App Info */}
          <View style={styles.appInfoContainer}>
            <View style={styles.appInfoCard}>
              <Ionicons name="information-circle-outline" size={40} color={COLORS.primary} />
              <Text style={styles.appInfoTitle}>WashBox v2.1.0</Text>
              <Text style={styles.appInfoText}>Professional Laundry Service</Text>
              <Text style={styles.appInfoText}>© {new Date().getFullYear()} All rights reserved</Text>
            </View>
          </View>

          {/* Logout Button */}
          <View style={styles.logoutSection}>
            <TouchableOpacity 
              style={styles.logoutButton}
              onPress={handleLogout}
              disabled={loggingOut}
              activeOpacity={0.8}
            >
              <LinearGradient
                colors={['rgba(239, 68, 68, 0.1)', 'rgba(239, 68, 68, 0.05)']}
                style={styles.logoutGradient}
              >
                {loggingOut ? (
                  <ActivityIndicator color={COLORS.danger} size="small" />
                ) : (
                  <>
                    <Ionicons name="log-out-outline" size={22} color={COLORS.danger} />
                    <Text style={styles.logoutText}>Log Out</Text>
                  </>
                )}
              </LinearGradient>
            </TouchableOpacity>
          </View>

          {/* Bottom Spacing */}
          <View style={{ height: 40 }} />
        </Animated.View>
      </ScrollView>

      <TermsModal 
        visible={showTermsModal}
        onClose={() => setShowTermsModal(false)}
      />
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
  content: {
    flex: 1,
  },
  scrollContent: {
    paddingTop: Platform.OS === 'ios' ? 60 : 40,
  },

  // Hero Card
  heroCard: {
    marginHorizontal: 20,
    marginBottom: 24,
    borderRadius: 24,
    overflow: 'hidden',
    elevation: 8,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 12,
  },
  heroGradient: {
    padding: 24,
    position: 'relative',
  },
  patternContainer: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
  },
  patternCircle: {
    position: 'absolute',
    width: 150,
    height: 150,
    borderRadius: 75,
    backgroundColor: COLORS.primary,
    opacity: 0.1,
  },
  profileSection: {
    alignItems: 'center',
    marginBottom: 24,
  },
  avatarWrapper: {
    position: 'relative',
    marginBottom: 16,
  },
  avatarGradient: {
    width: 100,
    height: 100,
    borderRadius: 50,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 4,
    borderColor: 'rgba(255,255,255,0.2)',
  },
  avatarText: {
    fontSize: 36,
    fontWeight: '800',
    color: COLORS.textPrimary,
    letterSpacing: 2,
  },
  statusBadge: {
    position: 'absolute',
    top: -4,
    right: -4,
    backgroundColor: COLORS.background,
    borderRadius: 12,
  },
  editAvatarButton: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: COLORS.primary,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 3,
    borderColor: COLORS.cardDark,
  },
  profileDetails: {
    alignItems: 'center',
    width: '100%',
  },
  userName: {
    fontSize: 28,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 4,
    letterSpacing: 0.5,
  },
  userEmail: {
    fontSize: 14,
    color: COLORS.textSecondary,
    marginBottom: 8,
  },
  phoneContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: 'rgba(14, 165, 233, 0.15)',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
    marginBottom: 16,
  },
  phoneText: {
    fontSize: 13,
    color: COLORS.primaryLight,
    fontWeight: '600',
  },
  completionCard: {
    width: '100%',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  completionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  completionText: {
    fontSize: 13,
    color: COLORS.textSecondary,
    fontWeight: '600',
  },
  completionPercentage: {
    fontSize: 13,
    color: COLORS.primary,
    fontWeight: '700',
  },
  progressBarContainer: {
    height: 8,
  },
  progressBarBackground: {
    height: 8,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 4,
    overflow: 'hidden',
  },
  progressBarFill: {
    height: '100%',
    borderRadius: 4,
  },
  statsRow: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  statItem: {
    flex: 1,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 18,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  statLabel: {
    fontSize: 11,
    color: COLORS.textSecondary,
    fontWeight: '600',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  statDivider: {
    width: 1,
    height: 40,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  ratingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },

  // Quick Actions
  quickActionsSection: {
    marginBottom: 24,
    paddingHorizontal: 20,
  },
  sectionTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.textSecondary,
    marginBottom: 12,
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  quickActionsGrid: {
    flexDirection: 'row',
    gap: 12,
  },
  quickActionCard: {
    flex: 1,
    alignItems: 'center',
  },
  quickActionGradient: {
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
    elevation: 4,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 6,
  },
  quickActionLabel: {
    fontSize: 11,
    fontWeight: '600',
    color: COLORS.textSecondary,
    textAlign: 'center',
  },

  // Active Card
  sectionContainer: {
    marginBottom: 24,
    paddingHorizontal: 20,
  },
  activeCard: {
    borderRadius: 20,
    overflow: 'hidden',
    elevation: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
  },
  activeGradient: {
    padding: 4,
  },
  activeItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    gap: 12,
  },
  activeIconContainer: {
    width: 48,
    height: 48,
  },
  activeIconGradient: {
    width: '100%',
    height: '100%',
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
  },
  activeContent: {
    flex: 1,
  },
  activeTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  activeSubtitle: {
    fontSize: 12,
    color: COLORS.textSecondary,
  },

  // Membership Card
  membershipCard: {
    borderRadius: 20,
    overflow: 'hidden',
    elevation: 6,
    shadowColor: COLORS.purple,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 12,
  },
  membershipGradient: {
    padding: 20,
  },
  membershipContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
  },
  membershipBadge: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: 'rgba(255,255,255,0.15)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  membershipInfo: {
    flex: 1,
  },
  membershipTier: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 4,
  },
  membershipProgress: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.7)',
    marginBottom: 8,
  },
  membershipProgressBar: {
    height: 6,
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderRadius: 3,
    overflow: 'hidden',
  },
  membershipProgressFill: {
    height: '100%',
    backgroundColor: COLORS.warning,
    borderRadius: 3,
  },

  // Menu Items
  menuCard: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 20,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    gap: 12,
  },
  menuIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
  },
  menuContent: {
    flex: 1,
  },
  menuLabel: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  menuDescription: {
    fontSize: 12,
    color: COLORS.textSecondary,
  },
  menuDivider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginLeft: 76,
  },

  // App Info
  appInfoContainer: {
    paddingHorizontal: 20,
    marginBottom: 24,
  },
  appInfoCard: {
    backgroundColor: 'rgba(14, 165, 233, 0.05)',
    borderRadius: 20,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(14, 165, 233, 0.2)',
  },
  appInfoTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginTop: 12,
    marginBottom: 4,
  },
  appInfoText: {
    fontSize: 12,
    color: COLORS.textSecondary,
    marginBottom: 2,
  },

  // Logout
  logoutSection: {
    paddingHorizontal: 20,
  },
  logoutButton: {
    borderRadius: 16,
    overflow: 'hidden',
  },
  logoutGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 18,
    gap: 12,
  },
  logoutText: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.danger,
  },
});