<?php

use App\Models\Reservation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    Reservation::where("end_date", "<", now())->update(["status" => "completed"]);
})->everyFifteenMinutes();