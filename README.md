## Introduction

Mdogo means 'small, little; not large' in Swahili - and that's what it is:
Mdogo is a small-ish (<5ksloc) php5 framework facilitating modern front-end
centric development. Mdogo offers sufficient performance (<10ms from cache)
for most uses, usually needs less than 4mb/req of ram and has lots of nifty
features:

- array and object base classes
- model, collection and view classes
- miscellanous controller classes
- minimal dependency injection container
- bare-bones message broker (pub/sub)
- simple unified error/exception handling
- dozens of handy utility functions (~90)
- extensible multi-environment support

Mdogo leverages php's apc, bcmath, curl, dom, gd, memcached, mysqlnd, pdo and
sqlite extensions. It tries to provide simple yet powerful apis and to otherwise
not get in your way. It has no external dependencies. And no documentation.

Mdogo is licensed under the terms of the MIT license.


## Requirements
- [Composer](http://getcomposer.org/)
- PHP >= 5.3.10
- Apache >= 2.0 or Nginx >= 1.0
- PECL::APC > 3.1 (recommended)
- MySQL > 5.0 (optional)
- SQLite > 3.0 (optional)


## Installation Steps

(1) Clone mdogo-app, install php packages:

```
git clone git://github.com/gaiagroup/mdogo-app.git mdogo-app
cd mdogo-app
composer install --dev
```

(2) Create folders and files:

```
mkdir dat pub log tmp && chmod 777 log tmp
echo "<h1>Hello, Old Chap</h1>" > pub/index.html
echo "{\"animals\":[\"aardvark\"]}" > dat/example.json
```

(3) Run tests (optional)

```
phpunit lib/gaiagroup/mdogo/test
````

(4) Start examples application

```
cd www && php -S localhost:8080
```

(5) Configure Apache/Nginx and cron using cnf/server/{apache|nginx}.conf and
bin/cron, respectively.


## Contributors
- @agens
- @bspot
- @bwellhoefer
- @dmbch
- @LennyLinux
- @pshh78
