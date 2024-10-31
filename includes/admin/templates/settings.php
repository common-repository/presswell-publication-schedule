<div class="wrap pwps-settings">
  <h1><?php echo __( 'Publication Schedule Settings', $this->plugin->slug ); ?></h1>
  <p><?php echo __( 'Configure the publication schedule below by setting the days of the week and times durring the day when new posts should be published. Changes will not effect any posts already scheduled.', $this->plugin->slug ); ?></p>
  <!-- Settings -->
  <form action="<?php echo admin_url( 'admin-post.php' ) ?>" method="POST">
    <input type="hidden" name="action" value="<?php echo $this->plugin->key; ?>_update_settings">
    <?php wp_nonce_field( $this->plugin->key . '_update_settings' ); ?>
    <table class="form-table">
      <?php $this->draw_fields(); ?>
    </table>
    <?php submit_button(); ?>
  </form>
  <!-- Reset -->
  <h2>Reset Plugin</h2>
  <p>Reset all plugin data to the defaults. This will not effect any posts already scheduled and can not be undone.</p>
  <form action="<?php echo admin_url( 'admin-post.php' ) ?>" method="POST">
    <input type="hidden" name="action" value="<?php echo $this->plugin->key; ?>_reset_settings">
    <?php wp_nonce_field( $this->plugin->key . '_reset_settings' ); ?>
    <?php submit_button( 'Reset Plugin', 'delete pwps-reset' ); ?>
  </form>
</div>
