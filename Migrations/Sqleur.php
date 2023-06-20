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

class Sqleur extends \MajeurJoueurPdo
{
	public function __construct($migrateur, $bdd, $défs = [])
	{
		$this->migrateur = $migrateur;
		parent::__construct($bdd, $défs);
		$this->_sqleur->_sortie = [ $this, 'sortir' ];
	}
	
	public function sortir($sql, $bah, $interne = false)
	{
		if(!$interne)
			return $this->migrateur->sortir($sql);
		
		// Si $interne, c'est une requête d'introspection (ex.: savoir si une colonne existe déjà en table).
		
		$rés = $this->bdd->query($sql);
		$rés->setFetchMode(\PDO::FETCH_ASSOC);
		return $rés;
	}
}

?>
