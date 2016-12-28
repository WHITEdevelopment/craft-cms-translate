<?php

namespace Craft;

class TranslateElementType extends BaseElementType
{
    /**
     * @return null|string
     */
    public function getName()
    {
        return Craft::t('Translations');
    }

    /**
     * @return bool
     */
    public function isLocalized()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasStatuses()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getStatuses()
    {
        return array(
            TranslateModel::DONE    => Craft::t('Done'),
            TranslateModel::PENDING => Craft::t('Pending'),
        );
    }

    /**
     * @return array
     */
    public function defineAvailableTableAttributes()
    {
        return array(
            'original' => array('label' => Craft::t('Original')),
            'field'    => array('label' => Craft::t('Translation')),
        );
    }

    /**
     * @param null $source
     *
     * @return array
     */
    public function getDefaultTableAttributes($source = null)
    {
        return array('original', 'field');
    }

    /**
     * @param BaseElementModel $element
     * @param string           $attribute
     *
     * @return mixed
     */
    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        return $element->$attribute;
    }

    /**
     * @return array
     */
    public function defineCriteriaAttributes()
    {
        return array(
            'original'    => AttributeType::String,
            'translation' => AttributeType::String,
            'source'      => AttributeType::String,
            'file'        => AttributeType::String,
            'status'      => array(AttributeType::String, 'default' => TranslateModel::DONE),
            'locale'      => array(AttributeType::String, 'default' => 'en_us'),
        );
    }

    /**
     * @param DbCommand            $query
     * @param ElementCriteriaModel $criteria
     *
     * @return bool
     */
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        return false;
    }

    /**
     * @param array $row
     *
     * @return BaseModel
     */
    public function populateElementModel($row)
    {
        return TranslateModel::populateModel($row);
    }

    /**
     * @param null $context
     *
     * @return array
     */
    public function getSources($context = null)
    {
        // Get plugin sources
        $pluginSources = array();
        $plugins = craft()->plugins->getPlugins();
        foreach ($plugins as $path => $plugin) {
            $pluginSources['plugins:' . $path] = array(
                'label'    => $plugin->classHandle,
                'criteria' => array(
                    'source' => craft()->path->getPluginsPath() . $path,
                ),
            );
        }

        // Get template sources
        $templateSources = array();
        $templates = IOHelper::getFolderContents(craft()->path->getSiteTemplatesPath(), false);
        foreach ($templates as $template) {

            // Get path/name of template files and folders
            if (preg_match('/(.*)\/(.*?)(\.(html|twig|js|json|atom|rss)|\/)$/', $template, $matches)) {

                // If matches, get template name
                $path = $matches[2];

                // Add template source
                $templateSources['templates:' . $path] = array(
                    'label'    => $path,
                    'criteria' => array(
                        'source' => $template,
                    ),
                );
            }
        }

        // Get default sources
        $sources = array(
            '*'         => array(
                'label'    => Craft::t('All translations'),
                'criteria' => array(
                    'source' => array(
                        craft()->path->getPluginsPath(),
                        craft()->path->getSiteTemplatesPath(),
                    ),
                ),
            ),
            array('heading' => Craft::t('Default')),
            'plugins'   => array(
                'label'    => Craft::t('Plugins'),
                'criteria' => array(
                    'source' => craft()->path->getPluginsPath(),
                ),
                'nested'   => $pluginSources,
            ),
            'templates' => array(
                'label'    => Craft::t('Templates'),
                'criteria' => array(
                    'source' => craft()->path->getSiteTemplatesPath(),
                ),
                'nested'   => $templateSources,
            ),
        );

        // Get sources by hook
        $plugins = craft()->plugins->call('registerTranslateSources');
        if (count($plugins)) {
            $sources[] = array('heading' => Craft::t('Custom'));
            foreach ($plugins as $plugin) {

                // Add as own source
                $sources = array_merge($sources, $plugin);

                // Add to "All translations"
                foreach ($plugin as $key => $values) {
                    $sources['*']['criteria']['source'][] = $values['criteria']['source'];
                }
            }
        }

        // Return sources
        return $sources;
    }

    /**
     * @param ElementCriteriaModel $criteria
     * @param array                $disabledElementIds
     * @param array                $viewState
     * @param null|string          $sourceKey
     * @param null|string          $context
     * @param bool                 $includeContainer
     * @param bool                 $showCheckboxes
     *
     * @return string
     */
    public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes)
    {
        $variables = array(
            'viewMode'           => $viewState['mode'],
            'context'            => $context,
            'elementType'        => new ElementTypeVariable($this),
            'disabledElementIds' => $disabledElementIds,
            'attributes'         => $this->getTableAttributesForSource($sourceKey),
            'elements'           => craft()->translate->get($criteria),
            'showCheckboxes'     => $showCheckboxes,
        );

        // Inject some custom js also
        craft()->templates->includeJs("$('table.fullwidth thead th').css('width', '50%');");
        craft()->templates->includeJs("$('.buttons.hidden').removeClass('hidden');");

        $template = '_elements/' . $viewState['mode'] . 'view/' . ($includeContainer ? 'container' : 'elements');

        return craft()->templates->render($template, $variables);
    }
}
