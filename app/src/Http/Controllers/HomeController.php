<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Response;
use App\Models\ActivityLog;
use Illuminate\Database\Capsule\Manager as Capsule;
use Throwable;

final class HomeController extends AbstractController
{
    public function index(): Response
    {
        $dbVersion = null;
        $dbError = null;
        $activityLogs = [];

        try {
            $dbVersion = (string) Capsule::connection()->selectOne('SELECT version()')->version;
            $activityLogs = ActivityLog::orderBy('id', 'desc')->take(20)->get();
        } catch (Throwable $e) {
            $dbError = $e->getMessage();
        }

        $flashSuccess = $this->getFlash('success');
        $flashError = $this->getFlash('error');

        if ($this->getRequest()->query('logged_out') === '1') {
            $flashSuccess = 'Вы успешно вышли из системы. Сессионная кука удалена.';
        }

        return $this->render('home', [
            'currentUser' => $this->user(),
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'phpVersion' => PHP_VERSION,
            'hasPdoPgsql' => extension_loaded('pdo_pgsql'),
            'hostname' => gethostname(),
            'dbVersion' => $dbVersion,
            'dbError' => $dbError,
            'activityLogs' => $activityLogs,
        ]);
    }
}
