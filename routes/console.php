<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('meucash:alertas')->dailyAt('07:00');
