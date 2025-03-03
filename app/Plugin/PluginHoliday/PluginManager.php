<?php

namespace Plugin\PluginHoliday;

use Eccube\Plugin\AbstractPluginManager;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Block;
use Eccube\Repository\BlockRepository;
use Plugin\PluginHoliday\Entity\Config;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginManager extends AbstractPluginManager
{
    private $originalDir = __DIR__ . '/Resource/template/default/';
    private $template1 = 'holiday_message.twig';

    public function enable(array $meta, ContainerInterface $container)
    {
        $this->copyBlock($container);
        $this->registerBlock($container);
        $this->createDefaultConfig($container);
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        $this->removeBlock($container);
        $this->unregisterBlock($container);
    }

    private function copyBlock(ContainerInterface $container)
    {
        $templateDir = $container->get(EccubeConfig::class)->get('eccube_theme_front_dir');
        $file = new Filesystem();
        $file->copy($this->originalDir . $this->template1, $templateDir . '/Block/' . $this->template1);
    }

    private function removeBlock(ContainerInterface $container)
    {
        $templateDir = $container->get(EccubeConfig::class)->get('eccube_theme_front_dir');
        $file = new Filesystem();
        $file->remove($templateDir . '/Block/' . $this->template1);
    }

    private function registerBlock(ContainerInterface $container)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $blockRepository = $entityManager->getRepository(Block::class);
        $block = $blockRepository->findOneBy(['file_name' => 'holiday_message']);

        if (!$block) {
            $block = new Block();
            $block->setName('Holiday Message Block');
            $block->setFileName('holiday_message');
            $block->setDeletable(false);
            $block->setUseController(true);

            $entityManager->persist($block);
            $entityManager->flush();
        }
    }

    private function unregisterBlock(ContainerInterface $container)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $blockRepository = $entityManager->getRepository(Block::class);
        $block = $blockRepository->findOneBy(['file_name' => 'holiday_message']);

        if ($block) {
            $entityManager->remove($block);
            $entityManager->flush();
        }
    }

    private function createDefaultConfig(ContainerInterface $container)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $configRepository = $entityManager->getRepository(Config::class);
        $config = $configRepository->find(1);

        if (!$config) {
            $config = new Config();
            $config->setName('config 1');
            $entityManager->persist($config);
            $entityManager->flush();
        }
    }

 
}