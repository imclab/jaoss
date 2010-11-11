<?php
abstract class Controller {
	protected $smarty = NULL;
	protected $path = NULL;
	protected $adminUser = NULL;
	protected $session = NULL;
    protected $request = NULL;
    protected $response = NULL;
    
    protected $var_stack = array();

    public function init() {
		return "OK";
    }

	public function __construct($request = NULL) {
		
		$this->smarty = new Smarty();
		
		$apps = AppManager::getAppPaths();
		$tpl_dirs = array(PROJECT_ROOT."apps/");
		
		$this->smarty->template_dir	= $tpl_dirs;
		$this->smarty->compile_dir = Settings::getValue("smarty", "compile_dir");

        $this->request = $request;
        $this->response = new JaossResponse();
		
        $this->session = Session::getInstance();
	}
	
	public function setPath($path) {
		$this->path = $path;
        $this->smarty->template_dir = array_merge(
            array(PROJECT_ROOT."apps/".$this->path->getApp()."/views/"),
            $this->smarty->template_dir
        );
	}
	
	public static function factory($controller, $app_path = NULL, $request = NULL) {
		$c_class = $controller."Controller";
		if (!class_exists($c_class)) {
            // can force a path if required
            if ($app_path !== NULL) {
                $path = PROJECT_ROOT."apps/{$app_path}/controllers/".strtolower($controller).".php";
                if (file_exists($path)) {
                    include($path);
                }
            } else {
                $apps = AppManager::getAppPaths();
                foreach ($apps as $app) {
                    $path = PROJECT_ROOT."apps/{$app}/controllers/".strtolower($controller).".php";
                    if (file_exists($path)) {
                        include($path);
                        break;
                    }
                }
            }
        }

        if (class_exists($c_class)) {
            $request = $request ? $request : JaossRequest::getInstance();
            return new $c_class($request);
        }
		throw new CoreException(
            "Could not find controller in any path",
            CoreException::CONTROLLER_CLASS_NOT_FOUND,
            array(
                "controller" => $controller,
                "class" => $c_class,
                "app_path" => $app_path,
                "apps" => isset($apps) ? $apps : null,
            )
        );
	}
	
	public function getMatch($match, $default=NULL) {
		if (!$this->path->hasMatch($match)) {
			return $default;
		}
		return $this->path->getMatch($match);
	}

    public function redirect($url, $message = NULL) {
        // always add the flash message - if the ajax handler obeys the 
        // redirect we will pick it up next render
    	if ($message) {
    		FlashMessenger::addMessage($message);
    	}
    	if ($this->request->isAjax()) {
    		$this->assign("redirect", $url);
            return $this->renderJson();
    	} else {
            $this->response->setRedirect($url, 303);
            return true;
        }
    }
	
	public function render($template) {
		if ($this->request->isAjax()) {
            return $this->renderJson();
		} else {
            return $this->renderTemplate($template);
        }
	}
	
	public function renderJson() {
		if (!isset($this->var_stack["msg"])) {
			$this->var_stack["msg"] = "OK";
		}
		foreach ($this->var_stack as $var => $val) {
			$data[$var] = $val;
		}
		$this->response->setBody(json_encode($data));
        return true;
	}

    public function renderTemplate($template) {
		if ($this->smarty->templateExists($template.".tpl")) {
            $this->assign("base_href", $this->request->getBaseHref());
            $this->assign("current_url", $this->request->getUrl());
            $this->assign("messages", FlashMessenger::getMessages());

            foreach ($this->var_stack as $var => $val) {
                $this->smarty->assign($var, $val);
            }
			$this->response->setBody($this->smarty->fetch($template.".tpl"));
            return true;
		}

        throw new CoreException(
            "Template Not Found",
            CoreException::TPL_NOT_FOUND,
            array(
                "paths" => $this->smarty->template_dir,
                "tpl" => $template,
            )
        );
    }
	
	public function renderStatic($template) {
		if ($this->smarty->templateExists("static/".$template.".tpl")) {
			return $this->fetch("static/".$template.".tpl");
		}
		// manual for HTML files
		foreach ($this->smarty->template_dir as $dir) {
			if (file_exists($dir."static/".$template.".html")) {
				return file_get_contents($dir."static/".$template.".html");
			}
		}
		throw new CoreException("no static template found");
	}
	
	public function assign($var, $value) {
		$this->var_stack[$var] = $value;
	}

    public function unassign($var) {
        unset($this->var_stack[$var]);
    }
    
    public function setFlash($flash) {
        $this->session->setFlash($flash);
    }

    public function getFlash($flash) {
       return $this->session->getFlash($flash);
    }
    
    public function getResponse() {
        $this->response->setPath($this->path);
        return $this->response;
    }
    
    public function setResponseCode($code) {
        $this->response->setResponseCode($code);
    }
    
    public function templateForPattern() {
        $pattern = $this->path->getPattern();
        if (!preg_match("@(?P<tpl>\w+)@", $pattern, $matches)) {
            throw new CoreException("pattern could not be auto converted to template");
        }
        return $this->render($matches["tpl"]);
    }
}
