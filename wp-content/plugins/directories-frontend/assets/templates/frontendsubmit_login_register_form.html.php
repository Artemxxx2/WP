<?php
$form = $this->Form_Render($form);
$html = $form->getHtml(true);
$is_two_columns = isset($html['register']) || isset($html['guest']);
$hidden_html = $form->getHiddenHtml();
$js_html = $form->getJsHtml(false);
?>
<?php echo $form->getHeaderHtml();?>
<?php echo $form->getFormTag();?>
    <div class="drts-frontendsubmit-login-register-form-column" data-column-type="login">
        <div class="drts-frontendsubmit-login-form">
            <h2><?php echo $this->H(__('Login', 'directories-frontend'));?></h2>
            <?php echo $html['login'];?>
        </div>
    </div>
<?php if ($is_two_columns):?>
    <div class="drts-frontendsubmit-login-register-form-separator">
        <div class="drts-frontendsubmit-login-register-form-separator-line"></div>
        <div class="drts-frontendsubmit-login-register-form-separator-word">
            <span><?php echo $this->H(__('or', 'directories-frontend'));?></span>
        </div>
    </div>
    <div class="drts-frontendsubmit-login-register-form-column" data-column-type="register">
<?php   if (isset($html['register'])):?>
        <div class="drts-frontendsubmit-register-form">
            <h2><?php echo $this->H(__('Register', 'directories-frontend'));?></h2>
            <?php echo $html['register'];?>
        </div>
<?php   endif;?>
<?php   if (isset($html['guest'])):?>
        <div class="drts-frontendsubmit-guest-form">
            <h2><?php echo $this->H(__('Continue as guest', 'directories-frontend'));?></h2>
            <?php echo $html['guest'];?>
        </div>
<?php   endif;?>
    </div>
<?php endif;?>
<?php echo $hidden_html;?>
</form>
<script type="text/javascript">
<?php echo $js_html;?>
<?php if (\SabaiApps\Directories\Request::isXhr()):?>
jQuery(function($) {
<?php else:?>
document.addEventListener('DOMContentLoaded', function(event) { var $ = jQuery;
<?php endif;?>
    var form = $('#<?php echo $form->settings['#id'];?>');
<?php if ($is_two_columns):?>
    setTimeout(function () {
        form.find('.drts-frontendsubmit-login-register-form-separator').css('height', form.outerHeight() + 'px');
    }, 500); // wait for recaptcha to load
<?php endif;?>
    form.on('keypress', function (e) {
        var key = e.keyCode || e.which;
        if (key === 13) {
            if (document.activeElement && $(document.activeElement).is(':input')) {
                var _form = $(document.activeElement).closest('.drts-frontendsubmit-login-form, .drts-frontendsubmit-register-form, .drts-frontendsubmit-guest-form');
                if (_form.length > 0) {
                    e.preventDefault();
                    _form.find('button[type=submit]:not(:disabled)').click();
                }
            }
        }
    });
});
</script>