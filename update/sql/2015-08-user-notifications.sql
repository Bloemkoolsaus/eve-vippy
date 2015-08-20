create table users_notification (
    id int(11) unsigned auto_increment,
    userid int(11) unsigned default null,
    type varchar(255),
    title varchar(255),
    content text,
    notifydate datetime not null,
    readdate datetime default null,
    primary key (id),
    key `User` (userid)
);