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

        afterInit: function() {
            this.$explorerContainer = $('<div class="chart-explorer-container"></div>').prependTo(this.$container);

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
                $currencyContainer = $('<div class="date-range" />').appendTo($chartHeader),
                $dateRange = $('<div class="date-range" />').appendTo($chartHeader),
                $startDateContainer = $('<div class="datewrapper"></div>').appendTo($dateRange),
                $to = $('<span class="to light">to</span>').appendTo($dateRange),
                $endDateContainer = $('<div class="datewrapper"></div>').appendTo($dateRange),
                $currencyTo = $('<span class="to light"> - </span>').appendTo($dateRange),
                $total = $('<div class="total"></div>').appendTo($chartHeader),
                $totalLabel = $('<div class="total-label light">' + Craft.t('enupal-stripe', 'Total Revenue') + '</div>').appendTo($total),
                $totalValueWrapper = $('<div class="total-value-wrapper"></div>').appendTo($total),
                $totalValue = $('<span class="total-value">&nbsp;</span>').appendTo($totalValueWrapper);

            this.$chartExplorer = $chartExplorer;
            this.$totalValue = $totalValue;
            this.$chartContainer = $('<div class="chart-container"></div>').appendTo($chartExplorer);
            this.$spinner = $('<div class="spinner hidden" />').prependTo($chartHeader);
            this.$error = $('<div class="error"></div>').appendTo(this.$chartContainer);
            this.$chart = $('<div class="chart"></div>').appendTo(this.$chartContainer);

            this.$startDate = $('<input type="text" class="text" size="20" autocomplete="off" />').appendTo($startDateContainer);
            this.$endDate = $('<input type="text" class="text" size="20" autocomplete="off" />').appendTo($endDateContainer);
            this.$currency = $('<select title = "Currency" id="fields-currency" class="text" name="fields[currency]"><option value="*">All</option> <option value="AED">AED</option> <option value="AFN">AFN</option> <option value="ALL">ALL</option> <option value="AMD">AMD</option> <option value="ANG">ANG</option> <option value="AOA">AOA</option> <option value="ARS">ARS</option> <option value="AUD">AUD</option> <option value="AWG">AWG</option> <option value="AZN">AZN</option> <option value="BAM">BAM</option> <option value="BBD">BBD</option> <option value="BDT">BDT</option> <option value="BIF">BIF</option> <option value="BGN">BGN</option> <option value="BMD">BMD</option> <option value="BND">BND</option> <option value="BOB">BOB</option> <option value="BRL">BRL</option> <option value="BSD">BSD</option> <option value="BWP">BWP</option> <option value="BZD">BZD</option> <option value="CAD">CAD</option> <option value="CDF">CDF</option> <option value="CHF">CHF</option> <option value="CLP">CLP</option> <option value="CNY">CNY</option> <option value="COP">COP</option> <option value="CRC">CRC</option> <option value="CVE">CVE</option> <option value="CZK">CZK</option> <option value="DJF">DJF</option> <option value="DKK">DKK</option> <option value="DOP">DOP</option> <option value="DZD">DZD</option> <option value="EGP">EGP</option> <option value="ETB">ETB</option> <option value="EUR">EUR</option> <option value="FJD">FJD</option> <option value="FKP">FKP</option> <option value="GBP">GBP</option> <option value="GEL">GEL</option> <option value="GIP">GIP</option> <option value="GMD">GMD</option> <option value="GNF">GNF</option> <option value="GTQ">GTQ</option> <option value="GYD">GYD</option> <option value="HKD">HKD</option> <option value="HNL">HNL</option> <option value="HRK">HRK</option> <option value="HTG">HTG</option> <option value="HUF">HUF</option> <option value="IDR">IDR</option> <option value="ILS">ILS</option> <option value="INR">INR</option> <option value="ISK">ISK</option> <option value="JMD">JMD</option> <option value="JPY">JPY</option> <option value="KES">KES</option> <option value="KGS">KGS</option> <option value="KHR">KHR</option> <option value="KMF">KMF</option> <option value="KRW">KRW</option> <option value="KYD">KYD</option> <option value="KZT">KZT</option> <option value="LAK">LAK</option> <option value="LBP">LBP</option> <option value="LKR">LKR</option> <option value="LRD">LRD</option> <option value="LSL">LSL</option> <option value="MAD">MAD</option> <option value="MDL">MDL</option> <option value="MGA">MGA</option> <option value="MKD">MKD</option> <option value="MNT">MNT</option> <option value="MOP">MOP</option> <option value="MRO">MRO</option> <option value="MUR">MUR</option> <option value="MVR">MVR</option> <option value="MWK">MWK</option> <option value="MXN">MXN</option> <option value="MYR">MYR</option> <option value="MZN">MZN</option> <option value="NAD">NAD</option> <option value="NGN">NGN</option> <option value="NIO">NIO</option> <option value="NOK">NOK</option> <option value="NPR">NPR</option> <option value="NZD">NZD</option> <option value="PAB">PAB</option> <option value="PEN">PEN</option> <option value="PGK">PGK</option> <option value="PHP">PHP</option> <option value="PKR">PKR</option> <option value="PLN">PLN</option> <option value="PYG">PYG</option> <option value="QAR">QAR</option> <option value="RON">RON</option> <option value="RSD">RSD</option> <option value="RUB">RUB</option> <option value="RWF">RWF</option> <option value="SAR">SAR</option> <option value="SBD">SBD</option> <option value="SCR">SCR</option> <option value="SEK">SEK</option> <option value="SGD">SGD</option> <option value="SHP">SHP</option> <option value="SLL">SLL</option> <option value="SOS">SOS</option> <option value="SRD">SRD</option> <option value="STD">STD</option> <option value="SVC">SVC</option> <option value="SZL">SZL</option> <option value="THB">THB</option> <option value="TJS">TJS</option> <option value="TOP">TOP</option> <option value="TRY">TRY</option> <option value="TTD">TTD</option> <option value="TWD">TWD</option> <option value="TZS">TZS</option> <option value="UAH">UAH</option> <option value="UGX">UGX</option> <option value="USD">USD</option> <option value="UYU">UYU</option> <option value="UZS">UZS</option> <option value="VND">VND</option> <option value="VUV">VUV</option> <option value="WST">WST</option> <option value="XAF">XAF</option> <option value="XCD">XCD</option> <option value="XOF">XOF</option> <option value="XPF">XPF</option> <option value="YER">YER</option> <option value="ZAR">ZAR</option> <option value="ZMW">ZMW</option> </select>').appendTo($currencyContainer);
            this.$currencyField = $("#fields-currency");
            this.$startDate.datepicker($.extend({
                onSelect: $.proxy(this, 'handleStartDateChange')
            }, Craft.datepickerOptions));

            this.$endDate.datepicker($.extend({
                onSelect: $.proxy(this, 'handleEndDateChange')
            }, Craft.datepickerOptions));

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

            this.$spinner.removeClass('hidden');
            this.$error.addClass('hidden');
            this.$chart.removeClass('error');

            Craft.postActionRequest('enupal-stripe/charts/get-revenue-data', requestData, $.proxy(function(response, textStatus) {
                this.$spinner.addClass('hidden');
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
                    this.$error.removeClass('hidden');
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
