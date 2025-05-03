<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelListing;
use App\Models\PackageListing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ListingManagementController extends Controller
{
    /**
     * Get all listings
     */
    public function getAllListings(Request $request)
    {
        // Vérifier manuellement si l'utilisateur est un admin
        if (!$request->user() || $request->user()->username !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }
        $type = $request->type ?? 'all';
        $active = $request->has('active') ? $request->active : null;

        if ($type === 'travel' || $type === 'all') {
            $travelQuery = TravelListing::with(['user', 'departureLocation', 'destinationLocation']);
            
            if (isset($active)) {
                $travelQuery->where('active', $active);
            }
            
            $travelListings = $travelQuery->latest()->get();
        } else {
            $travelListings = collect();
        }

        if ($type === 'package' || $type === 'all') {
            $packageQuery = PackageListing::with(['user', 'pickupLocation', 'deliveryLocation']);
            
            if (isset($active)) {
                $packageQuery->where('active', $active);
            }
            
            $packageListings = $packageQuery->latest()->get();
        } else {
            $packageListings = collect();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'travel' => $type === 'travel' || $type === 'all' ? $travelListings : null,
                'package' => $type === 'package' || $type === 'all' ? $packageListings : null,
            ],
        ]);
    }

    /**
     * Toggle listing active status
     */
    public function toggleListingStatus(Request $request)
    {
        // Vérifier manuellement si l'utilisateur est un admin
        if (!$request->user() || $request->user()->username !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:travel,package',
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->type === 'travel') {
            $listing = TravelListing::findOrFail($request->id);
        } else {
            $listing = PackageListing::findOrFail($request->id);
        }

        $listing->active = !$listing->active;
        $listing->save();

        return response()->json([
            'success' => true,
            'data' => $listing,
            'message' => $listing->active ? 'Listing activated' : 'Listing deactivated',
        ]);
    }

    /**
     * Clean expired listings
     */
    public function cleanExpiredListings(Request $request)
    {
        // Vérifier manuellement si l'utilisateur est un admin
        if (!$request->user() || $request->user()->username !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }
        $now = Carbon::now();

        $expiredTravelCount = TravelListing::where('active', true)
            ->where('departure_date', '<', $now)
            ->update(['active' => false]);

        $expiredPackageCount = PackageListing::where('active', true)
            ->where('needed_by', '<', $now)
            ->update(['active' => false]);

        return response()->json([
            'success' => true,
            'data' => [
                'expired_travel' => $expiredTravelCount,
                'expired_package' => $expiredPackageCount,
            ],
            'message' => 'Expired listings have been deactivated',
        ]);
    }
}