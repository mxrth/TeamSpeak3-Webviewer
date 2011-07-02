<?php

class ms_ModuleManager
{

    private $loadedModules = Array();
    private $loadedModules_name = Array();
    private $info;
    private $viewerConfig;
    private $debug;

    function __construct($viewerConfig, $debug=FALSE)
    {
        $this->viewerConfig = $viewerConfig;
        $this->debug = $debug;
    }

    public function loadModule($name)
    {

        if (is_array($name))
        {
            foreach ($name as $mod)
            {
                $this->loadModule($mod);
            }
            return true;
        }

        if ($this->moduleIsLoaded($name))
            return $this->getModule($name);

        if ($this->moduleExists($name))
        {
            require_once(s_root . 'modules/' . $name . '/' . $name . '.php');

            // Reading Config File \\
            // BEGIN \\
            if (file_exists(s_root . 'modules/' . $name . '/' . $name . '.xml'))
                $config_modul = parseConfigFile(s_root . 'modules/' . $name . '/' . $name . '.xml', true);

            $config = Array();
            foreach ($this->viewerConfig as $key => $value)
            {
                if (isset($config[$key]))
                    continue;
                $config[$key] = $value;
            }

            foreach ($config_modul as $key => $value)
            {
                if (isset($config[$key]))
                    continue;
                $config[$key] = $value;
            }

            // END \\
            $lang = NULL;
            $module = new $name($config, $lang, $this);
            $this->loadedModules[$name] = $module;
            $module->init();
            if(isset($this->info)) {
                $module->setInfo($this->info);
            }
            
            return $module;
        }
        else
        {
            if ($this->debug)
            {
                echo("cannot load Module $name\n");
            }
            return false;
        }
    }

    public function moduleExists($name)
    {
        return ((file_exists(s_root . 'modules/' . $name . '/' . $name . '.xml')) && file_exists(s_root . 'modules/' . $name . '/' . $name . '.php'));
    }

    public function moduleIsLoaded($name)
    {
        return (array_key_exists($name, $this->loadedModules));
    }

    public function getModule($name)
    {
        return $this->loadedModules[$name];
    }
    
    public function setInfo($info) {
        $this->info = $info;
        foreach($this->loadedModules as $mod) {
            $mod->setInfo($this->info);
        }
    }

    public function getHeaders()
    {
        $head = '';
        foreach ($this->loadedModules as $module)
        {
            $head .= $module->getHeader();
        }
        return $head;
    }

    public function getFooters()
    {
        $foot = '';
        foreach ($this->loadedModules as $module)
        {
            $foot .= $module->getFooter();
        }
        return $foot;
    }

    public function triggerEvent($e)
    {
        $out = '';
        foreach ($this->loadedModules as $mod)
        {
            $out .= $mod->onEvent($e);
        }
        return $out;
    }
    
    

}

