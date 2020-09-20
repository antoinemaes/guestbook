<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

use App\Repository\ConferenceRepository;

class TwigEventSubscriber implements EventSubscriberInterface
{

    private $twig;
    private $confRep;

    public function __construct(Environment $twig, ConferenceRepository $confRep)
    {
        $this->twig = $twig;
        $this->confRep = $confRep;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $this->twig->addGlobal(
            'conferences', 
            $this->confRep->findAll()
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
