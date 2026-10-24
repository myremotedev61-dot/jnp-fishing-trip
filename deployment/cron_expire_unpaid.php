<?php
/**
 * CRON JOB STANDALONE: Pembatalan Otomatis Peserta Menunggu Panjar yang Kadaluarsa
 * Dapat dijalankan langsung via Linux crontab:
 * */30 * * * * /usr/bin/php /var/www/fishing_trip/deployment/cron_expire_unpaid.php >> /var/log/fishing_cron.log 2>&1
 * ATAU menggunakan CodeIgniter Spark CLI:
 * */30 * * * * /usr/bin/php /var/www/fishing_trip/spark trip:expire-unpaid >> /var/log/fishing_cron.log 2>&1
 */

define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

// Jalankan spark command internal
$app = CodeIgniter\Boot::bootSpark($paths);
