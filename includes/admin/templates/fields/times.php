<tr class="pwps-mode-basic <?php echo ( $this->plugin->mode == 'basic' ) ? ' pwps-show' : ''; ?>">
  <th scope="row">
    <label for="pwps_times"><?php echo __( 'Times', $this->plugin->slug ); ?></label>
  </th>
  <td>
    <fieldset>
      <legend class="screen-reader-text">
        <span><?php echo __( 'Times', $this->plugin->slug ); ?></span>
      </legend>
      <div class="pwps-settings-times">
        <?php
          $i = 0;
          foreach ( $this->plugin->settings['times'] as $time ) :
        ?>
        <div class="pwps-settings-times-item">
          <label for="pwps_times_<?php echo $i; ?>">
            <input name="pwps_times[]" type="text" id="pwps_times_<?php echo $i; ?>" value="<?php echo $time; ?>">
            <button type="button" class="pwps-settings-remove-time button">
              <span class="screen-reader-text"><?php echo __( 'Delete', $this->plugin->slug ); ?></span>
              <span class="dashicons dashicons-trash"></span>
            </button>
          </label>
          <br>
        </div>
        <?php
            $i++;
          endforeach;
        ?>
        <button type="button" class="pwps-settings-add-time button" data-name="pwps_times[]" data-id="pwps_times_">+ <?php echo __( 'Add Time', $this->plugin->slug ); ?></button>
      </div>
    </fieldset>
  </td>
</tr>
