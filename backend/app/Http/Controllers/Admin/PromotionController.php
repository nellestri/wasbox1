<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class PromotionController extends Controller
{
    /**
     * Display promotion listing
     */
    public function index()
    {
        $promotions = Promotion::with('branch')->latest()->paginate(12);
        $now = now();

        $stats = [
    'total' => Promotion::count(),
    'active' => Promotion::where('is_active', true)
        ->where('start_date', '<=', now())
        ->where('end_date', '>=', now())
        ->count(),
    'scheduled' => Promotion::where('is_active', true)
        ->where('start_date', '>', now())
        ->count(),
    'expired' => Promotion::where('end_date', '<', now())->count(),
    'total_usage' => Promotion::sum('usage_count'), // Add this line
];

        return view('admin.promotions.index', compact('promotions', 'stats'));
    }

    /**
     * Show form for creating promotion
     * Supports both simple and poster mode
     */
    public function create(Request $request)
    {
        $branches = Branch::where('is_active', true)->get();
        $mode = $request->query('mode', 'simple'); // 'simple' or 'poster'

        // Return poster designer if mode=poster
        if ($mode === 'poster') {
            return view('admin.promotions.create-poster', compact('branches'));
        }

        // Existing simple discount form
        return view('admin.promotions.create', compact('branches'));
    }

    /**
     * Store a new promotion
     * Enhanced to handle both simple and poster promotions
     */
    public function store(Request $request)
    {
        // Detect if poster promotion
        $isPoster = $request->input('type') === 'poster_promo' || $request->has('poster_title');

        if ($isPoster) {
            return $this->storePosterPromotion($request);
        }

        // Existing simple discount logic
        return $this->storeSimplePromotion($request);
    }

    /**
     * Store simple percentage discount promotion
     */
    private function storeSimplePromotion(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'discount_percent' => 'required|numeric|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'promo_code' => 'nullable|string|unique:promotions,promo_code',
            'banner_image' => 'nullable|image|max:2048',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $data = [
            'name' => $request->name,
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => $request->discount_percent],
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'promo_code' => $request->promo_code,
            'branch_id' => $request->branch_id,
            'is_active' => true,
        ];

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('promotions', 'public');
        }

        Promotion::create($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion deployed to branches.');
    }

    /**
     * Store poster-style promotion (FIXED - Checkbox handling)
     */
    private function storePosterPromotion(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'poster_title' => 'required|string|max:255',
            'poster_subtitle' => 'nullable|string|max:255',
            'display_price' => 'required|numeric|min:0',
            'price_unit' => 'required|string|max:255',
            'poster_features' => 'nullable|array',
            'poster_features.*' => 'nullable|string',
            'poster_notes' => 'nullable|string',
            'color_theme' => 'required|string|in:blue,purple,green',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'promo_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            // ✅ NO is_active validation - handled manually below
        ]);

        // Filter out empty features
        $features = array_filter($request->input('poster_features', []), fn($f) => !empty($f));

        $data = [
            'name' => $request->name,
            'type' => 'poster_promo',
            'poster_title' => $request->poster_title,
            'poster_subtitle' => $request->poster_subtitle,
            'display_price' => $request->display_price,
            'price_unit' => $request->price_unit,
            'poster_features' => array_values($features), // Re-index array
            'poster_notes' => $request->poster_notes,
            'color_theme' => $request->color_theme,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'branch_id' => $request->branch_id,
            'promo_code' => $request->promo_code,
            'description' => $request->description,
            'is_active' => $request->has('is_active'), // ✅ Checkbox: true if checked, false if not
            'pricing_data' => [], // Empty for poster promos
        ];

        if ($request->hasFile('background_image')) {
            $data['banner_image'] = $request->file('background_image')->store('promotions/backgrounds', 'public');
        }

        Promotion::create($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Poster promotion created successfully!');
    }

    /**
     * Show the form for editing (ENHANCED)
     */
    public function edit($id)
    {
        $promotion = Promotion::findOrFail($id);
        $branches = Branch::where('is_active', true)->get();

        // Use poster edit view for poster promotions
        if ($promotion->isPosterPromotion()) {
            return view('admin.promotions.edit-poster', compact('promotion', 'branches'));
        }

        // Existing simple discount edit
        return view('admin.promotions.edit', compact('promotion', 'branches'));
    }

    /**
     * Update the specified promotion (FIXED - Routes to correct method)
     */
    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        // Check if poster promotion and route to correct method
        if ($promotion->isPosterPromotion()) {
            return $this->updatePosterPromotion($request, $promotion);
        }

        // Otherwise update as simple promotion
        return $this->updateSimplePromotion($request, $promotion);
    }

    /**
     * Update simple promotion
     */
    private function updateSimplePromotion(Request $request, Promotion $promotion)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'discount_percent' => 'required|numeric|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'promo_code' => 'nullable|string|unique:promotions,promo_code,' . $promotion->id,
            'banner_image' => 'nullable|image|max:2048',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $data = [
            'name' => $request->name,
            'pricing_data' => ['percentage' => $request->discount_percent],
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'promo_code' => $request->promo_code,
            'branch_id' => $request->branch_id,
        ];

        if ($request->hasFile('banner_image')) {
            if ($promotion->banner_image) {
                Storage::disk('public')->delete($promotion->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('promotions', 'public');
        }

        $promotion->update($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion updated successfully.');
    }

    /**
     * Update poster promotion (FIXED - Checkbox handling)
     */
    private function updatePosterPromotion(Request $request, Promotion $promotion)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'poster_title' => 'required|string|max:255',
            'poster_subtitle' => 'nullable|string|max:255',
            'display_price' => 'required|numeric|min:0',
            'price_unit' => 'required|string|max:255',
            'poster_features' => 'nullable|array',
            'poster_features.*' => 'nullable|string',
            'poster_notes' => 'nullable|string',
            'color_theme' => 'required|string|in:blue,purple,green',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'nullable|string',
            'promo_code' => 'nullable|string|max:50',
            // ✅ NO is_active validation - handled manually below
        ]);

        // Filter out empty features
        $features = array_filter($request->input('poster_features', []), fn($f) => !empty($f));

        $data = [
            'name' => $request->name,
            'poster_title' => $request->poster_title,
            'poster_subtitle' => $request->poster_subtitle,
            'display_price' => $request->display_price,
            'price_unit' => $request->price_unit,
            'poster_features' => array_values($features), // Re-index array
            'poster_notes' => $request->poster_notes,
            'color_theme' => $request->color_theme,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'branch_id' => $request->branch_id,
            'is_active' => $request->has('is_active'), // ✅ Checkbox: true if checked, false if not
            'description' => $request->description,
            'promo_code' => $request->promo_code,
        ];

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            // Delete old image
            if ($promotion->banner_image) {
                Storage::disk('public')->delete($promotion->banner_image);
            }
            $data['banner_image'] = $request->file('background_image')->store('promotions/backgrounds', 'public');
        }

        $promotion->update($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Poster promotion updated successfully!');
    }

    /**
     * Display the specified promotion
     */
    public function show($id)
    {
        $promotion = Promotion::with(['branch', 'usages.customer', 'usages.order'])
            ->findOrFail($id);

        return view('admin.promotions.show', compact('promotion'));
    }

    /**
     * Delete promotion
     */
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);

        // Delete associated images
        if ($promotion->banner_image) {
            Storage::disk('public')->delete($promotion->banner_image);
        }
        if ($promotion->generated_poster_path) {
            Storage::disk('public')->delete($promotion->generated_poster_path);
        }

        $promotion->delete();

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion deleted successfully!');
    }

    /**
     * Analytics dashboard
     */
    public function analytics()
    {
        $branchStats = DB::table('promotion_usages')
            ->join('promotions', 'promotion_usages.promotion_id', '=', 'promotions.id')
            ->join('branches', 'promotions.branch_id', '=', 'branches.id')
            ->select(
                'branches.name as branch_name',
                DB::raw('SUM(promotion_usages.final_amount) as total_revenue'),
                DB::raw('SUM(promotion_usages.discount_amount) as total_discounts'),
                DB::raw('COUNT(promotion_usages.id) as usage_count')
            )
            ->groupBy('branches.id', 'branches.name')
            ->get();

        return view('admin.promotions.analytics', compact('branchStats'));
    }

    /**
     * Toggle promotion active/inactive status
     */
    public function toggleStatus($id)
    {
        $promotion = Promotion::findOrFail($id);

        $promotion->update([
            'is_active' => !$promotion->is_active
        ]);

        $status = $promotion->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.promotions.index')
            ->with('success', "Promotion {$status} successfully!");
    }
}
