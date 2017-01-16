<?php

namespace Craft;

use Exception;

try{
    return craft()->translate->getTranslations(basename(__FILE__, '.php'));
}catch(Exception $e) {
    return [];
}

