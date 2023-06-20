<?php
/*
 * Copyright (c) 2023 Guillaume Outters
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Gui\MajeurBundle\Migrations;

use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\FilesystemMigrationsRepository as DoctrineFilesystemMigrationsRepository;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Version\Version;

class MigrationsRepository extends DoctrineFilesystemMigrationsRepository
{
	protected $_pigeonnier;
	/* À FAIRE: rendre paramétrables. */
	protected $_migrateurs =
	[
		'.sql' => 'Gui\MajeurBundle\Migrations\MigrationSql',
	];
	
	protected $_migrations = [];
	
	public function __construct(DependencyFactory $germe)
	{
		parent::__construct
		(
			$germe->getConfiguration()->getMigrationClasses(),
			$germe->getConfiguration()->getMigrationDirectories(),
			$germe->getMigrationsFinder(),
			$germe->getMigrationFactory()
		);
		
		$this->_pigeonnier = $germe->getMigrationFactory();
	}
	
	/*- Accesseurs -----------------------------------------------------------*/
	
	public function hasMigration(string $version): bool
	{
		return parent::hasMigration($version) || isset($this->_migrations[$version]);
	}
	
	public function getMigration(Version $version): AvailableMigration
	{
		if(($r = parent::getMigration($version)))
			return $r;
		return $this->_migrations[(string)$version];
	}
	
	public function getMigrations(): AvailableMigrationsSet
	{
		$r = parent::getMigrations();
		return new AvailableMigrationsSet(array_merge($r->getItems(), $this->_migrations));
	}
	
	/*- Constitueurs ---------------------------------------------------------*/
	
	/** @throws MigrationException */
	public function registerMigration(string $classe): AvailableMigration
	{
		if(substr($classe, 0, 1) != '#')
			return parent::registerMigration($classe);
		
		$classe = explode('#', $classe, 3); /* À FAIRE: exceptions si format non respecté. */
		$suffixe = $classe[1];
		$classe = explode('@', $classe[2], 2);
		$fichier = $classe[1];
		$version = $classe[0];
		
		$version = new Version($version);
		$migration = $this->_pigeonnier->createVersion($this->_migrateurs[$suffixe]);
		$migration->chemin($fichier);
		
		/* À FAIRE: péter si déjà présente, chez nous ou chez parent (mais comme tout y est privé: à intercepter?). Ne pas appeler hasMigration() (car invoque loadFromDirectories, qui est sans doute en train de nous appeler en ce moment-même). */
		$this->_migrations[(string)$version] = $r = new AvailableMigration($version, $migration);
		return $r;
	}
}

?>
