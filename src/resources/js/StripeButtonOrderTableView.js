if (typeof Craft.StripeButton === typeof undefined) {
    Craft.StripeButton = {};
}

/**
 * Class Craft.PaymentForm.OrderTableView
 */
Craft.StripeButton.OrderTableView = Craft.TableElementIndexView.extend({

        startDate: null,
        endDate: null,
        currency: null,

        startDatepicker: null,
        endDatepicker: null,

        $chartExplorer: null,
        $totalValue: null,
        $chartContainer: null,
        $spinner: null,
        $error: null,
        $chart: null,
        $startDate: null,
        $endDate: null,
        $currency: null,
        $currencyField: null,
        $currencies: null,
        $exportButton: null,

        afterInit: function() {
            this.$explorerContainer = $('<div class="chart-explorer-container"></div>').prependTo(this.$container);

            this.$currencies = jQuery.parseJSON($("#currencies").val());

            this.createChartExplorer();

            this.base();
        },

        getStorage: function(key) {
            return Craft.StripeButton.OrderTableView.getStorage(this.elementIndex._namespace, key);
        },

        setStorage: function(key, value) {
            Craft.StripeButton.OrderTableView.setStorage(this.elementIndex._namespace, key, value);
        },

        createChartExplorer: function() {
            // chart explorer
            var $chartExplorer = $('<div class="chart-explorer"></div>').appendTo(this.$explorerContainer),
                $chartHeader = $('<div class="chart-header"></div>').appendTo($chartExplorer),
                $exportButton = $('<div class="btn menubtn export-menubtn">'+Craft.t('enupal-stripe', 'Export')+'</div>').appendTo($chartHeader),
                $exportMenu = $('<div class="menu"><ul><li><a data-format="csv">CSV</a> <a data-format="xls">XLS</a></li><li><a data-format="xlsx">XLSX</a></li><li><a data-format="ods">ODS</a></li></ul></div>').appendTo($chartHeader),
                $currencyContainer = $('<div class="date-range" />').appendTo($chartHeader),
                $dateRange = $('<div class="date-range" />').appendTo($chartHeader),
                $startDateContainer = $('<div class="datewrapper"></div>').appendTo($dateRange),
                $to = $('<span class="to light">to</span>').appendTo($dateRange),
                $endDateContainer = $('<div class="datewrapper"></div>').appendTo($dateRange),
                $total = $('<div class="total"></div>').appendTo($chartHeader),
                $totalLabel = $('<div class="total-label light">' + Craft.t('enupal-stripe', 'Total Revenue') + '</div>').appendTo($total),
                $totalValueWrapper = $('<div class="total-value-wrapper"></div>').appendTo($total),
                $totalValue = $('<span class="total-value">&nbsp;</span>').appendTo($totalValueWrapper);

            this.$exportButton = $exportButton;
            this.$chartExplorer = $chartExplorer;
            this.$totalValue = $totalValue;
            this.$chartContainer = $('<div class="chart-container"></div>').appendTo($chartExplorer);
            this.$spinner = $('<div class="spinner enupal--hidden" />').prependTo($chartHeader);
            this.$error = $('<div class="error"></div>').appendTo(this.$chartContainer);
            this.$chart = $('<div class="chart"></div>').appendTo(this.$chartContainer);

            this.$startDate = $('<input type="text" class="text" size="20" autocomplete="off" />').appendTo($startDateContainer);
            this.$endDate = $('<input type="text" class="text" size="20" autocomplete="off" />').appendTo($endDateContainer);
            if (this.$currencies.length > 1){

                $currencyTo = $('<span class="to light"> - </span>').appendTo($dateRange);
                var currencySelect = '<select title = "Currency" id="fields-currency" class="text" name="fields[currency]">';
                currencySelect += '<option value="*">All</option>';
                $.each( this.$currencies, function( key, value ) {
                    currencySelect += '<option value="'+value.currency+'">'+value.currency+'</option>';
                });
                currencySelect += '</select>';
                this.$currency = $(currencySelect).appendTo($currencyContainer);
            }
            this.$currencyField = $("#fields-currency");
            this.$startDate.datepicker($.extend({
                onSelect: $.proxy(this, 'handleStartDateChange')
            }, Craft.datepickerOptions));

            this.$endDate.datepicker($.extend({
                onSelect: $.proxy(this, 'handleEndDateChange')
            }, Craft.datepickerOptions));

            new Garnish.MenuBtn(this.$exportButton, {
                onOptionSelect: $.proxy(this, 'handleClickExport')
            });

            this.startDatepicker = this.$startDate.data('datepicker');
            this.endDatepicker = this.$endDate.data('datepicker');

            this.addListener(this.$startDate, 'keyup', 'handleStartDateChange');
            this.addListener(this.$endDate, 'keyup', 'handleEndDateChange');
            this.addListener(this.$currencyField, 'change', 'handleCurrencyChange');

            // Set the start/end dates
            var startTime = this.getStorage('startTime') || ((new Date()).getTime() - (60 * 60 * 24 * 7 * 1000)),
                endTime = this.getStorage('endTime') || ((new Date()).getTime());

            this.setStartDate(new Date(startTime));
            this.setEndDate(new Date(endTime));
            this.setCurrency(this.$currencyField.val());

            // Load the report
            this.loadReport();
        },

        handleClickExport: function(option) {
            var data = {};
            data.source = this.settings.params.source;
            data.format = option.dataset.format;
            data.startDate = Craft.StripeButton.OrderTableView.getDateValue(this.startDate);
            data.endDate = Craft.StripeButton.OrderTableView.getDateValue(this.endDate);
            location.href = Craft.getActionUrl('enupal-stripe/downloads/export-order', data);
        },

        handleStartDateChange: function() {
            if (this.setStartDate(Craft.StripeButton.OrderTableView.getDateFromDatepickerInstance(this.startDatepicker))) {
                this.loadReport();
            }
        },

        handleEndDateChange: function() {
            if (this.setEndDate(Craft.StripeButton.OrderTableView.getDateFromDatepickerInstance(this.endDatepicker))) {
                this.loadReport();
            }
        },

        handleCurrencyChange: function() {
            if (this.setCurrency(this.$currencyField.val())) {
                this.loadReport();
            }
        },

        setCurrency: function(currency) {
            // Make sure it has actually changed
            if (this.currency && currency === this.currency) {
                return false;
            }

            this.currency = currency;
            this.setStorage('currency', this.currency);

            return true;
        },


        setStartDate: function(date) {
            // Make sure it has actually changed
            if (this.startDate && date.getTime() === this.startDate.getTime()) {
                return false;
            }

            this.startDate = date;
            this.setStorage('startTime', this.startDate.getTime());
            this.$startDate.val(Craft.formatDate(this.startDate));

            // If this is after the current end date, set the end date to match it
            if (this.endDate && this.startDate.getTime() > this.endDate.getTime()) {
                this.setEndDate(new Date(this.startDate.getTime()));
            }

            return true;
        },

        setEndDate: function(date) {
            // Make sure it has actually changed
            if (this.endDate && date.getTime() === this.endDate.getTime()) {
                return false;
            }

            this.endDate = date;
            this.setStorage('endTime', this.endDate.getTime());
            this.$endDate.val(Craft.formatDate(this.endDate));

            // If this is before the current start date, set the start date to match it
            if (this.startDate && this.endDate.getTime() < this.startDate.getTime()) {
                this.setStartDate(new Date(this.endDate.getTime()));
            }

            return true;
        },

        loadReport: function() {
            var requestData = this.settings.params;

            requestData.startDate = Craft.StripeButton.OrderTableView.getDateValue(this.startDate);
            requestData.endDate = Craft.StripeButton.OrderTableView.getDateValue(this.endDate);
            requestData.currency = this.currency;

            if (requestData.source.includes('orderStatusId:')) {
                this.$exportButton.removeClass('enupal--hidden');
            }else{
                if (!requestData.source.includes('*')) {
                    this.$exportButton.addClass('enupal--hidden');
                }
            }

            this.$spinner.removeClass('enupal--hidden');
            this.$error.addClass('enupal--hidden');
            this.$chart.removeClass('error');

            Craft.postActionRequest('enupal-stripe/charts/get-revenue-data', requestData, $.proxy(function(response, textStatus) {
                this.$spinner.addClass('enupal--hidden');
                if (textStatus === 'success' && typeof(response.error) === 'undefined') {
                    if (!this.chart) {
                        this.chart = new Craft.charts.Area(this.$chart);
                    }

                    var chartDataTable = new Craft.charts.DataTable(response.dataTable);

                    var chartSettings = {
                        localeDefinition: response.localeDefinition,
                        orientation: response.orientation,
                        formats: response.formats,
                        dataScale: response.scale
                    };

                    this.chart.draw(chartDataTable, chartSettings);

                    this.$totalValue.html(response.totalHtml);
                }
                else {
                    var msg = Craft.t('enupal-stripe', 'An unknown error occurred.');

                    if (typeof(response) !== 'undefined' && response && typeof(response.error) !== 'undefined') {
                        msg = response.error;
                    }

                    this.$error.html(msg);
                    this.$error.removeClass('enupal--hidden');
                    this.$chart.addClass('error');
                }
            }, this));
        }
    },
    {
        storage: {},

        getStorage: function(namespace, key) {
            if (Craft.StripeButton.OrderTableView.storage[namespace] && Craft.StripeButton.OrderTableView.storage[namespace][key]) {
                return Craft.StripeButton.OrderTableView.storage[namespace][key];
            }

            return null;
        },

        setStorage: function(namespace, key, value) {
            if (typeof Craft.StripeButton.OrderTableView.storage[namespace] === typeof undefined) {
                Craft.StripeButton.OrderTableView.storage[namespace] = {};
            }

            Craft.StripeButton.OrderTableView.storage[namespace][key] = value;
        },

        getDateFromDatepickerInstance: function(inst) {
            return new Date(inst.currentYear, inst.currentMonth, inst.currentDay);
        },

        getDateValue: function(date) {
            return date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
        }
    });
