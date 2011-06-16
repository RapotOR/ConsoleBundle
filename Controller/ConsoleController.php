<?php

namespace Sf2gen\Bundle\ConsoleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

class ConsoleController extends Controller
{
    
    public function requestAction()
    {
        $request = $this->get('request');
        if ($request->isXmlHttpRequest() && $request->getMethod() == 'POST') {
            $sf2Command = $request->request->get('command'); // retrieve command string
            if($sf2Command == '.') // this trick is used to give the possibility to have "php app/console" equivalent
                $sf2Command = 'list';
            
            $php = $this->getPhpExecutable();
            $commandLine = $php.' console ';
            
            if(!empty($sf2Command))
                $commandLine .= $sf2Command;
            
            $app = ( $request->request->get('app') ? $request->request->get('app') : basename( $this->get('kernel')->getRootDir() ) );
            if(!in_array($app, $this->container->getParameter('sf2gen_console.apps') )) {
                return new Response('This application is not allowed...' , 200); // set to 200 to allow console display
            }
            
            $p = new Process(
                $commandLine, 
                dirname( $this->get('kernel')->getRootDir() ) . DIRECTORY_SEPARATOR . $app, 
                null, 
                null, 
                30, 
                array(
                    'suppress_errors' => false,
                    'bypass_shell' => false,
                )
            );
            $p->run();
            
            $output = str_replace("  ", "&nbsp;", nl2br($p->getOutput(), true));
            if(empty($output))
                $output = 'The command "'.$sf2Command.'" was successful.';
            
            if($p->isSuccessful())
                return new Response( $output );
            else
                return new Response('The command "'.$sf2Command.'" was not successful.' , 200); // set to 200 to allow console display
                
        }
        return new Response('This request was not found.', 404); // request is not a POST request
    }
    
    public function toolbarAction()
    {
        $request = $this->container->get('request');

        if (null !== $session = $request->getSession()) {
            // keep current flashes for one more request
            $session->setFlashes($session->getFlashes());
        }

        $position = false === strpos($this->container->get('request')->headers->get('user-agent'), 'Mobile') ? 'fixed' : 'absolute';

        return $this->container->get('templating')->renderResponse('Sf2genConsoleBundle:Console:toolbar.html.twig', array(
            'position'     => $position,
            'apps'         => $this->container->getParameter('sf2gen_console.apps'),
        ));
    }
    
    public function getPhpExecutable()
    {
        $executableFinder = new PhpExecutableFinder();
        if (false === $php = $executableFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }
        return $php;
    }
    
}