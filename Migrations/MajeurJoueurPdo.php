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

require_once __DIR__.'/../../majeur/MajeurJoueurPdo.php';

class MajeurJoueurPdo extends \MajeurJoueurPdo
{
	const MODE_DIRECT = 'direct';
	const MODE_DOCTRINE = 'doctrine';
	
	public $mode = self::MODE_DOCTRINE;
	
	public function __construct($migrateur, $bdd, $défs = [])
	{
		$this->migrateur = $migrateur;
		parent::__construct($bdd, $défs);
		$this->init();
		$this->_sqleur->_sortie = [ $this, 'sortir' ];
	}
	
	public function sortir($sql, $bah, $interne = false)
	{
		if(!$interne)
			return $this->migrateur->sortir($sql);
		
		// Si $interne, c'est une requête d'introspection (ex.: savoir si une colonne existe déjà en table).
		
		$bdd = $this->mode == self::MODE_DOCTRINE ? $this->bdd : $this->bdd();
		$rés = $bdd->query($sql);
		if($this->mode == self::MODE_DOCTRINE && !method_exists($rés, 'setFetchMode'))
			$rés = new MajeurJoueurPdoRésultat($rés);
		$rés->setFetchMode(\PDO::FETCH_ASSOC);
		return $rés;
	}
	
	public function bdd()
	{
		$bdd = $this->bdd;
		$accesseurs = [ 'getNativeConnection', 'getWrappedConnection', null ];
		while(true)
		{
			foreach($accesseurs as $accesseur)
			{
				if(!isset($accesseur))
					break 2;
				if(method_exists($bdd, $accesseur))
				{
					$bdd = $bdd->$accesseur();
					break;
				}
			}
		}
		return $bdd;
	}
}

/* NOTE: enrobage Doctrine
 * 
 * Côté Doctrine ils sont un peu flemmards (ou froussards?), pour éviter de devoir gérer des machins qui rendraient service mais sortent des clous auxquels ils préfèrent se cantonner (ce qui se comprend vue l'étendue du projet, mais bon, c'est pas sympa),
 * ils préfèrent tout verrouiller (déjà expérimenté en https://github.com/doctrine/orm/issues/9187).
 * Une de leurs charmantes techniques pour cela est d'empêcher l'accès à l'objet sous-jacent, en instanciant (s'ils estiment l'objet trop ouvert) non pas l'objet ou une classe dérivée, mais une passerelle final, qui se garde l'objet sous-jacent en private,
 * et qui singe quelques méthodes de l'objet, mais de façon incomplète (pas toutes les méthodes, et pour certaines méthodes, ignorent discrètement les valeurs de paramètres personnalisantes), de façon à ce que "presque tout" marche comme si on avait l'objet sous-jacent en direct. Mais contrôlé par la classe passerelle / douanière / cerbère / douairière.
 * 
 * Pour réémuler le fonctionnement de la classe sous-jacente,
 * deux possibilités:
 * - soit on récupère dès le départ le PDO sous-jacent, en court-circuitant toutes les couches Doctrine
 * - soit on veut bénéficier tout de même de Doctrine (par exemple le traçage systématique de toutes les requêtes SQL, tout de même appréciable),
 *   auquel cas on rajoute une surcouche à la passerelle Doctrine qui émule le PHP de base,
 *   grâce à quelques accesseurs tout de même laissés en place (mais sous un nom différent :-\) par Doctrine (sachant qu'on ne fait pas non plus des folies).
 * Le mode MODE_DOCTRINE du MajeurJoueurPdo implémente donc cette seconde voie,
 * et la classe MajeurJoueurPdoRésultat est cet enrobeur d'enrobage Doctrine.
 */
class _MajeurJoueurPdoRésultat extends \PDOStatement
{
	public $mode = \PDO::FETCH_DEFAULT;
	public $rés;
	
	public function __construct($rés)
	{
		$this->rés = $rés;
	}
	
	protected function _setFetchMode($mode)
	{
		$this->mode = $mode;
	}
	
	protected function _fetchAll(int $mode = \PDO::FETCH_DEFAULT)
	{
		switch($this->mode)
		{
			case \PDO::FETCH_ASSOC: return $this->rés->fetchAllAssociative();
			case \PDO::FETCH_NUM: return $this->rés->fetchAllNumeric();
		}
	}
}

switch(true)
{
	case PHP_VERSION_ID < 80000:  require_once __DIR__.'/MajeurJoueurPdoResultat.php7'; break;
	default:                      require_once __DIR__.'/MajeurJoueurPdoResultat.php8'; break;
}

?>
