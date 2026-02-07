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
  Linking,
  Animated,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';
import { LinearGradient } from 'expo-linear-gradient';

const COLORS = {
  background: '#0A1128',
  cardDark: '#1A2847',
  cardLight: '#253454',
  primary: '#0EA5E9',
  primaryDark: '#0284C7',
  textPrimary: '#FFFFFF',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  success: '#10B981',
  successLight: '#34D399',
  warning: '#F59E0B',
  danger: '#EF4444',
  border: '#1E293B',
};

export default function OrderDetailsScreen() {
  const { id } = useLocalSearchParams();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [order, setOrder] = useState(null);
  const [fadeAnim] = useState(new Animated.Value(0));

  useEffect(() => {
    fetchOrderDetails();
  }, [id]);

  useEffect(() => {
    if (order) {
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true,
      }).start();
    }
  }, [order]);

  const fetchOrderDetails = async () => {
    try {
      setLoading(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      
      if (!token) {
        router.replace('/(auth)/login');
        return;
      }

      const response = await fetch(`${API_BASE_URL}/v1/orders/${id}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.order) {
          setOrder(data.data.order);
        }
      } else if (response.status === 401) {
        await AsyncStorage.multiRemove([STORAGE_KEYS.TOKEN, STORAGE_KEYS.CUSTOMER]);
        router.replace('/(auth)/login');
      } else {
        Alert.alert('Error', 'Order not found');
        router.back();
      }
    } catch (error) {
      console.error('Error fetching order:', error);
      Alert.alert('Error', 'Failed to load order details');
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchOrderDetails();
    setRefreshing(false);
  };

  const getStatusConfig = (status) => {
    const configs = {
      'received': {
        color: COLORS.primary,
        gradient: ['#3B82F6', '#2563EB'],
        icon: 'checkmark-circle',
        label: 'Order Received',
        description: 'Your order has been received and is being prepared',
      },
      'processing': {
        color: COLORS.primary,
        gradient: ['#8B5CF6', '#7C3AED'],
        icon: 'sync',
        label: 'Processing',
        description: 'Your laundry is being washed and dried',
      },
      'ready': {
        color: COLORS.warning,
        gradient: ['#F59E0B', '#D97706'],
        icon: 'bag-check',
        label: 'Ready for Pickup',
        description: 'Your laundry is clean and ready to collect',
      },
      'paid': {
        color: COLORS.success,
        gradient: ['#10B981', '#059669'],
        icon: 'card',
        label: 'Payment Received',
        description: 'Thank you for your payment',
      },
      'completed': {
        color: COLORS.successLight,
        gradient: ['#34D399', '#10B981'],
        icon: 'checkmark-done-circle',
        label: 'Completed',
        description: 'Order completed successfully',
      },
      'cancelled': {
        color: COLORS.danger,
        gradient: ['#EF4444', '#DC2626'],
        icon: 'close-circle',
        label: 'Cancelled',
        description: 'This order has been cancelled',
      },
    };
    return configs[status?.toLowerCase()] || configs['received'];
  };

  const formatPrice = (price) => {
    if (!price || isNaN(price)) return '₱0.00';
    return `₱${parseFloat(price).toFixed(2)}`;
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const handleCall = (phoneNumber) => {
    Linking.openURL(`tel:${phoneNumber}`);
  };

  const getTimelineSteps = () => {
    if (!order) return [];

    const steps = [
      { 
        status: 'received', 
        label: 'Received', 
        icon: 'checkmark-circle',
        description: 'Order placed',
      },
      { 
        status: 'processing', 
        label: 'Processing', 
        icon: 'sync',
        description: 'Being washed',
      },
      { 
        status: 'ready', 
        label: 'Ready', 
        icon: 'bag-check',
        description: 'Ready for pickup',
      },
      { 
        status: 'paid', 
        label: 'Paid', 
        icon: 'card',
        description: 'Payment received',
      },
      { 
        status: 'completed', 
        label: 'Completed', 
        icon: 'checkmark-done-circle',
        description: 'Order completed',
      },
    ];

    const statusOrder = ['received', 'processing', 'ready', 'paid', 'completed'];
    const currentStatusIndex = statusOrder.indexOf(order.status?.toLowerCase());

    return steps.map((step, index) => ({
      ...step,
      completed: index <= currentStatusIndex,
      current: index === currentStatusIndex,
    }));
  };

  // Calculate subtotal for display
  const calculateSubtotal = () => {
    const subtotal = parseFloat(order.subtotal) || 0;
    return subtotal;
  };

  // Calculate fees total
  const calculateFeesTotal = () => {
    const pickupFee = parseFloat(order.pickup_fee) || 0;
    const deliveryFee = parseFloat(order.delivery_fee) || 0;
    return pickupFee + deliveryFee;
  };

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading order details...</Text>
      </View>
    );
  }

  if (!order) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <View style={styles.errorIconContainer}>
          <Ionicons name="alert-circle" size={80} color={COLORS.danger} />
        </View>
        <Text style={styles.errorTitle}>Order Not Found</Text>
        <Text style={styles.errorText}>
          We couldn't find this order. Please check the tracking number.
        </Text>
        <TouchableOpacity
          style={styles.primaryButton}
          onPress={() => router.back()}
        >
          <Ionicons name="arrow-back" size={20} color={COLORS.textPrimary} />
          <Text style={styles.primaryButtonText}>Go Back</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const timelineSteps = getTimelineSteps();
  const statusConfig = getStatusConfig(order.status);

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.headerBackButton}
          onPress={() => router.back()}
        >
          <Ionicons name="arrow-back" size={24} color={COLORS.textPrimary} />
        </TouchableOpacity>
        <View style={styles.headerCenter}>
          <Text style={styles.headerTitle}>Order Details</Text>
          <Text style={styles.headerSubtitle}>{order.tracking_number}</Text>
        </View>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView
        style={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={COLORS.primary}
          />
        }
      >
        <Animated.View style={{ opacity: fadeAnim }}>
          {/* Status Hero Card */}
          <View style={styles.statusHeroCard}>
            <LinearGradient
              colors={statusConfig.gradient}
              style={styles.statusGradient}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
            >
              <View style={styles.statusIconWrapper}>
                <Ionicons
                  name={statusConfig.icon}
                  size={64}
                  color={COLORS.textPrimary}
                />
              </View>
              <Text style={styles.statusLabel}>{statusConfig.label}</Text>
              <Text style={styles.statusDescription}>
                {statusConfig.description}
              </Text>
              
              {/* Tracking Number Pill */}
              <View style={styles.trackingPill}>
                <Ionicons name="barcode-outline" size={16} color={COLORS.textPrimary} />
                <Text style={styles.trackingNumber}>{order.tracking_number}</Text>
              </View>
            </LinearGradient>
          </View>

          {/* Timeline Card */}
          <View style={styles.card}>
            <View style={styles.cardHeader}>
              <Ionicons name="git-commit-outline" size={24} color={COLORS.primary} />
              <Text style={styles.cardTitle}>Order Progress</Text>
            </View>
            
            <View style={styles.timeline}>
              {timelineSteps.map((step, index) => (
                <View key={step.status} style={styles.timelineItem}>
                  {/* Connector Line */}
                  {index < timelineSteps.length - 1 && (
                    <View
                      style={[
                        styles.timelineConnector,
                        step.completed && styles.timelineConnectorActive,
                      ]}
                    />
                  )}
                  
                  {/* Icon Circle */}
                  <View
                    style={[
                      styles.timelineIconCircle,
                      step.completed && styles.timelineIconCircleActive,
                      step.current && styles.timelineIconCircleCurrent,
                    ]}
                  >
                    <Ionicons
                      name={step.completed ? step.icon : 'ellipse'}
                      size={step.completed ? 24 : 12}
                      color={step.completed ? COLORS.textPrimary : COLORS.textMuted}
                    />
                  </View>
                  
                  {/* Content */}
                  <View style={styles.timelineContent}>
                    <Text
                      style={[
                        styles.timelineLabel,
                        step.completed && styles.timelineLabelActive,
                      ]}
                    >
                      {step.label}
                    </Text>
                    <Text style={styles.timelineDescription}>
                      {step.description}
                    </Text>
                    {step.completed && (
                      <Text style={styles.timelineDate}>
                        {formatDate(order.updated_at)}
                      </Text>
                    )}
                  </View>
                </View>
              ))}
            </View>
          </View>

          {/* Order Details Grid */}
          <View style={styles.card}>
            <View style={styles.cardHeader}>
              <Ionicons name="information-circle-outline" size={24} color={COLORS.primary} />
              <Text style={styles.cardTitle}>Order Information</Text>
            </View>
            
            <View style={styles.detailsGrid}>
              <View style={styles.detailBox}>
                <View style={styles.detailIconContainer}>
                  <Ionicons name="pricetag" size={20} color={COLORS.primary} />
                </View>
                <Text style={styles.detailLabel}>Service</Text>
                <Text style={styles.detailValue}>{order.service_name}</Text>
              </View>

              <View style={styles.detailBox}>
                <View style={styles.detailIconContainer}>
                  <Ionicons name="scale" size={20} color={COLORS.primary} />
                </View>
                <Text style={styles.detailLabel}>Weight</Text>
                <Text style={styles.detailValue}>
                  {parseFloat(order.weight).toFixed(2)} kg
                </Text>
              </View>

              <View style={styles.detailBox}>
                <View style={styles.detailIconContainer}>
                  <Ionicons name="location" size={20} color={COLORS.primary} />
                </View>
                <Text style={styles.detailLabel}>Branch</Text>
                <Text style={styles.detailValue} numberOfLines={1}>
                  {order.branch_name}
                </Text>
              </View>

              <View style={styles.detailBox}>
                <View style={styles.detailIconContainer}>
                  <Ionicons name="calendar" size={20} color={COLORS.primary} />
                </View>
                <Text style={styles.detailLabel}>Date</Text>
                <Text style={styles.detailValue} numberOfLines={1}>
                  {new Date(order.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                </Text>
              </View>
            </View>
          </View>

          {/* Pricing Card - UPDATED WITH PICKUP & DELIVERY FEES */}
          <View style={styles.card}>
            <View style={styles.cardHeader}>
              <Ionicons name="calculator-outline" size={24} color={COLORS.primary} />
              <Text style={styles.cardTitle}>Pricing Details</Text>
            </View>
            
            <View style={styles.pricingContainer}>
              {/* Service/Subtotal */}
              <View style={styles.pricingRow}>
                <Text style={styles.pricingLabel}>
                  Service ({parseFloat(order.weight).toFixed(2)} kg)
                </Text>
                <Text style={styles.pricingValue}>
                  {formatPrice(order.subtotal)}
                </Text>
              </View>

              <View style={styles.pricingRow}>
                <Text style={styles.pricingLabelSmall}>
                  ₱{parseFloat(order.price_per_kg).toFixed(2)}/kg
                </Text>
              </View>

              {/* Pickup Fee */}
              {parseFloat(order.pickup_fee) > 0 && (
                <View style={styles.pricingRow}>
                  <View style={styles.feeLabelContainer}>
                    <Ionicons name="car-outline" size={16} color={COLORS.primary} />
                    <Text style={styles.pricingLabel}>Pickup Fee</Text>
                  </View>
                  <Text style={styles.pricingValue}>
                    {formatPrice(order.pickup_fee)}
                  </Text>
                </View>
              )}

              {/* Delivery Fee */}
              {parseFloat(order.delivery_fee) > 0 && (
                <View style={styles.pricingRow}>
                  <View style={styles.feeLabelContainer}>
                    <Ionicons name="bicycle-outline" size={16} color={COLORS.primary} />
                    <Text style={styles.pricingLabel}>Delivery Fee</Text>
                  </View>
                  <Text style={styles.pricingValue}>
                    {formatPrice(order.delivery_fee)}
                  </Text>
                </View>
              )}

              {/* Discount */}
              {parseFloat(order.discount_amount) > 0 && (
                <View style={styles.pricingRow}>
                  <View style={styles.discountLabelContainer}>
                    <Ionicons name="pricetag" size={16} color={COLORS.success} />
                    <Text style={styles.pricingLabel}>Discount</Text>
                  </View>
                  <Text style={[styles.pricingValue, { color: COLORS.success }]}>
                    -{formatPrice(order.discount_amount)}
                  </Text>
                </View>
              )}

              <View style={styles.pricingDivider} />

              {/* Total */}
              <View style={styles.pricingRow}>
                <Text style={styles.pricingTotalLabel}>Total Amount</Text>
                <Text style={styles.pricingTotalValue}>
                  {formatPrice(order.total_amount)}
                </Text>
              </View>

              {/* Payment Status Badge */}
              <View style={styles.paymentStatusContainer}>
                <View style={[
                  styles.paymentStatusBadge,
                  order.payment_status === 'paid' 
                    ? styles.paymentStatusPaid 
                    : styles.paymentStatusUnpaid
                ]}>
                  <Ionicons 
                    name={order.payment_status === 'paid' ? 'checkmark-circle' : 'time'} 
                    size={16} 
                    color={order.payment_status === 'paid' ? COLORS.success : COLORS.warning} 
                  />
                  <Text style={[
                    styles.paymentStatusText,
                    { color: order.payment_status === 'paid' ? COLORS.success : COLORS.warning }
                  ]}>
                    {order.payment_status === 'paid' ? 'Paid' : 'Payment Pending'}
                  </Text>
                </View>
              </View>
            </View>
          </View>

          {/* Branch Card */}
          {order.branch_address && (
            <View style={styles.card}>
              <View style={styles.cardHeader}>
                <Ionicons name="location" size={24} color={COLORS.primary} />
                <Text style={styles.cardTitle}>Branch Location</Text>
              </View>
              
              <View style={styles.branchContainer}>
                <View style={styles.branchIconLarge}>
                  <Ionicons name="business" size={32} color={COLORS.primary} />
                </View>
                <View style={styles.branchDetails}>
                  <Text style={styles.branchName}>{order.branch_name}</Text>
                  <Text style={styles.branchAddress}>{order.branch_address}</Text>
                  {order.branch_phone && (
                    <View style={styles.branchPhoneContainer}>
                      <Ionicons name="call" size={16} color={COLORS.primary} />
                      <Text style={styles.branchPhone}>{order.branch_phone}</Text>
                    </View>
                  )}
                </View>
              </View>
            </View>
          )}

          {/* Notes Card */}
          {order.notes && (
            <View style={styles.card}>
              <View style={styles.cardHeader}>
                <Ionicons name="document-text-outline" size={24} color={COLORS.primary} />
                <Text style={styles.cardTitle}>Order Notes</Text>
              </View>
              <View style={styles.notesContainer}>
                <Text style={styles.notesText}>{order.notes}</Text>
              </View>
            </View>
          )}

          <View style={{ height: 100 }} />
        </Animated.View>
      </ScrollView>

      {/* Bottom Action Bar */}
      {['ready', 'paid'].includes(order.status?.toLowerCase()) && order.branch_phone && (
        <View style={styles.bottomBar}>
          <TouchableOpacity
            style={styles.bottomBarButton}
            onPress={() => handleCall(order.branch_phone)}
          >
            <Ionicons name="call" size={24} color={COLORS.textPrimary} />
            <Text style={styles.bottomBarButtonText}>Call to Confirm Pickup</Text>
          </TouchableOpacity>
        </View>
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
    padding: 20,
  },
  loadingText: {
    color: COLORS.textSecondary,
    marginTop: 16,
    fontSize: 14,
  },
  errorIconContainer: {
    marginBottom: 24,
  },
  errorTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
    marginBottom: 12,
  },
  errorText: {
    fontSize: 16,
    color: COLORS.textSecondary,
    textAlign: 'center',
    marginBottom: 32,
    lineHeight: 24,
  },
  
  // Header
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 20,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  headerBackButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: COLORS.cardDark,
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerCenter: {
    flex: 1,
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  headerSubtitle: {
    fontSize: 12,
    color: COLORS.textSecondary,
    fontFamily: 'monospace',
    marginTop: 2,
  },
  
  // Content
  content: {
    flex: 1,
  },
  
  // Status Hero Card
  statusHeroCard: {
    margin: 20,
    borderRadius: 24,
    overflow: 'hidden',
    elevation: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
  },
  statusGradient: {
    padding: 32,
    alignItems: 'center',
  },
  statusIconWrapper: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 20,
  },
  statusLabel: {
    fontSize: 28,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 8,
    textAlign: 'center',
  },
  statusDescription: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.9)',
    textAlign: 'center',
    marginBottom: 20,
  },
  trackingPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 24,
    gap: 8,
  },
  trackingNumber: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.textPrimary,
    fontFamily: 'monospace',
    letterSpacing: 1,
  },
  
  // Card
  card: {
    backgroundColor: COLORS.cardDark,
    marginHorizontal: 20,
    marginBottom: 16,
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
    gap: 12,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  
  // Timeline
  timeline: {
    paddingLeft: 4,
  },
  timelineItem: {
    flexDirection: 'row',
    marginBottom: 32,
    position: 'relative',
  },
  timelineConnector: {
    position: 'absolute',
    left: 23,
    top: 48,
    bottom: -32,
    width: 2,
    backgroundColor: COLORS.cardLight,
  },
  timelineConnectorActive: {
    backgroundColor: COLORS.primary,
  },
  timelineIconCircle: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: COLORS.cardLight,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
    zIndex: 1,
    borderWidth: 2,
    borderColor: COLORS.border,
  },
  timelineIconCircleActive: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  timelineIconCircleCurrent: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
    transform: [{ scale: 1.1 }],
  },
  timelineContent: {
    flex: 1,
    paddingTop: 4,
  },
  timelineLabel: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.textSecondary,
    marginBottom: 4,
  },
  timelineLabelActive: {
    color: COLORS.textPrimary,
  },
  timelineDescription: {
    fontSize: 14,
    color: COLORS.textMuted,
    marginBottom: 6,
  },
  timelineDate: {
    fontSize: 12,
    color: COLORS.primary,
    fontWeight: '500',
  },
  
  // Details Grid
  detailsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  detailBox: {
    flex: 1,
    minWidth: '45%',
    backgroundColor: COLORS.cardLight,
    padding: 16,
    borderRadius: 16,
    alignItems: 'center',
  },
  detailIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: COLORS.primary + '20',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 12,
  },
  detailLabel: {
    fontSize: 12,
    color: COLORS.textMuted,
    marginBottom: 6,
    textAlign: 'center',
  },
  detailValue: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.textPrimary,
    textAlign: 'center',
  },
  
  // Pricing
  pricingContainer: {
    gap: 12,
  },
  pricingRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  pricingLabel: {
    fontSize: 15,
    color: COLORS.textSecondary,
  },
  pricingLabelSmall: {
    fontSize: 13,
    color: COLORS.textMuted,
  },
  pricingValue: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.textPrimary,
  },
  feeLabelContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  discountLabelContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  pricingDivider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginVertical: 8,
  },
  pricingTotalLabel: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  pricingTotalValue: {
    fontSize: 24,
    fontWeight: '800',
    color: COLORS.primary,
  },
  paymentStatusContainer: {
    marginTop: 16,
    alignItems: 'center',
  },
  paymentStatusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    gap: 8,
  },
  paymentStatusPaid: {
    backgroundColor: COLORS.success + '20',
  },
  paymentStatusUnpaid: {
    backgroundColor: COLORS.warning + '20',
  },
  paymentStatusText: {
    fontSize: 14,
    fontWeight: '600',
  },
  
  // Branch
  branchContainer: {
    flexDirection: 'row',
    gap: 16,
    marginBottom: 20,
  },
  branchIconLarge: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: COLORS.primary + '20',
    justifyContent: 'center',
    alignItems: 'center',
  },
  branchDetails: {
    flex: 1,
  },
  branchName: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 8,
  },
  branchAddress: {
    fontSize: 14,
    color: COLORS.textSecondary,
    lineHeight: 20,
    marginBottom: 8,
  },
  branchPhoneContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  branchPhone: {
    fontSize: 14,
    color: COLORS.primary,
    fontWeight: '600',
  },
  
  // Notes
  notesContainer: {
    backgroundColor: COLORS.cardLight,
    padding: 16,
    borderRadius: 12,
  },
  notesText: {
    fontSize: 14,
    color: COLORS.textSecondary,
    lineHeight: 22,
  },
  
  // Bottom Bar
  bottomBar: {
    backgroundColor: COLORS.cardDark,
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
  },
  bottomBarButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.primary,
    paddingVertical: 16,
    borderRadius: 16,
    gap: 12,
  },
  bottomBarButtonText: {
    color: COLORS.textPrimary,
    fontSize: 16,
    fontWeight: '700',
  },
  
  // Primary Button
  primaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.primary,
    paddingHorizontal: 32,
    paddingVertical: 16,
    borderRadius: 16,
    gap: 10,
  },
  primaryButtonText: {
    color: COLORS.textPrimary,
    fontSize: 16,
    fontWeight: '700',
  },
});