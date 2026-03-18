<?php

declare(strict_types=1);

namespace Symfony\Component\Security\Acl\Tests\Fixtures;

use Symfony\Component\Security\Acl\Model\AuditableEntryInterface;

interface SerializableAuditableEntryInterface extends AuditableEntryInterface
{
    public function __serialize(): array;

    public function __unserialize(array $data): void;
}
