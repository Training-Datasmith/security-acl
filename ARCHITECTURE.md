# Architecture: security-acl

## Purpose

A Symfony Security component that implements Access Control Lists (ACLs) — a fine-grained, object-level permission system. Rather than role-based access only, ACLs allow per-object, per-field, and per-user permission grants stored in a database.

## Directory Structure

```
Model/             - Interfaces defining the ACL domain model (AclInterface, EntryInterface, etc.)
Domain/
  Acl.php                              - Runtime ACL: collection of ACEs (Access Control Entries)
  Entry.php                            - Object-level ACE (permission grant/deny bit mask)
  FieldEntry.php                       - Field-level ACE (per-property permissions)
  ObjectIdentity.php                   - Identifies a domain object (class + ID)
  PermissionGrantingStrategy.php       - Evaluates whether a set of ACEs grant a permission mask
  SecurityIdentityRetrievalStrategy.php - Resolves the current user's security identities
  DoctrineAclCache.php / PsrAclCache.php - ACL cache implementations
  AuditLogger.php                      - Logs permission grant/deny decisions
Dbal/
  AclProvider.php                      - Reads ACLs from the database
  MutableAclProvider.php               - Reads and writes ACLs to the database
  Schema.php                           - Doctrine DBAL table schema for ACL storage
Permission/
  MaskBuilder.php                      - Fluent builder for bitmask permission values
  BasicPermissionMap.php               - Maps named permissions (VIEW, EDIT, DELETE) to masks
Voter/
  AclVoter.php                         - Symfony Security Voter: consults ACLs for access decisions
  FieldVote.php                        - Wraps an object + field name for field-level votes
Exception/        - Domain exception hierarchy
Util/             - ClassUtils: resolves class names for object identities
```

## Key Design Decisions

- **Bitmask permissions**: Permissions are stored as integer bitmasks (e.g., `VIEW=4`, `EDIT=8`) rather than strings, enabling efficient bitwise permission checks and compound masks.
- **Dual identity model**: Subjects can be identified either by user (`UserSecurityIdentity`) or role (`RoleSecurityIdentity`), matching how Symfony Security models principals.
- **Inheritance**: ACLs support inheritance chains — a resource can inherit permissions from its parent object, enabling category/tree hierarchies.
- **Cache layer**: ACLs are cached after first load (Doctrine Cache or PSR-6) to avoid repeated database round-trips on every access check.
- **Auditing**: `AuditableAcl` entries log grant/deny decisions to `AuditLoggerInterface` for compliance.

## Extension Points

- Implement `PermissionGrantingStrategyInterface` to customise how ACEs are evaluated.
- Implement `AclCacheInterface` for a custom cache backend.
- Extend `MaskBuilder` to add application-specific permission bits.

## Dependency Flow

```
AclVoter::vote()
  └─> AclProvider::findAcl(ObjectIdentity) — load ACL (with cache)
  └─> Acl::isGranted(mask, SecurityIdentities[])
        └─> PermissionGrantingStrategy — evaluates ACE list
        └─> AuditLogger — logs decision
```
