<?php

class Phste
{
    protected $states = array();
    protected $events = array();

    protected $current_state = "init";

    public $context = array();

    public function __construct($init_state = "init", array $opts = array())
    {
        $this->current_state = $init_state;
        if (isset($opts["states"])) {
            foreach ($opts["states"] as $state => $on_transition) {
                if (!$on_transition instanceof Closure) {
                    $on_transition = array($this, 'noop');
                }
                $this->addState($state, $on_transition);
            }
            $this->states = $opts["states"];
        }
        else {
            $this->states = array();
        }
        if (isset($opts["events"])) {
            foreach ($opts["events"] as $label => $event) {
                $this->addEvent($label, $event);
            }
        }
    }

    public function addState($state, $on_transition = null)
    {
        if (is_null($on_transition)) {
            $on_transition = array($this, 'noop');
        }
        $this->states[$state] = $on_transition;
    }

    public function addEvent($label, $event)
    {
        if (!isset($event['from']) || !isset($event['to']) || !isset($event['guard'])) {
            return;
        }
        if (!is_array($event["from"])) {
            $event["from"] = array($event["from"]);
        }
        $this->events[$label] = $event;
    }

    public function mayFire($label)
    {
        if (!isset($this->events[$label])) {
            return false;
        }
        $from = $this->events[$label]["from"];
        return in_array($this->getState(), $from);
    }

    public function fire($label)
    {
        if (!isset($this->events[$label]) || !$this->mayFire($label)) {
            throw new Exception();
        }
        $result = !!$this->call($this->events[$label]["guard"], $this->context);
        if ($result === true) {
            $to = $this->events[$label]["to"];
            if (!isset($this->states[$to])) {
                throw new Exception();
            }
            $this->current_state = $to;
        }
        return $result;
    }

    public function doState()
    {
        $this->call($this->states[$this->current_state], $this->context);
    }

    protected function call($func, &$arg)
    {
        if (is_array($func) || (is_string($func) && is_callable($func))) {
            return call_user_func($func, $arg);
        }
        else {
            return $func($arg);
        }
    }

    public function getState()
    {
        return $this->current_state;
    }

    public function getEventNames($state = null)
    {
        if (is_null($state)) {
            return array_keys($this->events);
        }
        else {
            $labels = array();
            foreach ($this->events as $label => $event) {
                if (in_array($state, $event["from"])) {
                    $labels[] = $label;
                }
            }
            return $labels;
        }
    }

    protected function noop() { return false; }
}
