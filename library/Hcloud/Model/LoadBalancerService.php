<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

class LoadBalancerService extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_load_balancer_service';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'load_balancer_id',
            'listen_port',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'protocol',
            'destination_port',
            'proxyprotocol',
            'health_check_protocol',
            'health_check_port',
            'health_check_interval',
            'health_check_timeout',
            'health_check_retries',
            'health_check_http_domain',
            'health_check_http_path',
            'health_check_http_response',
            'health_check_http_status_codes',
            'health_check_http_tls',
            'http_cookie_name',
            'http_cookie_lifetime',
            'http_timeout_idle',
            'http_sticky_sessions',
            'http_redirect_http',
            'http_certificates',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'load_balancer_id' => mt('hcloud', 'Load Balancer ID'),
            'listen_port' => mt('hcloud', 'Listen Port'),
            'protocol' => mt('hcloud', 'Protocol'),
            'destination_port' => mt('hcloud', 'Destination Port'),
            'proxyprotocol' => mt('hcloud', 'Proxyprotocol'),
            'health_check_protocol' => mt('hcloud', 'Health Check Protocol'),
            'health_check_port' => mt('hcloud', 'Health Check Port'),
            'health_check_interval' => mt('hcloud', 'Health Check Interval'),
            'health_check_timeout' => mt('hcloud', 'Health Check Timeout'),
            'health_check_retries' => mt('hcloud', 'Health Check Retries'),
            'health_check_http_domain' => mt('hcloud', 'Health Check HTTP Domain'),
            'health_check_http_path' => mt('hcloud', 'Health Check HTTP Path'),
            'health_check_http_response' => mt('hcloud', 'Health Check HTTP Response'),
            'health_check_http_status_codes' => mt('hcloud', 'Health Check HTTP Status Codes'),
            'health_check_http_tls' => mt('hcloud', 'Health Check HTTP TLS'),
            'http_cookie_name' => mt('hcloud', 'HTTP Cookie Name'),
            'http_cookie_lifetime' => mt('hcloud', 'HTTP Cookie Lifetime'),
            'http_timeout_idle' => mt('hcloud', 'HTTP Timeout Idle'),
            'http_sticky_sessions' => mt('hcloud', 'HTTP Sticky Sessions'),
            'http_redirect_http' => mt('hcloud', 'HTTP Redirect HTTP'),
            'http_certificates' => mt('hcloud', 'HTTP Certificates'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Json(['health_check_http_status_codes', 'http_certificates']));
    }
}
