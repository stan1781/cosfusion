/**
 * WooCommerce 评论批量操作 - JavaScript
 * 处理批量设置国家时的对话框交互
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // 检查是否有批量操作相关的数据
        if (typeof wcReviewImagesBulk === 'undefined') {
            return;
        }

        var $bulkActionSelect = $('#bulk-action-selector-top, #bulk-action-selector-bottom');
        var $doActionButton = $('#doaction, #doaction2');
        // 支持标准评论页面和WooCommerce产品评论页面
        var $commentsForm = $('#comments-form, #reviews-filter, #posts-filter, form[name="posts-filter"]');
        
        // 如果找不到表单，尝试查找包含批量操作的表单
        if ($commentsForm.length === 0) {
            $commentsForm = $bulkActionSelect.closest('form');
        }
        
        // 如果还是找不到表单，尝试查找包含批量操作选择器的表单
        if ($commentsForm.length === 0) {
            $commentsForm = $('form').has($bulkActionSelect);
        }
        
        // 如果找到了批量操作选择器，添加批量操作选项
        if ($bulkActionSelect.length > 0) {
            var bulkActions = {
                'wc_bulk_verify_buyer': '标记为认证买家',
                'wc_bulk_unverify_buyer': '取消认证买家',
                'wc_bulk_set_country': '批量设置国家'
            };
            
            // 添加批量操作选项到下拉菜单（延迟执行以确保DOM完全加载）
            setTimeout(function() {
                $bulkActionSelect.each(function() {
                    var $select = $(this);
                    $.each(bulkActions, function(value, text) {
                        if ($select.find('option[value="' + value + '"]').length === 0) {
                            $select.append('<option value="' + value + '">' + text + '</option>');
                        }
                    });
                });
            }, 200);
        }

        // 监听批量操作按钮点击
        $doActionButton.on('click', function(e) {
            var $select = $(this).prev('select');
            var action = $select.val();

            // 如果选择的是批量设置国家操作
            if (action === wcReviewImagesBulk.bulk_set_country_action) {
                e.preventDefault();
                e.stopPropagation();

                // 检查是否有选中的评论（支持标准评论页面和WooCommerce产品评论页面）
                var checkedComments = $('input[name="check[]"]:checked, input[name="delete_comments[]"]:checked');
                if (checkedComments.length === 0) {
                    alert('请先选择要操作的评论。');
                    return false;
                }

                // 显示国家选择对话框
                showCountryDialog(action, $select);
                return false;
            }
        });

        /**
         * 显示国家选择对话框
         */
        function showCountryDialog(action, $select) {
            // 创建对话框HTML
            var dialogHTML = '<div id="wc-review-country-dialog" style="display:none;">';
            dialogHTML += '<div class="wc-review-country-dialog-content" style="padding:20px;background:#fff;border-radius:4px;box-shadow:0 2px 10px rgba(0,0,0,0.1);min-width:300px;">';
            dialogHTML += '<h3 style="margin-top:0;">' + wcReviewImagesBulk.select_country + '</h3>';
            dialogHTML += '<select id="wc-review-country-select" style="width:100%;padding:8px;margin-bottom:15px;">';
            dialogHTML += '<option value="">' + wcReviewImagesBulk.select_country + '</option>';

            // 添加国家选项
            if (wcReviewImagesBulk.countries && typeof wcReviewImagesBulk.countries === 'object') {
                for (var code in wcReviewImagesBulk.countries) {
                    if (wcReviewImagesBulk.countries.hasOwnProperty(code)) {
                        dialogHTML += '<option value="' + code + '">' + wcReviewImagesBulk.countries[code] + '</option>';
                    }
                }
            }

            dialogHTML += '</select>';
            dialogHTML += '<div style="text-align:right;">';
            dialogHTML += '<button type="button" class="button wc-review-country-cancel" style="margin-right:10px;">' + wcReviewImagesBulk.cancel + '</button>';
            dialogHTML += '<button type="button" class="button button-primary wc-review-country-confirm">' + wcReviewImagesBulk.confirm + '</button>';
            dialogHTML += '</div>';
            dialogHTML += '</div>';
            dialogHTML += '</div>';

            // 如果对话框已存在，先移除
            $('#wc-review-country-dialog').remove();

            // 添加到页面
            $('body').append(dialogHTML);

            // 使用WordPress的Thickbox或者简单的遮罩层
            var $dialog = $('#wc-review-country-dialog');
            var $overlay = $('<div id="wc-review-country-overlay" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:100000;display:flex;align-items:center;justify-content:center;"></div>');
            
            // 将对话框内容移到遮罩层中
            $overlay.append($dialog.find('.wc-review-country-dialog-content'));
            $('body').append($overlay);
            $dialog.remove();

            // 显示对话框
            $overlay.fadeIn(200);

            // 关闭对话框的函数
            function closeDialog() {
                $overlay.fadeOut(200, function() {
                    $overlay.remove();
                    $(document).off('keydown.wc-review-country-dialog');
                });
            }

            // 确认按钮点击事件（使用事件委托）
            $overlay.on('click', '.wc-review-country-confirm', function() {
                var countryCode = $('#wc-review-country-select').val();
                
                if (!countryCode) {
                    alert(wcReviewImagesBulk.select_country);
                    return;
                }

                // 移除可能存在的旧隐藏字段
                $commentsForm.find('input[name="bulk_country_code"]').remove();

                // 创建隐藏字段保存国家代码
                var $hiddenInput = $('<input>', {
                    type: 'hidden',
                    name: 'bulk_country_code',
                    value: countryCode
                });

                // 添加到表单
                $commentsForm.append($hiddenInput);

                // 关闭对话框
                closeDialog();

                // 设置选择的操作并提交表单
                $select.val(action);
                $commentsForm.submit();
            });

            // 取消按钮点击事件（使用事件委托）
            $overlay.on('click', '.wc-review-country-cancel', function() {
                closeDialog();
            });

            // 点击遮罩层关闭
            $overlay.on('click', function(e) {
                if (e.target === this) {
                    closeDialog();
                }
            });

            // ESC键关闭
            $(document).on('keydown.wc-review-country-dialog', function(e) {
                if (e.keyCode === 27) { // ESC键
                    closeDialog();
                }
            });
        }
    });

})(jQuery);

