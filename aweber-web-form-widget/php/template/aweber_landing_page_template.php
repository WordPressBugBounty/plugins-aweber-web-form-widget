<?php
   global $post;
   /* Load and display the synchronized landing page content.
    *
    * The landing page HTML is echoed out as-is. It has already passed security
    * controls and sanitization on the AWeber side, and must be preserved to
    * maintain full functionality here.
    */

   $content = get_post_field('post_content', $post->ID);

   # Extract Head from the Content
   preg_match('/<head>(.*?)<\/head>/s', $content, $head);

   # fetch the information in the head and body tags
   preg_match('/<head(.*?)>/s', $content, $head_tag);
   preg_match('/<body(.*?)>/s', $content, $body_tag);

   # Extract Body from the Content
   preg_match('/<body>(.*?)<\/body>/s', $content, $body);

   # Extract the content above the Head Tag
   preg_match('/(.*?)<head>/s', $content, $content_above_head_tag);
?>
<?php
# Combining the extracted content from the AWeber landing pages.
# Output the content above the Head Tag.
   echo isset($content_above_head_tag[1]) ? $content_above_head_tag[1] : '<!DOCTYPE html><html>';?>
   <head<?php echo isset($head_tag[1]) ? $head_tag[1] : ''; ?>>
      <?php
         # Hook that allows WordPress, themes, and plugins to add HTML wherever it is placed
         wp_head();

         # Output the AWeber Landing Page head content.
         echo isset($head[1]) ? $head[1] : '';
		?>
         <style type="text/css">
            .aweber-page-not-reverted{
               color: #ce2222;
               text-align: center;
            }
         </style>
      </head>
	<body<?php echo isset($body_tag[1]) ? $body_tag[1] : ''; ?>>
		<?php
      # Output the AWeber Landing Page body content.
      # If their is no body tag involved, then just display the content, might content other HTML tags.
      echo isset($body[1]) ? $body[1] : $content;

      # Hooks, Injects scripts in the footer or before the body tag closed.
      wp_footer();
      ?>
      <!-- AWeber for WordPress <?php echo AWEBER_PLUGIN_VERSION; ?> -->
   </body>
</html>
