<?php

namespace Craft;

class TranslatePlugin extends BasePlugin
{
    /**
     * @return null|string
     */
    public function getName()
    {
        return Craft::t('Translate');
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return '0.7.3';
    }

    /**
     * @return string
     */
    public function getDeveloper()
    {
        return 'Internetbureau WHITE';
    }

    /**
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'http://www.white.nl';
    }

    /**
     * @return string
     */
    public function getDocumentationUrl()
    {
        return 'https://github.com/WHITEdevelopment/craft-translate';
    }

    /**
     * @return bool
     */
    public function hasCpSection()
    {
        return true;
    }

    /**
     * init
     */
    public function init()
    {
        parent::init();
        $this->addEventListeners();
    }

    /**
     * event listeners
     */
    protected function addEventListeners()
    {
        craft()->on('i18n.onAddLocale', array($this, 'onAddLocale'));
        craft()->on('i18n.onBeforeDeleteLocale', array($this, 'onBeforeDeleteLocale'));
        craft()->on('plugins.onLoadPlugins', array($this, 'onLoadPlugins'));
    }

    /**
     * @param Event $event
     */
    public function onAddLocale(Event $event)
    {
        craft()->translate->saveTranslationFile($event->params['localeId']);
    }

    /**
     * @param Event $event
     */
    public function onBeforeDeleteLocale(Event $event)
    {
        craft()->translate->deleteTranslationFile($event->params['localeId']);
    }

    /**/
    public function onLoadPlugins()
    {
        craft()->translate->checkTranslationFiles();
    }
}
