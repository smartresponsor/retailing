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
