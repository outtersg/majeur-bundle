<?php

namespace Gui\MajeurBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class GuiMajeurExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $conteneur): void
	{
		$chercheur = new FileLocator(__DIR__.'/../Resources/config/');
		$chargeur  = new XmlFileLoader($conteneur, $chercheur);
		$chargeur->load('services.xml');
	}
}

?>
