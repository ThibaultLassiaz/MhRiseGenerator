# Monster Hunter Rise Generator

[![PHP Version](https://img.shields.io/badge/PHP-8.1.5-green.svg)](https://www.php.net/releases/8.1.5.php)
[![PHPUnit Version](https://img.shields.io/badge/PHPUnit-9.6.15-green.svg)](https://phpunit.de/)
[![Symfony Version](https://img.shields.io/badge/Symfony-6.2.14-blue.svg)](https://symfony.com/releases/6.2.14)

**Note** : It only currently support a static set of charms, mainly used for bows.


## The Principle (short version)

A simple tool to generate charms and qurious armors

The goal of this tool is to help speedrunning setup on Monster Hunter Rise by generating every possible armor and charm given a set of skills and armors.


This way these combinations can be imported into an build optimizer to make the most optimized build for speedruns.

You can use ckudzu's [MH Rise Builder](https://mhrise.wiki-db.com/sim/?hl=en) for this.

## How to use

### Generate charms

```BASH
php bin\console app:generate-charms > output.txt
```

### Generate filtered armor

```BASH
php bin\console app:generate-armors > output.json
```

### Generate Qurious Armor

```BASH
php bin\console app:generate-qurious-armors > output.txt
```

## Dev

### Static analysis

```Bash
composer run phpstan
```

### Linter

```Shell
composer run linter
```

### Unit tests

```Bash
composer run unit
```
