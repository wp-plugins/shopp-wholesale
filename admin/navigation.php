<?php

	$sw = 'shopp-wholesale-';
	$pages = array(
		'settings'=>'Settings',
		'presentation'=>'Presentation',
		'account-settings'=>'Accounts'
	);

	echo "<ul class='subsubsub'>";

	$last = count($pages);
	$i = 0;
	foreach ($pages as $page => $title) {
		$page = $sw . $page;
		$current = ($_GET['page'] == $page) ? "class='current'" : '';
		echo "<li><a href='?page=$page' $current>$title</a>";
		if (++$i != $last) echo " | ";
		echo "</li>";
	}

	echo "</ul>";
	echo "<br class='clear'>";

?>