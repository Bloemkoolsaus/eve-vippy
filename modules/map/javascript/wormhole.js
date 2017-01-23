function Wormhole(id) {
    this.id = id;
    this.name = "Unknown";
    this.titles = [];
    this.subtitles = [];
    this.status = {
        status: "unknown",
        homesystem: false,
        persistant: false
    };
    this.scanned = {
        scanned: false,
        finished: false
    };
    this.solarsystem = {
        name: "Unknown",
        type: "unknown",
        class: {
            tag: "??",
            color: "#999999"
        },
        statics: [],
        faction: null
    };
    this.map = {
        position: {x: 0, y: 0},
        width: whDefaultWidth,
        height: whDefaultHeight,
        colors: {
            background: "#ffffff",
            border: "#555555",
            title: "#222222",
            style: "normal"
        }
    };
    this.characters = [];
    this.icons = [];
    this.notification = null;
}

/** Setters **/
Wormhole.prototype.setPosition = function(x, y) {
    this.map.position.x = x-0;
    this.map.position.y = y-0;

    if (totalWidth < this.map.width+this.map.position.x)
        totalWidth = this.map.width+this.map.position.x;
    if (totalHeight < this.map.height+this.map.position.y)
        totalHeight = this.map.height+this.map.position.y;
};
Wormhole.prototype.setWidth = function(w) {
    this.map.width = w-0;
    if (totalWidth < this.map.width+this.map.position.x)
        totalWidth = this.map.width+this.map.position.x;
};
Wormhole.prototype.setHeight = function(h) {
    this.map.height = h-0;
    if (totalHeight < this.map.height+this.map.position.y)
        totalHeight = this.map.height+this.map.position.y;
};
Wormhole.prototype.setStatus = function(code) {
    if (code == 2)
        this.map.colors.border = "#00cc00";
    if (code == 3)
        this.map.colors.border = "#ffaa00";
    if (code == 4)
        this.map.colors.border = "#ff0000";
};

Wormhole.prototype.addTitle = function(title, color) {
    this.titles.push({
        title: title,
        color: (color)?color:"#444444"
    });
};
Wormhole.prototype.addSubTitle = function(title, color) {
    this.subtitles.push({
        title: title,
        color: (color)?color:"#888888"
    });
    this.map.height += whDefaultLineHeight;
};
Wormhole.prototype.addStatic = function(whStatic) {
    this.solarsystem.statics.push(whStatic);
    if (this.solarsystem.statics.length > 1)
        this.map.height += whDefaultLineHeight;
};
Wormhole.prototype.addCharacter = function(id, name) {
    this.characters.push({ id: id, name: name });
    if (this.characters.length > 1 && this.characters.length < 5)
        this.map.height += whDefaultLineHeight;
};
Wormhole.prototype.addIcon = function(icon) {
    this.icons.push(icon);
};
Wormhole.prototype.setAsHomesystem = function() {
    this.map.colors.border = "#0066ff";
    this.map.colors.title = "#0066ff";
    this.map.colors.style = "bold";
    this.status.persistant = true;
};


/** Getters **/
Wormhole.prototype.getPosition = function() {
    return { x: this.map.position.x, y: this.map.position.y };
};
Wormhole.prototype.getFullname = function() {
    return this.solarsystem.name+" - "+this.name;
};
Wormhole.prototype.isUnknown = function() {
    return (this.solarsystem.type == "unknown");
};
Wormhole.prototype.isKspace = function() {
    if (!this.isUnknown())
        return (this.solarsystem.type !== "wormhole");
    return false;
};
Wormhole.prototype.isWspace = function() {
    if (!this.isUnknown())
        return (this.solarsystem.type == "wormhole");
    return false;
};


/** Render **/
Wormhole.prototype.render = function(canvas)
{
    if (!this.isUnknown())
    {
        var wormholeFade = new Kinetic.Group({
            x: this.map.position.x,
            y: this.map.position.y,
            draggable: false
        });
        wormholeFade.add(new Kinetic.Rect({
            width: this.map.width,
            height: this.map.height,
            fill: "#666666",
            stroke: "#444444",
            strokeWidth: 2,
            draggable: false
        }));
        wormholeFade.add(new Kinetic.Text({
            x: 4,
            y: 4,
            text: this.solarsystem.class.tag,
            fontSize: 12,
            fontFamily: "Calibri",
            fill: "#444444",
            draggable: false
        }));
        wormholeFade.add(new Kinetic.Text({
            x: 22,
            y: 4,
            text: this.getFullname(),
            fontSize: 12,
            fontFamily: "Calibri",
            fill: "#444444",
            draggable: false
        }));
        canvas.add(wormholeFade);
    }

    var wormhole = new Kinetic.Group({
        x: this.map.position.x-0,
        y: this.map.position.y-0,
        name: this.id,
        draggable: true
    });

    wormhole.add(new Kinetic.Rect({
        width: this.map.width,
        height: this.map.height,
        fill: this.map.colors.background,
        stroke: this.map.colors.border,
        strokeWidth: 3,
        draggable: false
    }));


    if (this.isKspace()) {
        wormhole.add(new Kinetic.Rect({
            x: 2,
            y: 2,
            width: 17,
            height: this.map.height-4,
            fill: this.solarsystem.class.color,
            draggable: false
        }));
    }

    if (!this.scanned.finished) {
        var offset = (this.scanned.scanned) ? 36 : 17+((this.isKspace())?5:0);
        wormhole.add(new Kinetic.Rect({
            x: 2,
            y: offset,
            width: 17,
            height: this.map.height-offset-2,
            fill: "#bbbbbb",
            draggable: false
        }));
    }

    wormhole.add(new Kinetic.Text({
        x: 4,
        y: 4,
        fontSize: 12,
        fontFamily: "Calibri",
        fontStyle: "bold",
        text: this.solarsystem.class.tag,
        fill: (this.isKspace()) ? "#ffffff" : this.solarsystem.class.color,
        draggable: false
    }));
    wormhole.add(new Kinetic.Text({
        x: 22,
        y: 4,
        text: this.getFullname(),
        fontSize: 12,
        fontFamily: "Calibri",
        fontStyle: this.map.colors.style,
        fill: this.map.colors.title,
        draggable: false
    }));

    var extraTxtHeight = 14;


    // Icons (eerst, zodat de tekst(en) er over heen vallen)
    if (this.solarsystem.faction) {
        wormhole.add(new Kinetic.Image({
            x: this.map.width-25,
            y: this.map.height-25,
            image: mapIcons.faction[this.solarsystem.faction],
            width: 24,
            height: 24,
            draggable: false
        }));
    }
    if (this.notification) {
        wormhole.add(new Kinetic.Image({
            x: this.map.width-20,
            y: 2,
            image: mapIcons.notifications[this.notification],
            width: 18,
            height: 18,
            draggable: false
        }));
    } else {
        if (this.status.persistant) {
            wormhole.add(new Kinetic.Image({
                x: this.map.width-15,
                y: 2,
                image: mapIcons.pin,
                width: 13,
                height: 13,
                draggable: false
            }));
        }
    }


    // titles
    for (var t=0; t<this.titles.length; t++) {
        wormhole.add(new Kinetic.Text({
            x: 22,
            y: extraTxtHeight+1,
            text: this.titles[t].title,
            fontSize: 11,
            fontFamily: "Calibri",
            fontStyle: "bold",
            fill: this.titles[t].color,
            draggable: false
        }));
        extraTxtHeight += whDefaultLineHeight;
    }
    for (var t=0; t<this.subtitles.length; t++) {
        wormhole.add(new Kinetic.Text({
            x: 22,
            y: extraTxtHeight,
            text: this.subtitles[t].title,
            fontSize: 11,
            fontFamily: "Calibri",
            fontStyle: "normal",
            fill: this.subtitles[t].color,
            draggable: false
        }));
        extraTxtHeight += whDefaultLineHeight;
    }

    // statics
    for (var s=0; s<this.solarsystem.statics.length; s++) {
        wormhole.add(new Kinetic.Text({
            x: 70,
            y: this.map.height-(whDefaultLineHeight*(s+1))-3,
            text: this.solarsystem.statics[s],
            fontSize: 11,
            fontFamily: "Calibri",
            fontStyle: "normal",
            fill: "#888888",
            draggable: false
        }));
    }

    // pilots
    var myCurrentLocation = false;
    var extraPilots = 0;
    extraTxtHeight += Math.round(whDefaultLineHeight/2);
    for (var c=0; c<this.characters.length; c++) {
        if (this.characters[c].isme)
            myCurrentLocation = true;
        if (c < 4) {
            wormhole.add(new Kinetic.Text({
                x: 22,
                y: extraTxtHeight,
                text: this.characters[c].name,
                fontSize: 11,
                fontFamily: "Calibri",
                fill: "#666666",
                draggable: false
            }));
            extraTxtHeight += whDefaultLineHeight;
        } else
            extraPilots += 1;
    }

    if (extraPilots > 0) {
        wormhole.add(new Kinetic.Text({
            x: 22,
            y: extraTxtHeight,
            text: " + "+extraPilots+" others",
            fontSize: 11,
            fontFamily: "Calibri",
            fill: "#666666",
            draggable: false
        }));
        extraTxtHeight += whDefaultLineHeight;
    }

    if (myCurrentLocation) {
        wormhole.add(new Kinetic.Image({
            x: 5,
            y: 16,
            image: mapIcons.star,
            width: 12,
            height: 12,
            draggable: false
        }));
    }



    var iconPosition = {
        x: 22,
        y: this.map.height - 15
    };
    for (var j=0; j<this.icons.length; j++) {
        wormhole.add(new Kinetic.Image({
            x: iconPosition.x,
            y: iconPosition.y,
            image: this.icons[j],
            width: 12,
            height: 12,
            draggable: false
        }));
        iconPosition.x += 13;
    }




    /**
     * Events
     */
    wormhole.on("dragstart", function() {
        if (!mapIsMassDeleteMode()) {
            $(".tooltip").remove();
            whDragMode = true;
            disableMapRefresh();
            whDrag.x = mousePosX;
            whDrag.y = mousePosY;
            whOldPosition.x = this.getPosition().x;
            whOldPosition.y = this.getPosition().y;
        }
    });
    wormhole.on("dragend", function() {
        if (!mapIsMassDeleteMode()) {
            $(".tooltip").remove();
            enableMapRefresh();
            var diffX = mousePosX-whDrag.x;
            var diffY = mousePosY-whDrag.y;
            loadSignatureMap("move", { system: this.getName(), x: whOldPosition.x+diffX, y: whOldPosition.y+diffY }, true);
            whDragMode = false;
        }
    });
    if (!this.isUnknown()) {
        wormhole.on("mouseover", function () {
            document.body.style.cursor = "pointer";
            if (!massDeleteMode && !whDragMode) {
                if (!whMouseOver) {
                    whMouseOver = true;
                    openWormholeDetails(this.getName(), this.getAbsolutePosition().x, this.getAbsolutePosition().y);
                }
            }
        });
        wormhole.on("mouseout", function () {
            document.body.style.cursor = "default";
            closeWormholeDetails(this.getName());
            whMouseOver = false;
        });
        wormhole.on("click", function (e) {
            closeContextMenu();
            closeWormholeDetails(this.getName());
            if (e.evt.which == 3) {
                openContextMenu(this.getName(), mousePosX, mousePosY);
                return false;
            } else {
                if (mapIsMassDeleteMode())
                    deleteWormhole(this.getName());
                else
                    document.location = "/map/"+$("#mapName").val()+"/"+mapWormholes[this.getName()].solarsystem.name;
            }
        });
    } else {
        wormhole.on("click", function (e) {
            closeContextMenu();
            closeWormholeDetails(this.getName());
            if (e.evt.which == 3) {
                openContextMenu(this.getName(), mousePosX, mousePosY);
                return false;
            }
        });
    }



    canvas.add(wormhole);
    return wormhole;
};