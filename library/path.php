<?php
class JaossPath {
    protected $pattern;
    protected $location;
    protected $app;
    protected $controller;
    protected $action;
    protected $matches = array();
    protected $discarded;

    public function run($request = NULL) {
        $controller = Controller::factory($this->controller, $this->app, $request);
        if (method_exists($controller, $this->action)) {
            if (is_callable(array($controller, $this->action))) {
                $controller->setPath($this);

                try {
                    $controller->init();
                } catch (CoreException $e) {
                    Log::debug($this->controller."Controller->init() failed with message [".$e->getMessage()."]");
                    return $controller->getResponse();
                }

                Log::debug("running [".$this->controller."Controller->".$this->action."]");
                $result = call_user_func(array($controller, $this->action));
                if ($result === NULL) {
                    $controller->render($this->action);
                }
                return $controller->getResponse();
            } else {
                throw new CoreException("Controller action is not callable");
            }
        } else {
            throw new CoreException(
                "Controller action does not exist",
                CoreException::ACTION_NOT_FOUND,
                array(
                    "controller" => get_class($controller),
                    "action" => $this->action,
                    "path" => $this->location,
                )
            );
        }
    }

    public function setPattern($pattern) {
        $this->pattern = $pattern;
    }

    public function setLocation($location) { 
        $this->location = $location;
    }

    public function setAction($action) {
        $this->action = $action;
    }

    public function setController($controller) {
        $this->controller = $controller;
    }

    public function setApp($app) {
        $this->app = $app;
    }

    public function getPattern() {
        return $this->pattern;
    }

    public function getLocation() {
        return $this->location;
    }

    public function getAction() {
        return $this->action;
    }

    public function getController() {
        return $this->controller;
    }

    public function getApp() {
        return $this->app;
    }

    public function setMatches($matches) {
        $this->matches = $matches;
    }

    public function hasMatch($match) {
        return isset($this->matches[$match]);
    }

    public function getMatch($match) {
        return isset($this->matches[$match]) ? $this->matches[$match] : null;
    }
    
    public function setDiscarded($discarded) {
        $this->discarded = $discarded;
    }
    
    public function isDiscarded() {
        return $this->discarded;
    }
}
