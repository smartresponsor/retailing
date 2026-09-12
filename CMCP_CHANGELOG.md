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
