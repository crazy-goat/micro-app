#!/usr/bin/env php
<?php

namespace Apps;

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new HelloWorld());
$application->run();