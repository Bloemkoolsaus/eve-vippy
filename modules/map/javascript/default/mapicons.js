var mapIcons = {
    star: createImage('/images/eve/star.png'),
    pvp: createImage('/images/eve/skull.red.png'),
    pve: createImage('/images/eve/skull.orange.png'),
    station: createImage('/images/eve/station.png'),
    hsisland: createImage('/images/eve/stargate.red.png'),
    direcths: createImage('/images/eve/stargate.green.png'),
    cyno: createImage('/images/eve/cyno.png'),
    rifter: createImage('/images/eve/rifter.png'),
    pin: createImage('/images/eve/pin-dark.png'),
    fw: createImage('/images/eve/fw.png'),
    contested: createImage('/images/eve/fw.contested.png'),
    faction: []
};

for (var i=500001; i<=500020; i++) {
	mapIcons.faction[i] = createImage('/images/eve/factions/'+i+'.png');
}

function createImage(file) {
	var img = new Image();
	img.ready = false;
	img.onload = function () {
		   this.ready = true;
		};
	img.src = file;
	return img;
}