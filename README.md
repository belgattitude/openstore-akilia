# Openstore Akilia

[![Dependency Status](https://www.versioneye.com/user/projects/570d0c0afcd19a0039f16aeb/badge.svg?style=flat)](https://www.versioneye.com/user/projects/570d0c0afcd19a0039f16aeb)

Openstore/Akilia binding support

## Requirements

- PHP engine 5.4+, 7.0+ or HHVM >= 3.2.

## Install

### In your existing project

Via composer

```sh
$ composer require belgattitude/openstore-akilia:dev-master
```

Or clone and install with composer

```sh
$ git clone https://github.com/belgattitude/openstore-akilia.git
$ composer update
```

### Configuration

Copy dist configuration file

```sh
cd openstore-akilia/config
cp openstore-akilia.config.php.dist openstore-akilia.config.php
```

Edit the values for your setup.

## Running a synchronization

```sh
$ ./bin/openstore-akilia openstore:akilia:syncdb --entities product_rank,country
```