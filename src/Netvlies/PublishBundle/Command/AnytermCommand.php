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

class AnytermCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('publish:anyterm')
            ->addArgument('action', null, 'install|start|stop|restart anyterm daemon', null)
            ->setDescription('This command controls the anyterm daemon')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $status = $input->getArgument('action');
        switch($status){
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'install':
                $this->install();
                break;
            default:
                echo "No such argument. Please provide start\n";
                exit;
        }
    }


    protected function install()
    {
        // set at runlevel 3, 4 and 5 in CentOS
        // 3 Full multi user mode (with networking)
        // 4 unused (but set anyway)
        // 5 X11

        if(file_exists('/etc/init.d/anyterm')){
            echo "Anyterm daemon already installed\n";
            exit;
        }

        if(!is_writable('/etc/init.d')){
            echo "Cant install daemon, please execute as root or use sudo\n";
            exit;
        }


        $initd = file_get_contents(__DIR__.'/Anyterm/anyterm');
        $initd = str_replace('#path#', dirname(dirname(dirname(dirname(__DIR__)))).'/app/console', $initd);
        file_put_contents('/etc/init.d/anyterm', $initd);
        chmod('/etc/init.d/anyterm', 777);

        shell_exec('chkconfig --add anyterm');
        shell_exec('chkconfig --level 126 anyterm off');
        shell_exec('chkconfig --level 345 anyterm on');
    }


    protected function start()
    {
        $pidFile = '/var/run/anyterm.pid';
        if(is_file($pidFile)){
            echo "PID present, so service is already started\n";
            exit;
        }

        $user = $this->getContainer()->getParameter('netvlies_publish.anyterm_user');
        $port = $this->getContainer()->getParameter('netvlies_publish.anyterm_exec_port');
        $command = 'anytermd -c '.__DIR__.'/Anyterm/exec.sh -p '.$port.' -u '.$user.' --name anyterm';
        shell_exec($command);
    }


    protected function stop()
    {
        $pidFile = '/var/run/anyterm.pid';
        if(!is_file($pidFile)){
            echo "PID file not present, so service is not started\n";
            exit;
        }
        if(!is_writable($pidFile)){
            echo "Insufficient permissions. Please execute as root or use sudo";
            exit;
        }

        shell_exec('kill `cat /var/run/anyterm.pid`');
        unlink($pidFile);
    }

    protected function restart()
    {
        $this->stop();
        $this->start();
    }

}