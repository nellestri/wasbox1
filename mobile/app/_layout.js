// app/_layout.js
import { useEffect } from 'react';
import { Stack, useRouter, useSegments } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { AuthProvider, useAuth } from '../context/AuthContext';
import { Platform } from 'react-native';

// Import web CSS only on web platform
if (Platform.OS === 'web') {
  require('../styles/globals.css');
}

// Prevent splash screen from auto-hiding
SplashScreen.preventAutoHideAsync();

function RootLayoutNav() {
  const { hasToken, isReady } = useAuth();
  const segments = useSegments();
  const router = useRouter();

  useEffect(() => {
    if (!isReady) return;

    const inAuthGroup = segments[0] === '(auth)';

    if (!hasToken && !inAuthGroup) {
      // Force unauthenticated users to login
      router.replace('/(auth)/login');
    } else if (hasToken && inAuthGroup) {
      // Redirect authenticated users away from login/register
      router.replace('/(tabs)');
    }
    
    SplashScreen.hideAsync();
  }, [hasToken, isReady, segments]);

  if (!isReady) return null;

  return (
    <Stack screenOptions={{ 
      headerShown: false,
      contentStyle: {
        backgroundColor: '#0A1128', // Your app's background color
      },
      animation: 'fade',
    }}>
      <Stack.Screen name="(auth)" />
      <Stack.Screen name="(tabs)" />
      {/* ✅ ADD ORDER DETAILS ROUTE */}
      <Stack.Screen 
        name="orders/[id]" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
      {/* ✅ ADD NOTIFICATIONS ROUTE */}
      <Stack.Screen 
        name="notifications" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
      {/* ✅ ADD ORDER CONFIRMATION ROUTE (for pickup) */}
      <Stack.Screen 
        name="order-confirm" 
        options={{
          presentation: 'modal',
          animation: 'slide_from_bottom',
        }}
      />
      {/* ✅ ADD PROFILE EDIT ROUTE */}
      <Stack.Screen 
        name="profile/edit" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
      {/* ✅ ADD PROMOTIONS ROUTE */}
      <Stack.Screen 
        name="promotions" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
    </Stack>
  );
}

export default function RootLayout() {
  return (
    <AuthProvider>
      <RootLayoutNav />
    </AuthProvider>
  );
}