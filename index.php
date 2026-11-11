<?php

use RcnSistemas\Core\Http\Request;

$application = require __DIR__ . '/app/Bootstrap/app.php';
$documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$projectRoot = realpath(__DIR__);
if ($documentRoot !== false && $projectRoot !== false && str_starts_with($projectRoot, $documentRoot)) {
    $basePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($projectRoot, strlen($documentRoot)));
} else {
    $basePath = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
}
$_SERVER['RCN_BASE_PATH'] = rtrim($basePath, '/');

$request = Request::fromGlobals();
if ($_SERVER['RCN_BASE_PATH'] !== '' && str_starts_with($request->path(), $_SERVER['RCN_BASE_PATH'])) {
    $request = $request->withPath(substr($request->path(), strlen($_SERVER['RCN_BASE_PATH'])) ?: '/');
}

(require __DIR__ . '/app/Bootstrap/routes.php')($application)->dispatch($request)->send();
