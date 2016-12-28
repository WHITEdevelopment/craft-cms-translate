<?php

namespace Craft;

$localeId = basename(__FILE__, '.php');

$translateRecord = craft()->translate->getByLocale($localeId);
$translations = unserialize($translateRecord->getAttribute('translations'));

return $translations;