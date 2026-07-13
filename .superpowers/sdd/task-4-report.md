# Task 4 report: public PIN assessment form

## RED

Added five public assessment feature tests covering direct-save access denial, valid PIN unlock and numeric result persistence, invalid PIN, expired/deactivated links, foreign candidates, and invalid select values.

Command:

```powershell
php artisan test --filter=public_assessment tests/Feature/AssessmentLinkTest.php
```

Initial result: 5 failed because the three named public routes did not exist.

## GREEN

Implemented the public controller, top-level result request, three throttled public routes, and PIN/form Blade views. The controller only uses `AssessmentLinkService` for PIN verification and result persistence. It does not call `LamaranStatusService` or write Lamaran records.

Focused result: 5 passed.

## Verification

```powershell
php artisan test
```

Result: 33 passed, 8 failed. The eight failures match the supplied baseline: missing app key in unrelated feature tests, a missing `public/user/css/vhire-custom.css`, and one existing PKWT status expectation mismatch (`PKWT` vs `PKWT 合同工`). `AssessmentLinkTest` is fully green (11 passed).

`git diff --check` completed without whitespace errors. Route listing confirms the exact named routes and throttle middleware.

## Concerns

- The public form intentionally labels candidates only by assessment candidate ID until PIN/session access is established; no candidate data is loaded before unlock.
- The global suite remains red due to the pre-existing unrelated baseline failures above; no task-4 regression was observed.

## Review follow-up

Added a regression test for `POST assessment-links.public.results.store` against both expired and deactivated links while the exact `assessment_link_access.{linkId}` session key is present. Both responses are verified as 404, confirming access is checked before candidate/result processing. The production controller already enforced this behavior, so no production change was required.

Focused verification:

```powershell
php artisan test --filter=AssessmentLinkTest
```

Result: 12 passed.

## Final review fix wave

### RED

Added two regression assertions and ran `php artisan test --filter=AssessmentLinkTest`.

- The admin creation flash was expected to equal `route('assessment-links.public.show', $link->public_token)` and the resulting URL was exercised through GET and valid PIN unlock.
- A result POST without a session and without `values` was expected to be 403.

The initial run failed in both intended ways: admin flashed `/assessment/{token}` instead of `/penilaian/{token}`, and the unauthenticated missing-payload request received a validation redirect (302) before the controller session check.

### GREEN

- Replaced both admin-generated/copyable hardcoded public URLs with the named `assessment-links.public.show` route.
- Added `EnsurePublicAssessmentAccess` route middleware to resolve an accessible link, return 404 for invalid/inactive/expired tokens, and return 403 for missing `assessment_link_access.{linkId}` before `PublicAssessmentResultRequest` is validated. The middleware passes the resolved link to the controller.
- Preserved throttle `30,1`; route listing confirms throttle and access middleware are applied.
- The existing unlocked invalid-select test still receives validation errors, proving service schema validation remains reachable after access control.

Focused verification: `php artisan test --filter=AssessmentLinkTest` — 13 passed.
