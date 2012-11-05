<?php

// include 'phpunit.phar';
require_once dirname(__FILE__)."/../Phste.php";

class PhesteTest extends PHPUnit_Framework_TestCase
{
    public function testAtoZ()
    {
        $phste = new Phste("input", array(
            "states" => array(
                "input"    => function(&$context) { $context["sign"] = "foo"; },
                "confirm"  => function(&$context) { $context["sign"] = "bar"; },
                "complete" => function(&$context) { $context["sign"] = "baz"; },
            ),
            "events" => array(
                "submit"    => array(
                    "from"  => "input", "to" => "confirm",
                    "guard" => function(&$context) { return isset($context['submit']); }
                ),
                "rollback"  => array(
                    "from"  => "confirm", "to" => "input",
                    "guard" => function(&$context) { return isset($context['rollback']); }
                ),
                "all_green" => array(
                    "from"  => "confirm", "to" => "complete",
                    "guard" => function(&$context) { return isset($context['all_green']); }
                )
            )
        ));
        $this->assertEquals("Phste", get_class($phste));
        $phste->doState();
        $this->assertEquals("foo", $phste->context["sign"]);

        $phste->fire("submit");
        $phste->doState();
        $this->assertEquals("input", $phste->getState());
        $this->assertEquals("foo", $phste->context["sign"]);

        $phste->context["submit"] = true;

        $phste->fire("submit");
        $phste->doState();
        $this->assertEquals("confirm", $phste->getState());
        $this->assertEquals("bar", $phste->context["sign"]);

        $phste->context["rollback"] = true;

        $phste->fire("rollback");
        $phste->doState();
        $this->assertEquals("input", $phste->getState());
        $this->assertEquals("foo", $phste->context["sign"]);

        unset($phste->context["rollback"]);
        $phste->fire("submit");
        $phste->doState();

        $phste->context["all_green"] = true;

        $phste->fire("all_green");
        $phste->doState();
        $this->assertEquals("complete", $phste->getState());
        $this->assertEquals("baz", $phste->context["sign"]);
    }
}