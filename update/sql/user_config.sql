create table user_config (
	id int(11) unsigned auto_increment,
	userid int(11) unsigned not null,
	var varchar(255),
	val text,
	primary key (id),
	key `User` (userid)
);