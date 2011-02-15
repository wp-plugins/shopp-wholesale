<?php
	global $ShoppWholesale;
	$Controller = $ShoppWholesale->Admin->Controller;

	$_page_title = $Controller->getAdminPageTitle();
	$_icon_src = $Controller->getAdminPageIcon();
?>

<div class="wrap shopp">

	<!-- wordpress update message -->
	<?php if (isset($_REQUEST['updated'])): ?><div id="message" class="updated fade"><p>Shopp Wholesale settings saved.</p></div><?php endif; ?>

	<!-- specific controller message -->
	<?php //TODO: info, warn and error ?>
	<?php	foreach ($Controller->getMessages('info') as $message): ?>
			<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
	<?php endforeach; ?>

	<?php	foreach ($Controller->getMessages('warn') as $message): ?>
			<div id="warning" class="error"><p>WARNING: <?php echo $message; ?></p></div>
	<?php endforeach; ?>

	<?php	foreach ($Controller->getMessages('error') as $message): ?>
			<div id="error" class="error"><p><?php echo $message; ?></p></div>
	<?php endforeach; ?>

	<div style="background: url('<?php echo $_icon_src; ?>')"></div>
	<h2><img src="<?php echo $_icon_src; ?>" /> <?php ShoppWholesale::_e($_page_title); ?> </h2>

		<?php
			$Controller->printNavigationLinks();
			$Controller->printFormTag();
		?>
