<?php

namespace Gui\MajeurBundle\Command;

use Doctrine\ORM\EntityManagerInterface;

class LanceurMajeurDoctrine
{
	public function __construct(EntityManagerInterface $em, $nomTableVersions = 'versions')
	{
		$this->em = $em;
		$this->nomTableVersions = $nomTableVersions;
		$this->dossiers = array
		(
			'vendor/{*/*}',
			'{src}',
		);
		$this->racine = '.';
	}
	
	public function tourner()
	{
		$bdd = $this->em->getConnection()->getWrappedConnection();

		// Le Majeur n'utilise pas d'autoload.
		$cMajeur = __DIR__.'/../../majeur/';
		require_once $cMajeur.'Majeur.php';
		require_once $cMajeur.'MajeurSiloPdo.php';
		require_once $cMajeur.'MajeurListeurDossiers.php';
		require_once $cMajeur.'MajeurJoueurPdo.php';
		
		$silo = new \MajeurSiloPdo($bdd, $this->nomTableVersions);

		$racine = $this->racine;
		$dossiersFouille = array_map(function($x) use($racine) { return (substr($x, 0, 1) == '/' ? '' : $racine.'/').$x.'/Resources/install'; }, $this->dossiers);
		$exprDossiers = \GlobExpr::globEnExpr($dossiersFouille);
		$exprSousDossiersEtFichiers = '(?:{[^/]*}/|)'.\MajeurListeurDossiers::ExprFichiers('update-', array('sql', 'php'));
		$listeur = new \MajeurListeurDossiers($exprDossiers.'/'.$exprSousDossiersEtFichiers);

		$joueur = new \MajeurJoueurPdo($bdd);

		$majeur = new \Majeur($silo, $listeur, $joueur);
		
		return $majeur->tourner();
	}
}

?>
