<?php

namespace Sf2gen\Bundle\ConsoleBundle;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Finder\Finder;

class Sf2genConsoleListener
{
    protected $templating;
    protected $kernel;
    protected $cacheDir;
    protected $cacheFile;
    
    public function __construct(Kernel $kernel, TwigEngine $templating)
    {
        $this->templating = $templating;
        $this->kernel = $kernel;
        $this->cacheDir = $this->kernel->getCacheDir() . DIRECTORY_SEPARATOR . 'sf2genconsole' . DIRECTORY_SEPARATOR;
        $this->cacheFile = 'commands.json';
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
                array(
                    'commands' => $this->getCommands(),
                )
            ))."\n";
            $content = $substrFunction($content, 0, $pos).$toolbar.$substrFunction($content, $pos);
            $response->setContent($content);
        }
    }
    
    protected function getCommands() {
        
        $commands = $this->getCacheContent();
        
        if($commands === false) {
            if(!is_dir( $this->cacheDir ))
                mkdir( $this->cacheDir, 777 );
            
            $commands = $this->fetchCommands();
            
            file_put_contents( $this->cacheDir . $this->cacheFile, json_encode($commands) );
        }else{
            $commands = json_decode($commands);
        }
        
        return $commands;
    }
    
    protected function fetchCommands() {
        $commands = array();
        foreach ($this->kernel->getBundles() as $bundle) {
            $finder = new Finder();
            $finder->files()->name('*Command.php')->in($bundle->getPath());
            
            foreach ($finder as $file) {
                $content = file_get_contents($bundle->getPath() . DIRECTORY_SEPARATOR . $file->getRelativePathName());
                if (preg_match("/setName\((['\"])([a-z:]*)(['\"])\)/", $content, $matches)) {
                    if(isset($matches[2])){
                        $commands[] = $matches[2];
                    }
                }
            }
        }
        return $commands;
    }
    
    protected function getCacheContent() {
        if(is_file( $this->cacheDir . $this->cacheFile )){
            return file_get_contents( $this->cacheDir . $this->cacheFile );
        }
        return false;
    }
}
