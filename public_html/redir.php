<?php

namespace ogproxy;

require __DIR__ . '/../vendor/autoload.php';

( new Redir( __DIR__ . '/../config/config.php' ) )->execute();
