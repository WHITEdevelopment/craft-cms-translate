<?php

namespace Craft;

use Exception;

try {
    $output = craft()->db->createCommand()->select('translations')->from('translate')->where([
        'locale' => basename(__FILE__, '.php')
    ])->queryColumn();

    if (isset($output[0])) {
        return unserialize($output[0]);
    }

} catch (Exception $e){}

return [];



