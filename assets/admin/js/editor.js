/**
 * WPCalibrate Invoice Studio - Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initLineItemCalculator();
        initMediaUploader();
        initCustomerAutofill();
        initInvoicePreviewModal();
        initTemplateSettingsSync();
    });

    /**
     * Line Item Dynamic Rows & Live Calculations
     */
    function initLineItemCalculator() {
        var $table = $('#wpci-items-table');
        if (!$table.length) return;

        // Recalculate on input
        $table.on('input change', '.wpci-calc-trigger', function() {
            recalculateInvoiceTotals();
        });

        // Add Row
        $('#wpci-add-item-row').on('click', function(e) {
            e.preventDefault();
            var index = $table.find('tbody tr').length;
            var newRow = `
                <tr class="wpci-item-row" data-index="${index}">
                    <td><input type="text" name="line_items[${index}][description]" class="widefat" placeholder="Item description" required /></td>
                    <td><input type="text" name="line_items[${index}][sku]" style="width: 90px;" placeholder="SKU" /></td>
                    <td><input type="number" step="any" min="0.0001" name="line_items[${index}][quantity]" value="1" class="wpci-calc-trigger wpci-qty" style="width: 70px;" /></td>
                    <td><input type="number" step="0.01" min="0" name="line_items[${index}][unit_price]" value="0.00" class="wpci-calc-trigger wpci-price" style="width: 90px;" /></td>
                    <td><input type="number" step="0.01" min="0" name="line_items[${index}][discount]" value="0.00" class="wpci-calc-trigger wpci-discount" style="width: 70px;" /></td>
                    <td><input type="number" step="0.01" min="0" max="100" name="line_items[${index}][tax_rate]" value="0.00" class="wpci-calc-trigger wpci-tax-rate" style="width: 65px;" /></td>
                    <td class="wpci-row-total" style="font-weight: 600; text-align: right; padding-top: 12px;">$0.00</td>
                    <td style="text-align: center;"><button type="button" class="wpci-row-actions-btn wpci-remove-row" title="Remove">&times;</button></td>
                </tr>
            `;
            $table.find('tbody').append(newRow);
            recalculateInvoiceTotals();
        });

        // Remove Row
        $table.on('click', '.wpci-remove-row', function(e) {
            e.preventDefault();
            if ($table.find('tbody tr').length <= 1) {
                alert('An invoice must have at least one line item.');
                return;
            }
            $(this).closest('tr').remove();
            recalculateInvoiceTotals();
        });

        // Global adjustment triggers (shipping, fee, fixed discount, amount paid)
        $('#wpci_shipping_amount, #wpci_fee_amount, #wpci_fixed_discount, #wpci_amount_paid').on('input change', function() {
            recalculateInvoiceTotals();
        });

        // Initial calculation on load
        recalculateInvoiceTotals();
    }

    function recalculateInvoiceTotals() {
        var subtotal = 0.0;
        var totalItemDiscount = 0.0;
        var totalTax = 0.0;

        $('.wpci-item-row').each(function() {
            var $row = $(this);
            var qty = parseFloat($row.find('.wpci-qty').val()) || 0.0;
            var price = parseFloat($row.find('.wpci-price').val()) || 0.0;
            var discount = parseFloat($row.find('.wpci-discount').val()) || 0.0;
            var taxRate = parseFloat($row.find('.wpci-tax-rate').val()) || 0.0;

            var lineSubtotal = qty * price;
            subtotal += lineSubtotal;
            totalItemDiscount += discount;

            var discounted = Math.max(0, lineSubtotal - discount);
            var lineTax = discounted * (taxRate / 100.0);
            totalTax += lineTax;

            var lineTotal = discounted + lineTax;
            $row.find('.wpci-row-total').text('$' + lineTotal.toFixed(2));
        });

        var fixedDiscount = parseFloat($('#wpci_fixed_discount').val()) || 0.0;
        var shipping = parseFloat($('#wpci_shipping_amount').val()) || 0.0;
        var fee = parseFloat($('#wpci_fee_amount').val()) || 0.0;
        var amountPaid = parseFloat($('#wpci_amount_paid').val()) || 0.0;

        var totalDiscount = totalItemDiscount + fixedDiscount;
        var netSubtotal = Math.max(0, subtotal - totalItemDiscount);
        var discountedNet = Math.max(0, netSubtotal - fixedDiscount);
        var grandTotal = discountedNet + totalTax + shipping + fee;
        var amountDue = Math.max(0, grandTotal - amountPaid);

        $('#wpci-summary-subtotal').text('$' + subtotal.toFixed(2));
        $('#wpci-summary-discount').text('-$' + totalDiscount.toFixed(2));
        $('#wpci-summary-tax').text('$' + totalTax.toFixed(2));
        $('#wpci-summary-shipping').text('$' + shipping.toFixed(2));
        $('#wpci-summary-fee').text('$' + fee.toFixed(2));
        $('#wpci-summary-total').text('$' + grandTotal.toFixed(2));
        $('#wpci-summary-due').text('$' + amountDue.toFixed(2));
    }

    /**
     * Logo Media Uploader for Settings Page
     */
    function initMediaUploader() {
        $('#wpci-upload-logo-btn').on('click', function(e) {
            e.preventDefault();
            var frame = wp.media({
                title: 'Select Business Logo',
                button: { text: 'Use this logo' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#wpci_logo_id').val(attachment.id);
                $('#wpci-logo-preview-img').attr('src', attachment.url).show();
                $('#wpci-remove-logo-btn').show();
            });

            frame.open();
        });

        $('#wpci-remove-logo-btn').on('click', function(e) {
            e.preventDefault();
            $('#wpci_logo_id').val(0);
            $('#wpci-logo-preview-img').hide().attr('src', '');
            $(this).hide();
        });
    }

    /**
     * Customer Dropdown Autofill
     */
    function initCustomerAutofill() {
        $('#wpci_customer_user_id').on('change', function() {
            var $selected = $(this).find('option:selected');
            if (!$selected.val()) return;

            var name = $selected.data('name');
            var email = $selected.data('email');

            if (name && !$('#wpci_customer_name').val()) {
                $('#wpci_customer_name').val(name);
            }
            if (email && !$('#wpci_customer_email').val()) {
                $('#wpci_customer_email').val(email);
            }
        });
    }

    /**
     * Live Invoice Preview Modal
     */
    function initInvoicePreviewModal() {
        var modalHtml = `
            <div class="wpci-modal-backdrop" id="wpci-preview-modal-backdrop">
                <div class="wpci-modal-dialog">
                    <div class="wpci-modal-header">
                        <div class="wpci-modal-header-left">
                            <span class="dashicons dashicons-media-document" style="color: #60a5fa; font-size: 20px;"></span>
                            <h3 class="wpci-modal-title">Invoice Output Preview</h3>
                            <div class="wpci-modal-template-selector" id="wpci-modal-tpl-picker">
                                <button type="button" data-template="classic">Classic</button>
                                <button type="button" data-template="modern">Modern</button>
                                <button type="button" data-template="minimal">Minimal</button>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="#" target="_blank" id="wpci-modal-newtab-btn" class="button button-small" style="background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.25); color: #fff; text-decoration: none;" title="Open in new window">
                                <span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
                            </a>
                            <button type="button" class="wpci-modal-close-btn" id="wpci-modal-close-btn" title="Close preview">&times;</button>
                        </div>
                    </div>
                    <div class="wpci-modal-body">
                        <div id="wpci-modal-loader" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: #f8fafc; z-index: 2;">
                            <span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span>
                            <span style="font-size: 13px; color: #475569; font-weight: 600;">Rendering invoice preview...</span>
                        </div>
                        <iframe class="wpci-modal-iframe" id="wpci-preview-iframe" src="about:blank"></iframe>
                    </div>
                    <div class="wpci-modal-footer">
                        <div style="font-size: 12px; color: #64748b;">
                            <span>High-fidelity output matching Dompdf & browser print rendering.</span>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="button" id="wpci-modal-print-btn">
                                <span class="dashicons dashicons-printer" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
                                Print
                            </button>
                            <button type="button" class="button button-primary" id="wpci-modal-done-btn">Close Preview</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (!$('#wpci-preview-modal-backdrop').length) {
            $('body').append(modalHtml);
        }

        var $backdrop = $('#wpci-preview-modal-backdrop');
        var $iframe = $('#wpci-preview-iframe');
        var $loader = $('#wpci-modal-loader');
        var $newTabBtn = $('#wpci-modal-newtab-btn');
        var currentTemplate = 'modern';
        var currentUuid = '';

        function buildPreviewUrl(template, uuid) {
            var base = (window.wpciEditorData && window.wpciEditorData.directPreviewUrl)
                ? window.wpciEditorData.directPreviewUrl
                : 'admin-post.php?action=wpci_preview_template';

            var url = base + '&template=' + encodeURIComponent(template);
            if (uuid) {
                url += '&uuid=' + encodeURIComponent(uuid);
            }
            return url;
        }

        function openModal(template, uuid) {
            currentTemplate = template || getActiveTemplate();
            currentUuid = uuid || '';

            // Update template switcher active state
            $('#wpci-modal-tpl-picker button').removeClass('active');
            $('#wpci-modal-tpl-picker button[data-template="' + currentTemplate + '"]').addClass('active');

            var previewUrl = buildPreviewUrl(currentTemplate, currentUuid);
            $newTabBtn.attr('href', previewUrl);

            $loader.show();
            $iframe.attr('src', previewUrl);
            $backdrop.addClass('active');
            $('body').css('overflow', 'hidden');
        }

        function closeModal() {
            $backdrop.removeClass('active');
            $iframe.attr('src', 'about:blank');
            $('body').css('overflow', '');
        }

        function getActiveTemplate() {
            // Check settings radio
            var $checked = $('input[name="default_template"]:checked');
            if ($checked.length) {
                return $checked.val();
            }
            // Check editor select
            var $editorSelect = $('#wpci_template');
            if ($editorSelect.length && $editorSelect.val()) {
                return $editorSelect.val();
            }
            return 'modern';
        }

        $iframe.on('load', function() {
            $loader.fadeOut(150);
        });

        // Template switcher clicks inside modal
        $('#wpci-modal-tpl-picker').on('click', 'button', function(e) {
            e.preventDefault();
            var tpl = $(this).data('template');
            if (!tpl || tpl === currentTemplate) return;

            currentTemplate = tpl;
            $('#wpci-modal-tpl-picker button').removeClass('active');
            $(this).addClass('active');

            var previewUrl = buildPreviewUrl(currentTemplate, currentUuid);
            $newTabBtn.attr('href', previewUrl);
            $loader.show();
            $iframe.attr('src', previewUrl);
        });

        // Trigger buttons
        $(document).on('click', '.wpci-preview-template-btn, #wpci-header-preview-btn, #wpci-preview-template-btn', function(e) {
            e.preventDefault();
            var tpl = $(this).data('template') || getActiveTemplate();
            openModal(tpl, '');
        });

        $(document).on('click', '.wpci-preview-editor-btn', function(e) {
            e.preventDefault();
            var tpl = $('#wpci_template').val() || getActiveTemplate();
            var uuid = $('#wpci_invoice_uuid').val() || '';
            openModal(tpl, uuid);
        });

        $(document).on('click', '.wpci-trigger-preview', function(e) {
            e.preventDefault();
            var uuid = $(this).data('uuid') || '';
            var tpl = $(this).data('template') || getActiveTemplate();
            openModal(tpl, uuid);
        });

        // Print button inside modal
        $('#wpci-modal-print-btn').on('click', function(e) {
            e.preventDefault();
            try {
                var iframeWin = $iframe[0].contentWindow;
                if (iframeWin) {
                    iframeWin.focus();
                    iframeWin.print();
                }
            } catch (err) {
                // If cross-origin or blocked, open in new tab
                window.open($iframe.attr('src'), '_blank');
            }
        });

        // Close handlers
        $('#wpci-modal-close-btn, #wpci-modal-done-btn').on('click', function(e) {
            e.preventDefault();
            closeModal();
        });

        $backdrop.on('click', function(e) {
            if ($(e.target).is($backdrop)) {
                closeModal();
            }
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $backdrop.hasClass('active')) {
                closeModal();
            }
        });
    }

    /**
     * Template Settings Visual Sync
     */
    function initTemplateSettingsSync() {
        // Toggle active style on template cards
        $('.wpci-template-picker-grid').on('change', 'input[type="radio"]', function() {
            $('.wpci-template-card').removeClass('active');
            $(this).closest('.wpci-template-card').addClass('active');
        });

        // Color input <-> hex text input sync
        function syncColorPair(pickerId, hexId) {
            var $picker = $(pickerId);
            var $hex = $(hexId);
            if (!$picker.length || !$hex.length) return;

            $picker.on('input change', function() {
                $hex.val($(this).val().toUpperCase());
            });

            $hex.on('input change', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $picker.val(val);
                }
            });
        }

        syncColorPair('#primary_color', '#primary_color_hex');
        syncColorPair('#accent_color', '#accent_color_hex');
    }

})(jQuery);

