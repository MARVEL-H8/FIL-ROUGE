<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\PackageListing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageListingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PackageListing::with(['user', 'pickupLocation', 'deliveryLocation'])
            ->where('active', true)
            ->where('needed_by', '>=', Carbon::now());
        
        // Filtres possibles
        // Déboguer les filtres reçus
        \Log::info('Filtres reçus dans PackageListingController:', $request->all());
        
        // Filtre par ville de ramassage
        if ($request->has('pickup_city') && !empty($request->pickup_city)) {
            $query->whereHas('pickupLocation', function($q) use ($request) {
                $q->where('city', 'like', '%' . $request->pickup_city . '%');
            });
        }
        
        // Filtre par pays de ramassage
        if ($request->has('pickup_country') && !empty($request->pickup_country)) {
            $query->whereHas('pickupLocation', function($q) use ($request) {
                $q->where('country', 'like', '%' . $request->pickup_country . '%');
            });
        }
        
        // Filtre par ville de livraison
        if ($request->has('delivery_city') && !empty($request->delivery_city)) {
            $query->whereHas('deliveryLocation', function($q) use ($request) {
                $q->where('city', 'like', '%' . $request->delivery_city . '%');
            });
        }
        
        // Filtre par pays de livraison
        if ($request->has('delivery_country') && !empty($request->delivery_country)) {
            $query->whereHas('deliveryLocation', function($q) use ($request) {
                $q->where('country', 'like', '%' . $request->delivery_country . '%');
            });
        }
        
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('needed_by', '<=', $request->date_to);
        }
        
        if ($request->has('max_weight') && !empty($request->max_weight)) {
            $query->where('weight', '<=', $request->max_weight);
        }
        
        $listings = $query->latest()->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => [
                'listings' => $listings
            ]
        ]);
    }

    /**
     * Get all listings for the authenticated user including inactive ones.
     */
    public function myListings(Request $request)
    {
        $user = $request->user();
        $listings = $user->packageListings()
            ->with(['pickupLocation', 'deliveryLocation'])
            ->latest()
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'listings' => $listings
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup.city' => 'required|string|max:255',
            'pickup.country' => 'required|string|max:255',
            'delivery.city' => 'required|string|max:255',
            'delivery.country' => 'required|string|max:255',
            'needed_by' => 'required|date|after_or_equal:today',
            'weight' => 'required|numeric|min:0|max:20',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'description' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Créer ou trouver les emplacements
        $pickupLocation = Location::firstOrCreate([
            'city' => $request->pickup['city'],
            'country' => $request->pickup['country'],
        ]);

        $deliveryLocation = Location::firstOrCreate([
            'city' => $request->delivery['city'],
            'country' => $request->delivery['country'],
        ]);

        // Créer l'annonce
        $packageListing = PackageListing::create([
            'user_id' => auth()->id(),
            'pickup_location_id' => $pickupLocation->id,
            'delivery_location_id' => $deliveryLocation->id,
            'needed_by' => $request->needed_by,
            'weight' => $request->weight,
            'width' => $request->width,
            'height' => $request->height,
            'length' => $request->length,
            'description' => $request->description,
            'notes' => $request->notes,
            'active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $packageListing->load(['user', 'pickupLocation', 'deliveryLocation']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $packageListing = PackageListing::with(['user', 'pickupLocation', 'deliveryLocation'])
            ->findOrFail($id);
            
        return response()->json([
            'success' => true,
            'data' => $packageListing,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $packageListing = PackageListing::findOrFail($id);
        
        // Vérifier que l'utilisateur est le propriétaire
        if ($packageListing->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'pickup.city' => 'sometimes|required|string|max:255',
            'pickup.country' => 'sometimes|required|string|max:255',
            'delivery.city' => 'sometimes|required|string|max:255',
            'delivery.country' => 'sometimes|required|string|max:255',
            'needed_by' => 'sometimes|required|date|after_or_equal:today',
            'weight' => 'sometimes|required|numeric|min:0|max:20',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'description' => 'sometimes|required|string',
            'notes' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Mettre à jour les emplacements si nécessaire
        if ($request->has('pickup')) {
            $pickupLocation = Location::updateOrCreate(
                [
                    'id' => $packageListing->pickup_location_id,
                ],
                [
                    'city' => $request->pickup['city'],
                    'country' => $request->pickup['country'],
                ]
            );
        }

        if ($request->has('delivery')) {
            $deliveryLocation = Location::updateOrCreate(
                [
                    'id' => $packageListing->delivery_location_id,
                ],
                [
                    'city' => $request->delivery['city'],
                    'country' => $request->delivery['country'],
                ]
            );
        }

        // Mettre à jour l'annonce
        $packageListing->update([
            'needed_by' => $request->needed_by ?? $packageListing->needed_by,
            'weight' => $request->weight ?? $packageListing->weight,
            'width' => $request->width,
            'height' => $request->height,
            'length' => $request->length,
            'description' => $request->description ?? $packageListing->description,
            'notes' => $request->notes ?? $packageListing->notes,
            'active' => $request->has('active') ? $request->active : $packageListing->active,
        ]);

        return response()->json([
            'success' => true,
            'data' => $packageListing->load(['user', 'pickupLocation', 'deliveryLocation']),
        ]);
    }

    /**
     * Toggle active status of the listing.
     */
    public function toggleActive(string $id)
    {
        $packageListing = PackageListing::findOrFail($id);
        
        // Vérifier que l'utilisateur est le propriétaire
        if ($packageListing->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        $packageListing->active = !$packageListing->active;
        $packageListing->save();
        
        return response()->json([
            'success' => true,
            'data' => $packageListing,
            'message' => $packageListing->active ? 'Listing activated' : 'Listing deactivated',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $packageListing = PackageListing::findOrFail($id);
        
        // Vérifier que l'utilisateur est le propriétaire
        if ($packageListing->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        $packageListing->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Package listing deleted successfully',
        ]);
    }
}
