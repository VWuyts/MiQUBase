# MiQUBase - An application for the management of the quality and use of the NGS platform @Sciensano

MiQUBase started as a project for the Professional Bachelor Elektronics-ICT at Thomas More campus De Nayer.
The application is tailored made for the Next-Generation Sequencing (NGS) platform at the [Sciensano](https://www.sciensano.be) institute
in Brussels, Belgium.

The initial goal of the MiQUBase application is the follow-up of the quality at run level and of the use
of the NGS platform. Hereto, it should be possible to request specific reports, but also to freely consult
the underlying [PostgreSQL](https://www.postgresql.org/) database. In the latter case, it should be taken into account that the primary
users of the application have no knowledge of databases or SQL. An invoicing module is an additional tool
for the application.

## Installation
Note that this application is still under development.

Clone or download and unzip the repository and install the dependencies with the following command
while in the MiQUBase root directory:
```bash
$ composer install
```

## Setup and configuration
Run [MiQUBase.sql](/sql/MiQUBase.sql) in your [PostgreSQL](https://www.postgresql.org/) RDBM to create
the database and user roles.

Rename the [example.config.ini](/conf/example.config.ini) file to config.ini and set the necessary
configuration values.

## About
### Requirements
- MiQUBase works with PHP `7.1.1` or above
- PHP extensions `pdo_pgsql` and `pgsql` have to be loaded
- [PostgreSQL](https://www.postgresql.org/) version `9.6.2` or above is necessary as RDBM

### Author
Veronique Wuyts - <veronique.wuyts@student.thomasmore.be>

### License
MiQUBase is licensed under the GNU General Public License - see the `[LICENSE](LICENSE)` file for details.

### Acknowledgements
This project was made possible by Sigrid De Keersmaecker and Nancy Roosens of the Transversal and Applied
Genomics service of the [Sciensano](https://www.sciensano.be) institute.
