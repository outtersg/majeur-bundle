<?php

namespace Gui\MajeurBundle\Command;

use Doctrine\ORM\EntityManagerInterface;

class LanceurMajeurDoctrine
{
	public $sousDossiers = 'Resources/install';
	public $préfixe = 'UPDATE-';
	
	public $dossiers = array
	(
		'vendor/{*/*}' => 0,
		'{src}' => 0,
		'src' => array(1, 2),
	);
	
	public function __construct(EntityManagerInterface $em, $paramétrage = array())
	{
		$this->em = $em;
		$this->params = $paramétrage;
	}
	
	public function chemins()
	{
		$racine = $this->racine;
		
		$préfixe = isset($this->params['listeur']['préfixe']) ? $this->params['listeur']['préfixe'] : $this->préfixe;
		$fichiers = array($préfixe, array('php', 'sql'));
		
		$r = array();
		$fExprNiveaux = '\MajeurListeurDossiers::ExprNiveaux';
		foreach($this->dossiers as $dossier => $sousNiveaux)
		{
			$exprNiveaux = is_array($sousNiveaux) ? call_user_func_array($fExprNiveaux, $sousNiveaux) : $fExprNiveaux($sousNiveaux);
			$r[] = array($racine, $dossier, $this->sousDossiers, $exprNiveaux, $fichiers);
		}
		
		return $r;
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
		$listeur = new \MajeurListeurDossiers(array('chemins' => $this->chemins()));
		$this->_configurer($listeur, 'listeur');

		$joueur = new \MajeurJoueurPdo($bdd);
		$paramsJoueur = array
		(
			'+défs' => array
			(
				'#@\\\\?(?:[A-Z][a-zA-Z0-9]+\\\\)+[A-Z][a-zA-Z0-9]+#' => array($this, 'nomTableEntité'),
				':env' => getenv('APP_ENV'),
			),
		);
		$this->_configurer($joueur, 'joueur', $paramsJoueur);

		$majeur = new \Majeur($silo, $listeur, $joueur);
		
		return $majeur->tourner();
	}
	
	protected function _configurer($o, $section, $paramsParDéfaut = array())
	{
		$params = is_string($section) ? (isset($this->params[$section]) ? $this->params[$section] : array()) : $section;
		$params += $paramsParDéfaut;
		foreach($params as $param => $val)
			if(substr($param, 0, 1) == '+')
			{
				if(isset($params[substr($param, 1)]))
					$params[substr($param, 1)] += $val;
				else
					$params[substr($param, 1)] = $val;
				unset($params[$param]);
			}
		foreach($params as $param => $val)
			$o->$param = $val;
	}

	public function nomTableEntité($corr)
	{
		return $this->em->getClassMetadata(substr($corr[0], 1))->table['name'];
	}
}

?>
