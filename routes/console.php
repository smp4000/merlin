<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('merlin:registrations:purge')
    ->dailyAt('02:30')
    ->withoutOverlapping();
