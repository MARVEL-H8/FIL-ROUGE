<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\TravelListing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TravelListingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TravelListing::with(['user', 'departureLocation', 'destinationLocation'])
            ->where('active', true)
            ->where('departure_date', '>=', Carbon::now());
        
        // Filtres possibles
        // Déboguer les filtres reçus
        \Log::info('Filtres reçus dans TravelListingController:', $request->all());
        
        // Filtre par ville de départ
        if ($request->has('departure_city') && !empty($request->departure_city)) {
            $query->whereHas('departureLocation', function($q) use ($request) {
                $q->where('city', 'like', '%' . $request->departure_city . '%');
            });
        }
        
        // Filtre par pays de départ
        if ($request->has('departure_country') && !empty($request->departure_country)) {
            $query->whereHas('departureLocation', function($q) use ($request) {
                $q->where('country', 'like', '%' . $request->departure_country . '%');
            });
        }
        
        // Filtre par ville de destination
        if ($request->has('destination_city') && !empty($request->destination_city)) {
            $query->whereHas('destinationLocation', function($q) use ($request) {
                $q->where('city', 'like', '%' . $request->destination_city . '%');
            });
        }
        
        // Filtre par pays de destination
        if ($request->has('destination_country') && !empty($request->destination_country)) {
            $query->whereHas('destinationLocation', function($q) use ($request) {
                $q->where('country', 'like', '%' . $request->destination_country . '%');
            });
        }
        
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->where('departure_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('departure_date', '<=', $request->date_to);
        }
        
        if ($request->has('min_weight') && !empty($request->min_weight)) {
            $query->where('available_weight', '>=', $request->min_weight);
        }
        
        $listings = $query->latest()->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * Get all listings for the authenticated user including inactive ones.
     */
    public function myListings(Request $request)
    {
        $user = $request->user();
        $listings = $user->travelListings()
            ->with(['departureLocation', 'destinationLocation'])
            ->latest()
            ->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'departure.city' => 'required|string|max:255',
            'departure.country' => 'required|string|max:255',
            'departure.airport' => 'nullable|string|max:255',
            'destination.city' => 'required|string|max:255',
            'destination.country' => 'required|string|max:255',
            'destination.airport' => 'nullable|string|max:255',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
            'available_weight' => 'required|numeric|min:0|max:30',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Créer ou trouver les emplacements
        $departureLocation = Location::firstOrCreate([
            'city' => $request->departure['city'],
            'country' => $request->departure['country'],
        ], [
            'airport' => $request->departure['airport'] ?? null,
        ]);

        $destinationLocation = Location::firstOrCreate([
            'city' => $request->destination['city'],
            'country' => $request->destination['country'],
        ], [
            'airport' => $request->destination['airport'] ?? null,
        ]);

        // Créer l'annonce
        $travelListing = TravelListing::create([
            'user_id' => auth()->id(),
            'departure_location_id' => $departureLocation->id,
            'destination_location_id' => $destinationLocation->id,
            'departure_date' => $request->departure_date,
            'return_date' => $request->return_date,
            'available_weight' => $request->available_weight,
            'notes' => $request->notes,
            'active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $travelListing->load(['user', 'departureLocation', 'destinationLocation']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $travelListing = TravelListing::with(['user', 'departureLocation', 'destinationLocation'])
            ->findOrFail($id);
            
        return response()->json([
            'success' => true,
            'data' => $travelListing,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $travelListing = TravelListing::findOrFail($id);
        
        // Vérifier que l'utilisateur est le propriétaire
        if ($travelListing->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'departure.city' => 'sometimes|required|string|max:255',
            'departure.country' => 'sometimes|required|string|max:255',
            'departure.airport' => 'nullable|string|max:255',
            'destination.city' => 'sometimes|required|string|max:255',
            'destination.country' => 'sometimes|required|string|max:255',
            'destination.airport' => 'nullable|string|max:255',
            'departure_date' => 'sometimes|required|date|after_or_equal:today',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
            'available_weight' => 'sometimes|required|numeric|min:0|max:30',
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
        if ($request->has('departure')) {
            $departureLocation = Location::updateOrCreate(
                [
                    'id' => $travelListing->departure_location_id,
                ],
                [
                    'city' => $request->departure['city'],
                    'country' => $request->departure['country'],
                    'airport' => $request->departure['airport'] ?? null,
                ]
            );
        }

        if ($request->has('destination')) {
            $destinationLocation = Location::updateOrCreate(
                [
                    'id' => $travelListing->destination_location_id,
                ],
                [
                    'city' => $request->destination['city'],
                    'country' => $request->destination['country'],
                    'airport' => $request->destination['airport'] ?? null,
                ]
            );
        }

        // Mettre à jour l'annonce
        $travelListing->update([
            'departure_date' => $request->departure_date ?? $travelListing->departure_date,
            'return_date' => $request->return_date,
            'available_weight' => $request->available_weight ?? $travelListing->available_weight,
            'notes' => $request->notes ?? $travelListing->notes,
            'active' => $request->has('active') ? $request->active : $travelListing->active,
        ]);

        return response()->json([
            'success' => true,
            'data' => $travelListing->load(['user', 'departureLocation', 'destinationLocation']),
        ]);
    }

    /**
     * Toggle active status of the listing.
     */
    public function toggleActive(string $id)
    {
        $travelListing = TravelListing::findOrFail($id);
        
        // Vérifier que l'utilisateur est le propriétaire
        if ($travelListing->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        $travelListing->active = !$travelListing->active;
        $travelListing->save();
        
        return response()->json([
            'success' => true,
            'data' => $travelListing,
            'message' => $travelListing->active ? 'Listing activated' : 'Listing deactivated',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $travelListing = TravelListing::findOrFail($id);
        
        // Vérifier que l'utilisateur est le propriétaire
        if ($travelListing->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        $travelListing->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Travel listing deleted successfully',
        ]);
    }
}
