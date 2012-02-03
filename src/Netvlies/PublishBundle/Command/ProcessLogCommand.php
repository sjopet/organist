<?php
/**
 * Created by JetBrains PhpStorm.
 * User: markri
 * Date: 13-1-12
 * Time: 9:52
 * To change this template use File | Settings | File Templates.
 */

namespace Netvlies\PublishBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Netvlies\PublishBundle\Entity\DeploymentLog;


class ProcessLogCommand extends ContainerAwareCommand
{

     protected function configure()
     {
         $this
             ->setName('publish:processlog')
             ->setDescription('Processes log entry of given command.')
             ->addOption('uid', null, InputOption::VALUE_OPTIONAL, 'Execution UID')
             ->addOption('exitcode', null, InputOption::VALUE_OPTIONAL, 'Exit code')
         ;
     }

     protected function execute(InputInterface $input, OutputInterface $output)
     {
         $uid = $input->getOption('uid');
         $exitcode = $input->getOption('exitcode');

         if(empty($uid)){
             //@todo delete logs/scripts older than 2 days and update them in database
             return;
         }

         $em = $this->getContainer()->get('doctrine')->getEntityManager();
         /**
          * @var \Netvlies\PublishBundle\Entity\DeploymentLog $logentry
          */
         $logentry = $em->getRepository('NetvliesPublishBundle:DeploymentLog')->findOneByUid($uid);

         if(is_null($logentry)){
             echo "Warning: No log entry available to update...";
             return;
         }

         $logfile = dirname(dirname(dirname(dirname(__DIR__)))).'/app/logs/scripts/'.$uid.'.log';
         $logentry->setDatetimeEnd(new \DateTime());
         $logentry->setLog(file_get_contents($logfile));
         $logentry->setExitCode($exitcode);

         $em->persist($logentry);
         $em->flush();
         unlink($logfile);

         // Target id will be empty when internally used for commands
         $targetId = $logentry->getTargetId();
         if(empty($targetId)){
             return;
         }

         /**
          * @var \Netvlies\PublishBundle\Entity\Target $target
          */
         $target = $em->getRepository('NetvliesPublishBundle:Target')->findOneById($logentry->getTargetId());

         $command = 'ssh '.$target->getUsername().'@'.$target->getEnvironment()->getHostname().' cat '.$target->getApproot().'/REVISION';
         $revision = shell_exec($command);
         $target->setCurrentRevision($revision);
         $em->persist($target);
         $em->flush();
     }
}