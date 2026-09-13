# CMCP_CHANGELOG

## engine-20260911142857-retailing-846401

### Iteration 1 — reconnaissance baseline

- Target: `Retailing` (`retailing/retail`, Symfony bundle, `App\Retailing\`).
- Repository baseline read: `composer.json`, bundle class, service configuration, repository tree, current generic CRUD controller, Canonization/Gating agent rules, Canon021 textual rule, and mandatory dependency contour declarations.
- Canon mapping:
  - Symfony/PHP baseline: Symfony 8 / PHP 8.4.
  - Namespace identity: `App\Retailing\` mapped to `src/` — compliant.
  - Mandatory runtime contour: `objecting/object`, `cruding/crud`, `viewing/view`, `interfacing/interface` — declared with local path repositories.
  - Canon021 / zero-CRUD target: generic CRUD belongs to Cruding; Retailing must not own a component-local generic CRUD controller.
- RC-critical work selected: remove the component-local EasyAdmin `RetailCrudController`, remove its explicit service registration, and drop the now-unused direct EasyAdmin dependency while preserving Retailing-specific Entity/Repository/Form/Service semantics for Cruding.
- Growth workstream (non-blocking): keep advanced commerce capabilities modular (pricing, inventory, fulfillment, ordering, catalog organization) rather than folding cross-domain concerns into Retailing.
- Risks: no local Windows workspace is mounted in the execution runtime, so local Composer/Gating/Doctrine execution remains unavailable; GitHub repository state is used for bounded source mutation and structural verification only.
- Gates planned: Composer manifest validation/consistency, zero generic CRUD controller scan, service/YAML review, PHP syntax/static checks and platform Gating when a local runtime is available.

### Iteration 2 — material implementation

- Removed confirmed obsolete `src/Controller/Admin/RetailCrudController.php` generic CRUD surface.
- Removed its explicit `controller.service_arguments` registration from `config/services.yaml`.
- Removed direct `easycorp/easyadmin-bundle` dependency because the component no longer owns an EasyAdmin admin CRUD surface.
- Preserved `cruding/crud` and the mandatory Objecting/Viewing/Interfacing runtime contour.

### Iteration 3 — verification and fix

- Verified the resulting branch tree has no component-local `RetailCrudController` and no direct EasyAdmin dependency or service registration.
- Reviewed Retailing Entity/Form/Cruding integration and the `retail_placement` handoff path.
- Found an identity-canon violation in `RetailNewService`: a separate tenant identity was synthesized from request attributes with a `default` fallback.
- Removed that tenant resolver. The legacy downstream `tenantId` payload key is retained only as a compatibility alias and now carries the already established Retailing owner identity rather than an independent tenant identity.
- Verified downstream Shipping currently requires the legacy `tenantId` session key, so removing the key itself would cross the Retailing boundary and break the current integration contract.
- Remaining runtime limitation: local Composer/PHPStan/PHPUnit/Symfony/Doctrine/Gating execution is unavailable until the Windows workspace execution plane is connected.

### Iteration 4 — debt closure and integration

- Rechecked branch scope against `master`: only the bounded Retailing RC files are changed; branch was ahead and not behind.
- Opened PR #3 (`Retailing RC boundary hardening`) from `engine/retailing-846401-rc` to `master`.
- PR mergeability resolved green (`mergeable: true`) with no reported commit status checks on the current head.
- No additional in-scope code debt requiring speculative changes was introduced; executable local-runtime gates remain unavailable in this session and are explicitly carried as an environment limitation rather than reported as passing.

### Iteration 5 — final acceptance and handoff

- PR #3 merged successfully into `master` with merge commit `66f1eddd0002f7e5ac2d999e5e902d5cf2eda383`.
- Post-merge acceptance confirms the bounded RC changes are present on `master`: component-local generic CRUD surface removed, EasyAdmin dependency/registration removed, and Retailing placement identity no longer synthesizes a separate tenant identity.
- Mandatory runtime dependency contour remains declared and no sibling repository was modified by this task.
- GitHub reported the PR clean/mergeable before integration and no commit status contexts were registered for the head.
- Local executable gates (`composer validate`, PHP syntax/static analysis, PHPUnit, Symfony container/YAML lint, Doctrine validation, Gating) were not runnable in this session because the authoritative Windows execution plane was unavailable; they are not claimed as passing.
- Bounded repository work is complete. Any further growth work remains post-RC and must be separately authorized.

### Local execution-plane closure — 2026-09-11

- Connected to the authoritative Windows workspace through Console MCP and fetched `origin`; `origin/master` resolves to `f84e01ebcf5acad5d6c8e03d68124eff915fa9fa`.
- Preserved the pre-existing local feature branch `feature/retail-response-pricing-20260820` and its unpushed commit `20c8f62286edaae60633e0bd3b302d7c9f391f55`; no destructive reset was used.
- Preserved the local `.gating/` tooling copy and excluded it only through `.git/info/exclude` so branch synchronization could proceed without deleting or committing local tooling.
- Verified the merged RC state from `origin/master` on local branch `engine/retailing-846401-local-verify`.
- Found a real integration drift against current local Cruding: `RetailNewService` still referenced the previous Cruding DTO and service namespaces. Updated it to `CrudServiceContextDTO`, `CrudServiceResultDTO`, and `App\\Cruding\\Service\\AbstractCrudService`, matching the current `afterDefault(...)` contract.
- `composer validate --strict` passes. Full tracked PHP syntax validation passes.
- PHPUnit cannot run because this component workspace has no installed `vendor/bin/phpunit`; Composer reports no installed dependencies. No Composer scripts are declared by Retailing.
- Current Console MCP allowlist does not expose a targetable executable Gating command for the sibling `Gating` CLI, so executable Gating remains an explicit tooling limitation rather than a claimed pass.

## engine-20260912081454-retailing-b47af5

### Iteration 1 — RECONNAISSANCE_AND_BASELINE

- Authoritative workspace: `D:\PhpstormProjects\www\Retailing`, inspected and mutated only through Console MCP.
- Git baseline: clean `engine/retailing-846401-local-verify`, one commit ahead of `origin/master` (`751aac6`, `fix(retailing): align Cruding lifecycle contract`).
- Market/maturity baseline: mature commerce/marketplace platforms separate catalog, order, fulfillment/shipping, vendor and marketplace concerns; Retailing should remain the listing/marketplace product boundary rather than absorb neighboring capabilities.
- Runtime contour verified in `composer.json`: `objecting/object`, `cruding/crud`, `viewing/view`, and `interfacing/interface` are real dependencies with local path repositories for the required sibling packages.
- Canonization consulted: root `AGENTS.md`, Canon003 DTO naming rule, and Canon021 Cruding ownership rule. Relevant mapping: Retailing remains under `App\\Retailing\\`; generic CRUD stays in Cruding; foreign Cruding lifecycle types must follow the current public Cruding contract rather than retain legacy namespaces.
- Current Cruding contract verified directly: `App\\Cruding\\Dto\\CrudMutationLifecycleContextDTO` and `App\\Cruding\\ServiceInterface\\CrudMutationLifecycleSubscriberInterface`.
- RC-critical workstream: verify and integrate the existing bounded compatibility commit that updates `RetailOwnershipSubscriber` from the obsolete Cruding lifecycle namespaces/types to the current contract.
- Growth workstream (non-blocking): continue capability maturity through separately owned catalog, pricing, fulfillment, order and marketplace-response components/services; do not fold those responsibilities into this RC fix.
- Planned gates: Composer strict validation, PHP syntax/static/test checks where locally available, exact diff review, branch/upstream cleanliness, and post-integration acceptance.

### Iteration 2 — MATERIAL_IMPLEMENTATION

- Adopted the already-local, not-yet-upstream bounded implementation commit `751aac6` as the RC fix for this task rather than creating a duplicate patch.
- The implementation changes only `RetailOwnershipSubscriber`: obsolete `App\\Cruding\\Dto\\Crud\\CrudMutationLifecycleContext` and `App\\Cruding\\ServiceInterface\\Crud\\CrudMutationLifecycleSubscriberInterface` references are replaced by the current `CrudMutationLifecycleContextDTO` and root `CrudMutationLifecycleSubscriberInterface` public contract.
- No sibling repository was mutated and no generic CRUD/runtime responsibility was pulled into Retailing.

### Iteration 3 — VERIFICATION_AND_FIX

- Compared the Retailing subscriber directly with the current Cruding interface; method signatures and imported lifecycle context type match exactly.
- `composer validate --strict` passes.
- `php -l src/Subscriber/Retail/RetailOwnershipSubscriber.php` passes with no syntax errors.
- Composer confirms that no dependencies are installed and there is no `composer.lock`; therefore PHPUnit/PHPStan/Symfony container execution is not claimed. An unbounded dependency resolution/install was intentionally not introduced as part of this bounded compatibility RC.
- Exact branch diff against `origin/master` remains one PHP file plus this task journal; no unrelated source changes were found.

### Iteration 4 — DEBT_CLOSURE_AND_INTEGRATION

- Committed the current-task journal as `52e4bfa` after the pre-existing bounded implementation commit `751aac6`.
- Because the prior local verification branch tracked `origin/master`, created dedicated integration branch `engine/retailing-b47af5-rc` at the same verified HEAD instead of risking a protected-branch push.
- Pushed the dedicated branch and opened PR #5, `Retailing RC: align Cruding lifecycle contract`, against `master`.
- PR inspection reports `MERGEABLE`, non-draft, no conflicts, no pending/failed status checks, and a green safe-merge gate.
- No additional in-scope source debt was identified; remaining work is integration plus post-merge acceptance, not speculative feature growth.

### Iteration 2 continuation — MATERIAL_IMPLEMENTATION quality repair

- Engine continuation converted the previously unexecuted PHPUnit/PHPStan evidence into a bounded implementation task instead of treating missing tooling as an external blocker.
- Consulted Canon029, Canon039, Canon034, Canon023, and Canon025. Added repository-owned PHPStan/PHPUnit tooling, PHPUnit coverage execution, PHP-CS-Fixer/PHPStan/PHPUnit dev dependencies, and Composer scripts.
- Added `Administering` and `Navigating` as root development path repositories only, because Cataloging and Locating require them transitively and Composer does not inherit repositories from dependencies. They were not added as direct Retailing business dependencies and no sibling repository was mutated.
- Development resolution now uses `minimum-stability: dev` with `prefer-stable: true`, allowing the real local `dev-master` component graph to resolve without inventing direct dependencies.
- Added the required Composer `symfony/runtime` plugin allowlist entry and generated the development `composer.lock`; `composer install --no-scripts` now succeeds with local sibling junctions.
- Added a focused `RetailKindTest`; PHPUnit is green with 4 tests and 8 assertions. PHPUnit 12 configuration was corrected to keep branch-capable coverage in the executable `--path-coverage` script rather than an invalid XML attribute.
- Enabled Xdebug coverage at PHP process startup; persistent path-coverage execution is green and writes `var/coverage.txt`.
- PHPStan level 8 exposed 10 Retailing-only issues. Fixed the unsafe kernel parameter cast, impossible owner-type branch, literal return contract, array-shape/value-type annotations, and redundant null coalescing. PHPStan now reports no errors.
- PHP-CS-Fixer was configured to preserve the repository's CRLF convention, then its canonical formatting was applied to 17 tracked PHP files; subsequent `cs:check` is green.
- Added root `.gitignore` to close Canon034 and keep `vendor/`, `var/`, quality caches, local env overrides, IDE state, and OS noise out of Git.

### Iteration 3 — VERIFICATION_AND_FIX

- Ran RC validation against committed quality-tooling state. Composer validation, PHPUnit, and coverage were green; PHPStan exposed current sibling-contract drift against Cruding.
- Verified the authoritative Cruding source directly. Current DTO namespaces are `App\\Cruding\\DTO\\...`, and the abstract entrypoint service is `App\\Cruding\\Service\\CrudAbstractService`.
- Updated `RetailNewService` to use `CrudAbstractService` and the canonical uppercase `DTO` namespace for `CrudServiceContextDTO` / `CrudServiceResultDTO`.
- Updated `RetailOwnershipSubscriber` to use `App\\Cruding\\DTO\\CrudMutationLifecycleContextDTO` with the current root `CrudMutationLifecycleSubscriberInterface`.
- Re-ran PHPStan: no errors. Re-ran PHPUnit: 4 tests / 8 assertions green. Re-ran PHP-CS-Fixer check: 0 files fixable.
- RC validation then reported all executable validation commands green; the only temporary blocker was the expected uncommitted change state prior to this iteration's commit.
- The `placeholder` warning in `RetailType` remains a scanner false positive caused by Symfony's legitimate `ChoiceType` `placeholder` option, not a TODO/stub marker.

## engine-20260913-retailing-rc-continuation

### Iteration 1 — RECONNAISSANCE_AND_BASELINE

- Authoritative workspace: `D:\PhpstormProjects\www\Retailing`; current branch `engine/retailing-b47af5-rc` was clean and synchronized with its upstream at reconnaissance start.
- Re-read the active Retailing Composer/runtime surface plus the mandatory dependency contour for Objecting, Cruding, Viewing, and Interfacing, and the Canonization/Gating contract sources.
- Canonization rules consulted directly: Canon003, Canon007, Canon008, Canon010, Canon018, Canon019, Canon021, Canon023, Canon024, Canon025, Canon026, Canon029, Canon034, Canon039, and Canon040. Target mapping: `retailing/retail` => `App\\Retailing\\` plus `Retail*`; generic CRUD remains in Cruding; local development dependencies use symlinked path repositories; PHP/Symfony baseline is 8.4/8.1+; quality tooling and executable PHPUnit coverage evidence are mandatory.
- Market/maturity baseline: mature commerce stacks separate product/catalog, pricing, inventory/order/fulfillment and channel/marketplace concerns. Retailing remains the marketplace listing/request/response and matching boundary; adjacent domain ownership must not be folded into this component merely for feature growth.
- RC-critical workstream: verify the prior Cruding-contract/tooling repair and close factual test/coverage debt without changing Retailing production semantics unnecessarily. Growth workstream remains post-RC: richer marketplace ranking, commercial UX and additional integrations only after correctness/operability gates are green.
- Initial executable gates: `composer validate --strict --check-lock`, PHP-CS-Fixer, PHPStan and PHPUnit were green; persistent coverage exposed a real Canon040 blocker at 1.08% lines, 0% methods and 80.95% branches, classifying the repository as `HIGH_TEST_DEBT`.
- Material risk: coverage debt was substantially larger than the previous journal implied, so RC cannot be accepted from PHPUnit pass/fail alone.

### Iteration 2 — MATERIAL_IMPLEMENTATION

- Replaced the four-case enum-only test with a focused Retailing behavioral suite covering entity normalization/publication/selection invariants, response lifecycle, availability and service-area matching, pricing normalization, order-intent creation, Cataloging vocabulary adaptation/fallback, accepted-response commercial projection, and Viewing payload production.
- Kept production PHP unchanged; test design follows public sibling contracts rather than reaching into neighboring implementation internals.
- PHPUnit grew from 4 tests / 8 assertions to 24 tests / 128 assertions with no notices. PHPStan and PHP-CS-Fixer remain green.
- Coverage improved materially to 45.67% lines, 37.14% methods, and 78.76% branches. Branch coverage now exceeds Canon040's 70% target, while line/method coverage remain below the 50% `HIGH_TEST_DEBT` boundary and require continuation.

### RC canonicalization and standalone acceptance — 2026-09-13

- Materialized the canonical Gating severity policy and executed the full local Gating contract. The first actionable run exposed technical-role placement, premature subject folders, silent fallback, standalone dependency baseline, YAML prefixing, and behavioral tooling debt; those hard failures were repaired rather than suppressed.
- Canonicalized Retailing source topology without changing marketplace responsibility: Factory, Normalizer, EventSubscriber, Provider and ValueObject now live under their technical role roots; premature `Retail/` folders under Enum/Form/Repository/Service were flattened; service configuration files now use the `retail_` subject prefix.
- Closed the Cataloging vocabulary silent-failure path: Cataloging metadata may fall back to the declared legacy tree, but failure of both sources is now observable instead of being converted into an empty successful result.
- Completed the Canon022 standalone dependency baseline with Collectioning, Tabling and EasyAdmin; development uses sibling path repositories, while `composer.prod.json` uses the actual sibling Git remotes, including the repository's factual `tabling-.git` remote.
- Added standalone bundle/runtime wiring for Cataloging, Locating, EasyAdmin and Security plus a minimal standalone security configuration. `cache:clear --env=test --no-warmup` is green, proving the Symfony container compiles in standalone mode.
- Added Canon041 tooling: Symfony Test Pack, Panther, repository-local Playwright, Playwright configuration and an executable browser-harness smoke. `npm test` is green with 1 Playwright test.
- Final PHP gates are green: Composer strict lock validation, PHP syntax on all changed/untracked PHP files, PHP-CS-Fixer, PHPStan, PHPUnit 33 tests / 177 assertions, and persistent Xdebug coverage execution.
- Final Gating result: 58 rules, 0 failed, 3 warnings, 15 skipped. Canon011, Canon022, Canon024/025, Canon030 and Canon041 pass. Remaining warnings are non-blocking debt: Canon031 PHPDoc coverage, Canon040 PHP line/method coverage (54.4% lines, 50.4% methods; branch coverage 73.8% passes target), and Canon042 behavioral/UI coverage evidence because no reproducible application-surface denominator has yet been defined.
- Security audits are green: Composer reports no advisories; npm audit reports 0 vulnerabilities.
- Database-backed schema parity is not claimed as green: the standalone container compiles, but `doctrine:schema:validate`/migration currentness require an external PostgreSQL `DATABASE_URL`, which is not configured in this workspace. No fake SQLite substitution or fabricated migration/coverage evidence was introduced.
