<?php
/**
 * Created by PhpStorm.
 * User: imhotek
 * Date: 03/11/16
 * Time: 16:12
 */

namespace Carnage\EncryptedColumn\Tests;

use Carnage\EncryptedColumn\Configuration;
use Carnage\EncryptedColumn\Tests\Migration\Fixtures\Migrated\Entity;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

/**
 * Class FiftyOneSystemsTest
 * @package Carnage\EncryptedColumn\Tests
 * @runTestsInSeparateProcesses
 */
class FiftyOneSystemsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    private static $_em;

    /**
     * @var EntityManager
     */
    private $em;

    public function setUp()
    {
        if (self::$_em === null) {
            copy(__DIR__ . "/Fixtures/51systems/db.sqlite", __DIR__ . "/Fixtures/Migrated/db.sqlite");
            $isDevMode = true;
            $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . "/Fixtures/Migrated"), $isDevMode, null, null, false);

            $conn = array(
                'driver' => 'pdo_sqlite',
                'path' => __DIR__ . "/Fixtures/Migrated/db.sqlite",
            );

            self::$_em = EntityManager::create($conn, $config);

            Configuration::register(self::$_em, __DIR__ . '/Fixtures/enc.key');

            $schemaTool = new SchemaTool(self::$_em);

            $classes = [
                self::$_em->getClassMetadata(Entity::class)
            ];

            $schemaTool->updateSchema($classes);
        }

        $this->em = self::$_em;
    }

    public function testRead()
    {
        /** @var Entity $entity */
        $entity = $this->em->find(Entity::class, 1);
        $this->assertEquals('secret code', $entity->getSecretData());
    }
}