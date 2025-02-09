
# CF7 workshop scheduler

this  Wordpress plugin can be used to manage workshops. It uses contact-form-7 for the request by a client and manages the dates and team on a admin page. It was developed to reduce the workload of non-profit organizations.
The workflow is like this:
* create a contact form with contact-form-7
* in the form one ore more dates can be selected
* there is a calender in the admin area where all booked workshops can be seen and managed
   * a team can be defined, logged in users can note if they have time to teach that workshop
   * notes can be added
   * a checklist with to-dos can be created

## install

download the latest release and install the zip on the wordpress admin plugin page

## development

### setup

```
docker compose -f docker-compose.yml up --build
```

* install plugins
   * contact-form-7
   * advanced-cf7-db
   * wp-database-backup usefull for db backups (https://wordpress.org/plugins/wp-database-backup/)


### release

* increment version number in `www/wp-content/plugins/index.php
* run `./scripts/release.sh`
