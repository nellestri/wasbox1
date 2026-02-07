import { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
} from 'react-native';
import { Link, router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { Picker } from '@react-native-picker/picker';
import { StatusBar } from 'expo-status-bar';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';

export default function RegisterScreen() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
    phone: '',
    address: '',
    preferredBranchId: '',
  });
  
  const [branches, setBranches] = useState([]);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loadingBranches, setLoadingBranches] = useState(true);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    fetchBranches();
  }, []);

  const fetchBranches = async () => {
  try {
    setLoadingBranches(true);
    const response = await fetch(`${API_BASE_URL}/v1/branches`, {
      headers: { 'Accept': 'application/json' }
    });
    
    const json = await response.json(); 
    
    // ✅ FIX: Navigate the nested structure (success -> data -> branches)
    if (json.success && json.data && Array.isArray(json.data.branches)) {
      const branchesList = json.data.branches;
      setBranches(branchesList);
      
      // Auto-select first branch
      if (branchesList.length > 0) {
        setFormData(prev => ({
          ...prev,
          preferredBranchId: branchesList[0].id,
        }));
      }
    }
  } catch (error) {
    console.error('Error fetching branches:', error);
    // Fallback for development
    setBranches([
      { id: 1, name: 'Sibulan Branch', city: 'Sibulan' },
      { id: 2, name: 'Dumaguete Branch', city: 'Dumaguete' },
    ]);
  } finally {
    setLoadingBranches(false);
  }
};

  const updateField = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: null }));
    }
  };

  const validate = () => {
    const newErrors = {};
    if (!formData.name.trim()) newErrors.name = 'Full name is required';
    if (!formData.email.trim()) newErrors.email = 'Email is required';
    if (!formData.phone.trim()) newErrors.phone = 'Phone number is required';
    if (formData.password.length < 8) newErrors.password = 'Password must be 8+ characters';
    if (formData.password !== formData.passwordConfirmation) {
      newErrors.passwordConfirmation = 'Passwords do not match';
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleRegister = async () => {
    // 1. Immediate check to prevent stacking interactions
    if (loading || !validate()) return;

    // 2. Visual feedback occurs immediately on the main thread
    setLoading(true);

    // 3. Defer heavy processing to the next tick to improve INP
    setTimeout(async () => {
      const payload = {
        name: formData.name,
        email: formData.email,
        password: formData.password,
        password_confirmation: formData.passwordConfirmation,
        phone: formData.phone,
        address: formData.address,
        preferred_branch_id: formData.preferredBranchId,
      };

      try {
        const response = await fetch(`${API_BASE_URL}/v1/register`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (response.ok && data.success) {
          // Clear storage and prepare for a clean login
          await AsyncStorage.multiRemove([STORAGE_KEYS.TOKEN, STORAGE_KEYS.CUSTOMER]);

          Alert.alert(
            'Success', 
            'Account created successfully! Please sign in.', 
            [
              { 
                text: 'Sign In Now', 
                onPress: () => router.replace('/(auth)/login') 
              }
            ],
            { cancelable: false }
          );

          // Web fallback: Ensures redirect happens even if Alert is blocked
          if (Platform.OS === 'web') {
             setTimeout(() => router.replace('/(auth)/login'), 1500);
          }
        } else {
          const errorMsg = data.errors ? Object.values(data.errors).flat().join('\n') : data.message;
          Alert.alert('Registration Failed', errorMsg);
        }
      } catch (error) {
        console.error('Registration error:', error);
        Alert.alert('Error', 'Unable to reach the server. Check your connection.');
      } finally {
        // Final UI update
        setLoading(false);
      }
    }, 0); // Yields the thread for the browser to paint the 'loading' state
  };

  return (
    <>
      <StatusBar style="dark" />
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.container}
      >
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {/* Header */}
          <View style={styles.header}>
            <View style={styles.logoContainer}>
              <Text style={styles.logoEmoji}>🧺</Text>
              <Text style={styles.logoText}>WASHBOX</Text>
            </View>
            <Text style={styles.subtitle}>Create Your Account</Text>
            <Text style={styles.welcomeText}>Join us today!</Text>
          </View>

          {/* Registration Form */}
          <View style={styles.form}>
            {/* Full Name */}
            <View style={styles.inputContainer}>
              <Text style={styles.label}>Full Name *</Text>
              <View style={styles.inputWrapper}>
                <Ionicons name="person-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                <TextInput
                  style={[styles.input, errors.name && styles.inputError]}
                  placeholder="Juan Dela Cruz"
                  placeholderTextColor="#9CA3AF"
                  value={formData.name}
                  onChangeText={(text) => updateField('name', text)}
                  editable={!loading}
                />
              </View>
              {errors.name && <Text style={styles.errorText}>{errors.name}</Text>}
            </View>

            {/* Email */}
            <View style={styles.inputContainer}>
              <Text style={styles.label}>Email Address *</Text>
              <View style={styles.inputWrapper}>
                <Ionicons name="mail-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                <TextInput
                  style={[styles.input, errors.email && styles.inputError]}
                  placeholder="your.email@example.com"
                  placeholderTextColor="#9CA3AF"
                  value={formData.email}
                  onChangeText={(text) => updateField('email', text)}
                  keyboardType="email-address"
                  autoCapitalize="none"
                  autoCorrect={false}
                  editable={!loading}
                />
              </View>
              {errors.email && <Text style={styles.errorText}>{errors.email}</Text>}
            </View>

            {/* Phone */}
            <View style={styles.inputContainer}>
              <Text style={styles.label}>Phone Number *</Text>
              <View style={styles.inputWrapper}>
                <Ionicons name="call-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                <TextInput
                  style={[styles.input, errors.phone && styles.inputError]}
                  placeholder="09171234567"
                  placeholderTextColor="#9CA3AF"
                  value={formData.phone}
                  onChangeText={(text) => updateField('phone', text)}
                  keyboardType="phone-pad"
                  maxLength={11}
                  editable={!loading}
                />
              </View>
              {errors.phone && <Text style={styles.errorText}>{errors.phone}</Text>}
            </View>

            {/* Password */}
            <View style={styles.inputContainer}>
              <Text style={styles.label}>Password *</Text>
              <View style={styles.inputWrapper}>
                <Ionicons name="lock-closed-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                <TextInput
                  style={[styles.input, styles.passwordInput, errors.password && styles.inputError]}
                  placeholder="At least 8 characters"
                  placeholderTextColor="#9CA3AF"
                  value={formData.password}
                  onChangeText={(text) => updateField('password', text)}
                  secureTextEntry={!showPassword}
                  editable={!loading}
                />
                <TouchableOpacity
                  style={styles.eyeButton}
                  onPress={() => setShowPassword(!showPassword)}
                >
                  <Ionicons
                    name={showPassword ? 'eye-outline' : 'eye-off-outline'}
                    size={20}
                    color="#9CA3AF"
                  />
                </TouchableOpacity>
              </View>
              {errors.password && <Text style={styles.errorText}>{errors.password}</Text>}
              <Text style={styles.hintText}>Must contain uppercase, lowercase, and number</Text>
            </View>

            {/* Confirm Password */}
            <View style={styles.inputContainer}>
              <Text style={styles.label}>Confirm Password *</Text>
              <View style={styles.inputWrapper}>
                <Ionicons name="lock-closed-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                <TextInput
                  style={[styles.input, styles.passwordInput, errors.passwordConfirmation && styles.inputError]}
                  placeholder="Re-enter your password"
                  placeholderTextColor="#9CA3AF"
                  value={formData.passwordConfirmation}
                  onChangeText={(text) => updateField('passwordConfirmation', text)}
                  secureTextEntry={!showConfirmPassword}
                  editable={!loading}
                />
                <TouchableOpacity
                  style={styles.eyeButton}
                  onPress={() => setShowConfirmPassword(!showConfirmPassword)}
                >
                  <Ionicons
                    name={showConfirmPassword ? 'eye-outline' : 'eye-off-outline'}
                    size={20}
                    color="#9CA3AF"
                  />
                </TouchableOpacity>
              </View>
              {errors.passwordConfirmation && (
                <Text style={styles.errorText}>{errors.passwordConfirmation}</Text>
              )}
            </View>

            {/* Address */}
            <View style={styles.inputContainer}>
              <Text style={styles.label}>Address (Optional)</Text>
              <View style={styles.inputWrapper}>
                <Ionicons name="location-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder="123 Main Street, Dumaguete City"
                  placeholderTextColor="#9CA3AF"
                  value={formData.address}
                  onChangeText={(text) => updateField('address', text)}
                  multiline
                  numberOfLines={2}
                  editable={!loading}
                />
              </View>
            </View>

            {/* Preferred Branch Picker */}
            <View style={styles.inputContainer}>
              <Text style={styles.label}>Preferred Branch</Text>
              <View style={styles.pickerContainer}>
                <Ionicons name="business-outline" size={20} color="#9CA3AF" style={styles.pickerIcon} />
                {loadingBranches ? (
                  <ActivityIndicator size="small" color="#3D3B6B" style={{ marginLeft: 20 }} />
                ) : (
                  <Picker
                    selectedValue={formData.preferredBranchId}
                    onValueChange={(value) => updateField('preferredBranchId', value)}
                    style={styles.picker}
                  >
                    {branches.map((branch) => (
                      <Picker.Item key={branch.id} label={`${branch.name} - ${branch.city}`} value={branch.id} />
                    ))}
                  </Picker>
                )}
              </View>
            </View>

            {/* Register Button */}
            <TouchableOpacity
              style={[styles.registerButton, loading && styles.registerButtonDisabled]}
              onPress={handleRegister}
              disabled={loading}
            >
              {loading ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <>
                  <Ionicons name="person-add-outline" size={20} color="#FFFFFF" style={{ marginRight: 8 }} />
                  <Text style={styles.registerButtonText}>Create Account</Text>
                </>
              )}
            </TouchableOpacity>

            {/* Terms */}
            <Text style={styles.termsText}>
              By creating an account, you agree to our{' '}
              <Text style={styles.termsLink}>Terms of Service</Text> and{' '}
              <Text style={styles.termsLink}>Privacy Policy</Text>
            </Text>

            {/* Login Link */}
            <View style={styles.loginContainer}>
              <Text style={styles.loginText}>Already have an account? </Text>
              <Link href="/(auth)/login" asChild>
                <TouchableOpacity disabled={loading}>
                  <Text style={styles.loginLink}>Sign In</Text>
                </TouchableOpacity>
              </Link>
            </View>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  centerContent: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: 24,
    paddingVertical: 40,
  },
  loadingText: {
    marginTop: 16,
    color: '#6B7280',
    fontSize: 16,
  },
  header: {
    alignItems: 'center',
    marginBottom: 32,
  },
  logoContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  logoEmoji: {
    fontSize: 40,
    marginRight: 8,
  },
  logoText: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#3D3B6B',
    letterSpacing: 2,
  },
  subtitle: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 16,
  },
  welcomeText: {
    fontSize: 20,
    fontWeight: '600',
    color: '#1F2937',
  },
  form: {
    flex: 1,
  },
  inputContainer: {
    marginBottom: 16,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 8,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 12,
    backgroundColor: '#F9FAFB',
  },
  inputIcon: {
    marginLeft: 16,
  },
  input: {
    flex: 1,
    padding: 16,
    fontSize: 16,
    color: '#1F2937',
  },
  inputError: {
    borderColor: '#EF4444',
  },
  passwordInput: {
    paddingRight: 50,
  },
  eyeButton: {
    position: 'absolute',
    right: 16,
    padding: 8,
  },
  errorText: {
    color: '#EF4444',
    fontSize: 12,
    marginTop: 4,
  },
  hintText: {
    color: '#9CA3AF',
    fontSize: 12,
    marginTop: 4,
  },
  pickerContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 12,
    backgroundColor: '#F9FAFB',
    overflow: 'hidden',
  },
  pickerIcon: {
    marginLeft: 16,
  },
  picker: {
    flex: 1,
    height: 50,
  },
  registerButton: {
    backgroundColor: '#3D3B6B',
    borderRadius: 12,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 8,
    marginBottom: 16,
    shadowColor: '#3D3B6B',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  registerButtonDisabled: {
    opacity: 0.6,
  },
  registerButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  termsText: {
    textAlign: 'center',
    color: '#6B7280',
    fontSize: 12,
    marginBottom: 24,
  },
  termsLink: {
    color: '#3D3B6B',
    fontWeight: '600',
  },
  loginContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    paddingBottom: 24,
  },
  loginText: {
    color: '#6B7280',
    fontSize: 14,
  },
  loginLink: {
    color: '#3D3B6B',
    fontSize: 14,
    fontWeight: 'bold',
  },
});