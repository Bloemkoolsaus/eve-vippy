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
    faction: [],
    notifications: {
        message: createImage("/modules/map/images/notifications/message.png"),
        notice: createImage("/modules/map/images/notifications/notice.png"),
        warning: createImage("/modules/map/images/notifications/warning.png"),
        error: createImage("/modules/map/images/notifications/error.png"),
        drifter: createImage("/modules/map/images/notifications/drifter.png"),
    }
};

for (var i=500001; i<=500020; i++) {
	mapIcons.faction[i] = createImage('/modules/map/images/factions/'+i+'.png');
}

function createImage(file) {
	var img = new Image();
	img.ready = false;
	img.onload = function() { this.ready = true; };
	img.src = file;
	return img;
}