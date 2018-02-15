<?php

namespace HrmsApi\V1\Rest\BadgeHasRisorse;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\Router\RouteMatch;
use ZF\Hal\Link\Link;

class HalLinkListener
    extends AbstractListenerAggregate
{

    /**
     * @var \Zend\Router\RouteMatch
     */
    protected $routeMatch;

    public function __construct(RouteMatch $routeMatch)
    {
        $this->routeMatch = $routeMatch;
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach('renderEntity', [$this, 'onRenderEntity']);

    }

    public function onRenderEntity(Event $e)
    {

        $halEntity = $e->getParam('entity');
        $entity = $halEntity->getEntity();
        if ($entity instanceof BadgeHasRisorseEntity) {
            $entity_id = $this->routeMatch->getParam('risorse_id');
            $halEntity->getLinks()
                      ->add(Link::factory([
                                              'rel'   => 'badge_current',
                                              'route' => [
                                                  'name'   => 'hrms-api.rest.risorse',
                                                  'params' => [
                                                      'badge_id' => $entity_id,
                                                  ],
                                              ],
                                          ])
                      )
            ;
        }

    }
}
