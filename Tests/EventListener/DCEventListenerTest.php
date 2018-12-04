<?php

namespace AndreasGlaser\DCEventBundle\Tests\EventListener;

use AndreasGlaser\DCEventBundle\Tests\TestData;
use Doctrine\Common\DataFixtures;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM;
use PHPUnit\Framework\TestCase;

/**
 * Class DCEventListenerTest
 *
 * @package AndreasGlaser\DCEventBundle\Tests\EventListener
 * @author  Andreas Glaser
 */
class DCEventListenerTest extends TestCase
{
    /**
     * @var ORM\EntityManager
     */
    protected $em;

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\Tools\ToolsException
     * @author Andreas Glaser
     */
    protected function setUp()
    {

        $conn = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'dbname' => ':memory:',
            'memory' => true,
        ]);

        $config = ORM\Tools\Setup::createAnnotationMetadataConfiguration([__DIR__ . '/../TestData/Entity'], true, null, null, false);
        $this->em = ORM\EntityManager::create($conn, $config);

        $schemaTools = new ORM\Tools\SchemaTool($this->em);
        $schemaTools->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $loader = new DataFixtures\Loader();
        $loader->addFixture(new TestData\Fixtures\ORM());

        $executor = new DataFixtures\Executor\ORMExecutor($this->em, new DataFixtures\Purger\ORMPurger());
        $executor->execute($loader->getFixtures());
    }

    /**
     * @author Andreas Glaser
     */
    public function testPrePersist()
    {
        $userEntity = new TestData\Entity\User();
        $userEntity->email = 'test-email@example.com';
        $userEntity->name = 'Hans';
        $userEntity->passwordPlain = 'this is the password that needs to be encrypted on pre-persist';

        $this->em->persist($userEntity);
        $this->em->flush($userEntity);

        $this->assertEquals(md5($userEntity->passwordPlain), $userEntity->password);
    }
}