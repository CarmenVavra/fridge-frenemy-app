<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fridge:send-alerts')
    ->dailyAt('08:00')
    ->timezone((string) config('app.timezone'))
    ->withoutOverlapping();
