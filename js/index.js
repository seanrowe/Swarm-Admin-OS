$(document).ready(function()
{
	/**
	 * When the user clicks the task bar button,
	 * either hide the program box or hide it.
	 * We stop event propogation so that body
	 * events below the task bar are not executed
	 */
	$('#task-bar-start').click(function(e)
	{
		$('#program-box').toggle();
		$('#program-scroll-box-details ul').hide();

		e.stopPropagation();
	});

	/**
	 * We do no want events below the program box
	 * to execute when the program box is clicked,
	 * so we stop propagation
	 */
	$('#program-box').click(function(e)
	{
		e.stopPropagation();
	});

	/**
	 * When the user clicks to minimize a window, we
	 * set the data variable 'minimized' to true
	 * so that it shows up in the task bar, and then
	 * close the window.
	 */
	$('.ui-dialog-titlebar-minimize').live('click', function()
	{
		$(this).parent().next().data('minimized', true);
		$(this).parent().next().dialog('close');
	});

	/**
	 * When the user clicks on an icon in the task bar,
	 * it will display a list of all the windows that
	 * are grouped within the icon
	 */
	$('.minified').live('click', function()
	{
		$(this).children('.minified-list').toggle();
	});

	/**
	 * When the user clicks a link in the minified list
	 * that displays after the user clicks on a minified
	 * icon in the program task bar, it will user the
	 * href property of the anchor to determine which
	 * window we are working with.  If the window is
	 * already open, clicking on the link will minimize
	 * the window;  if the window is closed, it will
	 * open the window.  Event propagation is stopped
	 * at this point so that clickable events at the body
	 * level are not executed
	 */
	$('.minified-list ul li a').live('click', function(e)
	{
		var parent_href = $(this).attr('href').substr(1) + '-window';

		if ($('#' + parent_href).dialog('isOpen'))
		{
			$('#' + parent_href).data('minimized', true);
			$('#' + parent_href).dialog('close');
		}

		else
		{
			$('#' + parent_href).dialog('open');
		}

		$('.minified-list').hide();
		e.stopPropagation();
	});

	/**
	 * When the body is clicked, or a window is
	 * clicked
	 */
	$('#the-body, .window').click(function()
	{
		/**
		 * The program box and the details box within the
		 * program box will be hidden, so that when the
		 * program box is next opened, it will not show
		 * the last opened details
		 */
		if ($('#program-box').css('display') == 'block')
		{
			$('#program-box').hide();
			$('#program-scroll-box-details ul').hide();
		}

		$('.minified-list').hide();

		/**
		 * When a link is clicked that is listed in
		 * the program-scroll-box-details, it will
		 * create a window and then display it
		 */
		if ($(this).hasClass('window') && 'a' == $(this).context.localName)
		{
			var parent_href = $(this).attr('href').substr(1);
			var parent_id = $(this).parent().parent().attr('id');
			var id = parent_href + '-window';

			/**
			 * If the window doesn't exist, then create it.
			 * This creates the outer shell.
			 */
			if (!$('#' + id).get(0))
			{
				$('body').append('<div id="' + id + '" class="ui-widget window-body rounded-corners-bottom" style="display:none" title="' + $(this).attr('title') + '"></div>');
			}

			/**
			 * If the group taskbar icon does not exist, then
			 * create it
			 */
			if (!$('#' + parent_id + '-task-bar').get(0))
			{
				$('#task-bar').append(
					'<div id="' + parent_id + '-task-bar" class="minified">' +
						'<span class="ui-icon ' + parent_id + '-icon minify-box-icon"></span>' +
						'<div id="' + parent_id + '-task-bar-list" class="minified-list rounded-corners">' +
							'<ul><li><a role="button" href="#' + parent_href + '" title="' + $(this).attr('title') + '">' + $(this).attr('title') + '</a></li></ul>' +
						'</div>' +
					'</div>'
				);
			}

			/**
			 * If the group task bar icon already exists, but
			 * does not contain a link to the window, then
			 * add it to the group
			 */
			else if (!$('#' + parent_id + '-task-bar-list ul li a[href=#' + parent_href + ']').get(0))
			{
				$('#' + parent_id + '-task-bar-list ul').append(
					'<li><a role="button" href="#' + parent_href + '" title="' + $(this).attr('title') + '">' + $(this).attr('title') + '</a></li>'
				);
			}

			$('#' + id).dialog({
				autoOpen: false,
				height: 300,
				width: 350,

				/**
				 * Open the new window, and if the titlebar
				 * does not already exist, create it with the
				 * minimize and maximize buttons
				 */
				open: function()
				{
					$('#' + id).data('minimized', false);

					if (!$('div[aria-labelledby=ui-dialog-title-' + id + '] .ui-dialog-titlebar-maximize').get(0))
					{
						$('div[aria-labelledby=ui-dialog-title-' + id + '] .ui-dialog-titlebar-close').after(
							'<a href="#" rel="' + parent_href + '" class="ui-dialog-titlebar-maximize ui-corner-all" role="button"><span class="ui-icon ui-icon-plusthick">maximize</span></a>' +
							'<a href="#" rel="' + parent_href + '" class="ui-dialog-titlebar-minimize ui-corner-all" role="button"><span class="ui-icon ui-icon-minusthick">minimize</span></a>'
						);
					}
				},

				/**
				 * When we drag a window, it might be in different states
				 * such as maximized.  If it is maximized, then we want
				 * to un-maximize before beginning the drag
				 */
				dragStart: function(event, ui)
				{
					var maximize = $('a.ui-dialog-titlebar-maximize[rel=' + parent_href + ']');
					if (true == maximize.data('maximized'))
					{
						maximize.trigger('click');
						maximize.data('minimized-from-drag-start', true);
					}

					else
					{
						maximize.data('minimized-from-drag-start', false);
					}
				},

				/**
				 * When the user stops the drag, there are a few
				 * things that could happen.  If they drag the window
				 * to the top, then we maximize the window.  If they
				 * drag to the right of the screen, then we maximize
				 * to the right, only taking up half the screen.  It is
				 * the same with the left, only reversed.
				 */
				dragStop: function(e, ui)
				{
					var maximize = $('a.ui-dialog-titlebar-maximize[rel=' + parent_href + ']');

					if (true == maximize.data('minimized-from-drag-start'))
					{
						$('#' + id).data('minimized', true);
						$('#' + id).dialog('close');
						$('#' + id).dialog('open');

						maximize.data('minimized-from-drag-start', false);
					}

					else if (0 == ui.position.top)
					{
						maximize.trigger('click');
					}

					else if (0 == ui.position.left)
					{
						maximize.trigger('click');
						$('#' + id).dialog("option", "width", ($(window).width() - 24) / 2);
					}

					else if (12 + ui.position.left + parseInt($('#' + id).dialog('option', 'width')) == $(window).width())
					{
						maximize.trigger('click');
						$('#' + id).dialog("option", "width", ($(window).width() - 24) / 2);
						$('#' + id).parent().css('left', parseInt($('#' + id).dialog('option', 'width')) + 12);
					}
				},

				/**
				 * When a window is closed, we remove it from
				 * the group in the taskbar.  If it is the last
				 * item in the group, we also remove the group.
				 * If the user was just minimizing the window,
				 * then we just return.
				 */
				close: function(e, ui)
				{
					if ($('#' + id).data('minimized'))
					{
						return;
					}

					$('#' + parent_id + '-task-bar-list ul li a').each(function()
					{
						if ($(this).attr('title') == $('#' + id).dialog('option', 'title'))
						{
							$(this).parent().remove();
						}
					});

					if (!$('#' + parent_id + '-task-bar-list ul li').get(0))
					{
						$('#' + parent_id + '-task-bar').remove();
					}
				}
			});

			/**
			 * Now we open the window and display it for
			 * the user
			 */
			$('#' + id).dialog("open").show();
		}
	});

	/**
	 * When the browser window is resized, then we un-maximize
	 * each open window so that the window size is not greater
	 * than the new window size
	 */
	$(window).resize(function()
	{
		$('.ui-dialog-titlebar-maximize').each(function()
		{
			if (true == $(this).data('maximized'))
			{
				$(this).trigger('click');
			}
		});
	});

	/**
	 * Double-clicking on the window's title bar will
	 * maximize the window
	 */
	$('.ui-dialog-titlebar').live('dblclick', function()
	{
		$(this).children('.ui-dialog-titlebar-maximize').trigger('click');
	});

	/**
	 * Maximize the window when the user clicks the maximize
	 * button
	 */
	$('.ui-dialog-titlebar-maximize').live('click', function()
	{
		var topDiv = $(this).parent().parent();
		var d = $(this).parent().next().dialog();

		if (true != $(this).data('maximized'))
		{
			$(this).data('maximized', true);
			$(this).data('width', d.dialog("option", "width"));
			$(this).data('height', d.dialog("option", "height"));
			$(this).data('top', topDiv.css('top'));
			$(this).data('left', topDiv.css('left'));

			d.dialog("option", "width", $(window).width()-12);
			d.dialog("option", "height", $(window).height() - $('#task-bar').height()-10);
			topDiv.css('top', '0px').css('left', '0px');
		}

		else
		{
			$(this).data('maximized', false);
			d.dialog("option", 'width', $(this).data('width'));
			d.dialog("option", 'height', $(this).data('height'));
			topDiv.css('top', $(this).data('top'));
			topDiv.css('left', $(this).data('left'));
		}
	});

	/**
	 * When the user clicks on an application menu link in
	 * the program box, it will show the available windows
	 * that can be opened for that item
	 */
	$('a.main').click(function()
	{
		$('#program-scroll-box-details ul').hide();
		$('ul#' + $(this).attr('href').substr(1)).show();
	});

	/**
	 * Make the windows draggable and resizable
	 */
	$('div.window').draggable({ containment: "#the-body" }).resizable();
});