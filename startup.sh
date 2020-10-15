docker-compose up -d
symfony server:start -d
symfony run -d --watch=src,config,templates symfony console messenger:consume async