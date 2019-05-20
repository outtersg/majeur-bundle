<?php

namespace Gui\MajeurBundle\Command;

use Doctrine\ORM\EntityManagerInterface;

class LanceurMajeurDoctrine
{
	public function __construct(EntityManagerInterface $em, $paramétrage = array())
	{
		$this->em = $em;
		$this->params = $paramétrage;
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
		
		$silo = new \MajeurSiloPdo($bdd, isset($this->params['silo']) ? $this->params['silo'] : null);

		$racine = $this->racine;
		$dossiersFouille = array_map(function($x) use($racine) { return (substr($x, 0, 1) == '/' ? '' : $racine.'/').$x.'/Resources/install'; }, $this->dossiers);
		$exprDossiers = \GlobExpr::globEnExpr($dossiersFouille);
		$exprSousDossiersEtFichiers = '(?:{[^/]*}/|)'.\MajeurListeurDossiers::ExprFichiers('update-', array('sql', 'php'));
		$listeur = new \MajeurListeurDossiers($exprDossiers.'/'.$exprSousDossiersEtFichiers);

		$joueur = new \MajeurJoueurPdo($bdd, array('#@\\\\?(?:[A-Z][a-zA-Z0-9]+\\\\)+[A-Z][a-zA-Z0-9]+#' => array($this, 'nomTableEntité')));

		$majeur = new \Majeur($silo, $listeur, $joueur);
		
		return $majeur->tourner();
	}

	public function nomTableEntité($corr)
	{
		return $this->em->getClassMetadata(substr($corr[0], 1))->table['name'];
	}
}

?>
