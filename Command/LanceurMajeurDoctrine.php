<?php

namespace Gui\MajeurBundle\Command;

use Doctrine\ORM\EntityManagerInterface;

use Gui\MajeurBundle\Migrations\MajeurJoueurPdo;

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
		$fExprNiveaux = array('\MajeurListeurDossiers', 'ExprNiveaux');
		foreach($this->dossiers as $dossier => $sousNiveaux)
		{
			$exprNiveaux = is_array($sousNiveaux) ? call_user_func_array($fExprNiveaux, $sousNiveaux) : $fExprNiveaux($sousNiveaux);
			$r[] = array_filter(array($racine, $dossier, $this->sousDossiers, $exprNiveaux, $fichiers), function($x) { return isset($x); });
		}
		
		return $r;
	}
	
	public function configJoueur($type)
	{
		switch($type)
		{
			case 'pdo':
				return array
				(
					array
					(
						$this->bdd(),
					),
					array
					(
						'+défs' => array
						(
							'#@\\\\?(?:[A-Z][a-zA-Z0-9]+\\\\)+[A-Z][a-zA-Z0-9]+#' => array($this, 'nomTableEntité'),
							':env' => getenv('APP_ENV'),
						),
					)
				);
		}
	}
	
	public function bdd()
	{
		$bdd = $this->em->getConnection()->getWrappedConnection();
		// Récupération de la BdD encapsulée par Doctrine en mode traces, via un MajeurJoueurPdo qui sait le faire.
		// À FAIRE: sortir ça vers une fonction statique dédiée?
		$joueurPdo = new MajeurJoueurPdo(null, $bdd);
		return $joueurPdo->bdd();
	}
	
	public function joueur($cMajeur, $type, $params)
	{
		$config = $this->configJoueur($type);
		$config || $config = array();
		$config += array(array(), array());
		
		$classe = 'MajeurJoueur'.ucfirst($type);
		require_once $cMajeur.$classe.'.php';
		$classe = '\\'.$classe;
		$pouleÀJoueur = new \ReflectionClass($classe);
		$joueur = $pouleÀJoueur->newInstanceArgs($config[0]);
		
		$this->_configurer($joueur, $params, $config[1]);
		
		return $joueur;
	}
	
	public function tourner()
	{
		// Le Majeur n'utilise pas d'autoload.
		$cMajeur = __DIR__.'/../../majeur/';
		require_once $cMajeur.'Majeur.php';
		require_once $cMajeur.'MajeurSiloPdo.php';
		require_once $cMajeur.'MajeurListeurDossiers.php';
		
		$silo = new \MajeurSiloPdo($this->bdd(), isset($this->params['silo']) ? $this->params['silo'] : null);
		$listeur = new \MajeurListeurDossiers(array('chemins' => $this->chemins()));
		$this->_configurer($listeur, 'listeur');
		
		// Si un seul joueur est demandé, c'est celui pour le SQL.
		if(isset($this->params['joueur']) && !isset($this->params['joueurs']))
			$this->params['joueurs'] = array('sql' => $this->params['joueur']);
		
		$joueurs = array();
		foreach($this->params['joueurs'] as $typeJoueur => $paramsJoueur)
		{
			$typeJoueurRéel = $typeJoueur;
			switch($typeJoueur)
			{
				case 'sql': $typeJoueurRéel = 'pdo'; break;
			}
			$joueur = $this->joueur($cMajeur, $typeJoueurRéel, $paramsJoueur);
			$joueurs[$typeJoueur] = $joueur;
		}
		$listeurs = array($listeur);
		if(isset($this->params['listeurs']))
			$listeurs = array_merge($listeurs, $this->params['listeurs']);

		$this->majeur = new \Majeur($silo, $listeurs, $joueurs);
		
		if(isset($this->params['diag']))
		{
			require_once $cMajeur.'MajeurInfo.php';
			$mi = new \MajeurInfo($this->majeur);
			$mi->vers($this->params['diag']);
		}
		
		return $this->majeur->tourner();
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
	
	public $racine;
	public $params;
	public $em;
	public $majeur;
}

?>
