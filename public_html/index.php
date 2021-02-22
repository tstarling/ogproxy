<?php

namespace ogproxy;

require __DIR__ . '/../vendor/autoload.php';

( new Index( __DIR__ . '/../config/config.php' ) )->execute();
