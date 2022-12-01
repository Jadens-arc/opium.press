<?php

use App\Kernel;
header('x-powered-by: literally vibes and a spotify daily mix');
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
