create table user_auth_group_config (
	id int(11) unsigned auto_increment,
	authgroupid int(11) unsigned not null,
	var varchar(255),
	val text,
	primary key (id),
	key `AuthGroup` (authgroupid)
);

insert into user_auth_group_config (authgroupid, var, val) values
	(3, 'wh_naming_numeric', '1'),
	(13, 'wh_naming_numeric', '1');