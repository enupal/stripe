if (typeof Craft.StripeButton === typeof undefined) {
    Craft.StripeButton = {};
}

Craft.StripeButton.TableRowAdditionalInfoIcon = Garnish.Base.extend(
    {
        $icon: null,
        hud: null,

        init: function(icon) {
            this.$icon = $(icon);
            this.addListener(this.$icon, 'click', 'showHud');
        },

        showHud: function() {
                var item = this.$icon.closest('.infoRow');

                var $hudBody = $("<div />");
                var $title = $('<h2>Details</h2>').appendTo($hudBody);
                var $table = $("<table class='data fullwidth detailHud'><tbody></tbody></table>").appendTo($hudBody);
                var $tbody = $table.find('tbody');

                var info = item.data('info');
                var value = info;
                value = '<code class="language-json">'+JSON.stringify(value, undefined, 4)+'</code>';
                var $tr = $('<tr />').appendTo($tbody);
                var $label = $('<td><strong>' + Craft.t('enupal-stripe', 'All info') + '</strong></td><td>').appendTo($tr);
                $value = $('<td>'+value+'</td>');
                $value.appendTo($tr);

                this.hud = new Garnish.HUD(this.$icon, $hudBody, {
                    hudClass: 'hud'
                });

        }
    });

// Borrowed from https://stackoverflow.com/a/7220510/2040791
function syntaxHighlight(json) {
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}