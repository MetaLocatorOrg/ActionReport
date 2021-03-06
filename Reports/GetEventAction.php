<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MetaActionReport\Reports;

use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;

use Piwik\View;

/**
 * This class defines a new report.
 *
 * See {@link http://developer.piwik.org/api-reference/Piwik/Plugin/Report} for more information.
 */
class GetEventAction extends Base
{
    protected function init()
    {
        parent::init();

        $this->name          = Piwik::translate('MetaActionReport_EventAction');
        $this->dimension     = null;
        $this->documentation = Piwik::translate('');

        // This defines in which order your report appears in the mobile app, in the menu and in the list of widgets
        $this->order = 1;

        // By default standard metrics are defined but you can customize them by defining an array of metric names
        $this->metrics       = array('meta_action', 'meta_unique_action');
        $this->subcategoryId = 'Meta Action Report';


        // Uncomment the next line if your report does not contain any processed metrics, otherwise default
        // processed metrics will be assigned
        // $this->processedMetrics = array();

        // Uncomment the next line if your report defines goal metrics
        // $this->hasGoalMetrics = true;

        // Uncomment the next line if your report should be able to load subtables. You can define any action here
        // $this->actionToLoadSubTables = $this->action;

        // Uncomment the next line if your report always returns a constant count of rows, for instance always
        // 24 rows for 1-24hours
        // $this->constantRowsCount = true;

        // If a subcategory is specified, the report will be displayed in the menu under this menu item
        // $this->subcategoryId = 'MetaActionReport_EventAction';
    }

    /**
     * Here you can configure how your report should be displayed. For instance whether your report supports a search
     * etc. You can also change the default request config. For instance change how many rows are displayed by default.
     *
     * @param ViewDataTable $view
     */
    public function configureView(ViewDataTable $view)
    {
        if (!empty($this->dimension)) {
            $view->config->addTranslations(array('label' => $this->dimension->getName()));
        }

        // $view->config->show_search = false;
        $view->requestConfig->filter_sort_column = 'meta_action';
        $view->requestConfig->filter_sort_order = 'desc';
        // $view->requestConfig->filter_limit = 10';
        $view->config->columns_to_display = array_merge(array('label'), $this->metrics);

        if (!is_array($view->config->custom_parameters)) {
            $view->config->custom_parameters = array();
        }
        if (!in_array("idAction", $view->config->custom_parameters)) { 
            $view->config->custom_parameters["idAction"] = Common::getRequestVar('idAction', false, 'string');
        }

        if (!in_array("dimension1Name", $view->config->custom_parameters)) { 
            $view->config->custom_parameters["dimension1Name"] = Common::getRequestVar('dimension1Name', false, 'string');;
        }

        if (!in_array("dimension2Name", $view->config->custom_parameters)) { 
            $view->config->custom_parameters["dimension2Name"] = Common::getRequestVar('dimension2Name', false, 'string');;
        }

        if (!in_array("dimension3Name", $view->config->custom_parameters)) { 
            $view->config->custom_parameters["dimension3Name"] = Common::getRequestVar('dimension3Name', false, 'string');;
        }

        if (!in_array("dimension4Name", $view->config->custom_parameters)) { 
            $view->config->custom_parameters["dimension4Name"] = Common::getRequestVar('dimension4Name', false, 'string');;
        }

        if (!in_array("dimension5Name", $view->config->custom_parameters)) { 
            $view->config->custom_parameters["dimension5Name"] = Common::getRequestVar('dimension5Name', false, 'string');;
        }

        $header_view = new View('@MetaActionReport/_Selector');
        $header_view->clientSideParameters = $view->config->custom_parameters;
        $view->requestConfig->request_parameters_to_modify["idAction"] = $view->config->custom_parameters["idAction"];
        $view->requestConfig->request_parameters_to_modify["dimension1Name"] = $view->config->custom_parameters["dimension1Name"];
        $view->requestConfig->request_parameters_to_modify["dimension2Name"] = $view->config->custom_parameters["dimension2Name"];
        $view->requestConfig->request_parameters_to_modify["dimension3Name"] = $view->config->custom_parameters["dimension3Name"];
        $view->requestConfig->request_parameters_to_modify["dimension4Name"] = $view->config->custom_parameters["dimension4Name"];
        $view->requestConfig->request_parameters_to_modify["dimension5Name"] = $view->config->custom_parameters["dimension5Name"];
        if (!$view->requestConfig->idSubtable) {
            $view->config->show_header_message = $header_view->render();
        }
    }

    /**
     * Here you can define related reports that will be shown below the reports. Just return an array of related
     * report instances if there are any.
     *
     * @return \Piwik\Plugin\Report[]
     */
    public function getRelatedReports()
    {
        return array(); // eg return array(new XyzReport());
    }

    /**
     * A report is usually completely automatically rendered for you but you can render the report completely
     * customized if you wish. Just overwrite the method and make sure to return a string containing the content of the
     * report. Don't forget to create the defined twig template within the templates folder of your plugin in order to
     * make it work. Usually you should NOT have to overwrite this render method.
     *
     * @return string
    public function render()
    {
        $view = new View('@MetaActionReport/getEventAction');
        $view->myData = array();

        return $view->render();
    }
    */

    /**
     * By default your report is available to all users having at least view access. If you do not want this, you can
     * limit the audience by overwriting this method.
     *
     * @return bool
    public function isEnabled()
    {
        return Piwik::hasUserSuperUserAccess()
    }
     */
}
