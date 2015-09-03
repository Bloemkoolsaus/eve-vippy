create table map_namingscheme (
    id int(11) unsigned auto_increment,
    name varchar(255),
    title varchar(255),
    public tinyint(1) default 0,
    primary key (id)
);

insert into map_namingscheme (id, name, title, public) VALUES
    (1, 'letters', 'Letters', 1),
    (2, 'numbers', 'Numbers (by order)', 0),
    (3, 'numbers-static', 'Numbers (by static)', 0);


update mapwormholechains
set autoname_whs = 3
where autoname_whs = 2
and authgroupid in (3,14,21,22,26,38);