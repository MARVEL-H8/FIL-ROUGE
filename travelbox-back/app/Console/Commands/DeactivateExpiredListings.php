<?php

namespace App\Console\Commands;

use App\Models\TravelListing;
use App\Models\PackageListing;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeactivateExpiredListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listings:deactivate-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate expired travel and package listings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Désactiver les annonces de voyage expirées
        $expiredTravelCount = TravelListing::where('active', true)
            ->where('departure_date', '<', $now)
            ->update(['active' => false]);

        $this->info("Deactivated {$expiredTravelCount} expired travel listings.");

        // Désactiver les annonces de colis expirées
        $expiredPackageCount = PackageListing::where('active', true)
            ->where('needed_by', '<', $now)
            ->update(['active' => false]);

        $this->info("Deactivated {$expiredPackageCount} expired package listings.");

        return Command::SUCCESS;
    }
}

