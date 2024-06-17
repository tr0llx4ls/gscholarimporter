<?php
//settings.php
// Check if user has the required capability

if ( !current_user_can( 'manage_options' ) ) {
    return;
  }

// Render the page content
?>
<h1>My Submenu Page</h1>
<p>This is the content of my submenu page.</p>
