<?php get_header(); ?>

<div class="row">
    <div class="span12">
        <div id="main-content">

            <div class="row">
                <div class="span10 offset1">
                    <div class="page-header">
                        <h1>
                            <?php echo get_the_title(); ?>
                        </h1>
                    </div><!-- end .page-header -->
                </div><!-- end .span10 -->
            </div><!-- end .row -->

            <div class="row">
                <div class="span10 offset1">
                    <p>
                    <?php echo get_the_content(); ?>
                    </p>
                </div><!-- end .span10 -->
            </div><!-- end .row -->

        </div><!-- end #main-content -->
    </div><!-- end .span12 -->
</div><!-- end .row -->


<?php get_footer(); ?>
