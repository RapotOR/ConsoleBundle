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
 * @todo nico : add the output formatter in the core
 * @todo nico : app validity test is not really efficient
 */
class ConsoleController extends Controller
{
    private $filename = null;
    private $cacheDir = null;

    public function requestAction()
    {
        $request = $this->get('request');
        if ($request->isXmlHttpRequest() && $request->getMethod() == 'POST') {
            // retrieve command string
            $sf2Command = stripslashes($request->request->get('command'));

            if ($sf2Command == '.') {
                // this trick is used to give the possibility to have "php app/console" equivalent
                $sf2Command = 'list';
            } elseif ($sf2Command == 'cache:clear') {
                // warming up the cache cannot be done after clearing it
                // fix issue #11
                $sf2Command .= ' --no-warmup';
            }
            //TODO: not really efficient
            $rootFolder = basename($this->container->getParameter('kernel.root_dir'));
            $app = $request->request->get('app') ?: $rootFolder;
            if (!in_array($app, $this->container->getParameter('sf2gen_console.apps') )) {
                return new Response('This application is not allowed...' , 200); // set to 200 to allow console display
            }

            //Try to run a separate shell process
            if ($this->container->getParameter('sf2gen_console.new_process')) {
                //Try to run a separate shell process
                try {
                    $php = $this->getPhpExecutable();
                    $commandLine = $php . ' ' . $app . '/' . 'console ';
                    if(!empty($sf2Command)) {
                        $commandLine .= $sf2Command;
                    }

                    $p = new Process(
                        $commandLine,
                        $rootFolder,
                        null,
                        null,
                        30,
                        array(
                            'suppress_errors'   => false,
                            'bypass_shell'      => false,
                        )
                    );
                    $p->run();

                    $output = $p->getOutput();

                    /*
                    if the process is not successful:
                    - 1) Symfony throws an error and ouput is not empty; continue without Exception.
                    - 2) Process throws an error and ouput is empty => Exception!
                    */
                    if (!$p->isSuccessful() && empty($output)) {
                        throw new \RuntimeException('Unabled to run the process.');
                    }

                } catch(\Exception $e) { // not trying the other method. It is interesting to know where it is not working (single process or not)
                    return new Response(nl2br("The request failed when using a separated shell process. Try to use 'new_process: false' in configuration.\n Error : ".$e->getMessage()));
                }
            } else {
                //Try to execute a console within this process
                //TODO: fix cache:clear issue
                try {
                    //Prepare input
                    $args = preg_split("/ /", trim($sf2Command));
                    array_unshift($args, "fakecommandline"); //To simulate the console's arguments
                    $app = $args[1];
                    $input = new ArgvInput($args);

                    //Prepare output
                    ob_start();
                    $output = new StreamOutput(fopen("php://output", 'w'), StreamOutput::VERBOSITY_NORMAL, true, new OutputFormatterHtml(true));

                    //Start a kernel/console and an application
                    $env = $input->getParameterOption(array('--env', '-e'), 'dev');
                    $debug = !$input->hasParameterOption(array('--no-debug', ''));
                    $kernel = new \AppKernel($env, $debug);
                    $kernel->boot();
                    $application = new Application($kernel);
                    foreach ($kernel->getBundles() as $bundle) {
                        $bundle->registerCommands($application); //integrate all availables commands
                    }

                    //Find, initialize and run the real command
                    $run = $application->find($app)->run($input, $output);

                    $output = ob_get_contents();

                    ob_end_clean();
                } catch(\Exception $e){
                    return new Response(nl2br("The request failed  when using same process.\n Error : ".$e->getMessage()));
                }
            }

            // common response for both methods
            if (empty($output)) {
                $output = 'The command "'.$sf2Command.'" was successful.';
            }

            return new Response($this->convertOuput($output));
        }

        return new Response('This request was not found.', 404); // request is not a POST request
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
        $php = $executableFinder->find();
        if (empty($php)) {
            throw new \RuntimeException('Unable to find the PHP executable. Verify your PATH variable.');
        }

        return $php;
    }
}
