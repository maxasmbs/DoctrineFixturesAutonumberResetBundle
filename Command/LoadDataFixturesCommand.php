<?php

namespace Migmolrod\DoctrineFixturesAutonumberResetBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\Console\Input\ArrayInput;

class LoadDataFixturesCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName("doctrine:fixtures:resetload")
            ->setDescription("Reset autonumbering with MySQL and then Load Data Fixtures")
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager($input->getOption('em'));

        // Get platform parameters
        $platform = $em->getConnection()->getDatabasePlatform();

        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);

        $purger->purge();

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            if (!$metadata->isMappedSuperclass) {
                $tbl = $metadata->getQuotedTableName($platform);

                $em->getConnection()->executeUpdate("ALTER TABLE " . $tbl . " AUTO_INCREMENT=1;");
            }
        }

        $command = $this->getApplication()->find('doctrine:fixtures:load');

        // Using the append option as data has already been purged from the database
        $arguments = array(
            'command' => 'doctrine:fixtures:load',
            '--append'  => true,
        );

        if ($input->getOption('em')) {
            $arguments['--em'] = $input->getOption('em');
        }

        $inputs = new ArrayInput($arguments);
        $returnCode = $command->run($inputs, $output);
    }
}
