(function ($) {
    'use strict';

    $.fn.multipleInput = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.multipleInput');
            return false;
        }
    };

    var events = {
        /**
         * afterAddRow event is triggered after widget's initialization.
         * The signature of the event handler should be:
         *     function (event)
         * where event is an Event object.
         *
         */
        afterInit: 'afterInit',
        /**
         * afterAddRow event is triggered after successful adding new row.
         * The signature of the event handler should be:
         *     function (event, row)
         * where event is an Event object.
         *
         */
        beforeAddRow: 'beforeAddRow',
        /**
         * afterAddRow event is triggered after successful adding new row.
         * The signature of the event handler should be:
         *     function (event, row)
         * where event is an Event object.
         *
         */
        afterAddRow: 'afterAddRow',
        /**
         * beforeDeleteRow event is triggered before row will be removed.
         * The signature of the event handler should be:
         *     function (event, row)
         * where event is an Event object and row is html container of row for removal
         *
         * If the handler returns a boolean false, it will stop removal the row.
         */
        beforeDeleteRow: 'beforeDeleteRow',

        /**
         * afterAddRow event is triggered after successful removal the row.
         * The signature of the event handler should be:
         *     function (event)
         * where event is an Event object.
         *
         */
        afterDeleteRow: 'afterDeleteRow',

        /**
         * afterDropRow event is triggered after drop the row in sortable mode.
         * The signature of the event handler should be:
         *     function (event, row)
         * where event is an Event object and row is html container of dragged row
         */
        afterDropRow: 'afterDropRow'
    };

    var defaultOptions = {
        /**
         * the ID of widget
         */
        id: null,

        /**
         * the ID of related input in case of using widget for an active field
         */
        inputId: null,

        /**
         * the template of row
         */
        template: null,

        /**
         * array that collect js templates of widgets which uses in the columns
         */
        jsTemplates: [],

        /**
         * array of scripts which need to execute before initialization
         */
        jsInit: [],

        /**
         * how many row are allowed to render
         */
        max: 1,

        /**
         * a minimum number of rows
         */
        min: 1,

        /**
         * active form options of attributes
         */
        attributes: {},

        /**
         * default prefix of a widget's placeholder
         */
        indexPlaceholder: 'multiple_index',

        /**
         * whether need to show general error message or no
         */
        showGeneralError: false,

        /**
         * if need to prepend new row, not append
         */
        prepend: false
    };

    var isActiveFormEnabled = false;

    var methods = {
        init: function (options) {
            if (typeof options !== 'object') {
                console.error('Options must be an object');
                return;
            }

            var settings = $.extend(true, {}, defaultOptions, options || {}),
                $wrapper = $('#' + settings.id),
                form = $wrapper.closest('form'),
                inputId = settings.inputId;

            for (i in settings.jsInit) {
                window.eval(settings.jsInit[i]);
            }

            $wrapper.data('multipleInput', {
                settings: settings,
                currentIndex: 0
            });

            $wrapper.on('click.multipleInput', '.js-input-remove', function (e) {
                e.stopPropagation();
                removeInput($(this));
            });

            $wrapper.on('click.multipleInput', '.js-input-plus', function (e) {
                e.stopPropagation();
                addInput($(this));
            });

            $wrapper.on('click.multipleInput', '.js-input-clone', function (e) {
                e.stopPropagation();
                cloneInput($(this));
            });

            var i = 0,
                event = $.Event(events.afterInit);

            var intervalID = setInterval(function () {
                if (typeof form.data('yiiActiveForm') === 'object') {
                    var attribute = form.yiiActiveForm('find', inputId),
                        defaultAttributeOptions = {
                            enableAjaxValidation: false,
                            validateOnBlur: false,
                            validateOnChange: false,
                            validateOnType: false,
                            validationDelay: 500
                        };

                    // fetch default attribute options from active from attribute
                    if (typeof attribute === 'object') {
                        $.each(attribute, function (key, value) {
                            if (['id', 'input', 'container'].indexOf(key) === -1) {
                                defaultAttributeOptions[key] = value;
                            }
                        });

                        if (!settings.showGeneralError) {
                            form.yiiActiveForm('remove', inputId);
                        }
                    }

                    // append default options to option from settings
                    $.each(settings.attributes, function (attribute, attributeOptions) {
                        attributeOptions = $.extend({}, defaultAttributeOptions, attributeOptions);
                        settings.attributes[attribute] = attributeOptions;
                    });

                    $wrapper.data('multipleInput').settings = settings;

                    $wrapper.find('.multiple-input-list').find('input, select, textarea').each(function () {
                        addActiveFormAttribute($(this));
                    });

                    $wrapper.data('multipleInput').currentIndex = findMaxRowIndex($wrapper);
                    isActiveFormEnabled = true;

                    clearInterval(intervalID);
                    $wrapper.trigger(event);
                } else {
                    i++;
                }

                // wait for initialization of ActiveForm a second
                // If after a second system could not detect ActiveForm it means
                // that widget is used without ActiveForm and we should just complete initialization of the widget
                if (form.length === 0 || i > 10) {
                    clearInterval(intervalID);
                    isActiveFormEnabled = false;

                    if (typeof $wrapper.data('multipleInput') !== 'undefined') {
                        $wrapper.data('multipleInput').currentIndex = findMaxRowIndex($wrapper);
                    }

                    $wrapper.trigger(event);
                }
            }, 100);
        },

        add: function (values) {
            addInput($(this), values);
        },

        remove: function (index) {
            var row = null;
            if (index !== undefined) {
                row = $(this).find('.js-input-remove:eq(' + index + ')');
            } else {
                row = $(this).find('.js-input-remove').last();
            }

            removeInput(row);
        },

        clear: function () {
            $(this).find('.js-input-remove').each(function () {
                removeInput($(this));
            });
        },

        option: function(name, value) {
            value = value || null;

            var data = $(this).data('multipleInput'),
                settings = data.settings;
            if (value === null) {
                if (!settings.hasOwnProperty(name)) {
                    throw new Error('Option "' + name + '" does not exist');
                }
                return settings[name];
            } else if (settings.hasOwnProperty(name)) {
                settings[name] = value;
                data.settings = settings;
                $(this).data('multipleInput', data);
            }
        }
    };

    var cloneInput = function (btn) {
        let $wrapper  = $(btn).closest('.multiple-input').first();
        let data      = $wrapper.data('multipleInput');
        let settings  = data.settings;

        let values = {};

        btn.closest('.multiple-input-list__item').find('input, select, textarea').each(function (k, v) {
            let $element = $(v);

            let id = getInputId($element);
            if (id) {
                // todo still doesn't work for sinlge column
                let columnName = id.replace(settings.inputId, '').replace(/-\d+-/, '');
                if ($element.is(':checkbox')) {
                    if (!values.hasOwnProperty(columnName)) {
                        values[columnName] = [];
                    }

                    if ($element.is(':checked')) {
                        values[columnName].push($element.val());
                    }
                } else {
                    values[columnName] = $element.val();
                }
            }
        });

        addInput(btn, values);
    }

    var addInput = function (btn, rowValues) {
        rowValues = rowValues || {};

        let $wrapper  = $(btn).closest('.multiple-input').first();
        let data      = $wrapper.data('multipleInput');
        let settings  = data.settings;
        let inputList = $wrapper.children('.multiple-input-list').first();

        if (settings.max !== null && getRowsCount($wrapper) >= settings.max) {
            return;
        }

        let newRowIndex = data.currentIndex + 1;

        let template = replaceAll('{' + settings.indexPlaceholder + '}', newRowIndex, settings.template);
        let $newRow = $(template);

        var beforeAddEvent = $.Event(events.beforeAddRow);

        $wrapper.trigger(beforeAddEvent, [$newRow, newRowIndex]);
        if (beforeAddEvent.result === false) {
            return;
        }

        $newRow.find('input, select, textarea').each(function (index, element) {
            let $element = $(element);

            let id = getInputId($element);
            if (id) {
                let columnName = id.replace(settings.inputId, '').replace(/-\d+-?/, '');

                if (rowValues.hasOwnProperty(columnName)) {
                    let tag = $element.get(0).tagName;

                    let inputValue = rowValues[columnName];

                    switch (tag) {
                        case 'INPUT':
                            if ($element.is(':checkbox')) {
                                if (inputValue.indexOf($element.val()) !== -1) {
                                    $element.prop('checked', true);
                                }
                            } else {
                                $element.val(inputValue);
                            }

                            break;

                        case 'TEXTAREA':
                            $element.val(inputValue);
                            break;

                        case 'SELECT':
                            if (inputValue && inputValue.indexOf('option') !== -1) {
                                $element.append(inputValue);
                            } else {
                                var option = $element.find('option[value="' + inputValue + '"]');
                                if (option.length) {
                                    $element.val(inputValue);
                                }
                            }

                            break;

                        default:
                            break;
                    }
                }
            }
        });

        if (settings.prepend) {
            $newRow.hide().prependTo(inputList).fadeIn(300);
        } else {
            $newRow.hide().appendTo(inputList).fadeIn(300);
        }

        // in order to initialize an active form attribute we need to find an input wrapper and we can do it
        // only after adding a new rows to dom tree
        if (isActiveFormEnabled) {
            $newRow.find('input, select, textarea').each(function (index, element) {
                let $element = $(element);
                addActiveFormAttribute($element);
            });
        }

        let jsTemplate = null;
        for (var i in settings.jsTemplates) {
            jsTemplate = settings.jsTemplates[i];
            jsTemplate = replaceAll('{' + settings.indexPlaceholder + '}', newRowIndex, jsTemplate);
            jsTemplate = replaceAll('%7B' + settings.indexPlaceholder + '%7D', newRowIndex, jsTemplate);

            window.eval(jsTemplate);
        }

        $wrapper.data('multipleInput').currentIndex = newRowIndex;

        var afterAddEvent = $.Event(events.afterAddRow);
        $wrapper.trigger(afterAddEvent, [$newRow, newRowIndex]);
    };

    var removeInput = function ($btn) {
        var $wrapper  = $btn.closest('.multiple-input').first(),
            $toDelete = $btn.closest('.multiple-input-list__item'),
            data      = $wrapper.data('multipleInput'),
            settings  = data.settings;

        var rowsCount = getRowsCount($wrapper);
        if (rowsCount > settings.min) {
            var event = $.Event(events.beforeDeleteRow);
            $wrapper.trigger(event, [$toDelete, data.currentIndex]);

            if (event.result === false) {
                return;
            }

            if (isActiveFormEnabled) {
                $toDelete.find('input, select, textarea').each(function (index, ele) {
                    removeActiveFormAttribute($(ele));
                });
            }

            $toDelete.fadeOut(300, function () {
                $(this).remove();

                event = $.Event(events.afterDeleteRow);
                $wrapper.trigger(event, [$toDelete, rowsCount]);
            });
        }
    };

    /**
     * Add an attribute to ActiveForm.
     *
     * @param input
     */
    var addActiveFormAttribute = function (input) {
        var id = getInputId(input);

        // skip if we could not get an ID of input
        if (id === null) {
            return;
        }

        var ele = $('#' + id),
            wrapper = ele.closest('.multiple-input').first(),
            form = ele.closest('form');

        // do not add attribute which are not the part of widget
        if (wrapper.length === 0) {
            return;
        }

        // check that input has been already added to the activeForm
        if (typeof form.yiiActiveForm('find', id) !== 'undefined') {
            return;
        }

        var data = wrapper.data('multipleInput'),
            attributeOptions = {};

        // try to find options for embedded attribute at first.
        // For example the id of new input is example-1-field-0.
        // We remove last index and check whether attribute with such id exists or not.
        var bareId = id.replace(/-\d+-([^\d]+)$/, '-$1');
        if (data.settings.attributes.hasOwnProperty(bareId)) {
            attributeOptions = data.settings.attributes[bareId];
        } else {
            // fallback in case of using flatten widget - just remove all digital indexes
            // and check whether attribute exists or not.
            bareId = replaceAll(/-\d-/, '-', bareId);
            bareId = replaceAll(/-\d/, '', bareId);
            if (data.settings.attributes.hasOwnProperty(bareId)) {
                attributeOptions = data.settings.attributes[bareId];
            }
        }

        form.yiiActiveForm('add', $.extend({}, attributeOptions, {
            'id': id,
            'input': '#' + id,
            'container': '.field-' + id
        }));
    };

    /**
     * Removes an attribute from ActiveForm.
     */
    var removeActiveFormAttribute = function (ele) {
        var id = getInputId(ele);

        if (id === null) {
            return;
        }

        var form = $('#' + id).closest('form');

        if (form.length !== 0) {
            form.yiiActiveForm('remove', id);
        }
    };

    var getInputId = function ($input) {
        var id = $input.attr('id');

        if (typeof id === 'undefined') {
            id = $input.data('id');
        }

        if (typeof id === 'undefined') {
            return null;
        }

        return id;
    };

    var getRowsCount = function($wrapper) {
        return findRows($wrapper).length;
    };

    var findRows = function($wrapper) {
        return $wrapper
            .find('.multiple-input-list .multiple-input-list__item')
            .filter(function(){
                return $(this).parents('.multiple-input').first().attr('id') === $wrapper.attr('id');
            });
    }

    var findMaxRowIndex = function($wrapper) {
        let maxIndex = 0;

        findRows($wrapper).each(function(key, element) {
            let index = $(element).data('index');
            if (index > maxIndex) {
                maxIndex = index;
            }
        });

        return maxIndex;
    };

    var replaceAll = function (search, replace, subject) {
        if (!(subject instanceof String) && typeof subject !== 'string') {
            console.warn('Call replaceAll for non-string value: ' + subject);
            return subject;
        }

        return subject.split(search).join(replace);
    };
})(window.jQuery);
