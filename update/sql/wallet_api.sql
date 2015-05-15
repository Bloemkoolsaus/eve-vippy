insert into config (var, val) values 
	('wallet_api_keyid', '4323768'),
	('wallet_api_vcode', '7g0ZWAoY4C9cZ7xOleYpGhzIi27zNFM83ROSaOnrbZdTJTCptqlbW65fWPeYBJdW'),
	('wallet_api_charid', '1899584653');

create table vippy_subscriptions_journal (
	id int(11) unsigned auto_increment,
    authgroupid int(11) unsigned,
	fromcharacterid int(11) unsigned,
	tocharacterid int(11) unsigned,
	amount bigint(20) default 0,
	description varchar(255),
	transactiondate datetime not null,
	primary key (id)	
);