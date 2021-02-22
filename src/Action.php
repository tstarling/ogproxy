<?php

namespace ogproxy;

abstract class Action {
    public function __construct( $configFile ) {
        $this->config = require $configFile;
    }

    public function getConfig( $name ) {
        if ( !isset( $this->config[$name] ) ) {
            throw new \Exception( "Configuration variable $name is required" );
        }
        return $this->config[$name];
    }

    abstract public function execute();
}
