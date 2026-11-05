<?php

use RcnSistemas\Repositories\MysqliGoogleCalendarConnectionRepository;
use RcnSistemas\Repositories\MysqliGoogleCalendarEventMigrationRepository;
use RcnSistemas\Repositories\MysqliGoogleCalendarIntegrationRepository;
use RcnSistemas\Repositories\MysqliTicketCalendarRepository;
use RcnSistemas\Repositories\MysqliTicketRepository;
use RcnSistemas\Services\Google\GoogleCalendarAppointmentSyncService;
use RcnSistemas\Services\Google\GoogleCalendarEventMigrationService;
use RcnSistemas\Services\Google\GoogleCalendarService;
use RcnSistemas\Services\Google\TokenEncryption;

$application = require __DIR__ . '/../app/Bootstrap/app.php';
$database = $application['database']->get();
$legacyConnections = new MysqliGoogleCalendarConnectionRepository($database);
$integrations = new MysqliGoogleCalendarIntegrationRepository($database);
$config = $application['config']->get('google', []);
$calendar = new GoogleCalendarService(
    $config,
    new TokenEncryption((string) $application['config']->get('app.encryption_key', ''))
);
$sync = new GoogleCalendarAppointmentSyncService(
    $integrations,
    new MysqliTicketCalendarRepository($database),
    $calendar,
    $config
);
$migration = new GoogleCalendarEventMigrationService(
    new MysqliGoogleCalendarEventMigrationRepository($database),
    new MysqliTicketRepository($database),
    $integrations,
    $legacyConnections,
    $sync,
    $calendar
);

$reportOnly = in_array('--report-only', $argv, true);
$limit = 100;
foreach ($argv as $argument) {
    if (preg_match('/^--limit=(\d+)$/', $argument, $matches)) $limit = max(1, min(1000, (int) $matches[1]));
}

try {
    $result = $reportOnly ? $migration->report() : $migration->run($limit);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if (!$reportOnly && (($result['failed'] ?? 0) > 0 || ($result['legacy_cleanup_failed'] ?? 0) > 0)) exit(1);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
