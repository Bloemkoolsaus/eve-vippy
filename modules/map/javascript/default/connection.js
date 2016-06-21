function Connection(id) {
    this.id = id;
    this.type = "normal";
    this.map = {
        color: "#338844",
        border: "#338844",
        fill: "solid"
    };
    this.solarsystems = {
        from: {
            system: 0,
            position: {x:0, y:0}
        },
        to: {
            system: 0,
            position: {x:0, y:0}
        },
        jumps: 0
    }
}

/** Setters **/
Connection.prototype.setEndOfLife = function() {
    this.map.fill = "blocked";
};
Connection.prototype.setMassCritical = function() {
    this.map.color = "#bb2222";
    this.map.border = "#bb2222";
};
Connection.prototype.setMassReduced = function() {
    this.map.color = "#ffaa22";
    this.map.border = "#ffaa22";
};
Connection.prototype.setJumpGates = function() {
    this.map.color = "#0088ff";
    this.map.border = "#0088ff";
};
Connection.prototype.setFrigate = function() {
    this.type = "frigate";
};
Connection.prototype.setCapital = function() {
    this.type = "capital";
};


/** Getters **/
Connection.prototype.getPoints = function() {
    var points = [
        this.solarsystems.from.position.x-0 + Math.round(whDefaultWidth/2),
        this.solarsystems.from.position.y-0 + Math.round(whDefaultHeight/2),
        this.solarsystems.to.position.x-0 + Math.round(whDefaultWidth/2),
        this.solarsystems.to.position.y-0 + Math.round(whDefaultHeight/2)
    ];
    return points;
};
Connection.prototype.getCenter = function() {
    var points = this.getPoints();
    var position = {
        x: Math.round((points[0]+points[2])/2),
        y: Math.round((points[1]+points[3])/2)
    };
    return position;
};
Connection.prototype.isFrigate = function() {
    return (this.type == "frigate")
};
Connection.prototype.isCapital = function() {
    return (this.type == "capital");
};


/** Render **/
Connection.prototype.render = function(canvas) {

    var connection = new Kinetic.Group({
        name: this.id
    });

    connection.add(new Kinetic.Line({
        points: this.getPoints(),
        draggable: false,
        stroke: this.map.border,
        strokeWidth: 10
    }));
    if (this.map.fill == "blocked") {
        console.log("hoi");
        connection.add(new Kinetic.Line({
            points: this.getPoints(),
            draggable: false,
            stroke: "#111111",
            strokeWidth: 8
        }));
    }
    connection.add(new Kinetic.Line({
        points: this.getPoints(),
        draggable: false,
        stroke: this.map.color,
        strokeWidth: 8,
        dash: (this.map.fill == "blocked") ? [5, 7] : null
    }));

    if (this.isCapital()) {
        connection.add(new Kinetic.Circle({
            x: this.getCenter().x,
            y: this.getCenter().y,
            radius: 8,
            fill: '#000000',
            stroke: this.map.border,
            strokeWidth: 1
        }));
        connection.add(new Kinetic.Image({
            x: this.getCenter().x - 6,
            y: this.getCenter().y - 6,
            image: mapIcons.cyno,
            width: 12,
            height: 12
        }));
    }

    if (this.isFrigate()) {
        connection.add(new Kinetic.Circle({
            x: this.getCenter().x,
            y: this.getCenter().y,
            radius: 12,
            fill: '#000000',
            stroke: this.map.border,
            strokeWidth: 1
        }));
        connection.add(new Kinetic.Image({
            x: this.getCenter().x - 9,
            y: this.getCenter().y - 7,
            image: mapIcons.rifter,
            width: 20
        }));
    }

    if (this.solarsystems.jumps > 1) {
        if (!this.isFrigate() && !this.isCapital()) {
            connection.add(new Kinetic.Circle({
                x: this.getCenter().x,
                y: this.getCenter().y,
                radius: 8,
                fill: '#dddddd',
                stroke: this.map.border,
                strokeWidth: 2
            }));
            connection.add(new Kinetic.Text({
                x: this.getCenter().x-((this.solarsystems.jumps.length>1)?6:3),
                y: this.getCenter().y-6,
                text: this.solarsystems.jumps,
                fontSize: 11,
                fontFamily: "Calibri",
                fill: "#000000"
            }));
        }
    }

    connection.on("mouseover", function() {
        document.body.style.cursor = "pointer";
        console.log("connection: "+this.getName());
        openConnectionDetails(this.getName(), mousePosX, mousePosY);
    });
    connection.on("mouseout", function() {
        document.body.style.cursor = "default";
        closeConnectionDetails(this.getName());
    });
    connection.on("click", function() {
        var fromto = this.getName().split(",");
        editConnection(this.getName());
    });

    canvas.add(connection);
};