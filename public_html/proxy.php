<?php

namespace ogproxy;

require __DIR__ . '/../vendor/autoload.php';

( new Proxy( __DIR__ . '/../config/config.php' ) )->execute();
