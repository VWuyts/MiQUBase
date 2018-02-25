/* 
 * Copyright (C) 2017-2018 Véronique Wuyts
 * student at Thomas More Mechelen-Antwerpen vzw -- Campus De Nayer
 * Professionele Bachelor Elektronica-ICT
 *
 * MiQUBase is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MiQUBase is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MiQUBase. If not, see <http://www.gnu.org/licenses/>.
 */

 /* To run as superuser (postgres)*/

-- Create roles
CREATE ROLE creator 
	WITH
    	NOSUPERUSER
        CREATEDB
        CREATEROLE
        NOINHERIT
        NOLOGIN
        NOREPLICATION
        NOBYPASSRLS
        CONNECTION LIMIT -1;

CREATE ROLE administrator 
	WITH
    	NOSUPERUSER
        NOCREATEDB
        CREATEROLE
        NOINHERIT
        NOLOGIN
        NOREPLICATION
        NOBYPASSRLS
        CONNECTION LIMIT -1;

CREATE ROLE executor 
	WITH
    	NOSUPERUSER
        NOCREATEDB
        NOCREATEROLE
        NOINHERIT
        NOLOGIN
        NOREPLICATION
        NOBYPASSRLS
        CONNECTION LIMIT -1;

CREATE ROLE readonly 
	WITH
    	NOSUPERUSER
        NOCREATEDB
        NOCREATEROLE
        NOINHERIT
        NOLOGIN
        NOREPLICATION
        NOBYPASSRLS
        CONNECTION LIMIT -1;


-- Set location of the PostgreSQL files for MiQUBase
CREATE TABLESPACE miqubasedb
	LOCATION 'D:/0_De_Nayer/MiQUBase/9_PostgreSQL';

-- Grant all privileges on tablespace miqubasedb to role creator
GRANT ALL PRIVILEGES
	ON TABLESPACE miqubasedb
	TO creator;


-- Create a user in role creator
-- Logout as superuser (postgres)
-- Login as user with role creator

-- Create database
SET ROLE creator; -- creator has privilege CREATEDB

DROP DATABASE IF EXISTS miqubase;

CREATE DATABASE miqubase
    WITH 
    ENCODING = 'UTF8'
    TABLESPACE = miqubasedb
    CONNECTION LIMIT = -1;
 
\connect miqubase

DROP TABLE IF EXISTS division;

CREATE TABLE division (
	divisionID 		SERIAL PRIMARY KEY,
	divisionName 	VARCHAR(50) NOT NULL,
	directorate 	VARCHAR(70) NOT NULL,
	isActive 		BOOLEAN NOT NULL DEFAULT TRUE
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS project;

CREATE TABLE project (
	projectID 		SERIAL PRIMARY KEY,
	projectNumber 	VARCHAR(70) UNIQUE NOT NULL,
	divisionID 		INTEGER REFERENCES division (divisionID) NOT NULL,
	isActive 		BOOLEAN NOT NULL DEFAULT TRUE
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS employee;

CREATE TABLE employee (
	employeeID 		SERIAL PRIMARY KEY,
	firstName 		VARCHAR(50) NOT NULL,
	lastName 		VARCHAR(50) NOT NULL,
	initials 		VARCHAR(5) UNIQUE NOT NULL,
	divisionID 		INTEGER REFERENCES division (divisionID) NOT NULL,
	username 		VARCHAR(30) UNIQUE DEFAULT NULL,
	isTechnician 	BOOLEAN NOT NULL DEFAULT TRUE,
	isActive 		BOOLEAN NOT NULL DEFAULT TRUE,
	lastLogin 		DATE DEFAULT NULL,
	loginAttempts 	INTEGER CHECK (loginAttempts >= 0) NOT NULL DEFAULT 0
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS organism;

CREATE TABLE organism (
	organismID 		SERIAL PRIMARY KEY,
	organismName 	VARCHAR(10) UNIQUE NOT NULL
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS species;

CREATE TABLE species (
	speciesID 		SERIAL PRIMARY KEY,
	speciesName 	VARCHAR(50) UNIQUE NOT NULL,
	organismID 		INTEGER REFERENCES organism (organismID) NOT NULL
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS libraryPrepKit;

CREATE TABLE libraryPrepKit (
	libraryPrepKitID 	SERIAL PRIMARY KEY,
	libraryPrepKitName 	VARCHAR(30) UNIQUE NOT NULL,
	isActive 			BOOLEAN NOT NULL DEFAULT TRUE
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS indexKit;

CREATE TABLE indexKit (
	indexKitID 		SERIAL PRIMARY KEY,
	indexKitName 	VARCHAR(80) UNIQUE NOT NULL,
	isActive 		BOOLEAN NOT NULL DEFAULT TRUE
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS run;

CREATE TABLE run (
	runID 						CHAR(15) PRIMARY KEY,
	runNumber 					CHAR(8) UNIQUE NOT NULL,
	instrumentSN 				CHAR(6) NOT NULL,
	startDate 					DATE NOT NULL,
	libraryPrepKitID 			INTEGER REFERENCES libraryPrepKit (libraryPrepKitID) NOT NULL,
	pooledLibraryConcentration 	NUMERIC(5,2) CHECK (pooledLibraryConcentration > 0) NOT NULL,
	loadingConcentration 		NUMERIC(5,2) CHECK (loadingConcentration > 0) NOT NULL,
	kapaQuantification 			NUMERIC(5,2) CHECK (kapaQuantification > 0) DEFAULT NULL,
	indexKitID 					INTEGER REFERENCES indexKit (indexKitID) NOT NULL,
	numCycles 					VARCHAR(10) NOT NULL,
	sequencingCartridge 		VARCHAR(10) NOT NULL,
	organismID 					INTEGER REFERENCES organism (organismID) DEFAULT NULL,
	totalCost 					NUMERIC(7,2) DEFAULT NULL,
	remark 						TEXT DEFAULT NULL
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS lane;

CREATE TABLE lane (
	laneID 				SERIAL PRIMARY KEY,
	laneNumber 			SMALLINT NOT NULL CHECK (laneNumber > 0),
	runID 				CHAR(15) REFERENCES run (runID) NOT NULL,
	tiles 				SMALLINT NOT NULL CHECK (tiles > 0),
	totalReads 			INTEGER NOT NULL CHECK (totalReads > 0),
	readsPF 			INTEGER NOT NULL CHECK (readsPF > 0),
	readsIdentifiedPF 	NUMERIC(7,4) NOT NULL CHECK (readsIdentifiedPF > 0),
	cv 					NUMERIC(6,4) NOT NULL CHECK (cv > 0)
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS ngsRead;

CREATE TABLE ngsRead (
	ngsReadID 			SERIAL PRIMARY KEY,
	laneID 				INTEGER REFERENCES lane (laneID) NOT NULL,
	readNumber 			SMALLINT NOT NULL CHECK (readNumber > 0),
	isIndexedRead 		BOOLEAN NOT NULL DEFAULT FALSE,
	density 			INTEGER,
	density_SD 			INTEGER,
	clusterPF 			NUMERIC(5,2) NOT NULL,
	clusterPF_SD 		NUMERIC(5,2) NOT NULL,
	phasing	 			NUMERIC(6,3) NOT NULL,
	prephasing 			NUMERIC(6,3) NOT NULL,
	noReads 			NUMERIC(5,2) NOT NULL,
	noReadsPF 			NUMERIC(5,2) NOT NULL,
	q30 				NUMERIC(5,2) NOT NULL,
	yield 				NUMERIC(4,2) NOT NULL,
	cyclesErrRated 		INTEGER NOT NULL,
	aligned 			NUMERIC(5,2) NOT NULL,
	aligned_SD 			NUMERIC(5,2) NOT NULL,
	errorRate 			NUMERIC(5,2) NOT NULL,
	errorRate_SD 		NUMERIC(5,2) NOT NULL,
	errorRate35 		NUMERIC(5,2) NOT NULL,
	errorRate35_SD 		NUMERIC(5,2) NOT NULL,
	errorRate75 		NUMERIC(5,2) NOT NULL,
	errorRate75_SD 		NUMERIC(5,2) NOT NULL,
	errorRate100 		NUMERIC(5,2) NOT NULL,
	errorRate100_SD 	NUMERIC(5,2) NOT NULL,
	intensityCycle1 	INTEGER NOT NULL,
	intensityCycle1_SD 	INTEGER NOT NULL
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS runEmployee;

CREATE TABLE runEmployee (
	runID 			CHAR(15) REFERENCES run (runID),
	employeeID 		INTEGER REFERENCES employee (employeeID),
	PRIMARY KEY (runID, employeeID)
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS invoice;

CREATE TABLE invoice (
	invoiceID 		SERIAL PRIMARY KEY,
	runID 			CHAR(15) REFERENCES run (runID) NOT NULL,
	projectID 		INTEGER REFERENCES project (projectID) NOT NULL,
	invoiceDate 	DATE NOT NULL,
	amount 			NUMERIC(7,2) NOT NULL,
	paymentDate 	DATE DEFAULT NULL
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS sample;

CREATE TABLE sample (
	sampleID 			SERIAL PRIMARY KEY,
	sampleName 			VARCHAR(50) NOT NULL,
	speciesID 			INTEGER REFERENCES species (speciesID) NOT NULL,
	projectID 			INTEGER REFERENCES project (projectID) NOT NULL,
	laneID 				INTEGER REFERENCES lane (laneID) NOT NULL,
	receptionDate 		DATE NOT NULL,
	resultSentDate 		DATE DEFAULT NULL,
	sop 				VARCHAR(30) DEFAULT NULL,
	priority 			VARCHAR(6) DEFAULT 'normal',
	toRepeat 			BOOLEAN DEFAULT FALSE,
	isRepeatOf 			INTEGER REFERENCES lane (laneID) DEFAULT NULL,
	r_d 				BOOLEAN DEFAULT FALSE,
	invoiceID 			INTEGER REFERENCES invoice (invoiceID) DEFAULT NULL,
	indexNumber 		SMALLINT NOT NULL CHECK (indexNumber > 0),
	index1_I7 			CHAR(8) NOT NULL,
	index2_I5 			CHAR(8) NOT NULL,
	readsIdentifiedPF 	NUMERIC(7,4) NOT NULL CHECK (readsIdentifiedPF > 0),
	remark 				TEXT DEFAULT NULL
	)
	TABLESPACE miqubasedb;

DROP TABLE IF EXISTS summaryTotal;

CREATE TABLE summaryTotal (
	summaryTotalID 		SERIAL PRIMARY KEY,
	laneID 				INTEGER REFERENCES lane (laneID) NOT NULL,
	isNonIndexedTotal 	BOOLEAN NOT NULL DEFAULT FALSE,
	yieldTotal 			NUMERIC(4,2) NOT NULL CHECK (yieldTotal > 0),
	aligned 			NUMERIC(5,2) NOT NULL CHECK (aligned > 0),
	errorRate 			NUMERIC(5,2) NOT NULL CHECK (errorRate > 0),
	intensityCycle1 	INTEGER NOT NULL CHECK (intensityCycle1 > 0),
	q30 				NUMERIC(5,2) NOT NULL CHECK (q30 > 0)
	)
	TABLESPACE miqubasedb;


-- Set privileges on database miqubase for different roles

-- Revoke connect privileges from all roles.
REVOKE CONNECT
	ON DATABASE miqubase
	FROM PUBLIC;

-- Grant connect privileges to all MiQUBase roles and allow all MiQUBase
-- roles to create temporary tables while using the MiQUBase database.
GRANT CONNECT, TEMPORARY
	ON DATABASE miqubase
	TO creator, administrator, executor, readonly;

-- Grant usage and select privileges on sequences (serial primary keys) to all roles
GRANT USAGE, SELECT
	ON ALL SEQUENCES IN SCHEMA public
	TO creator, administrator, executor, readonly;

-- Grant all privileges on all MiQUBase tables to creator.
GRANT ALL PRIVILEGES
	ON TABLE division, employee, indexKit, invoice, lane, libraryPrepKit, ngsRead,
		organism, project, run, runEmployee, sample, species, summaryTotal
	TO creator;

-- Grant select, insert and update privileges
-- on all MiQUBase tables to administrator.
GRANT SELECT, INSERT, UPDATE
	ON TABLE division, employee, indexKit, invoice, lane, libraryPrepKit, ngsRead,
		organism, project, run, runEmployee, sample, species, summaryTotal
	TO administrator;

-- Grant select, insert and update privileges
-- on necessary MiQUBase tables and columns to executor.
-- No privileges are granted on table invoice.
GRANT SELECT
	ON TABLE division, employee, indexKit, libraryPrepKit, organism, project, species
	TO executor;

GRANT SELECT, INSERT
	ON TABLE lane, ngsRead, run, runEmployee, sample, summaryTotal
	TO executor;	
	
GRANT UPDATE (resultsentdate, torepeat, remark)
	ON TABLE sample
	TO executor;

GRANT UPDATE (organismid, remark)
	ON TABLE run
	TO executor;

-- Grant select privileges on all MiQUBase tables to readonly
GRANT SELECT
	ON TABLE division, employee, indexKit, invoice, lane, libraryPrepKit, ngsRead,
		organism, project, run, runEmployee, sample, species, summaryTotal
	TO readonly;
