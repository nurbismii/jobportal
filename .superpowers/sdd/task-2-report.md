# Task 2 Report: Assessment Link Result Service

## Scope and files

- Added `app/Services/AssessmentLinkService.php`.
- Updated `app/Models/AssessmentLink.php` to accept `CarbonInterface` for accessibility checks.
- Extended `tests/Feature/AssessmentLinkTest.php` with the health-link creation, PIN verification, repeated result save, and audit-history flow.
- No routes, views, or `Lamaran` status workflow were changed.

## TDD evidence

### RED

Command:

```powershell
php artisan test --filter=AssessmentLinkTest
```

Result: 1 passed, 1 failed. The new test failed as expected because `App\Services\AssessmentLinkService` did not exist (`BindingResolutionException`). This verified that the new behavior was not present before implementation.

### GREEN

After implementing the service, the same command returned 2 passed, 0 failed in 0.16s.

The added test verifies:

- `kesehatan` always starts with the locked `health_status` select field and exact `Sehat` / `Tidak Sehat` options.
- The PIN is verifiable through `Hash::check` via the service.
- Duplicate lamaran IDs generate one candidate.
- Saving `Sehat` and then `Tidak Sehat` updates the latest candidate result/note and creates exactly two audits.

## Implementation notes

- `schemaFor` accepts only `kesehatan` and `lapangan`, enforces the allowed extra field types, field limits, option limits, label limits, and protects the health standard field from replacement.
- `create` creates a unique 64-character token, hashes the PIN, sets expiry to Makassar end-of-day, and atomically creates unique candidates.
- `saveResult` locks the candidate in a database transaction, verifies active/unexpired access while locked, validates the complete server-side schema, saves the current values/note/submission time, then writes an audit on every save.
- Audit snapshots retain the result keys and include `petugas_note`, while IP address and a 255-character-limited user agent are recorded.
- Inaccessible or missing records use a `ModelNotFoundException` so link state is not exposed. Validation failures use `ValidationException::withMessages`.

## Test results

Focused test:

```powershell
php artisan test --filter=AssessmentLinkTest
```

Result: **2 passed, 0 failed**.

Full suite:

```powershell
php artisan test
```

Result: **24 passed, 8 failed**. This matches the documented baseline count; no delta attributable to Task 2.

Baseline failures observed:

- 2 `AdminTermsApprovalProofTest` failures: missing `APP_KEY`.
- 1 feature `ExampleTest` failure: missing `APP_KEY`.
- 1 `VersionedAssetTest` failure: missing `public/user/css/vhire-custom.css`.
- 4 `VhirePkwtIntegrationTest` failures: 3 missing `APP_KEY`, 1 expected `PKWT` but actual value `PKWT 合同工`.

## Self-review

- The scope is limited to the requested service, model typing adjustment, and feature test.
- No secrets are persisted in plaintext; only the PIN hash is stored.
- Result validation rejects unknown keys, invalid select values, nonnumeric number values, missing required values, and oversized text/note values.
- The transaction and row lock protect against concurrent saves producing stale audits.

## Concerns

- The candidate table/migration does not enforce a foreign key to `lamaran`; the service intentionally does not alter that existing persistence design or status workflow.
- Full-suite baseline failures remain environmental/unrelated and should be resolved separately before treating the repository suite as fully green.

## Review fix: link-level lock and schema input validation

Following review, `saveResult` now first locks the candidate row and then obtains the owning `AssessmentLink` by `assessment_link_id` with `lockForUpdate()` in the same transaction. `ensureAccessible()` runs against that locked link before server-side schema validation, candidate update, or audit insertion; validation also uses the locked link schema. This serializes a concurrent deactivation against result submission, eliminating the prior check-then-write window.

`create` now rejects a supplied non-array `fields`/schema value with `ValidationException` instead of silently treating it as an empty schema.

### Review fix RED

Command:

```powershell
php artisan test --filter=AssessmentLinkTest
```

Output: **3 passed, 1 failed**. `test_create_rejects_non_array_schema_fields` failed as expected because the pre-fix service coerced `fields => 'not-an-array'` to `[]` and did not throw `ValidationException`. The inactive-link rejection test was also added and passed against the existing accessibility behavior.

### Review fix GREEN

Command:

```powershell
php artisan test --filter=AssessmentLinkTest
```

Output: **4 passed, 0 failed** in 0.22s:

- assessment link persists schema accessibility and candidate lamaran relation
- health link keeps standard field and audits each result save
- create rejects non array schema fields
- save result rejects an inactive link

The focused tests cover malformed schema input and safe rejection when a link is inaccessible through deactivation. The same locked `ensureAccessible()` guard also rejects expired links because it delegates to `isAccessibleAt()`.
