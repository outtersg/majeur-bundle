## MigrationsFactory pour .sql & .php

Le MajeurBundle embarque un composant permettant à Doctrine de jouer, en plus des Migrations PHP, celles déposées sous forme de "simples" fichiers SQL (en fait interprétés par [Sqleur](https://github.com/outtersg/sqleur/), donc avec toute une panoplie d'instructions préprocesseur).

### Configuration

Dans votre `config/bundles.php`:
```php
	[…]
	Gui\MajeurBundle\GuiMajeurBundle::class => [ 'all' => true ],
	[…]
```

Dans votre `config/packages/doctrine_migrations.yaml`:
```yaml
doctrine_migrations:
	[…]
	services:
		'Doctrine\Migrations\Finder\MigrationFinder':   'Gui\MajeurBundle\Migrations\GlobFinder'
		'Doctrine\Migrations\MigrationsRepository':     'Gui\MajeurBundle\Migrations\MigrationsRepository'
		[…]
```

Le moteur SQL sous-jacent est par défaut générique;
il est possible de l'enrichir de "définitions" (éléments qui seront remplacés par leur valeur dans le SQL)
en invoquant la méthode `MigrationSql.avecDéfs()` depuis votre `MigrationFactory` personnalisée
(vous aurez alors ajouté au `doctrine_migrations.yaml` ci-dessus une clé `doctrine_migrations: services: Doctrine\Migrations\Version\MigrationFactory: xxx`)
Ex.:
```php
if($migration instanceof \Gui\MajeurBundle\Migrations\MigrationSql)
	$migration->avecDéfs([ ':env' => 'prod' ]);
```
