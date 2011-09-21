<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=9" />
		<title>SwarmCMS</title>
		<link rel="stylesheet" type="text/css" href="/?r=minify/css&page=home/index" />
	</head>
	<body id="the-body">
		<div id="main-body">
			<div id="task-bar">
				<div id="task-bar-start"></div>
			</div>
			<div id="program-box" class="rounded-corners">
				<div id="program-scroll-box" class="rounded-corners">
					<ul>
						<?php foreach ($modules as $module) : ?>
							<li>
								<span class="ui-icon <?php echo $module['name'] ?>-icon program-box-icon"></span>
								<a role="button" title="<?php echo $module['title'] ?>" class="main" href="#<?php echo $module['name'] ?>"><?php echo $module['description'] ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div id="program-scroll-box-details">
					<?php foreach ($modules as $module) : ?>
						<ul id="<?php echo $module['name'] ?>">
							<?php foreach ($module['windows'] as $window) : ?>
								<li>
									<a role="button" class="window" title="<?php echo $module['description'] ?> - <?php echo $window['title'];?>" href="#<?php echo $window['name'];?>">
										<?php echo $window['title'];?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<script type="text/javascript" src="/?r=minify/js&page=home/index"></script>
	</body>
</html>