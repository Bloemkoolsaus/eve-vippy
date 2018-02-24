create table vippy_news (
  id int(11) unsigned auto_increment,
  name varchar(255) not null,
  title varchar(255) not null,
  newsdate datetime not null,
  primary key (id)
);

create table vippy_news_read (
  userid int(11) unsigned not null,
  newsid int(11) unsigned not null,
  readdate datetime,
  primary key (userid, newsid)
);

insert into vippy_news (name, title, newsdate) values ('crest', 'Transition from CREST to ESI', '2018-02-12');