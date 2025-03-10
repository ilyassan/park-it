<?php

namespace Database\Seeders;

use App\Models\Parking;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::clients()->get();
        $parkings = Parking::all();

        foreach ($parkings as $parking) {
            $maxOverlapping = $parking->limit;

            $groupCount = 5;
            $currentDateTime = Carbon::now()->subDays(2)->startOfHour();

            for ($g = 0; $g < $groupCount; $g++) {
                $groupStart = $currentDateTime->copy();
                $groupEnd = $currentDateTime->copy()->addHours(2);

                for ($i = 0; $i < $maxOverlapping; $i++) {
                    Reservation::create([
                        'from_date' => $groupStart->format('Y-m-d H:00:00'),
                        'to_date' => $groupEnd->format('Y-m-d H:00:00'),
                        'user_id' => $clients->random()->id,
                        'parking_id' => $parking->id,
                    ]);
                }

                $currentDateTime->addHours(4);
            }

            $nonOverlappingCount = 10;
            for ($n = 0; $n < $nonOverlappingCount; $n++) {
                $start = $currentDateTime->copy()->addHours($n * 3);
                $end = $start->copy()->addHour();

                Reservation::create([
                    'from_date' => $start->format('Y-m-d H:00:00'),
                    'to_date' => $end->format('Y-m-d H:00:00'),
                    'user_id' => $clients->random()->id,
                    'parking_id' => $parking->id,
                ]);
            }
        }
    }
}