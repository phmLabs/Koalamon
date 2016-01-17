<?php

namespace Koalamon\Integration\WebhookBundle\EventListener;

use Koalamon\IntegrationBundle\EventListener\IntegrationInitEvent;
use Koalamon\IntegrationBundle\Integration\Integration;
use Koalamon\IntegrationBundle\Integration\IntegrationContainer;
use Symfony\Component\DependencyInjection\Container;

class IntegrationListener
{
    private $router;

    public function __construct(Container $container)
    {
        $this->router = $container->get('router');
    }

    private function initIntegrations(IntegrationContainer $container) {

    }

    public function onInit(IntegrationInitEvent $event)
    {
        $integrationContainer = $event->getIntegrationContainer();

        $url = $this->router->generate('koalamon_integration_webhook', ['project' => $event->getProject()->getIdentifier(), 'hookName' => 'newRelic']);
        $integrationContainer->addIntegration(new Integration('NewRelic', '/images/integrations/newrelic.png', 'Tool for Pinging your systems', $url));

        /*$url = $this->router->generate('koalamon_integration_webhook', ['project' => $event->getProject()->getIdentifier(), 'hookName' => 'jira']);
        $integrationContainer->addIntegration(new Integration('Jira', '', 'Tool for Pinging your systems', $url));

        $url = $this->router->generate('koalamon_integration_webhook', ['project' => $event->getProject()->getIdentifier(), 'hookName' => 'jenkins']);
        $integrationContainer->addIntegration(new Integration('Jenkins', '', 'Tool for Pinging your systems', $url));

        $url = $this->router->generate('koalamon_integration_webhook', ['project' => $event->getProject()->getIdentifier(), 'hookName' => 'appDynamics']);
        $integrationContainer->addIntegration(new Integration('AppDynamics', '', 'Tool for Pinging your systems', $url));

        $url = $this->router->generate('koalamon_integration_webhook', ['project' => $event->getProject()->getIdentifier(), 'hookName' => 'monitis']);
        $integrationContainer->addIntegration(new Integration('Monitis', '', 'Tool for Pinging your systems', $url));*/
    }
}