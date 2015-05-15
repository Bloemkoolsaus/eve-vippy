var preloadedIcons = new Array();

var starIcon = createImage('images/eve/star.png');
var pvpIcon = createImage('images/eve/skull.red.png');
var pveIcon = createImage('images/eve/skull.orange.png');
var stationIcon = createImage('images/eve/station.png');
var hsIslandIcon = createImage('images/eve/stargate.red.png');
var directHsIcon = createImage('images/eve/stargate.green.png');
var cynoIcon = createImage('images/eve/cyno.png');
var rifterIcon = createImage('images/eve/rifter.png');

var factionIcons = new Array();
for (var i=500001; i<=500020; i++) {
	factionIcons[i] = createImage('images/eve/factions/'+i+'.png');
}

var fwIcon = createImage('images/eve/fw.png');
var fwContestedIcon = createImage('images/eve/fw.contested.png');

var scannedNot = createImage('images/default/scanned.not.png');
var scannedRecently = createImage('images/default/scanned.recently.png');


function createImage(file) {
	var img = new Image();
	img.ready = false;
	img.onload = function () {
		   this.ready = true;	
		};
	img.src = file;
	preloadedIcons.push(img);
	return img;
}

// creates a loop very 250ms, until the clear interval is called.
var preloader = setInterval(preloading, 250);
var attemptes = 0;

function preloading() {
	attemptes++;
	if (attemptes > 20) {
		// if more then 20x attempts clear the interval so we don't have an endless loop. 
		clearInterval(preloader);
	}
	var len = preloadedIcons.length;
	for (var i = 0; i < len; i++) {
		if (!preloadedIcons[i].ready) return; 
	}
	clearInterval(preloader);
}