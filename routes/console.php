<?php

use Illuminate\Support\Facades\Schedule;

// Hostinger cron (hPanel → Advanced → Cron Jobs), every minute (INSTALL-HOSTINGER.md, "Cron"):
//   /usr/bin/php /home/USER/domains/environetinc.com/public_html/environet-app/artisan schedule:run
// Times are Pacific/Honolulu (APP_TIMEZONE).
Schedule::command('app:remind-expiring')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('app:prune-held-uploads')->hourly();
