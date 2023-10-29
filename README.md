# Monster Hunter Rise Generator
A simple tool to generate charms and qurious armors

The goal of this tool is to help speedrunning setup on Monster Hunter Rise by generating every possible armor and charm given a set of skills and armors.


This way these combinations can be imported into an build optimizer to make the most optimized build for speedruns.

You can use ckudzu's [MH Rise Builder](https://mhrise.wiki-db.com/sim/?hl=en) for this.

**NOTE** : It only currently support a static set of charms, mainly used for bows.

---

## How to use

### Generate charms

```BASH
php bin\console app:generate-charms > output.txt
```

Generate Qurious Armor


--- 

## Dev
### Static analysis

```Bash
composer run phpstan
```

### Linter

```Bash
composer run linter
```
