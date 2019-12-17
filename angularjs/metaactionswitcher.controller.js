
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

(function () {
    angular.module('piwikApp').controller('MetaActionSwitcherController', MetaActionSwitcherController);

    MetaActionSwitcherController.$inject = ['piwikApi', '$filter', '$rootScope'];

    function MetaActionSwitcherController(piwikApi, $filter, $rootScope) {
        var translate = $filter('translate');

        var self = this;
        this.actionType = null;
        this.actionNameOptions = [];
        this.dimensionOptions = [];
        this.dimension1Name = null;
        this.dimension2Name = null;
        this.dimension3Name = null;
        this.dimension4Name = null;
        this.dimension5Name = null;
        this.actionTypeOptions = [
            {value: 'Search', key: 43},
            {value: 'Category', key: 13139640},
            {value: 'OpenMarkerWindow', key: 17},
            {value: 'Postal Code', key: 13147780},
            {value: 'Click', key: 5805100},
            {value: 'Details', key: 115},
            {value: 'Keyword', key: 13140307},
            {value: 'WebsiteClick', key: 13345557},
            {value: 'Click To Call', key: 5885831},
            {value: 'Interstitial-Button', key: 26773430},
            {value: 'EmailLeadOpen', key: 22622942},
            {value: 'Tab', key: 18107917},
            {value: 'Lead', key: 15350502},
            {value: 'EmailLeadSend', key: 22622892},
            {value: 'getDirections', key: 161},
            {value: 'EmailLeadNoticeOpen', key: 25669360},
            {value: 'SendToEmail', key: 23075289},
            {value: 'EmailLeadDisplay', key: 25669342},
            {value: 'EmailLeadConfirmationSend', key: 25669654},
            {value: 'SendToPhone', key: 28662167},
            {value: 'ButtonView', key: 24173919}
        ];
        this.isLoading = false;
        this.transitions = null;
        this.actionName = null;
        this.isEnabled = true;
        var noDataKey = '_____ignore_____';

        this.detectActionName = function (reports)
        {
            var othersLabel = translate('General_Others');

            var label, report;
            for (var i = 0; i < reports.length; i++) {
                if (!reports[i]) {
                    continue;
                }

                report = reports[i];

                if (report.label === othersLabel) {
                    continue;
                }

                var key = null;
                if (self.isUrlReport()) {
                    key = report.url
                } else {
                    key = report.label;
                }

                if (key) {
                    label = report.label + ' (' + translate('Transitions_NumPageviews', report.nb_hits) + ')';
                    self.actionNameOptions.push({key: key, value: label, url: report.url});
                    if (!self.actionName) {
                        self.actionName = key
                    }
                }
            }
        }

        this.isUrlReport = function()
        {
            return this.actionType === null;
        }
        this.load_dimension = function () {
            this.isLoading = true;
            this.dimensionOptions = []
            piwikApi.fetch({
                method: "MetaActionReport.getDimensionBySite",
                flat: 1, filter_limit: 100,
            }).then(function (options) {
                self.isLoading = false;
                self.dimensionOptions = []
                if (options && Object.keys(options).length !== 0) {
                    self.isEnabled = true;
                    for (var key in options) {
                        self.dimensionOptions.push({key: key, value: options[key]});
                    }
                }
            }, function () {
                self.isLoading = false;
                self.isEnabled = false;
            });
        }

        this.fetch = function (type) {
            this.isLoading = true;
            this.actionNameOptions = [];
            this.actionName = null;

            piwikApi.fetch({
                method: type,
                flat: 1, filter_limit: 100,
                filter_sort_order: 'desc',
                filter_sort_column: 'nb_hits',
                showColumns: 'label,nb_hits,url'
            }).then(function (report) {
                self.isLoading = false;
                self.actionNameOptions = [];
                self.actionName = null;

                if (report && report.length) {
                    self.isEnabled = true;
                    self.detectActionName(report);
                    self.onActionNameChange(self.actionName);
                }

                if (null === self.actionName || self.actionNameOptions.length === 0) {
                    self.isEnabled = false;
                    self.actionName = noDataKey;
                    self.actionNameOptions.push({key: noDataKey, value: translate('CoreHome_ThereIsNoDataForThisReport')});
                }
            }, function () {
                self.isLoading = false;
                self.isEnabled = false;
            });
        }

        this.onActionTypeChange = function (actionName) {
            this.fetch(actionName);
        };

        this.onActionNameChange = function (actionName) {
            if (actionName === null || actionName === noDataKey) {
                return;
            }

            var type = 'url';
            if (!this.isUrlReport()) {
                type = 'title';
            }
            if (!this.transitions) {
                this.transitions = new Piwik_Transitions(type, actionName, null, '');
            } else {
                this.transitions.reset(type, actionName, '');
            }
            this.transitions.showPopover(true);
        };

        $rootScope.$on('Transitions.switchTransitionsUrl', function (event, params) {
            if (params && params.url) {
                if (self.isUrlReport()) {
                    params.url = params.url.replace('https://', '').replace('http://', '');
                }

                var found = false, option, optionUrl;
                for (var i = 0; i < self.actionNameOptions.length; i++) {
                    option = self.actionNameOptions[i];
                    optionUrl = option.url;
                    if (optionUrl && self.isUrlReport()) {
                        optionUrl = String(optionUrl).replace('https://', '').replace('http://', '');
                    } else {
                        optionUrl = null;
                    }

                    if (!found && (option.key === params.url || (params.url === optionUrl && optionUrl))) {
                        found = true;
                        self.actionName = option.key;
                    }
                }
                if (!found) {
                    // we only fetch top 100 in the report... so the entry the user clicked on, might not be in the top 100
                    var options = angular.copy(self.actionNameOptions); // somehow needed to force angular to render it
                    options.push({key: params.url, value: params.url});
                    self.actionNameOptions = options;
                    self.actionName = params.url;
                }
                self.onActionNameChange(self.actionName);
            }
        });

        this.load_dimension();
        this.submitMetaReport = function() {
            var ajaxRequest = new ajaxHelper();
            ajaxRequest.setLoadingElement('#ajaxLoading');
            ajaxRequest.addParams({
                module: 'MetaActionReport',
                action: 'getEventAction',
                idAction: this.actionType,
                dimension1Name: this.dimension1Name,
                dimension2Name: this.dimension2Name,
                dimension3Name: this.dimension3Name,
            }, 'get');
            ajaxRequest.setCallback(
                function (response) {
                    $('#MetaResult').html(response);
                }
            );
            ajaxRequest.setFormat('html');
            ajaxRequest.send();
        }
    }
})();
