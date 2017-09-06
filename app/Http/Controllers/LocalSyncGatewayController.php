<?php

namespace App\Http\Controllers;

use App\Composer\GatewayHook;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LocalSyncGatewayController extends Controller
{
    public function gateway(Application $application, $hook)
    {
        if (!$application->bound($name = 'gateway.hook.' . $hook)) {
            throw new NotFoundHttpException();
        }

        /** @var GatewayHook $gateway */
        $gateway = $application->make($name);

        return $gateway->run();
    }
}
