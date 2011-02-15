<?php
	global $ShoppWholesale;
	$Controller = $ShoppWholesale->Admin->Controller;
	$Object = $Controller->getObject();
?>

<div id="poststuff" class="metabox-holder has-right-sidebar">

	<div id="side-info-column" class="inner-sidebar">
	<?php
		$side_meta_boxes = do_meta_boxes($Controller->getSlug(), 'side', $Object);
	?>
	</div>

	<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : 'has-sidebar'; ?>">

		<div id="post-body-content" class="has-sidebar-content">
		<?php
			do_meta_boxes($Controller->getSlug(), 'normal', $Object);
			//wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
		?>
		</div>

	</div>

</div> <!-- #poststuff -->