function licenserGenerateUniqueId() {
    if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
        const randomBytes = new Uint8Array(16);
        crypto.getRandomValues(randomBytes);

        // Set version (4) and variant bits (2 bits long)
        randomBytes[6] = (randomBytes[6] & 0x0f) | 0x40;
        randomBytes[8] = (randomBytes[8] & 0x3f) | 0x80;

        return Array.from(randomBytes)
            .map(function(byte) {
                return byte.toString(16).padStart(2, '0');
            })
            .join('');
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

jQuery(document).ready(function($) {
    var i18n = (typeof Licenser !== 'undefined' && Licenser.i18n) ? Licenser.i18n : {};

    function msg(key, fallback) {
        return i18n[key] || fallback;
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function escapeSingleQuote(str) {
        return String(str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    function formDataToJson(formEl) {
        var formData = new FormData(formEl);
        var jsonObject = {};

        formData.forEach(function(value, key) {
            if (key.includes('[') && key.includes(']')) {
                var keys = key.match(/\w+/g);
                var currentObject = jsonObject;
                for (var i = 0; i < keys.length - 1; i++) {
                    currentObject[keys[i]] = currentObject[keys[i]] || {};
                    currentObject = currentObject[keys[i]];
                }
                currentObject[keys[keys.length - 1]] = value;
            } else {
                jsonObject[key] = value;
            }
        });

        return jsonObject;
    }

    function renderTemplate(templateId, data) {
        var el = document.getElementById(templateId);
        if (!el) {
            return '';
        }

        var html = el.innerHTML;
        Object.keys(data || {}).forEach(function(key) {
            var pattern = new RegExp('\\{\\{\\{\\s*data\\.' + key + '\\s*\\}\\}\\}', 'g');
            html = html.replace(pattern, escapeHtml(data[key]));
        });

        return html;
    }

    // Notice helper (supports both new `licenser_notice` and legacy `lmfwppt_notice`).
    $(document).on('licenser_notice lmfwppt_notice', function(event, notice, type) {
        if (type === 'remove') {
            $('.lmfwppt-notices').html('');
            return;
        }

        var $notice = $('<div class="notice notice-alt is-dismissible"></div>')
            .addClass('notice-' + type)
            .append($('<p></p>').text(notice));

        $('.lmfwppt-notices').empty().append($notice);
        $(document).trigger('wp-updates-notice-added');
    });

    // Accordion Toggle.
    $(document).on('click', '.lmfwppt-toggle-head', function(e) {
        if ($(e.target).hasClass('prevent-toggle-head') || $(e.target).hasClass('remove-lwp-postbox')) {
            return;
        }
        e.preventDefault();
        $(this).parent().toggleClass('opened').find('.lmfwppt-toggle-wrap').slideToggle('fast');
        return false;
    });

    // Remove Section Field.
    $(document).on('click', '.remove-lwp-postbox', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var postBox = $(this).closest('.lwp-postbox');
        $(document).trigger('lwp_postbox_removed', postBox);
        postBox.remove();
    });

    // Media Frame File Picker.
    var fileFrame, pushSelector;
    $(document).on('click', '.trigger_media_frame', function() {
        var $this = $(this);

        pushSelector = $this.attr('data-push_selector');
        setMediaState('open');

        if (undefined !== fileFrame) {
            fileFrame.open();
            return;
        }

        fileFrame = wp.media.frames.file_frame = wp.media({
            title: 'Select File',
            multiple: false,
            library: {},
            button: { text: 'Insert' }
        });

        fileFrame.on('select', function() {
            var attachment = fileFrame.state().get('selection').first().toJSON();
            $(pushSelector).val(attachment.url);
        });

        fileFrame.on('close', function() {
            setMediaState('close');
        });

        fileFrame.open();
    });

    function setMediaState(state) {
        if (typeof Licenser === 'undefined' || !Licenser.media_state_nonce) {
            return;
        }

        $.post(Licenser.ajaxurl || ajaxurl, {
            action: 'licenser_media_frame_state',
            nonce: Licenser.media_state_nonce,
            state: state || 'close'
        });
    }

    // -------------------------------------------------------------------------
    // Products List & Edit Form
    // -------------------------------------------------------------------------
    $(document).on('click', '.licenser-delete-product', function(e) {
        e.preventDefault();
        var $this = $(this);
        var productId = $this.data('id');
        var wrapper = $this.closest('tr');

        if (!productId || !confirm(msg('confirm_delete_product', 'Are you sure you want to delete this product?'))) {
            return;
        }

        $.ajax({
            type: 'delete',
            url: Licenser.rest_url + 'products/' + productId,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
                wrapper.css('opacity', '0.5');
            },
            success: function() {
                $(document).trigger('licenser_notice', [msg('product_deleted', 'Product deleted successfully.'), 'success']);
                wrapper.remove();
            },
            error: function() {
                wrapper.css('opacity', '1');
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    $(document).on('submit', '#product-form', function(e) {
        e.preventDefault();
        var $this = $(this);
        var jsonObject = formDataToJson(this);
        var productType = $('#product_type').val() || 'plugin';

        $.ajax({
            type: 'post',
            url: Licenser.rest_url + 'products',
            data: JSON.stringify(jsonObject),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
                $this.find('.spinner').addClass('is-active');
                $this.find('[type="submit"]').prop('disabled', true);
                $(document).trigger('licenser_notice', ['', 'remove']);
            },
            complete: function() {
                $this.find('.spinner').removeClass('is-active');
                $this.find('[type="submit"]').prop('disabled', false);
            },
            success: function(res) {
                if ($('.lmfwppt_edit_id').val()) {
                    $(document).trigger('licenser_notice', [msg('product_updated', 'Product updated successfully.'), 'success']);
                } else if (res && res.id) {
                    $(document).trigger('licenser_notice', [msg('product_added', 'Product added successfully. Redirecting...'), 'success']);
                    window.location = Licenser.admin_url + '?page=licenser-' + productType + 's&action=edit&id=' + res.id + '&message=1';
                }
            },
            error: function() {
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    $(document).on('click', '.add-license-package', function() {
        var html = renderTemplate('licenser-tmpl-license-package-field', {
            field_id: licenserGenerateUniqueId(),
            id: '',
            label: '',
            update_period: '',
            domain_limit: ''
        });
        $('#license-packages-fields').append(html);
        $('#license-packages-fields .lmfwppt-toggle-head').last().click();
    });

    $(document).on('click', '.add-release-btn', function() {
        var html = renderTemplate('licenser-tmpl-add-release-field', {});
        $('.add-release-form').html(html);
        $('.hide-on-add-release').hide();
    });

    $(document).on('click', '.cancel-release', function() {
        $('.add-release-form').html('');
        $('.hide-on-add-release').show();
    });

    $(document).on('lwp_postbox_removed', function(e, postBox) {
        var $this = $(postBox);
        if ($this.attr('data-postbox-id') !== 'lwp_delete_package') {
            return;
        }

        var postBoxId = $this.find('.license-package-id').val();
        if (!postBoxId) {
            return;
        }

        $.ajax({
            type: 'delete',
            url: Licenser.rest_url + 'products/packages/' + postBoxId,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
            },
            error: function() {
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    $(document).on('input', '#product-form #slug', function() {
        var value = $(this).val().replace(/\s+/g, '-').replace(/[^a-zA-Z0-9-_]/g, '');
        $(this).val(value);
    });

    // Hydrate existing product packages on product edit screen.
    var $packagesContainer = $('#license-packages-fields');
    if ($packagesContainer.length) {
        var rawPackages = $packagesContainer.attr('data-packages');
        if (rawPackages) {
            try {
                var parsedPackages = JSON.parse(rawPackages);
                if (Array.isArray(parsedPackages)) {
                    $packagesContainer.empty();
                    parsedPackages.forEach(function(pkg) {
                        $packagesContainer.append(renderTemplate('licenser-tmpl-license-package-field', {
                            field_id: pkg.id,
                            id: pkg.id,
                            label: pkg.label,
                            update_period: pkg.update_period,
                            domain_limit: pkg.domain_limit
                        }));
                    });
                }
            } catch (err) {
                // Ignore malformed JSON attribute.
            }
        }
    }

    // -------------------------------------------------------------------------
    // Licenses List & Edit Form
    // -------------------------------------------------------------------------
    $(document).on('click', '.licenser-delete-license', function(e) {
        e.preventDefault();
        var $this = $(this);
        var licenseId = $this.data('id');
        var wrapper = $this.closest('tr');

        if (!licenseId || !confirm(msg('confirm_delete_license', 'Are you sure you want to delete this license?'))) {
            return;
        }

        $.ajax({
            type: 'delete',
            url: Licenser.rest_url + 'licenses/' + licenseId,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
                wrapper.css('opacity', '0.5');
            },
            success: function() {
                $(document).trigger('licenser_notice', [msg('license_deleted', 'License deleted successfully.'), 'success']);
                wrapper.remove();
            },
            error: function() {
                wrapper.css('opacity', '1');
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    $(document).on('submit', '#license-add-form', function(e) {
        e.preventDefault();
        var $this = $(this);
        var jsonObject = formDataToJson(this);

        $.ajax({
            type: 'post',
            url: Licenser.rest_url + 'licenses',
            data: JSON.stringify(jsonObject),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
                $this.find('.spinner').addClass('is-active');
                $this.find('[type="submit"]').prop('disabled', true);
                $(document).trigger('licenser_notice', ['', 'remove']);
            },
            complete: function() {
                $this.find('.spinner').removeClass('is-active');
                $this.find('[type="submit"]').prop('disabled', false);
            },
            success: function(res) {
                if ($('.lmfwppt_edit_id').val()) {
                    $(document).trigger('licenser_notice', [msg('license_updated', 'License updated successfully.'), 'success']);
                } else if (res && res.id) {
                    $(document).trigger('licenser_notice', [msg('license_added', 'License added successfully. Redirecting...'), 'success']);
                    window.location = Licenser.admin_url + '?page=licenser-licenses&action=edit&id=' + res.id + '&message=1';
                }
            },
            error: function() {
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    $(document).on('click', '#generate_key', function(e) {
        e.preventDefault();
        var $this = $(this);

        $.ajax({
            type: 'post',
            url: Licenser.rest_url + 'licenses/generate-key',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
                $this.find('.spinner').addClass('is-active');
                $this.find('.generate-key-label').hide();
            },
            complete: function() {
                $this.find('.spinner').removeClass('is-active');
                $this.find('.generate-key-label').show();
            },
            success: function(data) {
                $('#license_key').val(data);
            },
            error: function() {
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    $(document).on('change', '#license-add-form #product_type', function(e, isEdit) {
        var thisVal = $(this).val();
        $('#license-add-form .theme-opt, #license-add-form .plugin-opt').hide();

        if (!isEdit) {
            $('#license-add-form .products_list').val('');
            $('#lmfwppt_package_list').val('');
        }

        if (thisVal) {
            $('#license-add-form .' + thisVal + '-opt').show();
        } else {
            $('#license-add-form .theme-opt, #license-add-form .plugin-opt').show();
        }
    });

    $(document).on('change', '#license-add-form .products_list', function(e, isEdit) {
        var $this = $(this);
        var packageSelect = $('#lmfwppt_package_list');
        var selected = packageSelect.attr('data-pack_value') || '';

        if (!$this.val()) {
            packageSelect.html('<option value="" class="blank">' + escapeHtml(msg('select_package', 'Select Package')) + '</option>').prop('disabled', true);
            return;
        }

        $.ajax({
            type: 'get',
            data: {
                product_id: $this.val()
            },
            url: Licenser.rest_url + 'products/packages',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
            },
            success: function(packages) {
                var options = '';
                if (packages && packages.length) {
                    options = '<option value="" class="blank">' + escapeHtml(msg('select_package', 'Select Package')) + '</option>';
                    packages.forEach(function(pkg) {
                        options += '<option value="' + escapeHtml(pkg.id) + '">' + escapeHtml(pkg.label) + '</option>';
                    });
                    packageSelect.prop('disabled', false);
                } else {
                    options = '<option value="" class="blank">' + escapeHtml(msg('no_package_found', 'No Package Found')) + '</option>';
                    packageSelect.prop('disabled', true);
                }

                packageSelect.html(options);

                if (isEdit && selected) {
                    packageSelect.val(selected);
                }
            },
            error: function() {
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    $(document).on('click', '.lwp-delete-domain', function(e) {
        e.preventDefault();
        var $this = $(this);
        var domainId = $this.data('id');

        if (!confirm(msg('confirm_delete_domain', 'Are you sure you want to delete this domain?'))) {
            return;
        }

        $.ajax({
            type: 'delete',
            url: Licenser.rest_url + 'licenses/domains/' + domainId,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
            },
            success: function() {
                $this.closest('.postbox').remove();
                $(document).trigger('licenser_notice', [msg('domain_deleted', 'Domain deleted successfully.'), 'success']);
            },
            error: function() {
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    $(document).on('click', '.lwp-delete-meta', function(e) {
        e.preventDefault();
        var $this = $(this);
        var metaId = $this.data('id');

        if (!confirm(msg('confirm_delete_meta', 'Are you sure you want to delete this metadata entry?'))) {
            return;
        }

        $.ajax({
            type: 'delete',
            url: Licenser.rest_url + 'licenses/meta/' + metaId,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
            },
            success: function() {
                $this.closest('.postbox').remove();
                $(document).trigger('licenser_notice', [msg('meta_deleted', 'Meta deleted successfully.'), 'success']);
            },
            error: function() {
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    if ($('#license-add-form').length) {
        if ($('#license_key').val() === '') {
            $('#generate_key').trigger('click');
        }
        if ($('#license-add-form #product_type').val()) {
            $('#license-add-form #product_type').trigger('change', [true]);
        }
        if ($('#license-add-form .products_list').val()) {
            $('#license-add-form .products_list').trigger('change', [true]);
        }
    }

    // -------------------------------------------------------------------------
    // Settings Form
    // -------------------------------------------------------------------------
    $(document).on('submit', '#setting-add-form', function(e) {
        e.preventDefault();
        var $this = $(this);
        var jsonObject = formDataToJson(this);

        $.ajax({
            type: 'post',
            url: Licenser.rest_url + 'settings',
            data: JSON.stringify(jsonObject),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
                $this.find('.spinner').addClass('is-active');
                $this.find('[type="submit"]').prop('disabled', true);
                $(document).trigger('licenser_notice', ['', 'remove']);
            },
            complete: function() {
                $this.find('.spinner').removeClass('is-active');
                $this.find('[type="submit"]').prop('disabled', false);
            },
            success: function() {
                $(document).trigger('licenser_notice', [msg('settings_saved', 'Settings saved successfully.'), 'success']);
            },
            error: function() {
                $(document).trigger('licenser_notice', [msg('generic_error', 'Something went wrong. Try again.'), 'error']);
            }
        });
    });

    // -------------------------------------------------------------------------
    // SDK Generator Form
    // -------------------------------------------------------------------------
    $(document).on('change', '#sdk-generator-form #product_type', function() {
        var type = $(this).val();
        $('#select_product').val('');
        $('#select_product option').not('.blank').hide();
        if (type) {
            $('#select_product option.' + type + '-opt').show();
        } else {
            $('#select_product option').show();
        }
    });

    $(document).on('change', '#sdk-generator-form #select_product', function() {
        var productName = $('#select_product option:selected').text();
        if (!$(this).val()) {
            $('#lmfwppt_page_title').val('');
            $('#lmfwppt_menu_title').val('');
            return;
        }
        $('#lmfwppt_page_title').val(productName + ' License Activation');
        $('#lmfwppt_menu_title').val(productName + ' License');
    });

    $(document).on('submit', '#sdk-generator-form', function(e) {
        e.preventDefault();

        var productType = $('#sdk-generator-form #product_type').val();
        var productId = $('#select_product').val();
        var productName = $('#select_product option:selected').text();
        var productSlug = productName.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '') || 'my_product';
        var menuType = $('#lmfwppt_menu_select').val();
        var parentSlug = $('#lmfwppt_parent_menu_slug').val();
        var pageTitle = $('#lmfwppt_page_title').val();
        var menuTitle = $('#lmfwppt_menu_title').val();
        var apiUrl = (typeof Licenser !== 'undefined' && Licenser.sdk_api_url) ? Licenser.sdk_api_url : window.location.origin;
        var incLicensing = $('#inc-licensing').is(':checked');
        var incUpdater = $('#inc-updater').is(':checked');
        var incInsights = $('#inc-insights').is(':checked');

        var stepHeading = (productType === 'plugin')
            ? msg('sdk_step_plugin', 'Step 2: Add the following code to your plugin file.')
            : msg('sdk_step_theme', "Step 2: Add the following code to your theme's functions.php file.");

        var output = String.fromCharCode(60) + "?php\n" +
            "/**\n" +
            " * Initialize Licenser client.\n" +
            " *\n" +
            " * @return void\n" +
            " */\n" +
            "function " + productSlug + "_licenser_client_init() {\n" +
            "\tif ( ! class_exists( 'Licenser\\\\Client' ) ) {\n" +
            "\t\trequire_once __DIR__ . '/licenser/src/Client.php';\n" +
            "\t}\n\n" +
            "\t$client = new Licenser\\Client( '" + escapeSingleQuote(productId) + "', '" + escapeSingleQuote(productName) + "', __FILE__, '" + escapeSingleQuote(apiUrl) + "' );\n";

        if (incLicensing) {
            output += "\n\t// Activate license page and checker.\n\t$license = $client->license();\n";
            if (menuType !== 'section') {
                output += "\t$license->add_settings_page( [\n" +
                    "\t\t'type'        => '" + escapeSingleQuote(menuType) + "',\n" +
                    "\t\t'menu_title'  => '" + escapeSingleQuote(menuTitle) + "',\n" +
                    "\t\t'page_title'  => '" + escapeSingleQuote(pageTitle) + "',\n" +
                    "\t\t'menu_slug'   => '" + escapeSingleQuote(productSlug) + "_settings',\n" +
                    "\t\t'parent_slug' => '" + escapeSingleQuote(parentSlug) + "',\n" +
                    "\t] );\n";
            } else {
                output += "\t$license->add_settings_page( [\n" +
                    "\t\t'type' => 'section',\n" +
                    "\t] );\n";
            }
        }

        if (incUpdater) {
            output += "\n\t// Activate automatic updater.\n\t$client->updater()->init( $client );\n";
        }

        if (incInsights) {
            output += "\n\t// Activate usage insights.\n\t$client->insights()->init();\n";
        }

        output += "}\nadd_action( 'init', '" + productSlug + "_licenser_client_init' );";

        var $container = $('.client_generator_response').empty();
        $container.append($('<h3></h3>').text(stepHeading));
        var $textarea = $('<textarea class="fancy-textarea" readonly></textarea>').val(output);
        $container.append($textarea);

        if (typeof wp !== 'undefined' && wp.codeEditor && typeof licenser_cm !== 'undefined') {
            wp.codeEditor.initialize($textarea, licenser_cm);
        }
    });
});
