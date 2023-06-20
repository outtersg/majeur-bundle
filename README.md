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
