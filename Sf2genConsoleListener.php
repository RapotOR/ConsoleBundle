<?php

namespace Sf2gen\Bundle\ConsoleBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Bundle\TwigBundle\TwigEngine;

class Sf2genConsoleListener
{
    protected $templating;
    
    public function __construct(TwigEngine $templating)
    {
        $this->templating = $templating;
    }

    public function getVerbose()
    {
        return $this->verbose;
    }

    public function onCoreResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        if ($request->isXmlHttpRequest()) {
            return;
        }

        if ($response->headers->has('X-Debug-Token') && $response->isRedirect() && $this->interceptRedirects) {
            if (null !== $session = $request->getSession()) {
                // keep current flashes for one more request
                $session->setFlashes($session->getFlashes());
            }

            $response->setContent($this->templating->render('Sf2genConsoleBundle:Console:toolbar_redirect.html.twig', array('location' => $response->headers->get('Location'))));
            $response->setStatusCode(200);
            $response->headers->remove('Location');
        }

        if (!$response->headers->has('X-Debug-Token')
            || '3' === substr($response->getStatusCode(), 0, 1)
            || ($response->headers->has('Content-Type') && false === strpos($response->headers->get('Content-Type'), 'html'))
            || 'html' !== $request->getRequestFormat()
        ) {
            return;
        }

        $this->injectToolbar($response);
    }

    protected function injectToolbar(Response $response)
    {
        if (function_exists('mb_stripos')) {
            $posrFunction = 'mb_strripos';
            $substrFunction = 'mb_substr';
        } else {
            $posrFunction = 'strripos';
            $substrFunction = 'substr';
        }

        $content = $response->getContent();

        if (false !== $pos = $posrFunction($content, '</body>')) {
            $toolbar = "\n".str_replace("\n", '', $this->templating->render(
                'Sf2genConsoleBundle:Console:toolbar_js.html.twig',
                array('token' => $response->headers->get('X-Debug-Token'))
            ))."\n";
            $content = $substrFunction($content, 0, $pos).$toolbar.$substrFunction($content, $pos);
            $response->setContent($content);
        }
    }
}