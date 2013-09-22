<?php


namespace Netvlies\Bundle\PublishBundle\Tests\Fixtures;

use Doctrine\Common\DataFixtures\Doctrine;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Netvlies\Bundle\PublishBundle\Entity\Target;

class LoadTarget implements FixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param Doctrine\Common\Persistence\ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {
        $env = $manager->getRepository('NetvliesPublishBundle:Environment')->findOneById(1);
        $app = $manager->getRepository('NetvliesPublishBundle:Application')->findOneById(1);

        $target = new Target();
        $target->setEnvironment($env);
        $target->setApplication($app);
        $target->setUsername('vagrant');
        $target->setApproot('/home/vagrant/test');
        $target->setWebroot('/home/vagrant/test');
        $target->setCaproot('/home/vagrant/test');
        $target->setLabel('testtarget');
        $target->setInactive(false);

        $manager->persist($target);
        $manager->flush();
    }

}