<?php
  $dow_names = array(
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
  );
?>
<tr class="pwps-mode-basic <?php echo ( $this->plugin->mode == 'basic' ) ? ' pwps-show' : ''; ?>">
  <th scope="row">
    <label for="pwps_days"><?php echo __( 'Days', $this->plugin->slug ); ?></label>
  </th>
  <td>
    <fieldset>
      <legend class="screen-reader-text">
        <span><?php echo __( 'Days', $this->plugin->slug ); ?></span>
      </legend>
      <?php
        for ( $i = 0; $i < 7; $i++ ) :
          $checked = ( $this->plugin->settings['days'][$i] == 'on' ) ? ' checked="checked"' : '';
      ?>
      <label for="pwps_days_<?php echo $i; ?>">
        <input name="pwps_days[<?php echo $i; ?>]" type="checkbox" id="pwps_days_<?php echo $i; ?>" value="on"<?php echo $checked; ?>>
        <?php echo __( $dow_names[ $i ], $this->plugin->slug ); ?>
      </label>
      <br>
      <?php
        endfor;
      ?>
    </fieldset>
  </td>
</tr>
