
var mousePosX = 0;
var mousePosY = 0;

$(document).ready(function() {
	setSubmenu();
	setDatePicker();
	setAutoCompletes();
	hideRegisterFormAntiBot();
	setMouseCoordinates();
	setMenuNotifications();

	if (document.location.hash.length > 0)
	{
		setTimeout(function() {
			document.location.href = document.location.href;
		}, 1000);
	}
});
$(window).resize(function() {
	setMenuNotifications();
});
$(document).scroll(function() {
	// Alleen map horizontaal scrollen
    $("#header").css("left", "+"+$(window).scrollLeft()+"px");
    $(".content").css("left", "+"+$(window).scrollLeft()+"px");
});

function setMenuNotifications()
{
	if ($("#menuNotifications").length > 0)
	{
		$("#menuNotifications").width($("#maincontainer").width());

		if (isIGB())
		{
			$("#menuNotifications").show();
			setLayoutPositions();
		}
		else if (!$("#menuNotifications").is(":visible"))
		{
			setTimeout(function() {
				$("#menuNotifications").slideDown('fast', function() {
					setLayoutPositions();
				});
			}, 1000);
		}
	}
}
function setLayoutPositions()
{
	if ($("#signatureMap").length > 0)
		resizeMap();
}

function setMouseCoordinates()
{
	$(document).mousemove(function(e) {
		mousePosX = e.pageX;
		mousePosY = e.pageY;
	});
}

function hideRegisterFormAntiBot()
{
	if ($("#registerform").length > 0)
		$("#street").hide();
}

function setSubmenu()
{
	$("li.mainmenu").each(function() {
		$(this).mouseover(function() {
			$("#submenu"+$(this).attr("rel")).show();
		});
	});
	$("li.mainmenu").each(function() {
		$(this).mouseout(function() {
			$("#submenu"+$(this).attr("rel")).hide();
		});
	});
}

function setDatePicker()
{
	$("input[rel=datepicker]").each(function() {
		$(this).datepicker({
			dateFormat: 'dd-mm-yy',
			monthNames: ['Januari','Februari','March','April','May','June','July','August','September','October','November','December'],
			monthNamesShort: ['Jan','Feb','Mar','Apr','May','June','July','Aug','Sep','Okt','Nov','Dec'],
			dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
			dayNamesMin: ['Su', 'Mo', 'Tue', 'Wed', 'Th', 'Fr', 'Sa'],
			dayNamesShort: ['Su', 'Mo', 'Tue', 'Wed', 'Th', 'Fr', 'Sa'],
			firstDay: 1,
			showAnim: 'drop',
			showWeek: true
		});
	});
}

function setAutoCompletes()
{
	$("input[rel=autocomplete]").each(function()
	{
		var searchMinLength = 2;
		var searchDelay = 150;

		if ($(this).hasAttr("minlength"))
			searchMinLength = $(this).attr("minlength");

		if ($(this).hasAttr("delay"))
			searchDelay = $(this).attr("delay");

		var reqURL = "index.php?autocomplete=1&ajax=1&minsearchlen=" + searchMinLength;

		var field = $(this).attr("id");
		if ($(this).hasAttr("field")) {
			reqURL +="&field="+$(this).attr("field");
			field = $(this).attr("field");
		}

		if ($(this).hasAttr("element"))
			reqURL +="&element="+$(this).attr("element");

		if ($(this).hasAttr("table"))
			reqURL +="&table="+$(this).attr("table");

		if ($(this).hasAttr("keyfield"))
			reqURL +="&keyfield="+$(this).attr("keyfield");

		if ($(this).hasAttr("namefield"))
			reqURL +="&namefield="+$(this).attr("namefield");

		if ($(this).hasAttr("limit"))
			reqURL +="&limit="+$(this).attr("limit");

		$(this).autocomplete({
			source: reqURL,
			minLength: searchMinLength,
			delay: searchDelay,
			select: function(event,ui) {
                console.log(ui);
				$("#"+field).val(ui.item.id);
				$("#"+field).trigger('change');
			}
		});
	});
}

function showHelp(help)
{
    var content = "<div class='help' style='padding: 15px; text-align: left;'>";
    content += "<img src='images/default/information.png' align='left' style='margin-right: 15px; margin-bottom: 15px;' />";
    content += help + "</div>";

    content += "<div class='help' style='padding: 15px; text-align: center;'>";
    content += "<button type='button' onclick='destroyPopup();'> OK </button>";
    content += "</div>";

    showPopup(content, 400, 200);
    return false;
}

$.fn.hasAttr = function(attrName) {
    return this.attr(attrName) !== undefined;
};