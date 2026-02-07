<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\PickupRequest;
use App\Services\RouteService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogisticsController extends Controller
{
    protected $routeService;

    public function __construct(RouteService $routeService = null)
    {
        $this->routeService = $routeService ?? new RouteService();
    }

    /**
     * Get route from branch to pickup using ACTUAL ROADS (OSRM)
     */
    public function getPickupRoute(PickupRequest $pickup, Request $request)
    {
        try {
            $branch = $pickup->branch ?? Branch::first();

            if (!$branch || !$pickup->latitude || !$pickup->longitude) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid coordinates'
                ], 400);
            }

            // Use RouteService for actual road-following routes
            $route = $this->routeService->getRouteFromBranch($pickup, 'osrm');

            if (!$route['success']) {
                throw new \Exception($route['error'] ?? 'Route calculation failed');
            }

            return response()->json([
                'success' => true,
                'route' => $route['route'],
                'instructions' => $route['instructions'] ?? [],
                'provider' => $route['provider'],
                'estimated_arrival' => $route['estimated_arrival']
            ]);

        } catch (\Exception $e) {
            Log::error('Route calculation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get directions with turn-by-turn instructions
     */
    public function getDirections(PickupRequest $pickup)
    {
        try {
            $branch = $pickup->branch ?? Branch::first();

            if (!$branch || !$pickup->latitude || !$pickup->longitude) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid coordinates'
                ], 400);
            }

            // Get route with detailed instructions from OSRM
            $route = $this->routeService->getRouteFromBranch($pickup, 'osrm');

            if (!$route['success']) {
                throw new \Exception($route['error'] ?? 'Route calculation failed');
            }

            return response()->json([
                'success' => true,
                'pickup' => [
                    'id' => $pickup->id,
                    'customer' => $pickup->customer->name ?? 'Customer',
                    'address' => $pickup->pickup_address
                ],
                'branch' => [
                    'name' => $branch->name,
                    'address' => $branch->address
                ],
                'distance' => $route['route']['distance']['text'],
                'duration' => $route['route']['duration']['text'],
                'instructions' => $route['instructions'],
                'provider' => $route['provider'],
                'estimated_arrival' => $route['estimated_arrival']
            ]);

        } catch (\Exception $e) {
            Log::error('Directions request failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start navigation for pickup
     */
    public function startNavigation(PickupRequest $pickup, Request $request)
    {
        try {
            $pickup->update([
                'status' => 'en_route',
                'en_route_at' => now(),
                'assigned_to' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Navigation started for pickup #' . $pickup->id,
                'pickup' => $pickup->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Navigation start failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get optimized route for multiple pickups using OSRM
     */
    public function getOptimizedRoute(Request $request)
    {
        try {
            $pickupIds = $request->input('pickup_ids', []);
            $branchId = $request->input('branch_id') ?? Branch::first()->id;

            if (empty($pickupIds)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No pickups selected'
                ], 400);
            }

            // Use RouteService for optimized multi-stop routes
            $optimization = $this->routeService->getOptimizedRoute($branchId, $pickupIds, 'osrm');

            if (!$optimization['success']) {
                throw new \Exception($optimization['error'] ?? 'Route optimization failed');
            }

            return response()->json($optimization);

        } catch (\Exception $e) {
            Log::error('Route optimization failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active delivery routes
     */
    public function getActiveRoutes()
    {
        try {
            $pickups = PickupRequest::with(['customer', 'branch', 'assignedStaff'])
                ->whereIn('status', ['accepted', 'en_route'])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get();

            // Group by assigned driver
            $routes = $pickups->groupBy('assigned_to')->map(function($driverPickups, $driverId) {
                return [
                    'driver_id' => $driverId,
                    'pickup_count' => $driverPickups->count(),
                    'pickups' => $driverPickups->map(function($pickup) {
                        return [
                            'id' => $pickup->id,
                            'customer' => $pickup->customer->name,
                            'address' => $pickup->pickup_address,
                            'latitude' => $pickup->latitude,
                            'longitude' => $pickup->longitude,
                            'status' => $pickup->status
                        ];
                    })
                ];
            })->values();

            return response()->json([
                'success' => true,
                'routes' => $routes,
                'total_pickups' => $pickups->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Active routes fetch failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update vehicle location for real-time tracking
     */
    public function updateVehicleLocation(Request $request)
    {
        try {
            $validated = $request->validate([
                'pickup_id' => 'required|exists:pickup_requests,id',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric'
            ]);

            $pickup = PickupRequest::findOrFail($validated['pickup_id']);

            $pickup->update([
                'current_latitude' => $validated['latitude'],
                'current_longitude' => $validated['longitude'],
                'last_location_update' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated'
            ]);

        } catch (\Exception $e) {
            Log::error('Location update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate straight-line distance (Haversine) as fallback
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta/2) * sin($latDelta/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta/2) * sin($lonDelta/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * Generate polyline from route geometry
     */
    private function generatePolylineFromRoute($geometry)
    {
        // If geometry is already an array of coordinates, return it
        if (is_array($geometry)) {
            return $geometry;
        }

        // If it's a polyline string, decode it (OSRM returns polyline6 format)
        return $this->decodePolyline($geometry);
    }

    /**
     * Decode polyline (for polyline6 format from OSRM)
     */
    private function decodePolyline($encoded)
    {
        $points = [];
        $index = $len = 0;
        $lat = $lng = 0;

        while ($index < strlen($encoded)) {
            $b = $shift = $result = 0;

            do {
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lat += $dlat;

            $shift = $result = 0;

            do {
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lng += $dlng;

            $points[] = [
                'latitude' => $lat * 1e-5,
                'longitude' => $lng * 1e-5
            ];
        }

        return $points;
    }
}
