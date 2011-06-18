<?php

namespace Sf2gen\Bundle\ConsoleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

//Uses for shell access
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

//Uses for script access
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\StreamOutput;

//TODO: add the output formatter in the core
//use Symfony\Component\Console\Formatter\OutputFormatterHtml;
use Sf2gen\Bundle\ConsoleBundle\Formatter\OutputFormatterHtml;

/**
 * Controller for console
 *
 * @author Cédric Lahouste
 * @author Nicolas de Marqué
 *
 * @api
 */
class ConsoleController extends Controller
{
    private $filename = null;
    private $cacheDir = null;
    
    public function requestAction()
    {
        $request = $this->get('request');
        if ($request->isXmlHttpRequest() && $request->getMethod() == 'POST') {
            $sf2Command = $request->request->get('command'); // retrieve command string
            if($sf2Command == '.') // this trick is used to give the possibility to have "php app/console" equivalent
                $sf2Command = 'list';

            //TODO: not really efficient
            $app = ( $request->request->get('app') ? $request->request->get('app') : basename( $this->get('kernel')->getRootDir() ) );
            if(!in_array($app, $this->container->getParameter('sf2gen_console.apps') )) {
                return new Response('This application is not allowed...' , 200); // set to 200 to allow console display
            }
            
            //Try to run a separate shell process
            if($this->container->getParameter('sf2gen_console.new_process')) {
                //Try to run a separate shell process
                try
                {
                    $php = $this->getPhpExecutable();
                    $commandLine = $php.' console ';
                    if(!empty($sf2Command))
                        $commandLine .= $sf2Command;

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
                    
                    $output = $p->getOutput();
                    
                    if(!$p->isSuccessful())
                        $output = 'The command "' . $sf2Command . '" was not successful.\nError: ' . $p->getErrorOutput();

                }catch( \Exception $e){ // not trying the other method. It is interesting to know where it is not working (single process or not)
                    return new Response( nl2br('The request failed when using a separated shell process. Try to use "new_process: false" in configuration.\n' . $e->getMessage() ) ); 
                }
            }else{
                //Try to execute a console within this process
                //TODO: fix cache:clear issue
                try
                {
                    $result = "";
                    //Prepare input 
                    $args = preg_split("/ /", trim($sf2Command));
                    array_unshift($args, "fakecommandline"); //To simulate the console's arguments 
                    $app = $args[1];
                    $input = new ArgvInput($args);
                    
                    //Prepare output
                    $this->cacheDir = $this->container->get('kernel')->getCacheDir() . DIRECTORY_SEPARATOR . 'sf2genconsole' . DIRECTORY_SEPARATOR;
                    if(file_exists($this->filename))
                        unlink($this->filename);
                    $this->filename = $filename = "{$this->cacheDir}".time()."_commands";
                    $output = new StreamOutput(fopen($filename, 'w+'), StreamOutput::VERBOSITY_NORMAL, true, new OutputFormatterHtml());
                    
                    //Start a kernel/console and an application
                    $env = $input->getParameterOption(array('--env', '-e'), 'dev');
                    $debug = !$input->hasParameterOption(array('--no-debug', ''));
                    $kernel = new \AppKernel($env, $debug);
                    $kernel->boot();
                    
                    $application = new Application($kernel);
                    foreach ($kernel->getBundles() as $bundle)
                        $bundle->registerCommands($application); //integrate all availables commands
                    
                    //Find, initialize and run the real command
                    $run = $application->find($app)->run($input, $output);

                    $output = file_get_contents($filename);
                }catch( \Exception $e){                
                  return new Response( nl2br('The request failed  when using single process.\n' . $e->getMessage() ) ); 
                }
            }
            
            // common response for both methods
            if(empty($output))
                $output = 'The command "'.$sf2Command.'" was successful.';            
            
            return new Response( $this->convertOuput($output) );
        }
        
        return new Response('This request was not found.', 404); // request is not a POST request
    }
    
    public function __destruct()
    {
        if(method_exists(get_parent_class($this), "__destruct"))
            parent::__destruct();
        if(file_exists($this->filename))
            unlink($this->filename);
    }
    
    public function convertOuput($output)
    {
        // TODO : use OutputFormatterHtml
        return '<pre>'.$output.'</pre>';
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
