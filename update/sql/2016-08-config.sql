create table system_config (
  id int(11) unsigned auto_increment,
  var varchar(255) not null,
  val varchar(255) default null,
  primary key (id)
);
create table system_cache (
  id int(11) unsigned auto_increment,
  var varchar(255) not null,
  val text default null,
  primary key (id)
);

insert into system_config (var, val) values
('system_title', 'VIPPY'),
('system_url', 'https://eve-vippy.com/'),
('system_email', 'bloemkoolsaus.eve@gmail.com'),
('system_document_dir', 'documents/'),
('eve_api_url', 'https://api.eveonline.com/');