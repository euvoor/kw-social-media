# Kwerio SocialMedia module

SocialMedia consumes Kwerio's Core connector boundary. It does not own provider apps, OAuth sessions, or credentials.

The Account page declares one required connector field:

- capability: `post.publish`
- platform: `linkedin`
- verified connector app required
- persisted value: the opaque `core.connector_connections.id`

The UI and controller can list and persist only eligible connections owned by the authenticated user. Future backend publishing code must call `PublishingConnectorService::resolve_for_user_or_fail()`, which delegates credential resolution and refresh to `ConnectorConnectionLifecycleService`. Credential-bearing runtime payloads must never be returned by a controller or stored in the SocialMedia schema.

No publishing, scheduling, analytics, or content-management behavior is implemented here.

LinkedIn documents `w_member_social` as the permission used to create a post for an authenticated member: <https://learn.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/share-on-linkedin>.

## Validation in a Kwerio checkout

```bash
node --test modules/SocialMedia/tests/social-media-ui-contract.test.mjs
php modules/SocialMedia/tests/social-media-connector.test.php
npm run build
```
