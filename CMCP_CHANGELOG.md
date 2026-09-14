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

### Debt hardening baseline — 2026-09-13

- Started from merged `origin/master` (`c4d9f06`) on dedicated branch `engine/retailing-debt-hardening-20260913` with a clean worktree.
- Current Canon040 evidence: 54.43% lines, 50.35% methods, 73.79% branches. Domain entities and vocabulary are already well covered; the aggregate deficit is concentrated in previously unexecuted infrastructure/fixture/entrypoint surfaces.
- Selected next work: raise meaningful coverage through fixture guards/helpers, order-intent edge contracts, RetailNew placement/session handoff, kernel/bundle runtime surfaces, and remaining matching branches; do not game source filtering or fabricate Canon042 counters.
- Moved the broad behavioral suite from the obsolete `tests/Unit/Enum/Retail/` subject path to `tests/Unit/` so test topology reflects its actual cross-component behavioral scope before adding a dedicated debt-coverage suite.

### Debt hardening acceptance — 2026-09-13

- Expanded the behavioral suite through real previously unexecuted contracts: deterministic fixture loaders and guards, RetailNew session/identity handoff paths, order-intent selection/failure branches, marketplace availability/service-area validation, response-acceptance lifecycle guards, and standalone kernel/bundle surfaces. No production source filtering or production behavior was changed to raise coverage.
- PHPUnit is green at 44 tests / 262 assertions; PHPStan reports 0 errors; PHP-CS-Fixer check is green; changed PHP syntax lint is green; Composer strict lock validation is green; the repository-local Playwright smoke remains green at 1/1.
- Canon040 coverage improved from 54.43% lines / 50.35% methods / 73.79% branches to 80.08% lines (812/1014) / 58.16% methods (82/141) / 80.28% branches (859/1070). Line and branch targets are now met; method/path completeness remains the explicit residual test debt rather than being inflated with artificial assertions.
- Final Gating result remains 58 rules, 0 failed, 3 warnings, 15 skipped. The warnings are Canon031 PHPDoc coverage, Canon040 method coverage, and Canon042 behavioral/UI coverage evidence. Canon042 counters were not fabricated because a reproducible application-surface denominator is still not defined.
- Retailing owns nine tracked Doctrine migrations. Database-backed schema/migration parity remains a separate runtime acceptance step requiring the canonical PostgreSQL `DATABASE_URL`; the `data` connection was not replaced with SQLite for test convenience.

## engine-20260914-retailing-rc-hardening

### Reconnaissance baseline

- Authoritative workspace: `D:\PhpstormProjects\www\Retailing`; clean branch `engine/retailing-phpdoc-hardening-20260913` at `7171f04881a3363533b498e8fcbcf2cd1de68f77`, synchronized with `origin/master` at reconnaissance start.
- External maturity baseline: mature commerce platforms separate catalog, pricing, channel, order/fulfillment and presentation capabilities; Retailing remains the marketplace listing/request/response, matching and commercial-selection boundary rather than absorbing neighboring responsibilities.
- Mandatory runtime contour verified in `composer.json`: Objecting, Cruding, Collectioning, Tabling, Viewing and Interfacing are direct dependencies; the required local sibling path repositories are present with symlinking enabled, and `composer.prod.json` keeps production VCS package contracts rather than path repositories.
- Sibling contracts consulted directly: Objecting ownership/system-field canon, Cruding generic CRUD ownership, Viewing neutral presentation payload boundary, and Interfacing passive shell/template boundary. No sibling repository is authorized for mutation by this run.
- Canonization consulted directly: Canon003, Canon021, Canon031, Canon040, Canon042, Canon043 and Canon045 plus the current guard matrix and root agent projection. Concrete mapping: `retailing/retail` -> `App\\Retailing\\`; `RetailViewPayload` remains a genuine Viewing value object rather than a DTO; generic CRUD remains in Cruding; meaningful class and contract-method PHPDoc must independently reach 70%; PHP executable coverage targets remain 80/80/70; behavioral/UI coverage must use reproducible explicit inventories; local first-party path repositories require exact `dev-master` identity including `options.versions`; root development Composer must expose the reachable first-party repository closure.
- Gating consulted as the executable companion. The first real `composer gate` run did not reach rule execution because the consumer package currently resolves policy root to `.gating/` while its materialized severity policy is under `.gating/.gating/config/severity.yaml`; this is a reproducible consumer-tooling configuration blocker, not a green gate.
- RC-critical workstream: repair the Retailing-owned Gating invocation/configuration sufficiently to obtain authoritative findings, then close confirmed canonical quality/configuration debt without expanding production responsibility. Growth workstream (non-blocking): richer ranking, marketplace UX/API contracts and commercial capability only after RC correctness and operability evidence is green.
- Material risks: Canon040 prior evidence is 80.08% lines / 58.16% methods / 80.28% branches, so method coverage remains warning debt; Canon042 has no justified reproducible denominator yet and must not be fabricated; PostgreSQL schema parity still requires a canonical external `DATABASE_URL`.
- Planned gates: Gating, Composer strict lock validation, PHP syntax, PHP-CS-Fixer, PHPStan, PHPUnit, persistent coverage, Playwright, Symfony container/config checks, security audits, Git diff/status/upstream, and PostgreSQL schema parity only when the required database is actually available.

### Implementation and acceptance

- Repaired the Retailing-owned Gating invocation to point at the materialized consumer policy root (`.gating/.gating`), allowing the executable canon to run instead of failing before rule evaluation.
- Materialized Canon043 for all ten first-party development path repositories by keeping symlinks and declaring each package's exact `dev-master` identity through `options.versions`; Canon045 repository closure continues to pass.
- Added semantic PHPDoc to the actual production classes and contract-significant methods rather than adding tag-only comments or documenting trivial accessors. Canon031 now passes at 18/25 classes (72.0%) and 35/42 contract methods (83.3%).
- Kept `RetailViewPayload` in the Viewing value-object boundary, retained zero component-local generic CRUD machinery, and made no sibling repository mutations.
- Updated `composer.lock` only through a scoped first-party update after a full-update dry run showed 20 unrelated third-party upgrades. The applied lock change advanced only `cruding/crud` and `tabling/table`; `composer validate --strict --check-lock` is green.
- Restored standalone Symfony framework secret binding through `config/packages/framework.yaml` using `%env(APP_SECRET)%`; no secret value is committed. After test-cache refresh, `lint:container --env=test` and `lint:yaml config --env=test` are green.
- Verification is green for changed PHP syntax (18 files), PHP-CS-Fixer, PHPStan, PHPUnit/Xdebug coverage (44 tests / 262 assertions), Playwright (1/1 browser harness), Composer audit, npm audit, and final Gating execution.
- Final Gating: 61 rules, 0 failed, 2 warnings, 15 skipped. Canon040 remains a non-blocking method-coverage warning: lines 812/1014 (80.1%) and branches 859/1070 (80.3%) meet target, methods 82/141 (58.2%) remain below the 80% target. This debt was not hidden by changing source filters or replacing path coverage with weaker evidence.
- Canon042 remains a non-blocking evidence warning. The normative rule and executable implementation were re-read: explicit reproducible functional/behavioral/UI/critical denominators are required. The current Playwright test proves only the browser harness, while actual presentation belongs to Viewing/Interfacing; no empty-inventory or fabricated 100% evidence file was generated.
- `composer schema:parity` was executed and is externally blocked because canonical PostgreSQL `DATABASE_URL` is absent. The data connection was not substituted with SQLite merely to obtain a green check.
- RC-critical repository defects found in this run are closed. Residual items are explicit evidence/infrastructure tails: Canon040 method/path completeness, a genuine Canon042 repository-owned application-surface evidence producer, and PostgreSQL-backed schema parity when the canonical database environment is available.

### Git integration

- Created signed commit `87feabc` (`chore(retailing): harden RC canon and runtime gates`) from the verified clean diff.
- The historical local branch tracked `origin/master`; the push guard correctly refused to repoint it. Created dedicated integration branch `engine/retailing-rc-hardening-20260914` at the same verified HEAD and pushed it with its own upstream instead of risking a protected-master push.
- Opened PR #9, `Retailing RC canon and runtime hardening`, against `master`. GitHub reports the exact head as mergeable with no conflicts and no registered status-check contexts.
- PR #9 is intentionally left open rather than merged: GitHub merge safety is green, but full RC acceptance still has a factual external PostgreSQL schema-parity blocker (`DATABASE_URL` absent). No merge is claimed until that runtime gate can be executed against the canonical data connection.

### PostgreSQL parity continuation — 2026-09-14

- Rechecked the canonical host at `D:\PhpstormProjects\www\app`. Console MCP PostgreSQL diagnostics resolved the host-owned `DATABASE_URL` without exposing its secret, connected successfully to PostgreSQL 16.4 database `app`, and confirmed the live `retail` and `retail_response` tables.
- Confirmed all nine migrations previously tracked by the current Retailing branch are recorded as executed in `doctrine_migration_versions`. The canonical database also records `App\\Retailing\\Migrations\\Version20260821050000`, while that migration artifact was absent from the current branch.
- Recovered `Version20260821050000` from Git object `afd9b77`; it is the historical migration that added `retail.type_path` and `idx_retail_type_path_kind`. Live schema inspection confirmed the nullable `type_path VARCHAR(255)` column still exists while current Retailing Doctrine mapping no longer owns that field.
- Restored the executed historical migration artifact and added forward-only `Version20260914224500` to remove the retired type-path index/column without rewriting executed migration history.
- Both migration files pass PHP syntax lint. PHPUnit remains green at 44 tests / 262 assertions; PHPStan reports no errors; Gating remains 61 rules, 0 failed, 2 warnings (Canon040 method coverage and Canon042 behavioral/UI evidence).
- Standalone `composer schema:parity` still cannot inherit the host-owned environment automatically. The host Doctrine dry-run was therefore attempted through `www/app`, but the host Symfony runtime currently fails before migration planning because `App\\Facting\\FactingBundle` is enabled for `prod` while the class is unavailable.
- This changes the remaining RC blocker from “missing PostgreSQL credentials” to a concrete host-runtime dependency blocker outside Retailing ownership. The Retailing schema drift is diagnosed and repaired in source, but the new cleanup migration has not been applied to the canonical database and full host parity is therefore not claimed green.
- Follow-up host diagnosis confirmed `facting/fact` is installed from the local path package, but App's installed/locked Composer projection still exposes the older `App\\ => src/` autoload while current Facting source exposes `App\\Facting\\ => src/`. Canonical `composer dump-autoload` therefore reproduces the missing `FactingBundle` boot failure.
- A package-scoped `composer update facting/fact --dry-run --no-scripts` was attempted and refused by Composer because App's root dependency/repository graph has unrelated unresolved drift, including missing `collectioning/collection` repository closure, legacy Streaming package resolution, and incompatible in-flight dependency constraints in other components. No App lock or generated Composer metadata was rewritten to fabricate a green host boot.
- App's own orchestration journal independently documents this same stale path-package projection/root-graph condition. Retailing therefore remains merge-ready at the repository level, but canonical database migration execution is still blocked by host integration state outside Retailing ownership.
