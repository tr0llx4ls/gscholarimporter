<?php
//settings.php
// Check if user has the required capability

if ( !current_user_can( 'manage_options' ) ) {
    return;
  }

// Render the page content
?>
<?php
gscholarimporter_settings_page();
