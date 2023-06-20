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

use Doctrine\Migrations\Finder\Finder;

class GlobFinder extends Finder
{
	protected $_globsParClasse = [ 'Version*.php' ]; /* À FAIRE: s'accorder aussi avec LanceurMajeurDoctrine et son préfixe. */
	protected $_globsParLanceur = [ 'Version*.sql' ];
	
	/**
	 * {@inheritDoc}
	 */
	public function findMigrations(string $dossier, ?string $espace = null): array
	{
		$dossier = $this->getRealPath($dossier);
		$fichiers = [];
		
		$globs =
			array_fill_keys($this->_globsParClasse, false)
			+ array_fill_keys($this->_globsParLanceur, true)
		;
		foreach($globs as $glob => $parLanceur)
			if(($fichiersIci = glob(rtrim($dossier, '/').'/'.$glob)))
			{
				if($parLanceur)
					$fichiersIci = array_map(function($x) { return '@'.$x; }, $fichiersIci);
				$fichiers = array_merge($fichiers, $fichiersIci);
			}
		
		return $this->loadMigrations($fichiers, $espace);
	}
	
	/**
	 * @param string[] $files
	 *
	 * @return string[]
	 *
	 * @throws NameIsReserved
	 */
	protected function loadMigrations(array $chemins, ?string $espace): array
	{
		$versions = [];
		
		foreach ($chemins as $numChemin => $chemin)
			if(substr($chemin, 0, 1) == '@')
			{
				$nom = basename($chemin);
				if(($césure = strpos($nom, '.')) === false)
					$césure = strlen($nom);
				$suffixes = substr($nom, 0, strpos($nom, '.'));
				$versions[] = '#'.substr($nom, $césure).'#'.$espace.'\\'.substr($nom, 0, $césure).$chemin;
				unset($chemins[$numChemin]);
			}
		
		$versions = array_merge($versions, parent::loadMigrations($chemins, $espace));
		
		return $versions;
	}
}

?>
