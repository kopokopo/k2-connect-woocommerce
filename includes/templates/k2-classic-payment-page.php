<?php
defined('ABSPATH') || exit;

get_header();
?>

<main class="wp-block-group">
    <div class="k2 modal-overlay">
      <div class="modal-body">
        <div class="modal-content">
           
        </div>
        <div class="modal-footer">
            Powered by <img src="<?php echo plugins_url('../../images/k2-logo-with-name.png', __FILE__); ?>" alt="Kopo Kopo (Logo)" />
        </div>
      </div>
      <p class='switch-to-manual-payments'>Having trouble ? Pay via 
          <button id='switch-to-manual-payments' class="link">M-PESA Buy Goods</button>
      </p>
    </div>
</main>

<?php get_footer();?>
