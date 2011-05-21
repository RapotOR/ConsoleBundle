Introduction
============

The Sf2gen namespace will be used for a future project. Sf2genConsoleBundle will be a little part of it.
Sf2genConsoleBundle give you the possibility to be able to execute a console command direclty from your application.
The interface is loaded with the same way than the WebProfilerBundle.

Use it
======

Instead of typing *php app/console list*, you will just have to type *list*.
The dot is an alias for *list*.

Installation
============

  1. Add this bundle to your vendor/ dir:

        $ git submodule add git://github.com/rapotor/ConsoleBundle.git vendor/bundles/Sf2gen/Bundle/ConsoleBundle

  2. Add the Sf2gen namespace to your autoloader:

        // app/autoload.php
        $loader->registerNamespaces(array(
            'Sf2gen' => __DIR__.'/../vendor/bundles',
            // other namespaces
        ));

  3. Add this bundle to your application's kernel, in the debug section:

        // app/ApplicationKernel.php
        public function registerBundles()
        {
            $bundles = array(
                // all bundles            
            );

            if (in_array($this->getEnvironment(), array('dev', 'test'))) {
                // previous bundles like WebProfilerBundle
                $bundles[] = new Sf2gen\Bundle\ConsoleBundle\Sf2genConsoleBundle();
            }

            return $bundles;
        }
          
  4. Add the following ressource to your routing_dev.yml:
        
        // app/config/routing_dev.php
        _sf2gencdt:
            resource: "@Sf2genConsoleBundle/Resources/config/routing.yml"
            prefix:   /_sf2gencdt    

  5. You have to disable the firewall if you use the `security component`:

        # app/config/config.yml
        security:
            firewalls:
                sf2gen:
                    pattern:    /_sf2gencdt/.*
                    security:  false
