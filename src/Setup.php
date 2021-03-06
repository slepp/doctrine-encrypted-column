<?php

namespace Carnage\EncryptedColumn;

use Carnage\EncryptedColumn\Container\VersionedContainer;
use Carnage\EncryptedColumn\Dbal\EncryptedColumn;
use Carnage\EncryptedColumn\Dbal\EncryptedColumnLegacySupport;
use Carnage\EncryptedColumn\Encryptor\HaliteEncryptor;
use Carnage\EncryptedColumn\Encryptor\LegacyEncryptor;
use Carnage\EncryptedColumn\Serializer\LegacySerializer;
use Carnage\EncryptedColumn\Serializer\PhpSerializer;
use Carnage\EncryptedColumn\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;

final class Setup
{
    private $keyPath;
    private $enableLegacy = false;
    private $legacyKey;

    public function register(EntityManagerInterface $em)
    {
        if ($this->enableLegacy) {
            $this->doRegisterLegacy($em);
        } else {
            $this->doRegister($em);
        }
    }

    public function enableLegacy(string $legacyKey)
    {
        $this->enableLegacy = true;
        $this->legacyKey = $legacyKey;
        return $this;
    }

    public function withKeyPath(string $keypath)
    {
        $this->keyPath = $keypath;
        return $this;
    }

    private function buildEncryptionService(): EncryptionService
    {
        $encryptors = self::buildEncryptorsContainer();
        $serializers = self::buildSerilaizerContainer();
        return new EncryptionService(
            $encryptors->get(HaliteEncryptor::IDENTITY),
            $serializers->get(PhpSerializer::IDENTITY),
            $encryptors,
            $serializers
        );
    }

    private function buildEncryptorsContainer(): VersionedContainer
    {
        $services = [new HaliteEncryptor($this->keyPath)];
        if ($this->enableLegacy) {
            $services[] = new LegacyEncryptor($this->legacyKey);
        }
        //@TODO add legacy encryptor, throw exceptions if required keys aren't specified
        return new VersionedContainer(...$services);
    }

    private function buildSerilaizerContainer(): VersionedContainer
    {
        $services = [new PhpSerializer()];
        if ($this->enableLegacy) {
            $services[] = new LegacySerializer();
        }
        return new VersionedContainer(...$services);
    }

    /**
     * @param EntityManagerInterface $em
     */
    private function doRegister(EntityManagerInterface $em)
    {
        EncryptedColumn::create($this->buildEncryptionService());
        $conn = $em->getConnection();
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping(
            EncryptedColumn::ENCRYPTED,
            EncryptedColumn::ENCRYPTED
        );
    }

    /**
     * @param EntityManagerInterface $em
     */
    private function doRegisterLegacy(EntityManagerInterface $em)
    {
        EncryptedColumnLegacySupport::create($this->buildEncryptionService());
        $conn = $em->getConnection();
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping(
            EncryptedColumnLegacySupport::ENCRYPTED,
            EncryptedColumnLegacySupport::ENCRYPTED
        );
    }
}