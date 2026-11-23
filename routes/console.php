<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('trace:reconcile-payments')->everyTenMinutes()->withoutOverlapping();
