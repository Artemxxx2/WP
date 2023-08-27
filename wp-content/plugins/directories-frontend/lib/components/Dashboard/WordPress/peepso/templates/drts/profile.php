<div class="peepso ps-page-profile">
    <?php PeepSoTemplate::exec_template('general','navbar');?>

    <?php PeepSoTemplate::exec_template('profile', 'focus', ['current'=> $link_name]);?>

    <section id="mainbody" class="ps-page-unstyled">
        <section id="component" role="article" class="ps-clearfix">

            <?php echo $content;?>

        </section><!--end component-->
    </section><!--end mainbody-->
</div><!--end row-->
<?php PeepSoTemplate::exec_template('activity', 'dialogs'); ?>
