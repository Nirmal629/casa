<?php

declare(strict_types=1);

namespace App\Controllers\Host;

use App\Core\Controller;
use App\Models\EventModel;
use App\Models\Host\HostSubscriptionModel;
use App\Helpers\Session;
use PDO;

class HostSubscriptionController extends Controller
{
    protected EventModel $eventModel;
    protected HostSubscriptionModel $hostSubModel;

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct();
        $this->eventModel   = new EventModel($pdo);
        $this->hostSubModel = new HostSubscriptionModel($pdo);
    }

    /**
     * Renders the Host Subscription Management View
     */
    public function index(): void
    {
        Session::start();
        $role   = Session::normalizeRole(Session::getCurrentUserType());
        $hostId = Session::userId();

        if (!$hostId || ($role !== 'host' && $role !== 'trainer')) {
            $this->redirect('/');
            return;
        }

        $this->render('host/host-monthly-subscription', [
            'host_id' => $hostId,
        ]);
    }
}
