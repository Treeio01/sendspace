function ga_event(category, action, label, value) {
    if (typeof ga !== 'function') {
        console.warn('Could not track event (ga not defined): ' + category + ',' + action + ', ' + label + ', ' + value);
        return false;
    }
    try {
        ga('send', 'event', category, action, label, value);
    } catch (e) {
        console.warn('Could not track event: ' + e.message);
        return false;
    }
    return true;
}
window.onerror = function (msg, url, line, col) {
    if (typeof msg === 'object' && msg.originalEvent && !(url || line || col)) {
        line = msg.originalEvent.lineno;
        col = msg.originalEvent.colno;
        url = msg.originalEvent.filename;
        msg = msg.originalEvent.message;
    }
    if (!url && !line && !col)
        return false;
    if (url && !url.match(/^https?:\/\/([^\.\/]+\.)?sendspace\.com/))
        return false;
    if (msg.match(/dealply/i))
        return false;
    if (!url)
        url = '[h]' + location.href;
    ga_event('Error', 'JavaScript', url + ':' + line + ':' + (col ? col : '?') + ' - ' + msg);
    return false;
};

var sendspace;

// Fake console in case it's not available for the current browser
if (typeof (console) === 'undefined') {
    var functionNames = ['info', 'error', 'warn', 'dir', 'trace', 'log', 'assert'];
    console = {};
    for (var i = 0; i < functionNames.length; i++) {
        console[functionNames[i]] = function () {
        }
    }
}

function ga_pageView(page) {
    if (typeof ga !== 'function') {
        console.warn('Could not track page view (ga not defined): ' + page);
        return false;
    }

    try {
        ga('send', 'pageview', page);
    } catch (e) {
        console.warn('Could not track page view: ' + e.message);
        return false;
    }

    return true;
}

function toggle_parent_sons(a) {
    $(a).parent().parent().find("div").each(function () {
        $(this).toggle();
    });
}

function in_array(value, array) {
    for (var i in array) {
        if (array[i] == value)
            return true;
    }
    return false;
}

function remove_from_array(value, array) {
    var new_array = Array();
    for (var i in array) {
        if (array[i] != value) {
            new_array.push(array[i]);
        }
    }
    return new_array;
}

function ss_ajax(method, path, data) {
    if (!data)
        data = {};
    data.is_ajax = 1;

    // Wrap ajax call to handle response as generated
    // by ajax_response() in misc.inc.php

    var deferred = $.Deferred();

    $.ajax({
        dataType: 'json',
        type: method,
        url: path,
        data: data
    })
            .done(function (result) {
                if (result.success) {
                    deferred.resolve(result.data);
                } else {
                    deferred.reject(result.error, result.data);
                }
            })
            .fail(function (xhr) {
                // We don't know what we got back so don't send
                // back the responseText (as that could be a
                // complete HTML page and not an error message).
                console.warn(xhr.responseText);
                deferred.reject('Server error');
            });

    return deferred.promise();
}

function ss_show_message_box(type, message) {
    // error, warning, information, success
    if (type == 'warn')
        type = 'warning';
    if (type == 'info')
        type = 'information';
    var $container = $('#ajax-message-container');
    if (!$container.length) {
        console.warn('Ajax message container missing');
        return;
    }
    var html = '<div style="cursor: default" class="msg ' + type + '">' + message + '</div>';
    $container.html(html);
}

function ss_hide_message_box() {
    var $container = $('#ajax-message-container');
    if (!$container.length) {
        console.warn('Ajax message container missing');
        return;
    }
    $container.empty();
}

var sendspaceClass = function () {

    var self = this;
    var is_ie7 = navigator.userAgent.match(/MSIE 7/);

    this.init = function () {
        self.assign_page_events();
    };

    this.assign_page_events = function () {

        // input focus event
        $("input:not(.noautosel)").focus(function () {
            this.select();
        });

        // login frame event
        var $login_frame = $("#login_frame");
        $("a.login").click(function (e) {
            var rel = $(this).attr("rel");
            if (rel === "open")
            {
                /*
                 if ($.browser.msie)
                 $login_frame.show();
                 else
                 $login_frame.fadeIn("normal");
                 */
                $login_frame.show();
                $("#top_login_username").focus();
                if ($('#mobile_upload_page').length)
                    $('#login_frame ul.options li a').show();
                self.overlay.show();
                return false;
            }
            self.overlay.hide();
            return false;
        });

        this.overlay = new function () {
            var self = this;
            var $glass = $("#glass");
            this.show = function () {
                if (!is_ie7)
                    $glass.show();
            };

            this.hide = function () {
                if ($.browser.msie)
                    $("div.overlay").hide();
                else
                    $("div.overlay").fadeOut("fast");
                if (!is_ie7)
                    $glass.hide();

                if ($('.openid-popup').length)
                    $('.openid-popup').hide();

            };

            $glass.click(function () {
                self.hide();
            });
        }

        // link copy event
        $("a.copy.selectable").click(function () {
            var $link = $("<input/>")
                    .addClass("file_url")
                    .attr({"type": "text", "readonly": "readonly"})
                    .val($("a.link").text());
            $("a.link.selectable").replaceWith($link);
            self.assign_page_events();
            $link.focus().select();
        });
    };

    // Define uploader UI
    if ($("#progress_bar").length > 0)
    {
        this.progress = new function ()
        {
            //focus
            var $progress = $("#progress_bar");
            var w = $progress.find(".bar").width();
            var $stats = $progress.find(".stats");

            var backupTitle = ''; //parent.document.title;

            this.restoreTitle = function () {
                parent.document.title = backupTitle;
            };

            this.update = function (params)
            {
                this.move(params[0]);
                this.stats(params);
            };

            this.last_pos = 0;
            this.move = function (to)
            {
                try
                {
                    to = parseInt(to, 10);
                    if (to <= this.last_pos)
                        return;
                    this.last_pos = to;

                    var to_bar = to >= 100 ? 99 : to;

                    $progress
                            .find(".fill")
                            .css('width', to_bar + "%");
                    var w = $("#progress_bar").find(".bar").width();
                    var val = parseInt(w * to_bar / 100, 10);
                    if (val > 0)
                    {
                        $progress
                                .find(".tag")
                                .text(to_bar + "%")
                                .css('left', (val - 19) + "px");
                    }

                    parent.document.title = 'Upload ' + to_bar + '% done';
                } catch (e) {
                }
            };

            this.stats = function (params)
            {
                try
                {
                    $stats.find(".time_left").text(params[1]);
                    $stats.find(".elapsed span").text(params[2]);
                    $stats.find(".data").text(params[3]);
                    $stats.find(".total").text(params[4]);
                    //$stats.find(".kbps").text(((Math.round(params[5] * 1024 * 8)) / 1000) + 'kbps' + ' (' + params[5] + 'KB/s)');
                    $stats.find(".kbps").text(params[5] + '/s');
                } catch (e) {
                }
            };
        };
    }
    ;
};

function ss_toJson(o) {
    if (o === null) {
        return 'null';
    }

    var pairs, k, name, val, type = $.type(o);

    if (type === 'undefined') {
        return undefined;
    }

    // Also covers instantiated Number and Boolean objects,
    // which are typeof 'object' but thanks to $.type, we
    // catch them here. I don't know whether it is right
    // or wrong that instantiated primitives are not
    // exported to JSON as an {"object":..}.
    // We choose this path because that's what the browsers did.
    if (type === 'number' || type === 'boolean') {
        return String(o);
    }
    if (type === 'string') {
        return ss_toJson_quoteString(o);
    }
    // if (typeof o.toJSON === 'function') {
    // 	return $.toJSON(o.toJSON());
    // }
    if (type === 'date') {
        var month = o.getUTCMonth() + 1
                , day = o.getUTCDate()
                , year = o.getUTCFullYear()
                , hours = o.getUTCHours()
                , minutes = o.getUTCMinutes()
                , seconds = o.getUTCSeconds()
                , milli = o.getUTCMilliseconds();

        if (month < 10) {
            month = '0' + month;
        }
        if (day < 10) {
            day = '0' + day;
        }
        if (hours < 10) {
            hours = '0' + hours;
        }
        if (minutes < 10) {
            minutes = '0' + minutes;
        }
        if (seconds < 10) {
            seconds = '0' + seconds;
        }
        if (milli < 100) {
            milli = '0' + milli;
        }
        if (milli < 10) {
            milli = '0' + milli;
        }
        return '"' + year + '-' + month + '-' + day + 'T' +
                hours + ':' + minutes + ':' + seconds +
                '.' + milli + 'Z"';
    }

    pairs = [];

    if ($.isArray(o)) {
        for (k = 0; k < o.length; k++) {
            pairs.push(ss_toJson(o[k]) || 'null');
        }
        return '[' + pairs.join(',') + ']';
    }

    // Any other object (plain object, RegExp, ..)
    // Need to do typeof instead of $.type, because we also
    // want to catch non-plain objects.
    if (typeof o === 'object') {
        for (k in o) {
            // Only include own properties,
            // Filter out inherited prototypes
            if (Object.prototype.hasOwnProperty.call(o, k)) {
                // Keys must be numerical or string. Skip others
                type = typeof k;
                if (type === 'number') {
                    name = '"' + k + '"';
                } else if (type === 'string') {
                    name = ss_toJson_quoteString(k);
                } else {
                    continue;
                }
                type = typeof o[k];

                // Invalid values like these return undefined
                // from toJSON, however those object members
                // shouldn't be included in the JSON string at all.
                if (type !== 'function' && type !== 'undefined') {
                    val = ss_toJson(o[k]);
                    pairs.push(name + ':' + val);
                }
            }
        }
        return '{' + pairs.join(',') + '}';
    }
}

function ss_toJson_quoteString(str) {
    if (str.match(escape)) {
        return '"' + str.replace(escape, function (a) {
            var c = meta[a];
            if (typeof c === 'string') {
                return c;
            }
            c = a.charCodeAt();
            return '\\u00' + Math.floor(c / 16).toString(16) + (c % 16).toString(16);
        }) + '"';
    }
    return '"' + str + '"';
}


(function ($) {
    $.fn.ss_tooltip = function () {
        this.off('mouseenter.tooltip,click.tooltip').bind('mouseenter.tooltip', function ()
        {
            if (!this.title)
                return false;
            var tip = '' + this.title;
            if (tip.indexOf('*') !== -1)
            {
                tip = tip.split('*');
                tip = '<ul><li>' + tip.join('</li><li>') + '</li></ul>';
            }
            var tooltip = $('<div id="tooltip"></div>').css('opacity', 0)
                    .html(tip)
                    .appendTo('body');
            this.title = '';
            var target = $(this);

            var init_tooltip = function ()
            {
                if ($(window).width() < tooltip.outerWidth() * 1.5)
                    tooltip.css('max-width', $(window).width() / 2);
                else
                    tooltip.css('max-width', 340);

                var pos_left = target.offset().left + (target.outerWidth() / 2) - (tooltip.outerWidth() / 2),
                        pos_top = target.offset().top - tooltip.outerHeight() - 20;

                if (pos_left < 0)
                {
                    pos_left = target.offset().left + target.outerWidth() / 2 - 20;
                    tooltip.addClass('left');
                } else
                    tooltip.removeClass('left');

                if (pos_left + tooltip.outerWidth() > $(window).width())
                {
                    pos_left = target.offset().left - tooltip.outerWidth() + target.outerWidth() / 2 + 20;
                    tooltip.addClass('right');
                } else
                    tooltip.removeClass('right');

                if (pos_top < 0)
                {
                    var pos_top = target.offset().top + target.outerHeight();
                    tooltip.addClass('top');
                } else
                    tooltip.removeClass('top');

                tooltip.css({left: pos_left, top: pos_top})
                        .animate({top: '+=10', opacity: 1}, 50);
            };

            init_tooltip();

            $(window).resize(init_tooltip);

            var remove_tooltip = function ()
            {
                tooltip.animate({top: '-=10', opacity: 0}, 50, function ()
                {
                    $(this).remove();
                });

                target.attr('title', tip);
            };

            target.bind('mouseleave.tooltip', remove_tooltip);
            tooltip.bind('click.tooltip', remove_tooltip);
        });

        return this;
    };
}(jQuery));

$(function () {
    sendspace = new sendspaceClass();
    sendspace.init();
    
    $('.tooltip').ss_tooltip();
});

var ssmsg = {
    _returnto: $(document),
    bg: function () {
        if (!this._dlg_bg)
            this._dlg_bg = $('#ssmsg_bg');
        return this._dlg_bg;
    },
    dlg: function () {
        if (!this._dlg)
        {
            this._dlg = $('#ssmsg_msg').on('keydown', function (evt) {
                if (evt.which === 13)
                    return true;
                if (evt.which === 27)
                    return ssmsg.pop();
                if (evt.shiftKey && evt.which !== 9)
                {
                    evt.preventDefault();
                    return false;
                }
                if (!evt.which === 9)
                    return ssmsg.pop();
                evt.preventDefault();
                var tabbable = $(this).find('div > div > *');
                tabbable.eq(($(':focus').index() + (evt.shiftKey ? -1 : +1)) % tabbable.length).focus();
                return true;
            });
        }
        return this._dlg;
    },
    /**
     * Please note, this is a non-blocking, use native alert() where needed
     * @param str msg Message to show
     * @param str title Optional title for the dialog
     * @returns false
     */
    alert: function (msg, title) {
        this._returnto = $(':focus');
        this.bg().show();
        this.dlg().find('p').html(msg.replace(/\n/g, '<br />'))
                .end().find('h1').html(title).toggle(!!title)
                .end().show().addClass('fadeable');
        this.setup_buttons({
            title: title ? title : trn('Alert!', 'Alert!'),
            msg: msg,
            buttons: {
                ok: '<button class="sbtn">OK</button>'
            }
        });
        return false;
    },
    /**
     * Please note, this is a non-blocking, use native confirm() where needed
     * 
     * @param mixed msg Either options or a string to use with callback param
     * @returns optional onok Assigns a handler for clicking the ok button
     * @returns false
     */
    confirm: function (msg, onok) {
        var options = {
            msg: trn('Confirmation', 'Confirmation'),
            title: '',
            focus: 'ok',
            buttons: {
                //index => template
                ok: '<button class="sbtn" />',
                cancel: '<button class="sbtn caution" />'
            },
            labels: {
                //index => label, otherwise "Ucfirst" applied to index
                ok: trn('OK', 'OK')
            },
            handlers: {
                //ok: function () {},
            }
        };
        if (typeof msg === 'object')
        {
            options = $.extend(true, options, msg);
            if (onok)
                options.handlers.ok = onok;
        } else
        {
            options.msg = msg;
            options.handlers.ok = onok;
        }

        this._returnto = $(':focus');
        this.bg().show();
        this.handlers = options.handlers;
        this.dlg().find('p').html(options.msg.replace(/\n/g, '<br />'))
                .end().find('h1').html(options.title).toggle(!!options.title);
        this._dlg.show().addClass('fadeable');
        options.title = options.title || trn('Confirmation', 'Confirmation');
        this.setup_buttons(options);
        return false;
    },
    setup_buttons: function (options) {
        if (!options)
            options = {};
        var btn_parent = this.dlg().find('div > div').empty();
        $.each(options.buttons, function (index, tpl) {
            var label = options.labels && options.labels[index] ?
                    options.labels[index] :
                    trn('btn ' + index[0].toUpperCase() + index.substr(1), index[0].toUpperCase() + index.substr(1));
            tpl = $(tpl).attr('onclick', "return ssmsg.pop('" + index + "', event)")
                    .html(label)
                    .addClass('ssmsg-' + index);
            if (!tpl.attr('aria-label'))
                tpl.attr('aria-label', (options.title || '') + ' ' + options.msg + ' - ' + label);
            btn_parent.append(tpl);
        });
        this._dlg.toggleClass('multiple', btn_parent.find(' > *').length > 1);
        return btn_parent.find(options.focus ? '.ssmsg-' + options.focus : 'button:first').focus();
    },
    pop: function (result, evt) {
        if (evt)
            evt.stopPropagation();
        this.dlg().hide().removeClass('fadeable multiple');
        this.bg().hide();
        if (result && this.handlers && this.handlers[result])
            this.handlers[result]();
        this._returnto.focus();
        return false;
    }
};

function debounce(wait, func) {
    var to;
    return function () {
        var context = this, args = arguments, later = function () {
            to = null;
            func.apply(context, args);
        };
        clearTimeout(to);
        to = setTimeout(later, wait);
    };
}
