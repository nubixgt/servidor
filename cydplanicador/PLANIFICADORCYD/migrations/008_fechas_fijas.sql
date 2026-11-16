-- Fechas fijas por modalidad (plan diario / plan fin de semana), bimestre y actividad.
-- Origen: FECHAS.xlsx y FECHAS FDS.xlsx. Las fechas del Excel venían con año 2026 pero corresponden al ciclo 2027
-- (los días coinciden lunes a viernes en 2027). Corrección: C18 de FECHAS.xlsx (fin Actividad 2, 4.º bimestre) decía 03-08;
-- se usa 10-09, que es entrega-3 días como en el resto del calendario.
ALTER TABLE courses ADD COLUMN plan_type TEXT NOT NULL DEFAULT 'diario' CHECK (plan_type IN ('diario','fds'));
CREATE TABLE fixed_dates (
  id             INTEGER PRIMARY KEY,
  school_year_id INTEGER NOT NULL REFERENCES school_years(id) ON DELETE CASCADE,
  plan_type      TEXT NOT NULL CHECK (plan_type IN ('diario','fds')),
  bimester       INTEGER NOT NULL CHECK (bimester BETWEEN 1 AND 4),
  slot           TEXT NOT NULL CHECK (slot IN ('act1','act2','act3','mejoramiento')),
  start_date     TEXT,
  end_date       TEXT,
  due_start      TEXT,
  due_end        TEXT,
  UNIQUE (school_year_id, plan_type, bimester, slot)
);
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',1,'act1','2027-01-18','2027-02-05','2027-02-08','2027-02-12' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',1,'act2','2027-02-08','2027-02-19','2027-02-22','2027-02-26' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',1,'act3','2027-02-22','2027-03-12','2027-03-15','2027-03-19' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',1,'mejoramiento',NULL,NULL,'2027-03-15','2027-03-19' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',2,'act1','2027-04-05','2027-04-16','2027-04-19','2027-04-23' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',2,'act2','2027-04-19','2027-04-30','2027-05-03','2027-05-07' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',2,'act3','2027-05-03','2027-05-14','2027-05-17','2027-05-21' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',2,'mejoramiento',NULL,NULL,'2027-05-17','2027-05-21' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',3,'act1','2027-05-31','2027-06-11','2027-06-14','2027-06-18' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',3,'act2','2027-06-14','2027-07-02','2027-07-05','2027-07-09' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',3,'act3','2027-07-12','2027-07-23','2027-07-26','2027-07-30' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',3,'mejoramiento',NULL,NULL,'2027-07-26','2027-07-30' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',4,'act1','2027-08-09','2027-08-20','2027-08-23','2027-08-27' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',4,'act2','2027-08-23','2027-09-10','2027-09-13','2027-09-17' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',4,'act3','2027-09-13','2027-09-24','2027-09-27','2027-10-01' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'diario',4,'mejoramiento',NULL,NULL,'2027-09-27','2027-10-01' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',1,'act1','2027-01-16','2027-01-30','2027-02-01','2027-02-05' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',1,'act2','2027-02-06','2027-02-20','2027-02-22','2027-02-26' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',1,'act3','2027-02-27','2027-03-06','2027-03-08','2027-03-12' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',1,'mejoramiento',NULL,NULL,'2027-03-13','2027-03-20' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',2,'act1','2027-04-03','2027-04-10','2027-04-12','2027-04-16' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',2,'act2','2027-04-17','2027-04-24','2027-04-26','2027-04-30' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',2,'act3','2027-05-08','2027-05-15','2027-05-17','2027-05-21' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',2,'mejoramiento',NULL,NULL,'2027-05-22','2027-05-29' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',3,'act1','2027-06-05','2027-06-19','2027-06-21','2027-06-25' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',3,'act2','2027-07-03','2027-07-10','2027-07-12','2027-07-16' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',3,'act3','2027-07-17','2027-07-24','2027-07-26','2027-07-30' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',3,'mejoramiento',NULL,NULL,'2027-07-31','2027-08-14' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',4,'act1','2027-08-21','2027-08-28','2027-08-30','2027-09-03' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',4,'act2','2027-09-04','2027-09-11','2027-09-13','2027-09-17' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',4,'act3','2027-09-18','2027-09-25','2027-09-27','2027-10-01' FROM school_years WHERE name = '2027';
INSERT INTO fixed_dates(school_year_id,plan_type,bimester,slot,start_date,end_date,due_start,due_end) SELECT id,'fds',4,'mejoramiento',NULL,NULL,'2027-10-02','2027-10-09' FROM school_years WHERE name = '2027';
