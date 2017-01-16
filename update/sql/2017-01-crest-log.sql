create table crest_log (
  id int(11) unsigned not null auto_increment,
  requesttype varchar(100) default 'get',
  url varchar(500) not null,
  httpstatus int(11) default null,
  content text default null,
  response text default null,
  requestdate datetime not null,
  primary key (id)
);