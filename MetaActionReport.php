<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MetaActionReport;

class MetaActionReport extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return array(
            //'AssetManager.getStylesheetFiles'        => 'getStylesheetFiles',
            'AssetManager.getJavaScriptFiles'        => 'getJsFiles',
        );
    }
    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = 'plugins/MetaActionReport/javascripts/metaactionreport.js';
        $jsFiles[] = 'plugins/MetaActionReport/angularjs/metaactionswitcher.controller.js';
    }
}
