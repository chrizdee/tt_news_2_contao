<?php
if (	$_POST['source_host'] && 
		$_POST['source_user'] && 
		$_POST['source_password'] &&
		$_POST['source_database'] &&
		$_POST['source_pid'] &&
		$_POST['target_host'] && 
		$_POST['target_user'] && 
		$_POST['target_password'] &&
		$_POST['target_database'] &&
		$_POST['target_pid']
		)
{
	// connect to source_database
	$db_source = mysql_connect($_POST['source_host'], $_POST['source_user'], $_POST['source_password']);
	if (!$db_source)
	{
		$messages[] = '<div class="alert alert-error">Can not connect to source database!</div>';
		die;
	}
	mysql_select_db($_POST['source_database'], $db_source);

	// connect to target_database
	$db_target = mysql_connect($_POST['target_host'], $_POST['target_user'], $_POST['target_password'], true);
	if (!$db_target)
	{
		$messages[] = '<div class="alert alert-error">Can not connect to target database!</div>';
		die;
	}
	mysql_select_db($_POST['target_database'], $db_target);

	// get news from typo3 database
	$sql_source = '	SELECT 
						* 
					FROM 
						tt_news 
					WHERE 
						pid='.$_POST['source_pid'].' AND 
						sys_language_uid='.$_POST['source_lang_uid'];

	$query_source = mysql_query($sql_source, $db_source) or die ($messages[] = '<div class="alert alert-error">MySQL-Error: '.mysql_error().'</div>');

	// insert into contao database
	$num = 1;
	while ($row = mysql_fetch_array($query_source))
	{
		$log[] = 'Get uid:'.$row['uid'].' | title: '.$row['title'];

		if ($row['hidden'] == 0) $published = 1;
		else $published = 0;

		if ($row['starttime'] > 0) $starttime = ' start='.$row['starttime'].', ';
		if ($row['endtime'] > 0) $endtime = ' stop='.$row['endtime'].', ';

		if ($row['image'])
		{
			$images = explode(',', $row['image']);
			$image_alttexts = explode(chr(10), $row['imagealttext']);
			// at default, contao only supports one image, so we will take the first one from typo3
			$image = ' addImage=1, singleSRC="'.$_POST['target_image_path'].$images[0].'", alt="'.mysql_real_escape_string($image_alttexts[0]).'", ';
		}

		$sql_target = '	INSERT INTO 
					   		tl_news 
					   	SET 
							pid='.$_POST['target_pid'].',
							tstamp='.$row['tstamp'].',
							date='.$row['datetime'].',
							time='.$row['datetime'].',
							'.$starttime.$endtime.$image.'
							published='.$published.',
							headline="'.mysql_real_escape_string($row['title']).'",
							teaser="'.mysql_real_escape_string($row['short']).'",
							text="'.mysql_real_escape_string($row['bodytext']).'"';

		if ($_POST['testing_mode'] != '1')
		{
			$query_target = mysql_query($sql_target, $db_target) or die ($messages[] = '<div class="alert alert-error">MySQL-Error: '.mysql_error().'</div>');
			if ($query_target)
			{
				$log[] = 'Insert uid:'.$row['uid'].' | title: '.$row['title'];
				$num++;
			}
		}
	}
	if ($num > 1) $messages[] = '<div class="alert alert-success">Import finished. '.$num.' records successfully imported.</div>';
}
else
{
	$messages[] = '<div class="alert alert-error">Please fill in all fields.</div>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>tt_news to contao news importer</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">
	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
	<style type="text/css">
		body { position: relative; padding-top: 40px; }
		h1 { margin-bottom: 40px; }
		textarea { width: 100%; box-sizing: border-box; height: 180px; }
	</style>
</head>
<body>
	<div class="container">
		<div class="row">
			<h1>Typo3 to contao news importer</h1>
			<?php
			if (count($messages))
			{
				foreach ($messages as $message)
				{
					echo $message;
				}
			}
			?>

			<?php if (count($log)): ?>
			<h2>Log</h2>
			<textarea><?php
				foreach ($log as $message)
				{
					echo $message.chr(10);
				}
			?></textarea>
			<?php endif ?>

			<h2>Import settings</h2>
			<form method="post">
				<div class="row">
				  	<fieldset class="span6">
					    <legend>MySQL Typo3</legend>
					    <label>Host</label>
					    <input name="source_host" type="text" placeholder="localhost" value="<?php echo $_POST['source_host'] ?>">
					  	<label>User</label>
					    <input name="source_user" type="text" value="<?php echo $_POST['source_user'] ?>">
					    <label>Password</label>
					    <input name="source_password" type="text" value="<?php echo $_POST['source_password'] ?>">
					    <label>Database</label>
					    <input name="source_database" type="text" value="<?php echo $_POST['source_database'] ?>">
					    <label>Archive pid</label>
					    <input name="source_pid" type="text" value="<?php echo $_POST['source_pid'] ?>">
					    <label>Language uid</label>
					    <input name="source_lang_uid" type="text" value="<?php echo $_POST['source_lang_uid'] ?>">
				  	</fieldset>
				  	<fieldset class="span6">
					    <legend>MySQL Contao</legend>
					    <label>Host</label>
					    <input name="target_host" type="text" placeholder="localhost" value="<?php echo $_POST['target_host'] ?>">
					  	<label>User</label>
					    <input name="target_user" type="text" value="<?php echo $_POST['target_user'] ?>">
					    <label>Password</label>
					    <input name="target_password" type="text" value="<?php echo $_POST['target_password'] ?>">
					    <label>Database</label>
					    <input name="target_database" type="text" value="<?php echo $_POST['target_database'] ?>">
					    <label>Archive pid</label>
					    <input name="target_pid" type="text" value="<?php echo $_POST['target_pid'] ?>">
					    <label>Image path</label>
					    <input name="target_image_path" type="text" placeholder="tl_files/news/" value="<?php echo $_POST['target_image_path'] ?>">
				  	</fieldset>
				</div>
				<div class="control-group">
					<div class="controls">
					  	<label class="checkbox">
				      	<input name="testing_mode" type="checkbox" value="1" checked="checked"> Testing mode
				    	</label>
				    	<button type="submit" class="btn btn-large btn-primary">Import</button>
				    </div>
				</div>
			</form>
		</div>
	</div>
</body>
</html>