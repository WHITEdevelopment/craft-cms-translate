<?php

namespace Craft;

class TranslateService extends BaseApplicationComponent
{
    protected $translateRecord;

    /**
     * TranslateService constructor.
     *
     * @param null $translateRecord
     */
    public function __construct($translateRecord = null)
    {
        $this->translateRecord = $translateRecord;
        if (is_null($this->translateRecord)) {
            $this->translateRecord = TranslateRecord::model();
        }
    }

    /**
     * @var array
     */
    protected $_expressions
        = array(

            // Expressions for Craft::t() variants
            'php'  => array(
                // Single quotes
                '/Craft::(t|translate)\(.*?\'(.*?)\'.*?\)/',
                // Double quotes
                '/Craft::(t|translate)\(.*?"(.*?)".*?\)/',
            ),

            // Expressions for |t() variants
            'html' => array(
                // Single quotes
                '/(\{\{\s*|\{\%.*?|:\s*)\'(.*?)\'.*?\|.*?(t|translate)(\(.*?\)|).*?(\}\}|\%\}|,)/',
                // Double quotes
                '/(\{\{\s*|\{\%.*?|:\s*)"(.*?)".*?\|.*?(t|translate)(\(.*?\)|).*?(\}\}|\%\}|,)/',
            ),

            // Expressions for Craft.t() variants
            'js'   => array(
                // Single quotes
                '/Craft\.(t|translate)\(.*?\'(.*?)\'.*?\)/',
                // Double quotes
                '/Craft\.(t|translate)\(.*?"(.*?)".*?\)/',
            ),

        );

    /**
     * init
     */
    public function init()
    {
        parent::init();

        // Also use html expressions for twig/json/atom/rss templates
        $this->_expressions['twig'] = $this->_expressions['html'];
        $this->_expressions['json'] = $this->_expressions['html'];
        $this->_expressions['atom'] = $this->_expressions['html'];
        $this->_expressions['rss'] = $this->_expressions['html'];
    }

    /**
     * @param       $locale
     * @param array $translations
     */
    public function set($locale, array $translations)
    {
        $translateRecord = $this->getByLocale($locale);

        // Get current translation (if any)
        if ($current = unserialize($translateRecord->getAttribute('translations'))) {
            // merge with new data
            $translations = array_merge($current, $translations);
        }

        // set model
        $translateModel = new TranslateModel();

        // save to database
        $translateModel::populateModel($translateRecord);
        $translateRecord->setAttributes(
            array(
                'locale'       => $locale,
                'translations' => serialize($translations),
            )
        );
        $translateRecord->save();
    }

    /**
     * @param ElementCriteriaModel $criteria
     *
     * @return array
     */
    public function get(ElementCriteriaModel $criteria)
    {
        // Ensure source is an array
        if (!is_array($criteria->source)) {
            $criteria->source = array($criteria->source);
        }

        // Gather all translatable strings
        $occurences = array();

        // Loop through paths
        foreach ($criteria->source as $path) {

            // Check if this is a folder or a file
            $isFile = IOHelper::fileExists($path);

            // If its not a file
            if (!$isFile) {

                // Set filter - no vendor folders, only template files
                $filter = '^((?!vendor|node_modules).)*(\.(php|html|twig|js|json|atom|rss)?)$';

                // Get files
                $files = IOHelper::getFolderContents($path, true, $filter);

                // Loop through files and find translate occurences
                foreach ($files as $file) {

                    // Parse file
                    $elements = $this->_parseFile($path, $file, $criteria);

                    // Collect in array
                    $occurences = array_merge($occurences, $elements);
                }
            } else {

                // Parse file
                $elements = $this->_parseFile($path, $path, $criteria);

                // Collect in array
                $occurences = array_merge($occurences, $elements);
            }
        }

        return $occurences;
    }

    /**
     * @param $locale
     *
     * @return TranslateRecord
     */
    public function getByLocale($locale)
    {
        $translateRecord = $this->translateRecord->find('locale = :locale', array('locale' => $locale));
        if (!$translateRecord) {
            $translateRecord = $this->translateRecord->create();
        }

        return $translateRecord;
    }

    /**
     * @param                      $path
     * @param                      $file
     * @param ElementCriteriaModel $criteria
     *
     * @return array
     */
    protected function _parseFile($path, $file, ElementCriteriaModel $criteria)
    {
        // Collect matches in file
        $occurences = array();

        // Get file contents
        $contents = IOHelper::getFileContents($file);

        // Get extension
        $extension = IOHelper::getExtension($file);

        // Get matches per extension
        foreach ($this->_expressions[$extension] as $regex) {

            // Match translation functions
            if (preg_match_all($regex, $contents, $matches)) {

                // Collect
                foreach ($matches[2] as $original) {

                    // Translate
                    $translation = Craft::t($original, array(), null, $criteria->locale);

                    // Show translation in textfield
                    $field = craft()->templates->render(
                        '_includes/forms/text', array(
                            'id'          => ElementHelper::createSlug($original),
                            'name'        => 'translation[' . $original . ']',
                            'value'       => $translation,
                            'placeholder' => $translation,
                        )
                    );

                    // Fill element with translation data
                    $element = TranslateModel::populateModel(
                        array(
                            'id'          => ElementHelper::createSlug($original),
                            'original'    => $original,
                            'translation' => $translation,
                            'source'      => $path,
                            'file'        => $file,
                            'locale'      => $criteria->locale,
                            'field'       => $field,
                        )
                    );

                    // If searching, only return matches
                    if ($criteria->search && !stristr($element->original, $criteria->search)
                        && !stristr(
                            $element->translation, $criteria->search
                        )
                    ) {
                        continue;
                    }

                    // If wanting one status, ditch the rest
                    if ($criteria->status && $criteria->status != $element->getStatus()) {
                        continue;
                    }

                    // Collect in array
                    $occurences[$original] = $element;
                }
            }
        }

        // Return occurences
        return $occurences;
    }

    /**
     * @return string
     */
    private function getTranslationFilePath()
    {
        // Determine locale's translation destination file
        return __DIR__ . '/../translations/';
    }

    /**
     * @param $localeId
     *
     * @return string
     */
    private function getTranslationFile($localeId)
    {
        // Determine locale's translation destination file
        return $this->getTranslationFilePath() . $localeId . '.php';
    }

    /**
     * @param $localeId
     *
     * @throws Exception
     */
    public function saveTranslationFile($localeId)
    {
        // copy php file
        IOHelper::copyFile($this->getTranslationFilePath() . '_template.php', $this->getTranslationFile($localeId));
    }

    /**
     * @param $localeId
     */
    public function deleteTranslationFile($localeId)
    {
        // set file
        $file = $this->getTranslationFile($localeId);

        if (IOHelper::fileExists($file)) {
            IOHelper::deleteFile($file);
        }
    }

    /**/
    public function checkTranslationFiles()
    {
        $locales = craft()->i18n->getSiteLocales();
        foreach($locales as $locale) {
            $localeId = $locale->getId();
            if(!IOHelper::fileExists($this->getTranslationFile($localeId))) {
                $this->saveTranslationFile($localeId);
            };
        }
    }

    /**
     * @param $localeId
     *
     * @return mixed
     */
    public function getTranslations($localeId) {
        $translateRecord = $this->getByLocale($localeId);
        return unserialize($translateRecord->getAttribute('translations'));
    }
}
