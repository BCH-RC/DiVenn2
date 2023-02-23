USE divenn_db;
alter table pathway add column symbol varchar(255);
update pathway p set p.symbol=(select substring(p.gene_desc, 1, locate(';', p.gene_desc)-1));
