var select_alma_fee_plan_ids = select_alma_fee_plan_ids || [];
jQuery(document).ready(function() {
    /**
     * Disable or enable menuItem Option depending to the checkbox state
     *
     * @param {jQuery} $checkbox
     * @param {jQuery} $items
     */
    function toggleItems($checkbox, $items) {
        if ($checkbox.is(":checked")) {
            $items.addClass('alma_option_enabled');
            $items.removeClass('alma_option_disabled');
        } else {
            $items.removeClass('alma_option_enabled');
            $items.addClass('alma_option_disabled');
        }
    }

    /**
     * Build option classes depending on enabled check / uncheck status
     *
     * @param {string} optionValue as plan
     * @return {string}
     */
    function optionStatusClass(optionValue) {
        return jQuery('#woocommerce_alma_enabled_' + optionValue).is(":checked") ? 'alma_option_enabled' : 'alma_option_disabled';
    }

    /**
     * Decorate / render fee plan select & options
     */
    jQuery.widget('custom.almaSelectMenu', jQuery.ui.selectmenu, {
        _renderButtonItem: function (item) {
            return jQuery('<span>', {
                text: item.label,
                id: this.options.id + '_button_' + item.value,
                class: "ui-selectmenu-text " + optionStatusClass(item.value),
                style: 'border: 1px solid #8c8f94; background: white; border-radius: 4px; line-height: 1; width: 400px; font-size: 14px;'
            });
        },

        _renderItem: function ($ul, item) {
            var $li = jQuery('<li>', {
                class: "ui-menu-item li_" + item.value
            });
            var $wrapper = jQuery('<div>', {
                text: item.label,
                class: 'ui-menu-item-wrapper ' + optionStatusClass(item.value),
                id: this.options.id + '_item_' + item.value
            });
            $li.append($wrapper);
            return $li.appendTo($ul);
        }
    });

    /**
     * Check if ids provided from select_alma_fee_plan_ids are well formatted (number or string only)
     *
     * @param id
     * @return {*|boolean}
     */
    function validId(id) {
        return ["number", "string"].includes(typeof id) && !!String(id).trim();
    }

    /**
     * Check if number input is between its min & max attributes
     *
     * @param $input
     * @return {boolean}
     */
    function isBetweenMinMax($input) {
        var val = parseInt($input.val());
        var min = parseInt($input.attr('min'));
        var max = parseInt($input.attr('max'));
        return (val >= min && val <= max)
    }

    /**
     * Show plan by planKey with effects
     *
     * @param plan
     */
    function showPlan(plan) {
        jQuery('.alma_fee_plan').hide();
        var $sections = jQuery('.alma_fee_plan_' + plan);
        $sections.show();
        $sections.effect('highlight', 1500);
        $sections.find('b').effect('highlight', 5000);
    }

    /**
     * Loop on injected alma select ids
     */
    select_alma_fee_plan_ids.filter(validId).forEach(function (id) {
        var $select = jQuery('#' + id);
        var previousPlan = $select.val();
        /**
         * Display another feePlan on change (only if min max inputs are ok)
         */
        $select.almaSelectMenu({
            id: id,
            change: function (event) {
                var plan = $select.val();
                var showingPlan = true;
                jQuery('#woocommerce_alma_min_amount_' + previousPlan + ', #woocommerce_alma_max_amount_' + previousPlan).each(function () {
                    var $input = jQuery(this);
                    if (!isBetweenMinMax($input)) {
                        jQuery('button[type=submit]').click();
                        showingPlan = false;
                        event.preventDefault();
                        return false;
                    }
                })
                if (showingPlan) {
                    showPlan(plan);
                    previousPlan = plan;
                }
            }
        });
    })

    /**
     * Listen feePlan checkbox status then toggle select options status
     */
    jQuery('[id^=woocommerce_alma_enabled_]').change(function () {
        var $checkbox = jQuery(this);
        var plan = $checkbox.attr('id').substring(25);
        select_alma_fee_plan_ids.filter(validId).forEach(function (id) {
            var selector = '#' + id + '_item_' + plan + ", #" + id + '_button_' + plan;
            toggleItems($checkbox, jQuery(selector));
        });
    });

    /**
     * @param plan
     */
    function scrollToSection(plan) {
        $section = jQuery('#woocommerce_alma_' + plan + "_section");
        var keyframes = {
            scrollTop: $section.offset().top - 100 // wc admin have a top bar fixed => -100px
        };
        jQuery([document.documentElement, document.body]).animate(keyframes, 0);
    }

    /**
     * Handle submit action to check inputs and focus on error if any
     */
    jQuery(document).on('click', 'form button[type=submit]', function (e) {
        var isValid = true;
        var $input = null;
        jQuery('[id^=woocommerce_alma_min_amount_], [id^=woocommerce_alma_max_amount_]').each(function() {
            $input = jQuery(this);
            isValid &= isBetweenMinMax($input);
            if (!isValid) {
                return false;
            }
        });
        if (!isValid) {
            var plan = $input.attr('id').substring(22);
            select_alma_fee_plan_ids.filter(validId).forEach(function (id) {
                var $select = jQuery('#'+id);
                if ($select.find('option[value='+plan+']').length > 0) {
                    $select.val(plan);
                    $select.almaSelectMenu('refresh');
                    showPlan(plan);
                    scrollToSection(plan);
                }
            });
        }
    });

    /**
     * I18n
     */
    // console.log('list_lang_title = '+list_lang_title);

    if (1 == is_page_alma_payment) {

        parent = jQuery( "table.form-table tr:has(input#woocommerce_alma_title)" );
        input  = jQuery( "input#woocommerce_alma_title" );

        input.after( "<a href='#add_title_translation' id='add_title_translation'> + Ajouter des traductions</a>" );

        jQuery(document).on('click', '#add_title_translation', function (e) {

            e.preventDefault();
            // console.log('click plus');
            let new_item = parent.clone();
            new_item.addClass('tr_title_translation');
            new_item.children('.titledesc').html('');
            new_item.find('#add_title_translation').remove();
            new_item.find('input').attr({
                'id':'',
                'name':'alma_title_tmp',
                'value':''
            });
            new_item.find('fieldset').append(list_lang_title);
            new_item.insertAfter(parent);
        });

        jQuery(document).on('change', '.list_lang_title', function (e) {
            e.preventDefault();
            let new_id = 'woocommerce_alma_title_' + jQuery(this).val();
            let input = jQuery(this).siblings('input');
            input.attr({
                'id':new_id,
                'name':new_id
            });
        });

        // console.log('BEFORE');
        // console.log(lang_title_saved);

        try {
            var json = jQuery.parseJSON(lang_title_saved);
        }
        catch(err) {
            console.error('alma json "lang_title_saved" could not be parsed !');
        }
        // console.log('json');
        // console.log(json);

        jQuery.each( json, function( code_lang, value ) {
            // console.log( code_lang + ": " + value );
            jQuery('#add_title_translation').trigger('click');

            // console.log('code_lang');
            // console.log(code_lang);

            let my_input = jQuery('input[name=alma_title_tmp]').filter(":last");
            my_input.val(value).css('border', '1px turquoise solid');
            my_input.siblings('select').val(code_lang);
            my_input.siblings('select').trigger('change');
        });
    }

})















