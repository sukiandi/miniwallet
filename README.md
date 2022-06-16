Yii 2 Dockerized
================

This is an private project built with [yii2-dockerized](https://github.com/codemix/yii2-dockerized).

This project is powered by Docker, So Please install docker if you didn't have one.

Initial Setup

Run following command:
```sh
docker-compose build
```

Create mysql volume:
```sh
docker volume create --name=mysql-data-mw
```

Now you can bring up the application by:
```sh
docker-compose up -d
# Wait some seconds to let the DB container fire up ...
docker-compose exec web ./yii migrate
```

Finally you need to set write permissions for some directories:
```sh
docker-compose exec web chgrp www-data web/assets runtime var/sessions
docker-compose exec web chmod g+rwx web/assets runtime var/sessions
```

Now you should able to access the application in http://localhost/

To stop the application:
```sh
docker-compose down
```