<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * @internal
 */
trait AclCacheTrait
{
    private $prefix;
    private $permissionGrantingStrategy;

    /**
     * Unserializes the ACL.
     */
    private function unserializeAcl(string $serialized): ?AclInterface
    {
        $acl = unserialize($serialized, ['allowed_classes' => true]);

        if (null !== $parentId = $acl->getParentAcl()) {
            $parentAcl = $this->getFromCacheById($parentId);

            if (null === $parentAcl) {
                return null;
            }

            $acl->setParentAcl($parentAcl);
        }

        $reflectionProperty = new \ReflectionProperty($acl, 'permissionGrantingStrategy');
        $aceAclProperty = new \ReflectionProperty(Entry::class, 'acl');
        $aceClassFieldProperty = new \ReflectionProperty($acl, 'classFieldAces');
        $aceObjectFieldProperty = new \ReflectionProperty($acl, 'objectFieldAces');

        if (\PHP_VERSION_ID < 80100) {
            $reflectionProperty->setAccessible(true);
            $aceAclProperty->setAccessible(true);
            $aceClassFieldProperty->setAccessible(true);
            $aceObjectFieldProperty->setAccessible(true);
        }
        $reflectionProperty->setValue($acl, $this->permissionGrantingStrategy);

        foreach ($acl->getObjectAces() as $ace) {
            $aceAclProperty->setValue($ace, $acl);
        }
        foreach ($acl->getClassAces() as $ace) {
            $aceAclProperty->setValue($ace, $acl);
        }

        foreach ($aceClassFieldProperty->getValue($acl) as $aces) {
            foreach ($aces as $ace) {
                $aceAclProperty->setValue($ace, $acl);
            }
        }

        foreach ($aceObjectFieldProperty->getValue($acl) as $aces) {
            foreach ($aces as $ace) {
                $aceAclProperty->setValue($ace, $acl);
            }
        }

        return $acl;
    }

    /**
     * Returns the key for the object identity.
     */
    private function getDataKeyByIdentity(ObjectIdentityInterface $oid): string
    {
        return $this->prefix.md5($oid->getType()).sha1($oid->getType())
               .'_'.md5($oid->getIdentifier()).sha1($oid->getIdentifier());
    }

    /**
     * Returns the alias key for the object identity key.
     */
    private function getAliasKeyForIdentity(string $aclId): string
    {
        return $this->prefix.$aclId;
    }
}
