# Mercury Parser Extension for FreshRSS

This extension fetches full article contents using [Mercury Parser](https://github.com/postlight/mercury-parser).
You will need to set up your own Mercury Parser server and configure the extension to use the correct URL to your API.

If you are using Docker, an exemplary `docker-compose.yml` might look like this:
```
version: '3'
services:
  freshrss:
    image: "freshrss/freshrss:latest"
    volumes:
      - ./data:/var/www/FreshRSS/data
      - ./xExtension-MercuryParser:/var/www/FreshRSS/extensions/xExtension-MercuryParser
    ports:
      - 80:80
  mercury:
    image: "wangqiru/mercury-parser-api:latest"
    ports:
      - 3000:3000
```
