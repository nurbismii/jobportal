# Task 1 Report: Assessment Link Persistence Foundation

## Scope

Implemented only the persistence and model foundation for candidate assessment links. Legacy tables and existing applicant-status workflow were not changed. The migration deliberately has no foreign keys, as required for legacy and minimal SQLite test-schema compatibility.

## RED evidence

Command:

```powershell
php artisan test --filter=AssessmentLinkTest
```

Result: failed (exit 1), one test failed. The expected failure was at `AssessmentLinkTest.php:39`: migration file `database/migrations/2026_07_13_000000_create_assessment_link_tables.php` did not exist. This demonstrated the persistence foundation was absent before implementation.

## GREEN evidence

Command:

```powershell
php artisan test --filter=AssessmentLinkTest
```

Result: passed (exit 0), 1 test passed in 0.16s. The focused test creates an in-memory SQLite minimal `users`, `biodata`, and `lamaran` schema, applies the new migration, then verifies a `lapangan` link with a 64-character token, bcrypt PIN, JSON schema cast, end-of-day expiry, active accessibility, candidate relationship, and `Lamaran` resolution.

## Full suite evidence

Command:

```powershell
php artisan test
```

Result: exit 1; 23 tests passed and 8 failed in 0.80s. `AssessmentLinkTest` passed. The failures are unrelated baseline/environment issues:

- `AdminTermsApprovalProofTest` (2), feature `ExampleTest` (1), and three `VhirePkwtIntegrationTest` cases fail because `APP_KEY` is absent.
- `VersionedAssetTest` fails because `public/user/css/vhire-custom.css` is absent.
- `VhirePkwtIntegrationTest::onboarding_candidate_payload_includes_profile_data_for_hris_employee_sync` expects `PKWT`, while the actual existing output is `PKWT 合同工`.

No full-suite failure references the assessment-link migration or models.

## Changed files

- `database/migrations/2026_07_13_000000_create_assessment_link_tables.php`
- `app/Models/AssessmentLink.php`
- `app/Models/AssessmentLinkCandidate.php`
- `app/Models/AssessmentResultAudit.php`
- `tests/Feature/AssessmentLinkTest.php`

## Self-review

- `assessment_links`, `assessment_link_candidates`, and `assessment_result_audits` use the specified columns, indexes, nullable fields, unique constraints, and reverse-order `down()` drops.
- No foreign keys were added.
- JSON payload columns use array casts; expiry/deactivation/submission fields use datetime casts; `is_active` uses a boolean cast.
- Relations are explicit: link creator/candidates, candidate link/lamaran/audits, and audit candidate.
- `isAccessibleAt()` requires active status and checks that `expires_at` is strictly later than the supplied `Carbon` time.
- The focused test runs against a standalone SQLite schema and does not change shared test configuration or unrelated tests.

## Concerns

The pre-existing `package-lock.json` modification is outside this task and was not included. Full-suite failures remain baseline/environment issues; this task does not change them.
