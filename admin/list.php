<?php
	global $ShoppWholesale;
	$Controller = $ShoppWholesale->Admin->Controller;
	$Controller->printSearchBox();
	$Controller->printBulkActions();
?>

<table class="widefat" cellspacing="0">

	<thead>
		<tr>
			<?php $Controller->printColumns(); ?>
		</tr>
	</thead>

	<tfoot>
		<tr>
			<?php $Controller->printColumns(false); ?>
		</tr>
	</tfoot>

	<tbody id="list-table" class="list">

		<?php
			$listData = $Controller->getListData();
			if (empty($listData)) {
				$label = $Controller->getRecordLabel(true);
?>
				<tr><td colspan="99"><?php ShoppWholesale::_e('No'); ?> <?php ShoppWholesale::_e("$label, yet."); ?></td></tr>
<?php
			}	else {
				foreach ($listData as $result) {
					$Controller->printTableRow($result);
				}
			}
		?>

	</tbody>
</table>
