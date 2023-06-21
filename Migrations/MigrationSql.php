<?php

namespace Gui\MajeurBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class MigrationSql extends AbstractMigration
{
	public $chemin;
	
	public function chemin($chemin)
	{
		$this->chemin = $chemin;
	}
	
	/**
	 * @throws MigrationException|DBALException
	 */
	public function up(Schema $schema): void
	{
		$joueur = new MajeurJoueurPdo($this, $this->connection->getWrappedConnection(), []);
		$joueur->sqleur->decoupeFichier($this->chemin);
	}
	
	public function sortir($sql)
	{
		$this->addSql($sql);
	}
}

?>
