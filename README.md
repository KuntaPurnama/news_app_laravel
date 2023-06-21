# QUICK START

After cloning this repository, you can run the repository with docker with compose script below :

```sh
docker-compose up
```

When application already run in your docker container, migrate the database :

```sh
docker exec <containerId> php artisan migrate
```

After migrate the database run these commands to get news from third party API

```sh
docker exec <containerId> php artisan most-recent-news:cron
docker exec <containerId> php artisan most-popular-news:cron
docker exec <containerId> php artisan review-article-news:cron
docker exec <containerId> php artisan top-news:cron
```
